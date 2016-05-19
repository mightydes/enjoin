<?php

namespace Enjoin;

use Carbon\Carbon;
use Exception;

class GettersOld
{

    /**
     * Types:
     *      String
     *      Text
     *      Integer
     *      Float
     *      Decimal
     *      Date
     *      Enum
     * Undecided:
     *      Bigint
     *      Array
     *      Blob
     *      Uuid
     *
     * @param $contextAttr
     * @return callable
     * @throws \Exception
     */
    public static function perform($contextAttr)
    {
        # Handle `allowNull` parameter
        $allowNull = true;
        if (array_key_exists('allowNull', $contextAttr)) {
            $allowNull = $contextAttr['allowNull'];
        }

        $type = $contextAttr['type']['key'];
        if (array_key_exists('get', $contextAttr)) {
            # User defined getter
            return $contextAttr['get'];
        } elseif ($type === Extras::$INT_TYPE) {
            return self::getInteger($allowNull);
        } elseif ($type === Extras::$FLOAT_TYPE) {
            return self::getFloat($allowNull);
        } elseif ($type === Extras::$BOOL_TYPE) {
            return self::getBoolean();
        } elseif ($type === Extras::$DATE_TYPE) {
            return self::getDate();
        } elseif ($type === Extras::$STR_TYPE
            || $type === Extras::$TEXT_TYPE
            || $type === Extras::$ENUM_TYPE
        ) {
            return self::getString($allowNull);
        }
        throw new Exception("Unknown attribute type: `$type`");
    }

    /**
     * Perform integer handler.
     * @param $allowNull
     * @return callable
     */
    private static function getInteger($allowNull)
    {
        return function ($attr, \Closure $getValue) use ($allowNull) {
            $value = $getValue($attr);
            if ($allowNull && is_null($value)) {
                return null;
            }
            return intval($value);
        };
    }

    /**
     * Perform float handler.
     * @param $allowNull
     * @return callable
     */
    private static function getFloat($allowNull)
    {
        return function ($attr, \Closure $getValue) use ($allowNull) {
            $value = $getValue($attr);
            if ($allowNull && is_null($value)) {
                return null;
            }
            return floatval($value);
        };
    }

    /**
     * Perform boolean handler.
     * @return callable
     */
    private static function getBoolean()
    {
        return function ($attr, \Closure $getValue) {
            return intval($getValue($attr)) > 0 ? true : false;
        };
    }

    /**
     * Perform date/datetime handler.
     * @return callable
     */
    private static function getDate()
    {
        return function ($attr, \Closure $getValue) {
            $value = $getValue($attr);
            if (is_string($value)) {
                return Carbon::createFromFormat(Extras::$DATE_FORMAT, $value);
            }
            return $value;
        };
    }

    /**
     * Perform string/text/enum handler.
     * @param $allowNull
     * @return callable
     */
    private static function getString($allowNull)
    {
        return function ($attr, \Closure $getValue) use ($allowNull) {
            $value = $getValue($attr);
            if ($allowNull && is_null($value)) {
                return null;
            }
            return $value;
        };
    }

    /**
     * Perform `created as` handler.
     * @return callable
     */
    public static function getCreatedAt()
    {
        return function ($attr, \Closure $getValue) {
            $value = $getValue($attr);
            if (is_string($value)) {
                return Carbon::createFromFormat(Extras::$DATE_FORMAT, $value);
            }
            return $value;
        };
    }

    /**
     * Perform `updated at` handler.
     * @return callable
     */
    public static function getUpdatedAt()
    {
        return self::getCreatedAt();
    }

} // end of class
