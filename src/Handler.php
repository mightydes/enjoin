<?php

namespace Enjoin;

use Doctrine\Common\Inflector\Inflector;
use stdClass, Closure;

class Handler
{

    /**
     * Contains list of paths.
     * @var array
     */
    private $body = [];

    /**
     * Contains list of path items.
     * Each item array has `model` option, and other info.
     * @var array
     */
    private $path = [];

    /**
     * Nested object of related path items (nodes).
     * @var array
     */
    private $tree = [];

    /**
     * Handler constructor.
     * @param Model $Model
     * @param array $params
     */
    public function __construct(Model $Model, array $params = [])
    {
        $this->pushPath($Model, $params);
        // TODO: handle include.
        $this->performTree();
    }

    /**
     * Perform tree from `body`.
     */
    private function performTree()
    {
        # Apply index item:
        $item = $this->body[0][0];
        $item['prefix'] = '';
        $this->tree [] = $item;

        $done = [];
        foreach ($this->body as $path) {
            $len = count($path);
            if ($len > 1) {
                $key = implode('.', Extras::pluck($path, 'key'));
                if (!array_key_exists($key, $done)) {
                    $this->extendTree($this->tree[0], $path);
                    $done[$key] = null;
                }
            }
        }
    }

    /**
     * TODO
     * @param array $node
     * @param array $path
     * @param int $pointer
     * @param array $prefix
     */
    private function extendTree(array &$node, array $path, $pointer = 1, array $prefix = [])
    {
        $len = count($path);
        if ($pointer < $len - 1) {
            # Move pointer:
            foreach ($node['relations'] as $idx => $item) {
                if ($item['key'] === $path[$pointer]['key']) {
                    $prefix [] = $item['rel']['as'];
                    return $this->extendTree($node['relations'][$idx], $path, $pointer + 1, $prefix);
                }
            }
        } else {
            # Resolve relation:
            $item =& $path[$len - 1];
            $item['rel'] = static::getRelation($path[$len - 2], $item);
            $prefix [] = $item['rel']['as'];
            $item['prefix'] = implode(Extras::$GLUE_CHAR, $prefix);

            # Apply data
            if (!array_key_exists('relations', $node)) {
                $node['relations'] = [];
            }
            $node['relations'] [] = $item;
        }
    }

    /**
     * @param array $itemA
     * @param array $itemB
     * @return array
     */
    public static function getRelation(array &$itemA, array &$itemB)
    {
        $as = $itemB['as'];
        $relation = Extras::findWhere(
            $itemA['model']->Definition->getRelations(),
            ['related_key' => $itemB['key']]
        );
        $relation['record_as'] = $as;

        # Make sure that foreign key is in attributes:
        if ($relation['type'] === Extras::BELONGS_TO) {
            # Key stored on `A`:
            if (!in_array($relation['foreign_key'], $itemA['attributes'])) {
                $itemA['attributes'] [] = $relation['foreign_key'];
                $itemA['skip'] [] = $relation['foreign_key'];
            }
        } else {
            # Key stored on `B`:
            if (!in_array($relation['foreign_key'], $itemB['attributes'])) {
                $itemB['attributes'] [] = $relation['foreign_key'];
                $itemB['skip'] [] = $relation['foreign_key'];
            }
        }

        # Handle `as` if not defined:
        if (is_null($as)) {
            if ($relation['type'] === Extras::HAS_ONE || $relation['type'] === Extras::BELONGS_TO) {
                # On `hasOne`, `belongsTo`:
                $relation['as'] = Inflector::singularize($itemB['model']->Definition->table);
            } else {
                # On `hasMany`:
                $relation['as'] = Inflector::pluralize($itemB['model']->Definition->table);
            }
            $relation['record_as'] = Inflector::camelize($relation['as']);
        }

        return $relation;
    }

    /**
     * @param Model $Model
     * @param array|null $params
     * @return array
     */
    public static function performItem(Model $Model, array $params = null)
    {
        $item = [
            'model' => $Model,
            'key' => get_class($Model->Definition),
            'as' => null
        ];

        # Handle `as`:
        if (isset($params['as'])) {
            $item['as'] = $params['as'];
            $item['key'] .= Extras::GLUE_CHAR . $params['as'];
        }

        # Handle `attributes`:
        $item['skip'] = [];
        $attrs = array_keys($Model->Definition->getAttributes());
        # Handle timestamps:
        if ($Model->isTimestamps()) {
            $attrs [] = $Model->getCreatedAtAttr();
            $attrs [] = $Model->getUpdatedAtAttr();
        }
        # Handle attributes in options:
        if (isset($params['attributes']) && is_array($params['attributes'])) {
            $attrs = array_intersect($attrs, $params['attributes']);
        }
        if (!in_array('id', $attrs)) {
            $attrs [] = 'id';
            $item['skip'] [] = 'id';
        }
        $item['attributes'] = $attrs;

        # Handle `where`:
        if (isset($params['where']) && is_array($params['where'])) {
            $item['where'] = $params['where'];
        }

        # Handle `required`:
        if (isset($params['required'])) {
            $item['required'] = (bool)$params['required'];
        }

        return $item;
    }

    /**
     * @param Model $Model
     * @param array|null $params
     */
    private function pushPath(Model $Model, array $params = null)
    {
        array_push($this->path, static::performItem($Model, $params));
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
     * @param Closure $closure
     */
    public function walkTree(Closure $closure)
    {
        $this->walk($this->tree[0], $closure);
    }

    /**
     * @param array $node
     * @param Closure $closure
     * @param array $path
     */
    private function walk(array &$node, Closure $closure, array $path = [])
    {
        $path [] = $node;
        $closure($node, $path);
        if (array_key_exists('relations', $node)) {
            foreach (array_keys($node['relations']) as $idx) {
                $this->walk($node['relations'][$idx], $closure, $path);
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

}
