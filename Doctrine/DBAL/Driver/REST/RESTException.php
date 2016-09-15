<?php

namespace Ucsf\RestOrmBundle\Doctrine\DBAL\Driver\REST;

use Doctrine\DBAL\Driver\AbstractDriverException;

/**
 * Class RESTException
 * @package Ucsf\RestOrmBundle\Doctrine\DBAL\Driver\REST
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class RESTException extends AbstractDriverException
{
    /**
     * @param array $error
     *
     * @return \Doctrine\DBAL\Driver\REST\RESTException
     */
    public static function fromErrorInfo($error)
    {
        return new self($error['message'], null, $error['code']);
    }
}
