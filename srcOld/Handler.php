<?php

namespace Enjoin;

use Doctrine\Common\Inflector\Inflector;

class HandlerOld
{

    /**
     * Contains list of paths.
     * @var array
     */
    private $body = [];

    /**
     * Contains list of path items.
     * Each item has `model` attribute, and other info.
     * @var array
     */
    private $path = [];

    /**
     * Nested list of related path items.
     * @var array
     */
    private $tree = [];

    /**
     * Perform tree from `body`.
     */
    public function resolveTree()
    {
        # Apply index item
        $item = $this->body[0][0];
        $item['prefix'] = '';
        $this->tree [] = $item;

        $done = [];
        foreach ($this->body as $path) {
            $len = count($path);
            if ($len > 1) {
                $key = implode('.', array_column($path, 'key'));
                if (!array_key_exists($key, $done)) {
                    $this->extendTree($this->tree[0], $path);
                    $done[$key] = null;
                }
            }
        }
    }

    /**
     * @param array $branch
     * @param array $path
     * @param int $pointer
     * @param array $prefix
     * @return null
     */
    private function extendTree(array &$branch, array $path, $pointer = 1, array $prefix = [])
    {
        $len = count($path);
        if ($pointer < $len - 1) {
            # Move pointer
            foreach ($branch['relations'] as $idx => $item) {
                if ($item['key'] === $path[$pointer]['key']) {
                    $prefix [] = $item['rel']['as'];
                    return $this->extendTree($branch['relations'][$idx], $path, $pointer + 1, $prefix);
                }
            }
        } else {
            # Resolve relation
            $item =& $path[$len - 1];
            $item['rel'] = self::getRelation($path[$len - 2], $item);
            $prefix [] = $item['rel']['as'];
            $item['prefix'] = implode(Extras::$GLUE_CHAR, $prefix);

            # Apply data
            if (!array_key_exists('relations', $branch)) {
                $branch['relations'] = [];
            }
            $branch['relations'] [] = $item;
        }
        return null;
    }

    /**
     * @param $itemA
     * @param $itemB
     * @return int|null|string
     */
    public static function getRelation(&$itemA, &$itemB)
    {
        $as = $itemB['as'];
        $relation = Extras::findWhere($itemA['model']->Context->getRelations(),
            ['related_key' => $itemB['key']]);
        $relation['record_as'] = $as;

        # Make sure that foreign key in attributes
        if ($relation['type'] === Extras::$BELONGS_TO) {
            # Key stored on `A`
            if (!in_array($relation['foreign_key'], $itemA['attributes'])) {
                $itemA['attributes'] [] = $relation['foreign_key'];
                $itemA['skip'] [] = $relation['foreign_key'];
            }
        } else {
            # Key stored on `B`
            if (!in_array($relation['foreign_key'], $itemB['attributes'])) {
                $itemB['attributes'] [] = $relation['foreign_key'];
                $itemB['skip'] [] = $relation['foreign_key'];
            }
        }

        # Handle `as` if not defined
        if (is_null($as)) {
            if ($relation['type'] === Extras::$HAS_ONE || $relation['type'] === Extras::$BELONGS_TO) {
                # On `hasOne`, `belongsTo`
                $relation['as'] = Inflector::singularize($itemB['model']->Context->table);
            } else {
                # On `hasMany`
                $relation['as'] = Inflector::pluralize($itemB['model']->Context->table);
            }
            $relation['record_as'] = Inflector::camelize($relation['as']);
        }

        return $relation;
    }

    /**
     * @param Model $Model
     * @param null $params
     * @return array
     */
    public static function performItem($Model, $params = null)
    {
        $item = [
            'model' => $Model,
            'key' => $Model->getKey(),
            'as' => null
        ];

        # Handle `as`
        if (is_array($params) && array_key_exists('as', $params)) {
            $item['as'] = $params['as'];
            $item['key'] .= Extras::$GLUE_CHAR . $params['as'];
        }

        # Handle `attributes`
        $item['skip'] = [];
        $attrs = array_keys($Model->Context->getAttributes());
        ## Handle timestamps
        if ($Model->isTimestamps()) {
            $attrs [] = $Model->getCreatedAtAttr();
            $attrs [] = $Model->getUpdatedAtAttr();
        }
        ## Handle attributes in options
        if (is_array($params) && array_key_exists('attributes', $params) && $params['attributes']) {
            $attrs = array_intersect($attrs, $params['attributes']);
        }
        if (!in_array('id', $attrs)) {
            $attrs [] = 'id';
            $item['skip'] [] = 'id';
        }
        $item['attributes'] = $attrs;

        # Handle `where`
        if (is_array($params) && array_key_exists('where', $params)) {
            $item['where'] = $params['where'];
        }

        # Handle `required`
        if (is_array($params) && array_key_exists('required', $params)) {
            $item['required'] = $params['required'];
        }

        return $item;
    }

    /**
     * @param Model $Model
     * @param null $params
     */
    public function pushPath($Model, $params = null)
    {
        array_push($this->path, self::performItem($Model, $params));
        array_push($this->body, $this->path);
    }

    /**
     * Pop path array.
     */
    public function popPath()
    {
        array_pop($this->path);
    }

    /**
     * @param callable $closure
     */
    public function walkTree(\Closure $closure)
    {
        $this->walk($this->tree[0], $closure);
    }

    /**
     * @param array $branch
     * @param callable $closure
     * @param array $path
     */
    private function walk(array &$branch, \Closure $closure, array $path = [])
    {
        $path [] = $branch;
        $closure($branch, $path);
        if (array_key_exists('relations', $branch)) {
            foreach (array_keys($branch['relations']) as $idx) {
                $this->walk($branch['relations'][$idx], $closure, $path);
            }
        }
    }

    /**
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

} // end of class
