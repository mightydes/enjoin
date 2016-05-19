<?php

namespace Enjoin;

use Exception;

class Orders
{

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    private $DB;

    /**
     * @var Model
     */
    private $Invoker;

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
     * Input examples:
     *      - string `title DESC`
     *      - [ 'name', 'username DESC', ['username', 'DESC'] ]
     *      - [ 'name', [
     *          ['model' => Enjoin::get('replies'), 'as' => 'comment'],
     *          Enjoin::get('rating'),
     *          'votes'
     *          ] ]
     *
     * @param mixed $options
     * @return \Illuminate\Support\Facades\DB
     */
    public function handle($options)
    {
        if (is_string($options)) {
            # Simplest notation (ie `'order' => 'title DESC'`)
            $this->applyString($options);
        } elseif ($this->isArrayNotation($options)) {
            # Ie `['username', 'DESC']`
            $this->applyArrayNotation($options);
        } elseif (is_array($options)) {
            # List of instructions
            foreach ($options as $v) {
                if (is_string($v)) {
                    $this->applyString($v);
                } elseif ($this->isArrayNotation($v)) {
                    $this->applyArrayNotation($v);
                } elseif (is_array($v)) {
                    # Eager order
                    $this->applyEager($v);
                }
            }
        }

        return $this->DB;
    }

    /**
     * @param array $path
     * @throws \Exception
     */
    private function applyEager(array $path)
    {
        $attr = array_pop($path);
        $itemA = null;
        $prefix = [];
        foreach ($path as $v) {
            if ($v instanceof Model) {
                # Ie `[ Enjoin::get('rating'), ... ]`
                $itemB = Handler::performItem($v);
            } elseif (is_array($v)) {
                # Ie `[ ['model' => Enjoin::get('replies'), 'as' => 'comment'], ... ]`
                $itemB = Handler::performItem($v['model'], Extras::omit($v, ['model']));
            } else {
                throw new Exception('Unknown eager order notation: ' . var_export($v, true));
            }
            if (is_null($itemA)) {
                # First iteration
                $itemA = Handler::performItem($this->Invoker);
            }
            $prefix [] = Handler::getRelation($itemA, $itemB)['as'];

            $itemA = $itemB;
        }
        $prefix = implode(Extras::$GLUE_CHAR, $prefix);
        if (is_string($attr)) {
            $this->applyString($attr, $prefix);
        } elseif ($this->isArrayNotation($attr)) {
            $this->applyArrayNotation($attr, $prefix);
        }
    }

    /**
     * @param array $notation
     * @param string $prefix
     */
    private function applyArrayNotation(array $notation, $prefix = '')
    {
        if ($prefix) {
            $notation[0] = $prefix . '.' . $notation[0];
        }
        $this->DB = call_user_func_array([$this->DB, 'orderBy'], $notation);
    }

    /**
     * Detects notations like `['name', 'DESC']`.
     * @param $input
     * @return bool
     */
    private function isArrayNotation($input)
    {
        if (is_array($input)) {
            $len = count($input);
            if ($len === 1 && is_string($input[0])) {
                return true;
            } elseif ($len === 2 && is_string($input[0]) && is_string($input[1])) {
                $r = [];
                preg_match("/[dD][eE][sS][cC]|[aA][sS][cC]/", $input[1], $r);
                if ($r) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * For example: 'order' => 'title DESC'.
     * @param $input
     * @param string $prefix
     */
    private function applyString($input, $prefix = '')
    {
        $params = array_map('trim', explode(' ', $input));
        if ($prefix) {
            $params[0] = $prefix . '.' . $params[0];
        }
        $this->DB = call_user_func_array([$this->DB, 'orderBy'], $params);
    }

} // end of class
