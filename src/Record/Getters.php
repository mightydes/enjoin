<?php

namespace Enjoin\Record;

use Carbon\Carbon;
use Enjoin\Extras;
use Closure;

/**
 * Class Getters
 *
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
 * @package Enjoin
 */
class Getters
{

    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param array $descAttr
     * @return Closure
     */
    public function perform(array $descAttr)
    {
        # Handle `allowNull` parameter:
        $allowNull = array_key_exists('allowNull', $descAttr)
            ? (bool)$descAttr['allowNull'] : true;

        # Handle user defined getter:
        if (isset($descAttr['get']) && is_callable($descAttr['get'])) {
            return $descAttr['get'];
        }

        switch ($descAttr['type']['key']) {
            case Extras::INT_TYPE:
                return function ($attr, Closure $getValue) use ($allowNull) {
                    $value = $getValue($attr);
                    return $allowNull && is_null($value)
                        ? null : intval($value);
                };
            case Extras::FLOAT_TYPE:
                return function ($attr, Closure $getValue) use ($allowNull) {
                    $value = $getValue($attr);
                    return $allowNull && is_null($value)
                        ? null : floatval($value);
                };
            case Extras::BOOL_TYPE:
                return function ($attr, Closure $getValue) {
                    return intval($getValue($attr)) > 0 ? true : false;
                };
            case Extras::DATE_TYPE:
                return $this->getDate();
            case Extras::STR_TYPE:
            case Extras::TEXT_TYPE:
            case Extras::ENUM_TYPE:
                return function ($attr, Closure $getValue) use ($allowNull) {
                    $value = $getValue($attr);
                    return $allowNull && is_null($value)
                        ? null : $value;
                };
        }
        return function ($attr, Closure $getValue) {
            return $getValue($attr);
        };
    }

    /**
     * Perform `created as` handler.
     * @return Closure
     */
    public function getCreatedAt()
    {
        return $this->getDate();
    }

    /**
     * Perform `updated at` handler.
     * @return Closure
     */
    public function getUpdatedAt()
    {
        return $this->getDate();
    }

    /**
     * @return Closure
     */
    private function getDate()
    {
        return function ($attr, Closure $getValue) {
            $value = $getValue($attr);
            return is_string($value)
                ? Carbon::createFromFormat(self::DATE_FORMAT, $value)
                : $value;
        };
    }

}
