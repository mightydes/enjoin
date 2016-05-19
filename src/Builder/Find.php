<?php

namespace Enjoin\Builder;

use Enjoin\Model\Model;
use Enjoin\Extras;
use Enjoin\Enjoin;
use stdClass;

class Find
{

    /**
     * @var Model
     */
    protected $Model;

    /**
     * @var Tree
     */
    public $Tree;

    protected $isById = false;
    protected $isSubquery = false;

    protected $params = [];

    protected $select = [];
    protected $subSelect = [];

    protected $join = [];
    protected $subJoin = [];

    protected $subWhere = [];

    protected $placeWhere = [];
    protected $placeJoin = [];
    protected $placeSubJoin = [];
    protected $placeSubWhere = [];

    protected $prepWhere = '';
    protected $prepLimit = '';

    /**
     * Find constructor.
     * @param Model $Model
     * @param array $params
     */
    public function __construct(Model $Model, array $params)
    {
        $this->Model = $Model;
        $this->params = $params;
        $this->Tree = new Tree($this->Model, $params);
//        !Enjoin::debug() ?: sd($this->Tree);
        $this->isById = isset($params['where']['id']) && is_numeric($params['where']['id']);
        $this->isSubquery = $this->Tree->hasMany && !$this->isById;
    }

    /**
     * @return array
     */
    public function getPrepared()
    {
        $this->handleLimit();
        $this->handleWhere();

        $this->Tree->walk(function (stdClass $node, array $path) {
            $depth = count($path) - 1;
            $this->handleNodeSelect($node, $path, $depth);
            !$depth ?: $this->handleNodeJoin($node, $path, $depth);
        });

//        $this->handleWhere();
        return $this->isSubquery
            ? $this->handleSubquery()
            : $this->handle();
    }

    /**
     * @param stdClass $node
     * @param array $path
     * @param int $depth
     */
    private function handleNodeSelect(stdClass $node, array $path, $depth)
    {
        $prefix = null;
        if ($node->prefix) {
            $prefix = $node->prefix;
        } elseif ($this->Tree->hasChildren) {
            $prefix = $this->Model->Definition->table;
        }
        !$prefix ?: $prefix = "`$prefix`.";
        $glue = Extras::GLUE_CHAR;
        foreach ($node->attributes as $attr) {
            $as = $node->as ? " AS `{$node->prefix}{$glue}{$attr}`" : '';
            $query = "$prefix`$attr`$as";
            if ($this->isSubquery && (!$depth || $depth && $node->required && $node->relation->type === Extras::BELONGS_TO)) {
                $this->subSelect [] = $query;
            } else {
                $this->select [] = $query;
            }
        }
//        !Enjoin::debug() ?: sd($this->select);
    }

    /**
     * @param stdClass $node
     * @param array $path
     * @param int $depth
     */
    private function handleNodeJoin(stdClass $node, array $path, $depth)
    {
        $parent = $path[$depth - 1];
//        $parentAs = $parent->as ?: $this->Model->Definition->table;
//        $on = $node->relation->type === Extras::BELONGS_TO
//            ? "`$parentAs`.`{$node->relation->foreignKey}` = `{$node->prefix}`.`id`"
//            : "`$parentAs`.`id` = `{$node->prefix}`.`{$node->relation->foreignKey}`";
        $parentPrefix = $parent->prefix ?: $this->Model->Definition->table;
        $on = $node->relation->type === Extras::BELONGS_TO
            ? "`$parentPrefix`.`{$node->relation->foreignKey}` = `{$node->prefix}`.`id`"
            : "`$parentPrefix`.`id` = `{$node->prefix}`.`{$node->relation->foreignKey}`";
        $junction = $node->required ? 'INNER' : 'LEFT OUTER';
        $query = "$junction JOIN `{$node->Model->Definition->table}` AS `{$node->prefix}` ON $on";

        $where = '';
        $place = [];
        if ($node->where) {
            list($where, $place) = (new Where($node->where, $node->prefix))->getPrepared();
            $query .= ' AND ' . $where;
        }

        if ($this->isSubquery && $node->required && $depth === 1) {
            if ($node->relation->type === Extras::BELONGS_TO) {
                $this->subJoin [] = $query;
                $this->placeSubJoin = array_merge($this->placeSubJoin, $place);
            } else {
                $limit = $this->prepLimit ? ' ' . $this->prepLimit : '';
                $clause = $where ? ' AND ' . $where : '';
                $subWhere = "SELECT `{$node->relation->foreignKey}` " .
                    "FROM `{$node->Model->Definition->table}` AS `{$node->prefix}` " .
                    "WHERE ($on$clause)$limit";
                $this->subWhere [] = "($subWhere) IS NOT NULL";
                $this->join [] = $query;
                $this->placeSubWhere = array_merge($this->placeSubWhere, $place);
                $this->placeJoin = array_merge($this->placeJoin, $place);
            }
        } else {
            $this->join [] = $query;
            $this->placeJoin = array_merge($this->placeJoin, $place);
        }
    }

    /**
     * Handle `where` part.
     */
    private function handleWhere()
    {
        $node = $this->Tree->get();
        if (isset($node->where)) {
            list($query, $place) = (new Where(
                $node->where,
                $node->Model->Definition->table)
            )->getPrepared();
            $this->prepWhere = $query;
            if ($this->isSubquery) {
                $this->placeSubWhere = array_merge($this->placeSubWhere, $place);
            } else {
                $this->placeWhere = array_merge($this->placeWhere, $place);
            }
        } else {
            // TODO: do something on empty `where`...
            sd('Empty where...');
        }
    }

    /**
     * Handle `offset` and `limit`.
     */
    private function handleLimit()
    {
        $offset = isset($this->params['offset']) && abs($this->params['offset']) > 0
            ? abs($this->params['offset']) : null;
        $limit = isset($this->params['limit'])
            ? abs($this->params['limit']) : null;
        if ($limit) {
            $query = 'LIMIT ';
            !$offset ?: $query .= $offset . ', ';
            $query .= $limit;
            $this->prepLimit = $query;
        }
    }

    /**
     * @return array
     */
    private function handle()
    {
        $table = $this->Model->Definition->table;
        $select = join(', ', $this->select);
        $query = "SELECT $select FROM `$table` AS `$table`";

        !$this->join ?: $query .= ' ' . join(' ', $this->join);
        !$this->prepWhere ?: $query .= ' WHERE ' . $this->prepWhere;

        if ($this->prepLimit && !$this->isById) {
            $query .= ' ' . $this->prepLimit;
        }

        $place = $this->placeWhere;
        return [$query, $place];
    }

    /**
     * @return array
     */
    private function handleSubquery()
    {
//        !Enjoin::debug() ?: sd($this);
        $table = $this->Model->Definition->table;
        $subSelect = join(', ', $this->subSelect);
        $sub = "SELECT $subSelect FROM `$table` AS `$table`";
        !$this->subJoin ?: $sub .= ' ' . join(' ', $this->subJoin);

        $where = $this->prepWhere;
        if ($this->subWhere) {
            !$where ?: $where .= ' AND ';
            $where .= join(' AND ', $this->subWhere);
        }

        !$where ?: $sub .= ' WHERE ' . $where;
        !$this->prepLimit ?: $sub .= ' ' . $this->prepLimit;

        array_unshift($this->select, "`{$this->Model->Definition->table}`.*");
        $select = join(', ', $this->select);
        $query = "SELECT $select FROM ($sub) AS `$table`";
        !$this->join ?: $query .= ' ' . join(' ', $this->join);

//        !Enjoin::debug() ?: sd($this->placeSubJoin, $this->placeSubWhere, $this->placeWhere, $this->placeJoin);
        $place = array_merge(
            $this->placeSubJoin,
            $this->placeSubWhere,
            $this->placeWhere,
            $this->placeJoin
        );

        return [$query, $place];
    }

}
