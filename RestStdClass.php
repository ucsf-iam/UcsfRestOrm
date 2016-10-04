<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 10/3/16
 * Time: 4:23 PM
 */

namespace Ucsf\RestOrmBundle;

/**
 * Class RestStdClass
 *
 * A base class for enable serializability
 * 
 * @package Ucsf\RestOrmBundle
 */
class RestStdClass extends \stdClass implements \JsonSerializable {
    public function jsonSerialize() {
        $encoded = array();
        foreach (get_object_vars($this) as $key => $value) {
            if (is_array($value)) {
                $encoded[$key] = array();
                foreach($value as $arrayKey => $arrayValue) {
                    $encoded[$key][$arrayKey] = $arrayValue;
                }
            } else if (is_bool($value)) {
                $encoded[$key] = $value ? 1 : 0;
            } else if (is_scalar($value)) {
                $encoded[$key] = $value;
            } else if ($value instanceof \DateTime) {
                $encoded[$key] = $value->format('m/d/Y');
            } else {
                $encoded[$key] = $value;
            }
        }
        return $encoded;
    }
}