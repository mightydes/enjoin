<?php

namespace Enjoin\Builder;

use Enjoin\Factory;
use Enjoin\Model\Model;

class Update
{

    /**
     * @var Model
     */
    protected $Model;

    protected $collection = [];
    protected $where = [];

    /**
     * Update constructor.
     * @param Model $Model
     * @param array $collection
     * @param array|null $where
     */
    public function __construct(Model $Model, array $collection, array $where = null)
    {
        $this->Model = $Model;
        $this->collection = $collection;
        $this->where = $where;
    }

    /**
     * @return array
     * @throws \Enjoin\Exceptions\ModelException
     * @throws \Enjoin\Exceptions\ValidationException
     */
    public function getPrepared()
    {
        $e = $this->Model->dialectify()->getEscapeChar();
        $table = $this->Model->getTableName();
        list($setQuery, $setPlace) = $this->handleSet($e);
        $query = "UPDATE {$e}$table{$e} SET $setQuery";

        $wherePlace = [];
        if ($this->where) {
            list($whereQuery, $wherePlace) = (new Where($this->Model, $this->where))->getPrepared();
            $query .= " WHERE $whereQuery";
        }

        return [$query, array_merge($setPlace, $wherePlace)];
    }

    /**
     * @param string $e
     * @return array
     * @throws \Enjoin\Exceptions\ModelException
     * @throws \Enjoin\Exceptions\ValidationException
     */
    private function handleSet($e)
    {
        $query = [];
        $place = [];

        $Setters = Factory::getSetters();
        $defAttributes = $this->Model->getDefinition()->getAttributes();

        # Perform timestamps:
        $createdAtField = null;
        $updatedAtField = null;
        if ($this->Model->isTimestamps()) {
            $createdAtField = $this->Model->getCreatedAtField();
            $updatedAtField = $this->Model->getUpdatedAtField();
        }

        $validate = [];
        foreach ($this->collection as $field => $value) {
            $query [] = "{$e}$field{$e}=?";

            if ($field === $createdAtField) {
                $value = $Setters->getCreatedAt($this->Model, $value);
            } elseif ($field === $updatedAtField) {
                $value = $Setters->getUpdatedAt($this->Model, $value);
            } else {
                if (!isset($defAttributes[$field])) {
                    continue;
                }
                $value = $Setters->perform($this->Model, $this->collection, $defAttributes[$field], $field);
                if (isset($defAttributes[$field]['validate'])) {
                    $validate [] = [$field, $value, $defAttributes[$field]['validate']];
                }
            }

            $place [] = $value;
        }

        !$validate ?: $Setters->validate($validate);

        return [join(', ', $query), $place];
    }

}
