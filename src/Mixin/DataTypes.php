<?php

namespace Enjoin\Mixin;

use Enjoin\Extras;

trait DataTypes
{

    /**
     * @return array
     */
    public static function Integer()
    {
        return ['key' => Extras::INT_TYPE];
    }

    /**
     * @return array
     */
    public static function Boolean()
    {
        return ['key' => Extras::BOOL_TYPE];
    }

    /**
     * @return array
     */
    public static function String()
    {
        return ['key' => Extras::STR_TYPE];
    }

    /**
     * @return array
     */
    public static function Text()
    {
        return ['key' => Extras::TEXT_TYPE];
    }

    /**
     * @return array
     */
    public static function Float()
    {
        return ['key' => Extras::FLOAT_TYPE];
    }

    /**
     * @return array
     */
    public static function Date()
    {
        return ['key' => Extras::DATE_TYPE];
    }

    /**
     * @return array
     */
    public static function Enum()
    {
        return ['key' => Extras::ENUM_TYPE];
    }

}
