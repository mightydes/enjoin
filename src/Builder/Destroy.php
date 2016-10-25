<?php

namespace Enjoin\Builder;

use Enjoin\Model\Model;

class Destroy
{

    /**
     * @var Model
     */
    protected $Model;

    protected $where = [];

    /**
     * Destroy constructor.
     * @param Model $Model
     * @param array $where
     */
    public function __construct(Model $Model, array $where)
    {
        $this->Model = $Model;
        $this->where = $where;
    }

    /**
     * @return array
     */
    public function getPrepared()
    {
        $e = $this->Model->dialectify()->getEscapeChar();
        $table = $this->Model->getTableName();
        list($query, $place) = (new Where($this->Model, $this->where))->getPrepared();
        $query = "DELETE FROM {$e}$table{$e} WHERE $query";
        return [$query, $place];
    }

}
