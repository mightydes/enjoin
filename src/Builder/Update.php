<?php

namespace Enjoin\Builder;

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
     */
    private function handleSet($e)
    {
        $query = [];
        $place = [];
        foreach ($this->collection as $field => $value) {
            $query [] = "{$e}$field{$e}=?";
            $place [] = $value;
        }
        return [join(', ', $query), $place];
    }

}
