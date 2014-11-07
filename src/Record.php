<?php

namespace Enjoin;

use Exception;

class Record
{

    /**
     * List of internal properties.
     * @var array
     */
    private $_internal = [

        # Blacklisted methods for values collecting.
        'omit' => ['_internal', '_getInternal', '_setInternal',
            '__construct', '__toArray', '__toString',
            'save', 'updateAttributes', 'destroy'],

        # Corresponded model instance.
        'model' => null,

        # Entry `id`
        'id' => null,

        # Record type (`persistent` or `non persistent`)
        'type' => null

    ]; // end of internal

    /**
     * @param $key
     * @return mixed
     */
    public function _getInternal($key)
    {
        return $this->_internal[$key];
    }

    /**
     * @param $key
     * @param $value
     */
    public function _setInternal($key, $value)
    {
        $this->_internal[$key] = $value;
    }

    /**
     * @param Model $Model
     * @param $type
     * @param null $id
     */
    public function __construct(Model $Model, $type, $id = null)
    {
        $this->_internal['model'] = $Model;
        $this->_internal['id'] = $id;
        $this->_internal['type'] = $type;
    }

    /**
     * @param array $attributes
     * @return bool|int
     * @throws \Exception
     */
    public function save(array $attributes = [])
    {
        switch ($this->_internal['type']) {
            case(Extras::$PERSISTENT_RECORD):
                return PersistentRecord::save($this, $attributes);
            case(Extras::$NON_PERSISTENT_RECORD):
                return NonPersistentRecord::save($this, $attributes);
            default:
                throw new Exception('Record destroyed');
        }
    }

    /**
     * @param array $collection
     * @param array $attributes
     * @return bool
     * @throws \Exception
     */
    public function updateAttributes(array $collection, array $attributes = [])
    {
        if ($attributes) {
            $collection = Extras::pick($collection, $attributes);
        }
        foreach ($collection as $attr => $value) {
            $this->$attr = $value;
        }
        switch ($this->_internal['type']) {
            case(Extras::$PERSISTENT_RECORD):
                return PersistentRecord::save($this, $attributes);
            case(Extras::$NON_PERSISTENT_RECORD):
                return true;
            default:
                throw new Exception('Record destroyed');
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function destroy()
    {
        switch ($this->_internal['type']) {
            case(Extras::$PERSISTENT_RECORD):
                return PersistentRecord::destroy($this);
            case(Extras::$NON_PERSISTENT_RECORD):
                return NonPersistentRecord::destroy($this);
            default:
                throw new Exception('Record destroyed');
        }
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        $out = [];
        foreach (Extras::omit(get_object_vars($this), $this->_internal['omit']) as $prop => $value) {
            if ($value instanceof Record) {
                $out[$prop] = $value->__toArray();
            } elseif (is_array($value)) {
                $out[$prop] = [];
                foreach ($value as $k => $v) {
                    if ($v instanceof Record) {
                        $out[$prop][$k] = $v->__toArray();
                    } else {
                        $out[$prop][$k] = $v;
                    }
                }
            } else {
                $out[$prop] = $value;
            }
        }
        return $out;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->__toArray(), JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES);
    }

} // end of class
