<?php

namespace Enjoin;

class Extras
{

    public static $SQL_OR = 'sql_or';

    /**
     * Delimiter for `as` statements.
     * @var string
     */
    public static $GLUE_CHAR = ':';

    /**
     * Date format in database.
     * @var string
     */
    public static $DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Default name for `created at` attribute.
     * @var string
     */
    public static $CREATED_AT_ATTR = 'created_at';

    /**
     * Default name for `updated at` attribute.
     * @var string
     */
    public static $UPDATED_AT_ATTR = 'updated_at';

    public static $INT_TYPE = 'int';
    public static $STR_TYPE = 'str';
    public static $TEXT_TYPE = 'text';
    public static $BOOL_TYPE = 'bool';
    public static $FLOAT_TYPE = 'float';
    public static $DATE_TYPE = 'date';
    public static $ENUM_TYPE = 'enum';

    public static $HAS_ONE = 'hasOne';
    public static $HAS_MANY = 'hasMany';
    public static $BELONGS_TO = 'belongsTo';

    /**
     * List of simple `where` clauses.
     * @var array
     */
    public static $WHERE_CLAUSES = [
        'gt' => '>', 'gte' => '>=',
        'lt' => '<', 'lte' => '<=',
        'like' => 'LIKE'
    ];

    /**
     * Persistent record type.
     * @var string
     */
    public static $PERSISTENT_RECORD = 'persistent';

    /**
     * Non persistent record type.
     * @var string
     */
    public static $NON_PERSISTENT_RECORD = 'non_persistent';

    /**
     * Determines if array is collection (ie `['name' => 'Alice', 'age' => 23]`)
     * @param $arr
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
     * @param array $collection
     * @param array $where
     * @return int|null|string
     */
    public static function findWhere(array $collection, array $where)
    {
        foreach ($collection as $v) {
            $done = true;
            foreach ($where as $attr => $value) {
                if ($v[$attr] !== $value) {
                    $done = false;
                    break;
                }
            }
            if ($done) {
                return $v;
            }
        }
        return null;
    }

} // end of class
