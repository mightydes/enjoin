<?php

namespace Enjoin\Builder;

use Enjoin\Enjoin;
use Enjoin\Model\Model;
use Enjoin\Exceptions\Error;

/**
 * Class Group
 *
 * Input examples:
 *      - string `title`
 *      - [ 'name', 'username' ]
 *      - [
 *          'name',
 *          [
 *              ['model' => Enjoin::get('replies'), 'as' => 'comment'],
 *              Enjoin::get('rating'),
 *              'votes'
 *          ]
 *        ]
 *
 * @package Enjoin\Builder
 */
class Group
{

    /**
     * @var Model
     */
    protected $Model;

    /**
     * @var \stdClass
     */
    protected $tree;

    protected $params;
    protected $query = [];

    /**
     * Group constructor.
     * @param Model $Model
     * @param Tree $Tree
     * @param string|array $params
     */
    public function __construct(Model $Model, Tree $Tree, $params)
    {
        $this->Model = $Model;
        $this->tree = $Tree->get();
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        if (is_string($this->params)) {
            # Simplest notation (ie `'group' => 'title'`):
            $this->handleString($this->params);
        } elseif (is_array($this->params)) {
            # List of instructions:
            foreach ($this->params as $v) {
                if (is_string($v)) {
                    $this->handleString($v);
                } elseif (is_array($v)) {
                    # Eager order:
                    $this->handleEager($v);
                }
            }
        }
        return join(', ', $this->query);
    }

    /**
     * For example: 'group' => 'title'.
     * Note: `books.etc.id` => `books.etc`.`id`.
     *
     * @param string $str
     * @param string $prefix
     */
    private function handleString($str, $prefix = '')
    {
        $e = $this->Model->dialectify()->getEscapeChar();
        $query = $prefix
            ? "{$e}$prefix{$e}.{$e}$str{$e}"
            : $this->handleColumnedString($str);
        $this->query [] = $query;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function handleColumnedString($str)
    {
        $e = $this->Model->dialectify()->getEscapeChar();
        $arr = explode('.', $str);
        if (count($arr) > 1) {
            $last = array_pop($arr);
            $str = join('.', $arr);
            return "{$e}$str{$e}.{$e}$last{$e}";
        }
        return "{$e}$str{$e}";
    }

    /**
     * @param array $path
     */
    private function handleEager(array $path)
    {
        $idxList = [];
        $str = '';
        $prefix = null;
        foreach ($path as $it) {
            if (is_string($it)) { // ie [ ... , 'name']
                $str = $it;
            } else {
                $node = $this->findNode($it, $idxList);
                $prefix = $node->prefix;
            }
        }
        $str ?: Error::dropBuilderException("Missed field in 'group' eager clause!");
        $this->handleString($str, $prefix);
    }

    /**
     * @param mixed $it
     * @param array $idxList
     * @return mixed
     * @throws \Enjoin\Exceptions\BuilderException
     */
    protected function findNode($it, array &$idxList)
    {
        $list = $this->tree->children;
        foreach ($idxList as $idx) {
            $list = $list[$idx]->children;
        }
        $as = null;
        if (is_array($it)) {
            $model = $it['model'];
            !isset($it['as']) ?: $as = $it['as'];
        } else {
            $model = $it;
        }
        foreach ($list as $idx => $node) {
            if ($node->Model->getUnique() === $model->getUnique() &&
                (!$as || $node->as === $as)
            ) {
                $idxList [] = $idx;
                return $node;
            }
        }
        $errCtx = $this instanceof Group ? 'group' : 'order';
        Error::dropBuilderException("Invalid '$errCtx' model: '{$model->getUnique()}'");
    }

}
