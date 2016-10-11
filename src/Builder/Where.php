<?php

namespace Enjoin\Builder;

use Enjoin\Enjoin;

class Where
{

    /**
     * List of `where` control statements.
     * @var array
     */
    public static $controls = [
        'and' => 'AND',
        'or' => 'OR',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'ne' => '!=',
        'in' => 'IN',
        'notIn' => 'NOT IN',
        'like' => 'LIKE',
        'notLike' => 'NOT LIKE'
    ];

    protected $table;
    protected $query;
    protected $place;

    private $initAndOr = null;
    private $countAndOr = 0;

    /**
     * Where constructor.
     * @param array $params
     * @param string $table
     */
    public function __construct(array $params, $table)
    {
        $this->table = $table;

        $handle = $this->handle($params);
//        !Enjoin::debug() ?: +sd($params, $handle);
        list($this->query, $this->place) = $this->resolve($handle);

        # Wrap with additional braces on init and-or control:
        if ($this->initAndOr['has'] && $this->initAndOr['isOnly']) {
            $this->query = '(' . $this->query . ')';
        }
    }

    /**
     * @return array
     */
    public function getPrepared()
    {
        return [$this->query, $this->place];
    }

    /**
     * @return bool
     */
    public function isComposite()
    {
        return $this->countAndOr > 0;
    }

    /**
     * @param array $where
     * @param null|string $field
     * @param bool $isOr
     * @return array
     */
    private function handle(array $where, $field = null, $isOr = false)
    {
        $stat = $this->statAndOr($where);
        is_array($this->initAndOr) ?: $this->initAndOr = $stat; // Store init and-or stat for additional braces.
        if ($stat['has'] && $stat['isOnly']) {
            return $this->handle($where[$stat['only']], $field, $stat['only'] === 'or');
        }

        if ($this->isSequential($where)) {
            $data = [];
            foreach ($where as $it) {
                $data [] = $this->handle($it, $field);
            }
            return ['isOr' => $isOr, 'data' => $data];
        }

        $data = [];
        foreach ($where as $key => $value) {
            if (array_key_exists($key, static::$controls)) {

                # Key is CONTROL:
                if ($key === 'and' || $key === 'or') {
                    $data [] = $this->handle($value, $field, $key === 'or');
                } elseif ($key === 'in' || $key === 'notIn') {
                    $data [] = $this->prepIn($field, $value, $key === 'notIn');
                } else {
                    $data [] = $this->prepCommon($field, $value, $key);
                }

            } else {

                # Key is FIELD:
                if (is_array($value)) {
                    if ($this->isPlainList($value)) {
                        $data [] = $this->prepIn($key, $value);
                    } else {
                        $data [] = $this->handle($value, $key);
                    }
                } else {
                    # Primary statement (ie 'name' => 'Bob'):
                    $data [] = $this->prepPrimary($key, $value);
                }

            }
        }
        return ['isOr' => $isOr, 'data' => $data];
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return array
     */
    private function prepPrimary($field, $value)
    {
        $query = $this->getTableField($field) . ' ';
        $query .= is_null($value) ? 'IS NULL' : '= ?';
        return [$query, $value];
    }

    /**
     * @param string $field
     * @param array $in
     * @param bool $isNot
     * @return array
     */
    private function prepIn($field, array $in, $isNot = false)
    {
        $operator = $isNot ? 'NOT IN' : 'IN';
        if ($in) {
            $prep = '(' . join(', ', array_fill(0, count($in), '?')) . ')';
        } else {
            $prep = '(NULL)';
            $in = null;
        }
        $query = "{$this->getTableField($field)} $operator $prep";
        return [$query, $in];
    }

    /**
     * @param string $field
     * @param mixed $value
     * @param string $control
     * @return array
     */
    private function prepCommon($field, $value, $control)
    {
        if ($control === 'ne') {
            $query = $this->getTableField($field) . ' ';
            $query .= is_null($value) ? 'IS NOT NULL' : '!= ?';
        } else {
            $operator = static::$controls[$control];
            $query = "{$this->getTableField($field)} $operator ?";
        }
        return [$query, $value];
    }

    /**
     * @param array $handle
     * @return array
     */
    private function resolve(array $handle)
    {
        $outQuery = [];
        $outPlace = [];

        foreach ($handle['data'] as $it) {
            if (isset($it['isOr'])) {
                list($query, $place) = $this->resolve($it);
                if (count($it['data']) > 1) {
                    $query = '(' . $query . ')';
                }
                $outQuery [] = $query;
            } else {
                $outQuery [] = $it[0]; // ie QUERY
                $place = $it[1]; // ie PLACE
            }

            if (is_array($place)) {
                $outPlace = array_merge($outPlace, $place);
            } elseif (!is_null($place)) {
                $outPlace [] = $place;
            }
        }

        $operator = $handle['isOr'] ? 'OR' : 'AND';
        $this->countAndOr += count($outQuery) - 1;
        $outQuery = join(" $operator ", $outQuery);
        return [$outQuery, $outPlace];
    }

    /**
     * @param array $where
     * @return array
     */
    private function statAndOr(array $where)
    {
        $has = isset($where['and']) || isset($where['or']);
        $isOnly = count($where) === 1;
        $only = null;
        if ($isOnly) {
            $only = isset($where['and']) ? 'and' : 'or';
        }
        return [
            'has' => $has,
            'isOnly' => $isOnly,
            'only' => $only
        ];
    }

    /**
     * @param array $arr
     * @return bool
     */
    private function isSequential(array $arr)
    {
        if ($arr) {
            return array_keys($arr) === range(0, count($arr) - 1);
        }
        return false;
    }

    /**
     * @param array $arr
     * @return bool
     */
    private function isPlainList(array $arr)
    {
        $idx = 0;
        foreach ($arr as $k => $v) {
            if (is_array($v) || $k !== $idx) {
                return false;
            }
            $idx++;
        }
        return true;
    }

    /**
     * @param string $field
     * @return string
     */
    private function getTableField($field)
    {
        $out = "`{$field}`";
        if ($this->table) {
            $out = "`{$this->table}`.$out";
        }
        return $out;
    }

}
