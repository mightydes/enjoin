<?php

namespace Enjoin\Builder;

use Enjoin\Enjoin;

class Where
{

    /**
     * List of simple `where` options.
     * @var array
     */
    public static $options = [
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
    protected $query = [];
    protected $place = [];

    /**
     * Where constructor.
     * @param array $params
     * @param string $table
     */
    public function __construct(array $params, $table)
    {
        $this->table = $table;
        $this->handle($params);
    }

    /**
     * @return array
     */
    public function getPrepared()
    {
        return [join('', $this->query), $this->place];
    }

    /**
     * @return bool
     */
    public function isComposite()
    {
        return count($this->query) > 1;
    }

    /**
     * @param array $where
     * @param null|string $attr
     * @param bool $isOr
     * @param bool $pristine
     */
    private function handle(array $where, $attr = null, $isOr = false, $pristine = true)
    {
        foreach ($where as $k => $v) {
            if (array_key_exists($k, static::$options)) {
                # Where option found:
                if ($k === 'and' || $k === 'or') {
                    $this->addQuery('(', $isOr, $pristine);
                    $this->handle($v, $attr, $k === 'or');
                    $this->addQuery(')');
                } else {
                    $prep = $this->prep($attr, $v, static::$options[$k]);
                    $this->addQuery($prep, $isOr, $pristine);
                }
            } elseif (is_array($v) && !$this->isPlainList($v)) {
                # Complex statement:
                if (is_numeric($k)) {
                    $this->handle($v, $attr, $isOr, $pristine);
                } else {
                    if (count($v) > 1) {
                        $this->addQuery('(', $isOr, $pristine);
                        $this->handle($v, $k, $isOr);
                        $this->addQuery(')');
                    } else {
                        $this->handle($v, $k, $isOr, $pristine);
                    }
                }
            } else {
                # General statement, ie 'name' => 'Alice', or 'id' => [1, 2, 3]:
                $prep = $this->prep($k, $v);
                $this->addQuery($prep, $isOr, $pristine);
            }
            $pristine = false;
        }
    }

    /**
     * @param string $query
     * @param bool $isOr
     * @param bool $pristine
     */
    private function addQuery($query, $isOr = false, $pristine = true)
    {
        if (!$pristine) {
            $add = $isOr ? 'OR' : 'AND';
            $query = ' ' . $add . ' ' . $query;
        }
        $this->query [] = $query;
    }

    /**
     * @param string $attr
     * @param mixed $value
     * @param null|string $operator
     * @return string
     */
    private function prep($attr, $value, $operator = null)
    {
        $hasValue = true;
        if (is_null($value) && (is_null($operator) || $operator === static::$options['ne'])) {
            $not = is_null($operator) ? '' : ' NOT';
            $operator = "IS$not NULL";
            $hasValue = false;
        }

        if ($hasValue) {
            if (is_array($value)) {
                $this->place = array_merge($this->place, $value);
            } else {
                $this->place [] = $value;
            }
        }

        if (is_null($operator) && is_array($value)) {
            $operator = static::$options['in'];
        }

        $prep = '';
        if ($hasValue) {
            $prep = ' ';
            $prep .= $operator === static::$options['in'] || $operator === static::$options['notIn']
                ? '(' . join(', ', array_fill(0, count($value), '?')) . ')'
                : '?';
        }

        !is_null($operator) ?: $operator = '=';
        return "`{$this->table}`.`$attr` $operator" . $prep;
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

}
