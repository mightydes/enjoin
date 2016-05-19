<?php

namespace Enjoin;

use Carbon\Carbon;

class SettersOld
{

    /**
     * @param string $attr
     * @param array $contextAttr
     * @param array $values
     * @return string
     */
    public static function perform($attr, array $contextAttr, array $values)
    {
        $type = $contextAttr['type']['key'];
        if (array_key_exists('set', $contextAttr)) {
            # User defined setter
            $getValue = function ($attr) use ($values) {
                return $values[$attr];
            };
            return $contextAttr['set']($attr, $getValue);
        } else {
            switch ($type) {
                case Extras::$DATE_TYPE:
                    return self::getDate($values[$attr]);
                case Extras::$BOOL_TYPE:
                    return intval($values[$attr]) > 0 ? 1 : null;
                case Extras::$STR_TYPE:
                    $v = $values[$attr];
                    is_null($v) ?: $v = strval($v);
                    if (array_key_exists('allowNull', $contextAttr) && !$contextAttr['allowNull']) {
                        $v = strval($v);
                    }
                    return $v;
                case Extras::$TEXT_TYPE:
                    $v = $values[$attr];
                    is_null($v) ?: $v = strval($v);
                    if (array_key_exists('allowNull', $contextAttr) && !$contextAttr['allowNull']) {
                        $v = strval($v);
                    }
                    return $v;
                case Extras::$INT_TYPE:
                    $v = $values[$attr];
                    is_null($v) ?: $v = intval($v);
                    if (array_key_exists('allowNull', $contextAttr) && !$contextAttr['allowNull']) {
                        $v = intval($v);
                    }
                    return $v;
                case Extras::$FLOAT_TYPE:
                    $v = $values[$attr];
                    is_null($v) ?: $v = floatval($v);
                    if (array_key_exists('allowNull', $contextAttr) && !$contextAttr['allowNull']) {
                        $v = floatval($v);
                    }
                    return $v;
            }
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
        } elseif ($value) {
            return $value;
        }
        return Carbon::now()->toDateTimeString();
    }

    /**
     * @return string
     */
    public static function getUpdatedAt()
    {
        return Carbon::now()->toDateTimeString();
    }

} // end of class
