<?php

namespace Enjoin\Builder;

use Enjoin\Model\Model;
use Enjoin\Exceptions\Error;
use Enjoin\Enjoin;

/**
 * Class Order
 *
 * Input examples:
 *      - string `title DESC`
 *      - [ 'name', 'username DESC', ['username', 'desc'] ]
 *      - [
 *          'name',
 *          [
 *              ['model' => Enjoin::get('replies'), 'as' => 'comment'],
 *              Enjoin::get('rating'),
 *              'votes', 'desc'
 *          ]
 *        ]
 *
 * @package Enjoin\Builder
 */
class Order extends Group
{

    /**
     * @return string
     */
    public function getQuery()
    {
        if (is_string($this->params)) {
            # Simplest notation (ie `'order' => 'title DESC'`):
            $this->handleString($this->params);
        } elseif ($this->isArrayNotation($this->params)) {
            # Ie `['username', 'DESC']`:
            $this->handleArrayNotation($this->params);
        } elseif (is_array($this->params)) {
            # List of instructions:
            foreach ($this->params as $v) {
                if (is_string($v)) {
                    $this->handleString($v);
                } elseif ($this->isArrayNotation($v)) {
                    $this->handleArrayNotation($v);
                } elseif (is_array($v)) {
                    # Eager order:
                    $this->handleEager($v);
                }
            }
        }
        return join(', ', $this->query);
    }

    /**
     * For example: 'order' => 'title DESC'.
     * @param string $str
     * @param string $prefix
     */
    private function handleString($str, $prefix = '')
    {
        $notation = array_map('trim', explode(' ', $str));
        $this->handleArrayNotation($notation, $prefix);
    }

    /**
     * Detects notations like `['name', 'DESC']`.
     * @param mixed $input
     * @return bool
     */
    private function isArrayNotation($input)
    {
        if (is_array($input)) {
            $len = count($input);
            if ($len === 1 && isset($input[0]) && is_string($input[0])) {
                return true;
            } elseif ($len === 2 && isset($input[0]) && is_string($input[0]) && isset($input[1]) && is_string($input[1])) {
                $r = [];
                preg_match("/desc|asc/i", $input[1], $r);
                if ($r) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $notation
     * @param string $prefix
     */
    private function handleArrayNotation(array $notation, $prefix = '')
    {
        $query = '`' . $notation[0] . '`';
        if ($prefix) {
            $query = '`' . $prefix . '`.' . $query;
        }
        if (isset($notation[1])) {
            $query .= ' ' . strtoupper($notation[1]);
        }
        $this->query [] = $query;
    }

    /**
     * @param array $path
     */
    private function handleEager(array $path)
    {
        $idxList = [];
        $notation = [];
        $prefix = null;
        foreach ($path as $it) {
            if (is_string($it)) { // ie [ ... , 'name', 'desc']
                $notation [] = $it;
            } elseif ($this->isArrayNotation($it)) { // ie [ ... , ['name', 'desc'] ]
                $notation = $it;
            } else {
                $node = $this->findNode($it, $idxList);
                $prefix = $node->prefix;
            }
        }
        $this->handleArrayNotation($notation, $prefix);
    }

}
