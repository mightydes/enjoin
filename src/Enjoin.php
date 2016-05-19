<?php

namespace Enjoin;

use Enjoin\Mixin\DataTypes;
use Enjoin\Model\Model;
use Doctrine\Common\Inflector\Inflector;
use stdClass;

class Enjoin
{

    use DataTypes;

    const SQL = 1;
    const WITH_CACHE = 2;
    const NO_CACHE = 4;

    private static $debug = false;

    /**
     * @param string $modelName
     * @return Model
     */
    public static function get($modelName)
    {
        $Factory = Factory::getInstance();
        $definitionClass = static::getModelDefinitionClass($modelName);
        if (isset($Factory->models[$definitionClass])) {
            return $Factory->models[$definitionClass];
        }

        # Register model:
        $Definition = new $definitionClass;
        return $Factory->models[$definitionClass] = $Definition->expanseModel
            ? new $Definition->expanseModel($Definition)
            : new Model($Definition);
    }

    /**
     * @param string $modelName
     * @return string
     */
    public static function getModelDefinitionClass($modelName)
    {
        return Factory::getConfig()['enjoin']['models_namespace'] .
        '\\' . str_replace('.', '\\', $modelName);
    }

    /**
     * @param null|bool $bool
     * @return bool
     */
    public static function debug($bool = null)
    {
        return is_bool($bool)
            ? static::$debug = $bool
            : static::$debug;
    }

    /**
     * @param Model $Model
     * @param array $options
     * @return array
     */
    public static function belongsTo($Model, array $options = [])
    {
        return static::performRelation(Extras::BELONGS_TO, $Model, $options);
    }

    /**
     * @param Model $Model
     * @param array $options
     * @return array
     */
    public static function hasOne($Model, array $options = [])
    {
        return static::performRelation(Extras::HAS_ONE, $Model, $options);
    }

    /**
     * @param Model $Model
     * @param array $options
     * @return array
     */
    public static function hasMany($Model, array $options = [])
    {
        return static::performRelation(Extras::HAS_MANY, $Model, $options);
    }

    /**
     * @param string $type
     * @param Model $Model
     * @param array $options
     * @return stdClass
     */
    private static function performRelation($type, $Model, array $options = [])
    {
        $as = isset($options['as']) ? $options['as'] : null;

        if (array_key_exists('foreignKey', $options)) {
            $foreignKey = $options['foreignKey'];
        } else {
            $className = get_class($Model->Definition);
            if ($pos = strrpos($className, '\\')) {
                $className = substr($className, $pos + 1);
            }
            $foreignKey = Inflector::tableize($className) . '_id';
        }

        $relatedKey = $Model->unique;
        !$as ?: $relatedKey .= Extras::GLUE_CHAR . $as;

        $relation = new stdClass;
        $relation->Model = $Model; // required for cache
        $relation->type = $type;
        $relation->as = $as;
        $relation->foreignKey = $foreignKey;
        $relation->relatedKey = $relatedKey;
        return $relation;
    }

}
