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
     * @param array|null $params
     * @return Record
     */
    public function save(array $params = null)
    {
        return $this->Engine->save($params);
    }

    /**
     * @param array $collection
     * @param array|null $params
     * @return Record
     */
    public function update(array $collection, array $params = null)
    {
        if (isset($params['fields'])) {
            $collection = Extras::pick($collection, $params['fields']);
        }
        foreach ($collection as $field => $value) {
            $this->$field = $value;
        }
        $flags = $this->Engine->type === Engine::NON_PERSISTENT ? Engine::SOFT_SAVE : 0;
        return $this->Engine->save($params, $flags);
    }

    /**
     * @deprecated use `update()` instead.
     * @param array $collection
     * @param array|null $pick
     * @return Record
     */
    public function updateAttributes(array $collection, array $pick = null)
    {
        return $this->update($collection, $pick);
    }

    /**
     * @return bool
     */
    public function destroy()
    {
        $this->Engine->destroy();
        foreach ($this as $prop => $v) {
            unset($this->$prop);
        }
        return true;
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
