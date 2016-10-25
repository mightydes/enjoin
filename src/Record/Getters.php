<?php

namespace Enjoin\Record;

use Enjoin\Extras;
use Enjoin\Model\Model;
use Carbon\Carbon;
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

    /**
     * @param Model $Model
     * @param array $descAttr
     * @return Closure
     */
    public function perform(Model $Model, array $descAttr)
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
                return $this->getDate($Model);
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
     * @param Model $Model
     * @return Closure
     */
    public function getCreatedAt(Model $Model)
    {
        return $this->getDate($Model);
    }

    /**
     * Perform `updated at` handler.
     * @param Model $Model
     * @return Closure
     */
    public function getUpdatedAt(Model $Model)
    {
        return $this->getDate($Model);
    }

    /**
     * @param Model $Model
     * @return Closure
     */
    private function getDate(Model $Model)
    {
        $dateFormat = $Model->dialectify()->getDateFormat();
        return function ($attr, Closure $getValue) use ($dateFormat) {
            $value = $getValue($attr);
            return is_string($value)
                ? Carbon::createFromFormat($dateFormat, $value)
                : $value;
        };
    }

}
