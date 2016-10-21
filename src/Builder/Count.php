<?php

namespace Enjoin\Builder;

use Enjoin\Enjoin;
use stdClass;

class Count extends Find
{

    /**
     * @return array
     */
    public function getPrepared()
    {
        $this->handleWhere();

        $this->Tree->walk(function (stdClass $node, array $path) use (&$hasRequiredInclude) {
            $depth = count($path) - 1;
            !$depth ?: $this->handleNodeJoin($node, $path, $depth);
        });

        return $this->join
            ? $this->handleEager()
            : $this->handlePrimitive();
    }

    /**
     * @return array
     */
    private function handlePrimitive()
    {
        $table = $this->Model->Definition->table;
        $query = "SELECT count(*) AS `count` FROM `$table` AS `$table`";
        !$this->prepWhere ?: $query .= ' WHERE ' . $this->prepWhere;
        return [$query, $this->placeWhere];
    }

    /**
     * @return array
     */
    private function handleEager()
    {
        $table = $this->Model->Definition->table;
        $query = "SELECT count(`$table`.`id`) AS `count` FROM `$table` AS `$table`";
        !$this->join ?: $query .= ' ' . join(' ', $this->join);
        !$this->prepWhere ?: $query .= ' WHERE ' . $this->prepWhere;

        $place = array_merge(
            $this->placeJoin,
            $this->placeSubWhere,
            $this->placeWhere
        );

        return [$query, $place];
    }

}
