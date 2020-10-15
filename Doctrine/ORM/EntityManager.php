<?php
namespace Ucsf\RestOrmBundle\Doctrine\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Setup;
use Ucsf\RestOrmBundle\Doctrine\DBAL\Driver\REST\RESTConnection;
use Ucsf\RestOrmBundle\Exception\RestOrmException;
use Ucsf\RestOrmBundle\Doctrine\DBAL\Driver\REST\Driver;
use Ucsf\RestOrmBundle\RestStdClass;

/**
 * Class EntityManager
 *
 * A custom Doctrine-line entity manager for REST services
 *
 * @package Ucsf\RestOrmBundle\Doctrine
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class EntityManager {

    protected $connection;
    protected $commands;
    protected $repositories;
    protected $doctrineEntityManager; // A true Doctrine entity manager for gathering entity metadata

    /**
     * EntityManager constructor.
     * @param $config Config.yml section.
     * @param $entityManagerName The name of the RestOrm entity manager as defined in the config.yml section for RestOrm.
     */
    public function __construct($config, $entityManagerName)
    {
        if (!isset($config['entity_managers'][$entityManagerName])) {
            throw new RestOrmException('RestOrm cannot find an entity manager definition of  "'.$entityManagerName.'"');
        }
        $em = $config['entity_managers'][$entityManagerName];

        if (!isset($em['connection'])) {
            throw new RestOrmException('RestOrm entity manager "'.$entityManagerName.'" configuration does not have a defined connection');
        }
        $connectionName = $em['connection'];

        if (!isset($config['connections'][$connectionName])) {
            throw new RestOrmException('RestOrm entity manager connection "'.$connectionName.'" is not defined for "'.$entityManagerName.'"');
        }
        $connection = $config['connections'][$connectionName];

        if (!isset($em['repositories'])) {
            throw new RestOrmException('RestOrm entity manager "'.$entityManagerName.'" configuration does not have defined repositories');
        }
        $this->repositories = $em['repositories'];

        if (!isset($em['commands'])) {
            throw new RestOrmException('RestOrm entity manager "'.$entityManagerName.'" configuration does not have defined commands');
        }
        $this->commands = $em['commands'];

        // Get Doctrine driver
        $this->doctrineEntityManager = \Doctrine\ORM\EntityManager::create(
            array('driverClass' => Driver::class),
            Setup::createAnnotationMetadataConfiguration(array(__DIR__.'/src'), false, null, null, FALSE)
        );

        $this->connection = new RESTConnection($connection['base_uri'], $connection['username'], $connection['password'], FALSE);
    }

    public function getRepository($entityName)
    {
        if (empty($this->repositories[$entityName])) {
            throw new RestOrmException('Could not find RestOrm repository "'.$entityName.'"');
        }

        return new Repository(
            $this,
            $this->doctrineEntityManager->getClassMetadata($entityName),
            $this->repositories[$entityName]
        );
    }

    /**
     * Use one of the preconfigured commands for this entity manager
     * @param $commandName The name of a command configured under the given REST respository
     * @param $variables A hash of key-value pairs for satisfying variables within the URI path
     * @param $data A stdClass object to send as the body of the REST command
     * @return mixed An array of entities or a single entity.
     * @throws RestOrmException
     */
    public function command($commandName, $variables = null, $data = null) {
        if (empty($this->commands[$commandName])) {
            throw new RestOrmException('Could not find RestOrm command "'.$commandName.'"');
        }
        if (empty($data)) {
            $json = null;
        } else {
            if (is_object($data) && get_class($data) == 'stdClass') {
                $json = json_encode($data);
            } else {
                $objects = $this->hydrateObjects($data);
                $json = json_encode($objects);
            }
        }
        $command = $this->commands[$commandName];
        $objects = $this->connection->persist($command['path'], $command['method'], $variables, $json);
        $entities = $this->hydrateEntities($command['class'], $objects);

        return $entities;
    }

    /**
     * A simple REST request.
     * @param $method The REST method to use
     * @param $path The URI path relative to $baseUri
     * @param $variables A hash of key-value pairs for satisfying variables within the URI path
     * @param $class The class name of the returned entities
     * @return mixed An array of entities or a single entity.
     */
    public function fetch($method, $path, $variables, $class) {
        $stmt = $this->connection->query($method, $path, $variables);
        $objects = $stmt->fetchAll();
        $entities = $this->hydrateEntities($class, $objects);
        return $entities;
    }

    /**
     * Basic persistance for the given entity via REST. The entity is transfered as
     * the body of the REST call.
     * @param $entity
     * @param null $method
     * @param array $variables
     * @return mixed
     * @throws \Exception
     */
    public function persist($entity, $method = null, $variables = array()) {
        $entityClass = get_class($entity);
        $persistConfig = $this->repositories[$entityClass]['persist'];
        $objects = $this->hydrateObjects($entity);
        $json = json_encode($objects);
        $responseObjects = $this->connection->persist(
            $persistConfig['path'],
            empty($method) ? $persistConfig['method'] : $method,
            $variables,
            $json
        );
        return $this->hydrateEntities($entityClass, $responseObjects);
    }

    public function raw($path, $method, $variables, $json) {
        return $this->connection->persist($path, $method, $variables, $json);
    }



    /**
     * Take the given entities and generate stdClass objects ready to convert to JSON for posting to REST service
     * @param $entities The Doctrine entities to turn into stdClass objects
     * @return mixed An array of stdClass objects if the input was an array of entities, otherwise a single stdClass object.
     */
    protected function hydrateObjects($entities) {
        if (!$entities) {
            return null;
        }
        // Record if the input is an array or a single db objects. If it is not an objet
        // wrap it in an array for operational consistency
        $isArray = is_array($entities);
        if (!$isArray) {
            $entities = array($entities);
        }

        $entityName = get_class($entities[0]);
        $restObjects = array();
        $metadata = $this->doctrineEntityManager->getClassMetadata($entityName);
        foreach($entities as $entity) {
            $restObject = new RestStdClass();

            // Copy scalar database columns into analogous object columns
            foreach ($metadata->getColumnNames() as $columnName) {
                $fieldName = $metadata->getFieldName($columnName);
                $getter = 'get'.$fieldName;
                $restObject->$columnName = $entity->$getter();
            }

            // Recurse to satisfy non-scalar object columns (i.e. joins/associatiations)
            foreach ($metadata->getAssociationMappings() as $fieldName => $associationMapping) {
                $getter = 'get'.$fieldName;
                $associationEntities = $entity->$getter();
                if ($associationEntities) {
                    $associatedObjects = $this->hydrateObjects($associationEntities);
                    if (isset($associationMapping['joinTable']) || isset($associationMapping['joinColumns'])) {

                    if (!empty($associationMapping['joinColumns'])) foreach ($associationMapping['joinColumns'] as $joinColumn) {
                        $restObject->$fieldName = $associatedObjects;
                    }
                    if (!empty($associationMapping['joinTable'])) {
                        $restObject->$fieldName = $associatedObjects;
                        }
                    }
                }
            }
            $restObjects[] = $restObject;
        }

        // If this started with an array, return result in an array,
        // otherwise return as a single entity
        return ($isArray) ? $restObjects : array_shift($restObjects);
    }

    /**
     * Take the given objects retrieved from the REST call and use them to hyrdate entities of the given classname
     * @param $entityName The fully qualified name of the entity class to be hydrated
     * @param $restObjects The stdClass objects returned from the REST client
     * @return mixed An array of entities if the input was an array of objects, otherwise a single entity.
     */
    protected function hydrateEntities($entityName, $restObjects) {
        if (!$restObjects) {
            return null;
        }
        // Record if the input is an array or a single db objects. If it is not an objet
        // wrap it in an array for operational consistency
        $isArray = is_array($restObjects);
        if (!$isArray) {
            $restObjects = array($restObjects);
        }

        $entities = array();
        $metadata = $this->doctrineEntityManager->getClassMetadata($entityName);
        foreach($restObjects as $object) {
            $entity = new $entityName();

            // Copy scalar database columns into analogous entity fields
            foreach ($metadata->getFieldNames() as $fieldName) {
                $columnName = $metadata->getColumnName($fieldName);
                $setter = 'set'.$fieldName;
                $entity->$setter($object->$columnName);
            }

            // Recurse to satisfy non-scalar entity fields (i.e. joins/associatiations)
            foreach ($metadata->getAssociationMappings() as $columnName => $associationMapping) {
                $associationEntityName = $associationMapping['targetEntity'];
                $associatedEntities = $this->hydrateEntities($associationEntityName, $object->$columnName);
                $setter = 'set'.$associationMapping['fieldName'];
                $entity->$setter($associatedEntities);
            }

            $entities[] = $entity;
        }

        // If this started with an array, return result in an array,
        // otherwise return as a single entity
        return ($isArray) ? $entities : array_shift($entities);
    }

}