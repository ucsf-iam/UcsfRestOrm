<?php

namespace Ucsf\RestOrmBundle\Doctrine\DBAL\Driver\REST;

use Ucsf\RestOrmBundle\Doctrine\DBAL\Driver\AbstractRESTDriver;

/**
 * A Doctrine DBAL driver for the Oracle OCI8 PHP extensions.
 *
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class Driver extends AbstractRESTDriver
{
    /**
     * Attempts to create a connection with the database.
     *
     * @param array $params All connection parameters passed by the user.
     * @param string|null $username The username to use when connecting.
     * @param string|null $password The password to use when connecting.
     * @param array $driverOptions The driver options to use when connecting.
     *
     * @return \Doctrine\DBAL\Driver\Connection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        if (empty($driverOptions['base_uri'])) {
            throw new RESTException('Cannot connect to REST service without a "base_uri"');
        } else {
            $baseUri = $driverOptions['base_uri'];
        }
        $verifyCertificate = empty($driverOptions['verify_certificate']) ? FALSE : $driverOptions['verify_certificate'];
        return new RESTConnection($baseUri, $username, $password, $verifyCertificate);
    }


    /**
     * Gets the name of the driver.
     *
     * @return string The name of the driver.
     */
    public function getName()
    {
        return "REST";
    }

}
