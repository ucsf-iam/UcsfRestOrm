<?php

namespace IAM\RestOrmBundle\Doctrine\DBAL\Driver\REST;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use GuzzleHttp\Client;
use IAM\RestOrmBundle\Components\TwigString;
use RAPL\RAPL\Client\GuzzleClient;

/**
 * OCI8 implementation of the Connection interface.
 *
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class RESTConnection implements Connection, ServerInfoAwareConnection
{
    const VERSION = '1.0';

    protected $currentErrorInfo;
    protected $currentErrorCode;

    /**
     * @var GuzzleClient
     * The REST client
     */
    protected $client;

    /**
     * @param $baseUri Base URI, used to prefix relative paths used when creating statements
     * @param $username Basic authentication username
     * @param $password Basic authentication password
     * @param bool $verifyCertificate If TRUE, verify the SSL certificate of the remote services
     */
    public function __construct($baseUri, $username, $password, $verifyCertificate = FALSE)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'auth' => [$username, $password],
            'verify' => $verifyCertificate,
            'headers' => ['accept' => 'application/json']
        ]);
    }

    public function persist($uri, $method, $variables = null, $data = null) {
        $variables = empty($variables) ? array() : $variables;
        $renderedUri = (new TwigString())->render($uri, $variables);

        if (empty($data)) {
            $response = $this->client->request($method, $renderedUri);
        } else {
            $response = $this->client->request($method, $renderedUri,[
                            'headers' => ['Content-type' => 'application/json'],
                            'body' => (is_array($data)) ? (object)$data : $data
                        ]);
        }
        return json_decode($response->getBody());
    }

    /**
     * Prepares a statement for execution and returns a Statement object.
     *
     * @param string $prepareString
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws RESTException
     */
    function prepare($method)
    {
        $args = func_get_args();
        // Second argument, $uri
        if (empty($args[1])) {
            throw new RESTException('Cannot call prepare($method, $uri) without a URI as the second argument.');
        }
        $uri = $args[1];

        return new RESTStatement($this->client, $method, $uri);
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param uri URI path related to $base_uri
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws RESTException
     */
    function query()
    {
        $args = func_get_args();

        // First argument, $method
        if (empty($args[0])) {
            throw new RESTException('Cannot call query($method, $uri, $variables) without a REST method as the second argument.');
        }
        $method = $args[0];

        // Second argument, $uri
        if (empty($args[1])) {
            throw new RESTException('Cannot call query($method, $uri, $variables) without a relative URI as the first argument.');
        }
        $uri = $args[1];

        // Third argument, $variables
        $variables = empty($args[2]) ? array() : $args[2];
        if (!is_array($variables)) {
            throw new RESTException('The third argument to query($method, $uri, $variables) must be an array.');
        }

        $statement = $this->prepare($method, $uri);
        $statement->execute($variables);

        return $statement;
    }

    /**
     * Quotes a string for use in a query.  This is implemented because Connect
     * requires it. For REST, good JSON is expected and will not be validated or
     * automatically escaped.
     *
     * @param string $input
     * @param integer $type
     *
     * @return string
     */
    function quote($input, $type = \PDO::PARAM_STR)
    {
        return $input;
    }

    /**
     * Executes a REST call and return the number of affected rows.
     *
     * @param string $statement
     *
     * @return integer
     */
    function exec($statement)
    {
        $statement->execute();
        return $statement->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name
     *
     * @return string
     */
    function lastInsertId($name = null)
    {
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    /**
     * REST is always autocommit. Return FALSE.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function beginTransaction()
    {
        return FALSE;
    }

    /**
     * There is no commit with REST.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function commit()
    {
        return FALSE;
    }

    /**
     * There is no rollback with REST. Just return TRUE.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function rollBack()
    {
        return FALSE;
    }

    /**
     * Returns the error code associated with the last operation on the database handle.
     *
     * @return string|null The error code, or null if no operation has been run on the database handle.
     */
    function errorCode()
    {
        return $this->currentErrorCode;
    }

    /**
     * Returns extended error information associated with the last operation on the database handle.
     *
     * @return array
     */
    function errorInfo()
    {
        return $this->currentErrorInfo;
    }

    /**
     * Returns the version number of the database server connected to.
     *
     * @return string
     */
    public function getServerVersion()
    {
        return self::VERSION;
    }

    /**
     * Checks whether a query is required to retrieve the database server version.
     *
     * @return boolean True if a query is required to retrieve the database server version, false otherwise.
     */
    public function requiresQueryForServerVersion()
    {
        return false;
    }

}
