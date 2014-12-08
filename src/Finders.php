<?php

namespace Enjoin;

use Exception;
use Illuminate\Database\Query\JoinClause;

class Finders
{

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    public $DB;

    /**
     * @var Model
     */
    private $Invoker;

    /**
     * @var Handler
     */
    public $Handler;

    /**
     * @param $DB
     * @param $Invoker
     */
    public function __construct($DB, $Invoker)
    {
        $this->DB = $DB;
        $this->Invoker = $Invoker;
    }

    /**
     * Handles params array to build sql query via builder.
     * @param array $params
     * @param array $options
     */
    public function handle(array $params, array $options = [])
    {
        # Handle options
        $isCount = array_key_exists('isCount', $options);

        $this->Handler = new Handler;

        # Push base path entry
        $this->Handler->pushPath($this->Invoker, $params);

        # Handle `include` part
        if (array_key_exists('include', $params)) {
            $this->handleInclude($params['include']);
        }

        $this->Handler->resolveTree();
        if (!$isCount) {
            $this->resolveSelect();
        }

        # Resolve `where` for invoker
        $item = $this->Handler->getTree()[0];
        if (array_key_exists('where', $item)) {
            $this->resolveWhere($item['where'], $item, null);
        }

        $this->resolveJoin();

        # Resolve `order`
        if (array_key_exists('order', $params) && !$isCount) {
            $this->DB = (new Orders($this->DB, $this->Invoker))
                ->handle($params['order']);
        }

        # Resolve `group`
        if (array_key_exists('group', $params)) {
            $this->DB = (new Groups($this->DB, $this->Invoker))
                ->handle($params['group']);
        }

        # Resolve `offset`
        if (array_key_exists('offset', $params) && !$isCount) {
            $this->DB = $this->DB->skip($params['offset']);
        }

        # Resolve `limit`
        if (array_key_exists('limit', $params) && !$isCount) {
            $this->DB = $this->DB->take($params['limit']);
        }
    }

    /**
     * Builds `select` query part.
     */
    private function resolveSelect()
    {
        $select = [];
        $this->Handler->walkTree(function ($item) use (&$select) {
            foreach ($item['attributes'] as $attr) {
                $name = $item['prefix']
                    ? $item['prefix'] . '.' . $attr
                    : $item['model']->Context->table . '.' . $attr;
                $as = '';
                if ($item['prefix']) {
                    $as = ' as ' . $item['prefix'] . Extras::$GLUE_CHAR . $attr;
                }
                $select[] = $name . $as;
            }
        });

        # Fire
        $this->DB = call_user_func_array([$this->DB, 'select'], $select);
    }

    /**
     * Builds `join` query part.
     */
    private function resolveJoin()
    {
        $this->Handler->walkTree(function ($itemB, $path) {
            $len = count($path);
            if ($len > 1) {
                $itemA = $path[$len - 2];

                $table = $itemB['model']->Context->table;
                $as = $itemB['prefix'];

                # Handle exception for first path item,
                # when table name used instead of prefix.
                $onA = $len < 3 ? $itemA['model']->Context->table : $itemA['prefix'];

                # Handle relation type
                if ($itemB['rel']['type'] === Extras::$BELONGS_TO) {
                    # Used foreign key
                    $onA .= '.' . $itemB['rel']['foreign_key'];
                    $onB = $as . '.' . 'id';
                } else {
                    # Used `id`
                    $onA .= '.' . 'id';
                    $onB = $as . '.' . $itemB['rel']['foreign_key'];
                }

                # Handle `required` option
                $joinMethod = array_key_exists('required', $itemB) && $itemB['required']
                    ? 'join'
                    : 'leftJoin';
                # Handle `where`
                if (array_key_exists('where', $itemB)) {
                    /**
                     * @param $join JoinClause
                     */
                    $closure = function ($join) use ($onA, $onB, $itemB) {
                        $join->on($onA, '=', $onB);
                        $getContext = function () use ($join) {
                            return $join;
                        };
                        $this->resolveWhere($itemB['where'], $itemB, $getContext);
                    };
                    $this->DB = call_user_func([$this->DB, $joinMethod], $table . " as $as", $closure);
                } else {
                    $this->DB = call_user_func([$this->DB, $joinMethod], $table . " as $as", $onA, '=', $onB);
                }
            }
        });
    }

    /**
     * Usage options:
     *      - [ 'name' => 'Alice' ]
     *      - [ 'name' => ['like' => '%Alice%', 'ne' => null] ]
     *      - [ ['name' => 'Alice'],
     *          ['age' => ['gt' => 21]] ]
     *      - [ 'name' => 'Bob',
     *          'gender' => ['male', 'female'],
     *          'age' => ['gt' => 18],
     *          sql_or->[ ... ]
     *      - sql_or->[ ... ]
     *
     * @param $where
     * @param array $item
     * @param null|\Closure $getContext
     * @return null
     * @throws Exception
     */
    private function resolveWhere($where, array $item, $getContext)
    {
        if (is_object($where)) {
            $this->handleWhereOperator($where, $item, $getContext);
        } elseif (is_array($where)) {
            foreach ($where as $attr => $value) {
                if (is_string($attr)) {
                    /*
                     * General statement, ie `'name' => 'Alice'`
                     */
                    $this->handleWhereStatement($attr, $value, $item, $getContext);
                } else {
                    $this->resolveWhere($value, $item, $getContext);
                }
            }
        } else {
            throw new Exception('Unsupported `where` options type: ' . gettype($where));
        }
    }

    /**
     * Currently handles only `sql_or`.
     * @param $Operator
     * @param array $item
     * @param $getContext
     * @throws Exception
     */
    private function handleWhereOperator($Operator, array $item, $getContext)
    {
        if ($Operator->type === Extras::$SQL_OR) {
            $closure = function ($query) use ($Operator, $item) {
                $getContext = function () use ($query) {
                    return $query;
                };
                $this->resolveWhere($Operator->body, $item, $getContext);
            };
            $this->applyWhere($getContext, 'orWhere', [$closure]);
        } else {
            throw new Exception("Unknown operator type: `{$Operator->type}`");
        }
    }

    /**
     * Handles `key` => `value` statement.
     * @param $attr
     * @param $statement
     * @param array $item
     * @param $getContext
     * @throws Exception
     */
    private function handleWhereStatement($attr, $statement, array $item, $getContext)
    {
        $name = $item['prefix']
            ? $item['prefix'] . '.' . $attr
            : $item['model']->Context->table . '.' . $attr;
        if (is_array($statement)) {
            if (Extras::isCollection($statement)) {
                /*
                 * Example: ['like' => '%Alice%', 'ne' => null]
                 * Should support:
                 *      - gt, gte
                 *      - lt, lte
                 *      - ne
                 *      - between, nbetween
                 *      - like
                 */
                foreach ($statement as $clause => $v) {
                    if (array_key_exists($clause, Extras::$WHERE_CLAUSES)) {
                        $this->applyWhere($getContext, 'where', [$name, Extras::$WHERE_CLAUSES[$clause], $v]);
                    } elseif ($clause === 'ne') {
                        # Not equal
                        if (is_null($v)) {
                            $this->applyWhere($getContext, 'whereNotNull', [$name]);
                        } elseif (is_array($v)) {
                            $this->applyWhere($getContext, 'whereNotIn', [$name, $v]);
                        } else {
                            $this->applyWhere($getContext, 'where', [$name, '!=', $v]);
                        }
                    } else {
                        throw new Exception("Unsupported where clause: `$clause`");
                    }
                }
            } else {
                /*
                 * Example: [ 'id' => [1, 2, 3] ]
                 */
                $this->applyWhere($getContext, 'whereIn', [$name, $statement]);
            }
        } else {
            /*
             * Simple statement, ie [ 'name' => 'Alice' ]
             */
            $this->applyWhere($getContext, 'where', [$name, '=', $statement]);
        }
    }

    /**
     * Applies `where` instructions to query builder object,
     * or to query closure.
     * @param $getContext
     * @param $method
     * @param array $args
     */
    private function applyWhere($getContext, $method, array $args)
    {
        if (is_callable($getContext)) {
            $context = $getContext();
            if ($context instanceof JoinClause && $method === 'whereIn') {
                $this->DB = call_user_func_array([$this->DB, $method], $args);
            } else {
                call_user_func_array([$context, $method], $args);
            }
        } else {
            $this->DB = call_user_func_array([$this->DB, $method], $args);
        }
    }

    /**
     * Usage options:
     *      - 'include' => Enjoin::get('Articles')
     *      - 'include' => [
     *              Enjoin::get('Articles'),
     *              Enjoin::get('Books'), [ model => ... ]
     *          ]
     *      - 'include' => [
     *              'model' => Enjoin::get('Articles'),
     *              'as' => 'Read',
     *              'where' => [ ... ],
     *              'required' => false, // `LEFT JOIN` vs `INNER JOIN`, Default: `true`
     *              'include' => [ ... ]
     *          ]
     *
     * @param $include
     */
    private function handleInclude($include)
    {
        if (is_array($include)) {
            if (array_key_exists('model', $include)) {
                # Used advanced model notation
                $this->Handler->pushPath($include['model'], $include);
                if (array_key_exists('include', $include)) {
                    $this->handleInclude($include['include']);
                }
                $this->Handler->popPath();
            } else {
                # Used array of models
                foreach ($include as $v) {
                    if (is_array($v)) {
                        # Used advanced notation
                        $this->Handler->pushPath($v['model'], $v);
                        if (array_key_exists('include', $v)) {
                            $this->handleInclude($v['include']);
                        }
                        $this->Handler->popPath();
                    } else {
                        # Used simple notation
                        $this->Handler->pushPath($v);
                        $this->Handler->popPath();
                    }
                }
            }
        } else {
            # Used simple model notation, ie
            # 'include' => Enjoin::get('Articles')
            $this->Handler->pushPath($include);
            $this->Handler->popPath();
        }
    }

} // end of class
