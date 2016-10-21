<?php

namespace Enjoin;

class Extras
{

    const INT_TYPE = 'INTEGER';
    const STR_TYPE = 'STRING';
    const TEXT_TYPE = 'TEXT';
    const BOOL_TYPE = 'BOOLEAN';
    const FLOAT_TYPE = 'FLOAT';
    const DATE_TYPE = 'DATETIME';
    const ENUM_TYPE = 'ENUM';

    /**
     * Delimiter for `as` statements.
     * @var string
     */
    const GLUE_CHAR = '.';

    const HAS_ONE = 'hasOne';
    const HAS_MANY = 'hasMany';
    const BELONGS_TO = 'belongsTo';

    /**
     * Returns a copy of the array, filtered to only have values for the whitelisted array of valid keys.
     * @param array $arr
     * @param array $pick
     * @return array
     */
    public static function pick(array $arr, array $pick)
    {
        $out = [];
        foreach ($arr as $k => $v) {
            if (in_array($k, $pick)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /**
     * Return a copy of the object, filtered to omit the blacklisted array of keys.
     * @param array $arr
     * @param array $omit
     * @return array
     */
    public static function omit(array $arr, array $omit)
    {
        $out = [];
        foreach ($arr as $k => $v) {
            if (!in_array($k, $omit)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /**
     * Looks through the list and returns the first value that matches
     * all of the key-value pairs listed in properties.
     * @param array|object $collection
     * @param array $where
     * @return mixed
     */
    public static function findWhere($collection, array $where)
    {
        foreach ($collection as $it) {
            $done = true;
            $isArray = is_array($it);
            foreach ($where as $k => $v) {
                $value = $isArray ? $it[$k] : $it->$k;
                if ($value !== $v) {
                    $done = false;
                    break;
                }
            }
            if ($done) {
                return $it;
            }
        }
        return null;
    }

    /**
     * A convenient version of what is perhaps the most common use-case for map:
     * extracting a list of property values.
     * @param array|object $list
     * @param string $propertyName
     * @return array
     */
    public static function pluck($list, $propertyName)
    {
        $out = [];
        foreach ($list as $it) {
            if (is_array($it) && array_key_exists($propertyName, $it)) {
                $out [] = $it[$propertyName];
            } elseif (is_object($it) && property_exists($it, $propertyName)) {
                $out [] = $it->$propertyName;
            }
        }
        return $out;
    }

    /**
     * Determines if array is collection (ie `['name' => 'Alice', 'age' => 23]`).
     * @param mixed $arr
     * @return bool
     */
    public static function isCollection($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        reset($arr);
        return key($arr) !== 0;
    }

}
