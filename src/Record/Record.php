<?php

namespace Enjoin\Record;

use Enjoin\Model\Model;
use Enjoin\Extras;
use stdClass;

class Record extends stdClass
{

    /**
     * @var Engine|null
     */
    private $Engine = null;

    /**
     * @param Model $Model
     * @param string $type
     * @param null|int $id
     */
    public function __construct(Model $Model, $type = Engine::NON_PERSISTENT, $id = null)
    {
        $this->Engine = new Engine($this, $Model, $type, $id);
    }

    /**
     * @param array|null $attributes
     * @return Record
     */
    public function save(array $attributes = null)
    {
        return $this->Engine->save($attributes);
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
        foreach ($this as $prop => $value) {
            if ($value instanceof Engine) {
                continue;
            }
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
     * @return mixed
     */
    public function __toString()
    {
        return json_encode($this->__toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
