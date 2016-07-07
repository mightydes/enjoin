<?php

namespace Enjoin\Builder;

use Enjoin\Enjoin;
use Enjoin\Model\Model;
use Enjoin\Extras;
use Enjoin\Exceptions\Error;
use Doctrine\Common\Inflector\Inflector;
use stdClass, Closure;

class Tree
{

    /**
     * @var stdClass
     */
    protected $tree;

    public $hasChildren = false;
    public $hasMany = false;
    public $hasLimit = false;

    /**
     * Tree constructor.
     * @param Model $Model
     * @param array $params
     */
    public function __construct(Model $Model, array $params)
    {
        $this->tree = $this->createNode($Model, $params);
        $this->hasChildren = count($this->tree->children) > 0;
        $this->hasLimit = isset($params['limit']);
        if ($this->hasChildren) {
            $this->walk(function (stdClass $node, array $path) {
                if (count($path) - 1) {
                    if ($node->where && is_null($node->required)) {
                        $node->required = true;
                    }
                }
            });
        }
    }

    /**
     * @param Closure $fn
     */
    public function walk(Closure $fn)
    {
        $this->visitNode($this->tree, $fn);
    }

    /**
     * @return stdClass
     */
    public function get()
    {
        return $this->tree;
    }

    /**
     * Defines: Model, key, prefix, as, asProp, skip, attributes, where, required, children.
     * @param Model $Model
     * @param array $params
     * @param null|stdClass $parent
     * @return stdClass
     */
    private function createNode(Model $Model, array $params = [], stdClass $parent = null)
    {
        $node = new stdClass;
        $node->Model = $Model;
        $node->key = $Model->unique;
        $node->prefix = null;
        $node->asProp = null;

        # Handle `as`:
        $node->as = null;
        if (isset($params['as'])) {
            $node->as = $params['as'];
            $node->key .= Extras::GLUE_CHAR . $params['as'];
        }

        # Handle `attributes`:
        $node->skip = [];
        $attrs = array_keys($Model->Definition->getAttributes());
        # Handle timestamps:
        if ($Model->isTimestamps()) {
            $attrs [] = $Model->getCreatedAtAttr();
            $attrs [] = $Model->getUpdatedAtAttr();
        }
        # Handle attributes in options:
        if (isset($params['attributes']) && $params['attributes']) {
            $attrs = array_intersect($attrs, $params['attributes']);
        }
        if (!in_array('id', $attrs)) {
            array_unshift($attrs, 'id');
            $node->skip [] = 'id';
        }
        $node->attributes = $attrs;

        # Handle `where`:
        $node->where = isset($params['where'])
            ? $params['where'] : [];

        # Handle `required`:
        $node->required = isset($params['required'])
            ? (bool)$params['required'] : null;

        # Handle `include`:
        !$parent ?: $this->handleRelation($parent, $node);
        $this->handleChildren($node, $params);

        return $node;
    }

    /**
     * @param stdClass $node
     * @param array $params
     */
    private function handleChildren(stdClass $node, array $params)
    {
        $node->children = [];
        if (isset($params['include'])) {
            if (is_array($params['include'])) {
                if (isset($params['include']['model'])) {
                    $node->children [] = $this->createNode($params['include']['model'], $params['include'], $node);
                } else {
                    foreach ($params['include'] as $it) {
                        if (is_array($it)) {
                            $node->children [] = $this->createNode($it['model'], $it, $node);
                        } elseif ($it instanceof Model) {
                            $node->children [] = $this->createNode($it, [], $node);
                        }
                    }
                }
            } elseif ($params['include'] instanceof Model) {
                $node->children [] = $this->createNode($params['include'], [], $node);
            }
        }
    }

    /**
     * @param stdClass $node
     * @param Closure $fn
     * @param array $path
     */
    private function visitNode(stdClass $node, Closure $fn, array $path = [])
    {
        $path [] = $node;
        $fn($node, $path);
        if ($node->children) {
            foreach ($node->children as $child) {
                $this->visitNode($child, $fn, $path);
            }
        }
    }

    /**
     * @param stdClass $parent
     * @param stdClass $child
     */
    private function handleRelation(stdClass $parent, stdClass $child)
    {
        $relation = Extras::findWhere($parent->Model->Definition->getRelations(), ['relatedKey' => $child->key]);
        $missedErr = "Unable to find relation between '{$parent->Model->unique}' " .
            "and '{$child->Model->unique}', foreign key: '{$child->key}'.";
        $relation ?: Error::dropModelException($missedErr);
        $child->relation = $relation;
        $child->foreignKey = $relation->foreignKey;

        if ($relation->type === Extras::HAS_MANY) {
            $this->hasMany = true;
        }

//        # Make sure that foreign key is in attributes:
//        if ($relation->type === Extras::BELONGS_TO) {
//            # Key stored in parent:
//            if (!in_array($relation->foreignKey, $parent->attributes)) {
//                $parent->attributes [] = $relation->foreignKey;
//                $parent->skip [] = $relation->foreignKey;
//            }
//        } else {
//            # Key stored in child:
//            if (!in_array($relation->foreignKey, $child->attributes)) {
//                $child->attributes [] = $relation->foreignKey;
//                $child->skip [] = $relation->foreignKey;
//            }
//        }

        # Handle child `as`:
        if (!$child->as) {
            if ($relation->type === Extras::HAS_ONE || $relation->type === Extras::BELONGS_TO) {
                # On `hasOne`, `belongsTo`:
                $child->as = Inflector::singularize($child->Model->Definition->table);
            } else {
                # On `hasMany`:
                $child->as = Inflector::pluralize($child->Model->Definition->table);
            }
            $child->asProp = Inflector::camelize($child->as);
        } else {
            $child->asProp = $child->as;
        }

        # Handle child `prefix`:
        if (!$child->prefix) {
            $child->prefix = $parent->prefix
                ? $parent->prefix . Extras::GLUE_CHAR . $child->as
                : $child->as;
        }
    }

}
