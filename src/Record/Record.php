<?php

namespace Enjoin\Record;

use Enjoin\Model\Model;
use stdClass;

class Record extends stdClass
{

    /**
     * @var \Enjoin\Record\Scope
     */
    private $Scope;

    /**
     * @param \Enjoin\Model\Model $Model
     * @param string $type
     * @param null|int $id
     */
    public function __construct(Model $Model, $type = Engine::NON_PERSISTENT, $id = null)
    {
        $this->Scope = new Scope($Model->getName(), $type, $id);
    }

    /**
     * @return \Enjoin\Record\Scope
     */
    public function scope()
    {
        return $this->Scope;
    }

    /**
     * @param array|null $params
     * @return \Enjoin\Record\Record
     */
    public function save(array $params = null)
    {
        return Engine::save($this, $params);
    }

    /**
     * @param array $collection
     * @param array|null $params
     * @return \Enjoin\Record\Record
     */
    public function update(array $collection, array $params = null)
    {
        return Engine::update($this, $collection, $params);
    }

    /**
     * @deprecated use `update()` instead.
     * @param array $collection
     * @param array|null $params
     * @return \Enjoin\Record\Record
     */
    public function updateAttributes(array $collection, array $params = null)
    {
        return $this->update($collection, $params);
    }

    /**
     * @return bool
     */
    public function destroy()
    {
        return Engine::destroy($this);
    }

    /**
     * @return bool
     */
    public function isNewRecord()
    {
        return $this->scope()->type === Engine::NON_PERSISTENT;
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return Engine::toArray($this);
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return json_encode($this->__toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
