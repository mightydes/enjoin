<?php

namespace Enjoin;

use Carbon\Carbon;

class Setters
{

    /**
     * @param $attr
     * @param $contextAttr
     * @param array $values
     * @return string
     */
    public static function perform($attr, $contextAttr, array $values)
    {
        $type = $contextAttr['type']['key'];
        if (array_key_exists('set', $contextAttr)) {
            # User defined setter
            $getValue = function ($attr) use ($values) {
                return $values[$attr];
            };
            return $contextAttr['set']($attr, $getValue);
        } elseif ($type === Extras::$DATE_TYPE) {
            return self::getDate($values[$attr]);
        } elseif ($type === Extras::$BOOL_TYPE) {
            return intval($values[$attr]) > 0 ? 1 : null;
        } elseif ($type === Extras::$INT_TYPE) {
            $v = $values[$attr];
            is_null($v) ?: $v = intval($v);
            if (array_key_exists('allowNull', $contextAttr) && !$contextAttr['allowNull']) {
                $v = intval($v);
            }
            return $v;
        } elseif ($type === Extras::$FLOAT_TYPE) {
            $v = $values[$attr];
            is_null($v) ?: $v = floatval($v);
            if (array_key_exists('allowNull', $contextAttr) && !$contextAttr['allowNull']) {
                $v = floatval($v);
            }
            return $v;
        }
        return $values[$attr];
    }

    /**
     * Handle date/datetime.
     * @param $value
     * @return string
     */
    private static function getDate($value)
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }
        return $value;
    }

    /**
     * @param $value
     * @param bool $isNew
     * @return string
     */
    public static function getCreatedAt($value, $isNew = false)
    {
        if ($isNew) {
            return Carbon::now()->toDateTimeString();
        }

        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        } else {
            return $value;
        }
    }

    /**
     * @return string
     */
    public static function getUpdatedAt()
    {
        return Carbon::now()->toDateTimeString();
    }

} // end of class
