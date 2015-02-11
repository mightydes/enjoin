<?php

namespace Enjoin;

use stdClass;
use Doctrine\Common\Inflector\Inflector;

class Main
{

    /**
     * List of cached models.
     * @var array
     */
    private $models = [];

    /**
     * Service parameters.
     * @var array
     */
    private $config = [];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $def = [
            'models_namespace' => '\Models'
        ];
        $config = array_merge($def, $config);
        $config['models_namespace'][0] === '\\'
            ?: $config['models_namespace'] = '\\' . $config['models_namespace'];
        $this->config = $config;
    }

    /**
     * @param $model_name
     * @return string
     */
    private function getClassName($model_name)
    {
        return $this->config['models_namespace'] . '\\' . str_replace('.', '\\', $model_name);
    }

    /**
     * @param $model_name
     * @return \Enjoin\Model
     */
    public function get($model_name)
    {
        $class = $this->getClassName($model_name);
        if (array_key_exists($class, $this->models)) {
            return $this->models[$class];
        }
        $this->models[$class] = new Model(new $class);
        return $this->models[$class];
    }


    /*
     * SQL operators
     */

    /**
     * @return array
     */
    public function sqlAnd()
    {
        return func_get_args();
    }

    /**
     * @return stdClass
     */
    public function sqlOr()
    {
        $out = new stdClass;
        $out->type = Extras::$SQL_OR;
        $out->body = func_get_args();
        return $out;
    }


    /*
     * Relations
     */

    /**
     * @param $type
     * @param Model $Model
     * @param array $options
     * @return array
     */
    private function performRelation($type, $Model, array $options = [])
    {
        if (array_key_exists('foreignKey', $options)) {
            $foreign_key = $options['foreignKey'];
        } else {
            $class_name = get_class($Model->Context);
            if ($pos = strrpos($class_name, '\\')) {
                $class_name = substr($class_name, $pos + 1);
            }
            $foreign_key = Inflector::tableize($class_name) . '_id';
        }
        $related_key = $Model->getKey();
        if (array_key_exists('as', $options)) {
            $related_key .= Extras::$GLUE_CHAR . $options['as'];
        }
        return [
            'type' => $type,
            'related_key' => $related_key,
            'foreign_key' => $foreign_key,
            'as' => array_key_exists('as', $options) ? $options['as'] : null,
            'model' => $Model, // required for cache
            'options' => $options
        ];
    }

    /**
     * @param Model $Model
     * @param array $options
     * @return array
     */
    public function belongsTo($Model, array $options = [])
    {
        return $this->performRelation(Extras::$BELONGS_TO, $Model, $options);
    }

    /**
     * @param Model $Model
     * @param array $options
     * @return array
     */
    public function hasOne($Model, array $options = [])
    {
        return $this->performRelation(Extras::$HAS_ONE, $Model, $options);
    }

    /**
     * @param Model $Model
     * @param array $options
     * @return array
     */
    public function hasMany($Model, array $options = [])
    {
        return $this->performRelation(Extras::$HAS_MANY, $Model, $options);
    }


    /*
     * Data types
     */

    /**
     * @return array
     */
    public function Integer()
    {
        return ['key' => Extras::$INT_TYPE];
    }

    /**
     * @return array
     */
    public function Boolean()
    {
        return ['key' => Extras::$BOOL_TYPE];
    }

    /**
     * @return array
     */
    public function String()
    {
        return ['key' => Extras::$STR_TYPE];
    }

    /**
     * @return array
     */
    public function Text()
    {
        return ['key' => Extras::$TEXT_TYPE];
    }

    /**
     * @return array
     */
    public function Float()
    {
        return ['key' => Extras::$FLOAT_TYPE];
    }

    /**
     * @return array
     */
    public function Date()
    {
        return ['key' => Extras::$DATE_TYPE];
    }

    /**
     * @return array
     */
    public function Enum()
    {
        return ['key' => Extras::$ENUM_TYPE];
    }

} // end of class
