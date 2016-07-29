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

        $this->Tree->walk(function (stdClass $node, array $path) {
            $depth = count($path) - 1;
            if ($depth && !$node->children) {
                // Bottom reached:
                for ($i = $depth; $i > 0; $i--) {
                    if ($path[$i]->required) {
                        break;
                    }
                }
                if ($i) {
                    for ($j = 1; $j <= $i; $j++) {
                        $this->handleNodeJoin($path[$j], $path, $j);
                    }
                }
            }
        });

        return $this->Tree->hasRequiredInclude
            ? $this->handleDistinct()
            : $this->handle();
    }

    /**
     * @return array
     */
    private function handle()
    {
        $table = $this->Model->Definition->table;
        $query = "SELECT count(*) AS `count` FROM `$table` AS `$table`";
        !$this->prepWhere ?: $query .= ' WHERE ' . $this->prepWhere;
        return [$query, $this->placeWhere];
    }

    /**
     * @return array
     */
    private function handleDistinct()
    {
        $table = $this->Model->Definition->table;
        $query = "SELECT count(DISTINCT(`$table`.`id`)) AS `count` FROM `$table` AS `$table`";
        !$this->join ?: $query .= ' ' . join(' ', $this->join);
        !$this->prepWhere ?: $query .= ' WHERE ' . $this->prepWhere;

        $place = array_merge(
            $this->placeJoin,
            $this->placeWhere
        );

        return [$query, $place];
    }

}
