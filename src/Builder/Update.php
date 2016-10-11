<?php

namespace Enjoin\Builder;

class Update
{

    protected $collection = [];
    protected $where = [];
    protected $table;

    /**
     * Update constructor.
     * @param array $collection
     * @param array $where
     * @param string $table
     */
    public function __construct(array $collection, array $where, $table)
    {
        $this->collection = $collection;
        $this->where = $where;
        $this->table = $table;
    }

    /**
     * @return array
     */
    public function getPrepared()
    {
        list($setQuery, $setPlace) = $this->handleSet();
        list($whereQuery, $wherePlace) = (new Where($this->where, null))->getPrepared();
        $query = "UPDATE `$this->table` SET $setQuery WHERE $whereQuery";
        return [$query, array_merge($setPlace, $wherePlace)];
    }

    /**
     * @return array
     */
    private function handleSet()
    {
        $query = [];
        $place = [];
        foreach ($this->collection as $field => $value) {
            $query [] = "`$field`=?";
            $place [] = $value;
        }
        return [join(', ', $query), $place];
    }

}
