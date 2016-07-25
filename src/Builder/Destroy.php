<?php

namespace Enjoin\Builder;

class Destroy
{

    protected $where = [];
    protected $table;

    /**
     * Destroy constructor.
     * @param array $where
     * @param string $table
     */
    public function __construct(array $where, $table)
    {
        $this->where = $where;
        $this->table = $table;
    }

    /**
     * @return array
     */
    public function getPrepared()
    {
        list($query, $place) = (new Where($this->where, $this->table))->getPrepared();
        $query = "DELETE FROM `$this->table` WHERE $query";
        return [$query, $place];
    }

}
