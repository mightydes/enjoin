<?php

namespace Enjoin\Record;

use Enjoin\Builder\Tree;
use Enjoin\Factory;
use Enjoin\Extras;
use Enjoin\Enjoin;
use stdClass;

class Records
{

    /**
     * @var Tree
     */
    protected $Tree;

    /**
     * List of records.
     * @var array
     */
    private $records = [];

    /**
     * Handled rows.
     * @var array
     */
    private $cache = [];

    /**
     * Records constructor.
     * @param Tree $Tree
     */
    public function __construct(Tree $Tree)
    {
        $this->Tree = $Tree;
        $Tree->walk(function (stdClass $node) {
            $node->getters = [];
            $skip = [];
            $Getters = Factory::getGetters();

            # Perform timestamps:
            if ($node->Model->isTimestamps()) {
                # Created at:
                $createdAtAttr = $node->Model->getCreatedAtAttr();
                if (in_array($createdAtAttr, $node->attributes)) {
                    $node->getters[$createdAtAttr] = $Getters->getCreatedAt($node->Model);
                    $skip [] = $createdAtAttr;
                }
                # Updated at:
                $updatedAtAttr = $node->Model->getUpdatedAtAttr();
                if (in_array($updatedAtAttr, $node->attributes)) {
                    $node->getters[$updatedAtAttr] = $Getters->getUpdatedAt($node->Model);
                    $skip [] = $updatedAtAttr;
                }
            }

            # Perform getters:
            $defAttributes = $node->Model->getDefinition()->getAttributes();
            foreach ($node->attributes as $attr) {
                if (!in_array($attr, $skip)) {
                    $node->getters[$attr] = $Getters->perform($node->Model, $defAttributes[$attr]);
                }
            }
        });
    }

    /**
     * @param array $rows
     * @return array
     */
    public function handleRows(array $rows)
    {
        $this->records = [];
        $this->cache = [];
        foreach ($rows as $row) {
            $this->handleRow($row);
        }
//        !Enjoin::debug() ?: sd($this->cache);
        return $this->records;
    }

    /**
     * @param stdClass $row
     */
    private function handleRow(stdClass $row)
    {
        $this->Tree->walk(function (stdClass $node, array $path) use ($row) {
            # Check is this part already handled. Perform road.
            $road = [];
            $cache = $this->cache;
            foreach ($path as $it) {
//                !Enjoin::debug() ?: sd($row);
                $id = $it->prefix
                    ? $row->{$it->prefix . Extras::GLUE_CHAR . 'id'}
                    : $row->id;
                if (is_null($id)) {
                    # Skip handle on `id = NULL` in sql result.
                    // TODO: break walk
                    return null;
                }
//                !Enjoin::debug() ?: sd($path);
                $prop = isset($it->asProp) ? $it->asProp : null;
                $mile = ['id' => $id, 'prop' => $prop];
                list($cache, $mile['index']) = $this->getIndex($cache, $id, $prop);
                $road [] = $mile;
            }

            # Check last mile cache index:
//            if (is_null($mile['index'])) {
            if (!isset($mile['index'])) {
                $Record = $this->getRecord($node, $row);
                $this->applyRecord($this->records, $this->cache, $Record, $road);
            }
        });
    }

    /**
     * @param stdClass $node
     * @param stdClass $row
     * @return Record
     */
    private function getRecord(stdClass $node, stdClass $row)
    {
        $id = $node->prefix
            ? $row->{$node->prefix . Extras::GLUE_CHAR . 'id'}
            : $row->id;
        $recordClass = $node->Model->getDefinition()->expanseRecord ?: Record::class;
        $Record = new $recordClass($node->Model, Engine::PERSISTENT, (int)$id);

        # Handle attributes:
        $getValue = function ($attr) use ($node, $row) {
            $col = $attr;
            if ($node->prefix) {
                $col = $node->prefix . Extras::GLUE_CHAR . $attr;
            }
            return isset($row->$col) ? $row->$col : null;
        };
        foreach ($node->attributes as $attr) {
            if (!in_array($attr, $node->skip) && !isset($Record->$attr)) {
                $Record->$attr = $node->getters[$attr]($attr, $getValue);
            }
        }

        # Handle relations:
        if (isset($node->children)) {
            foreach ($node->children as $child) {
                $asProp = $child->asProp;
                if (!isset($Record->$asProp)) {
                    $Record->$asProp = $child->relation->type === Extras::HAS_MANY
                        ? [] : null;
                }
            }
        }

        return $Record;
    }

    /**
     * @param array $cache
     * @param int $id
     * @param string $prop
     * @return array[ <body>[], <index> ]
     */
    private function getIndex(array $cache, $id, $prop)
    {
        $nan = [[], null];

        if (!is_null($prop)) {
            if (!array_key_exists($prop, $cache) || !array_key_exists($id, $cache[$prop])) {
                return $nan;
            }
            return [$cache[$prop][$id]['body'], $cache[$prop][$id]['index']];
        }

        # Root:
        if (!array_key_exists($id, $cache)) {
            return $nan;
        }
        return [$cache[$id]['body'], $cache[$id]['index']];
    }

    /**
     * @param array|Record $branch
     * @param array $cache
     * @param Record $Record
     * @param array $road
     */
    private function applyRecord(&$branch, &$cache, $Record, array $road)
    {
        $mile = array_shift($road);

        if ($road) {
            # Go on the road.

            # Perform cache pointer:
            if (is_null($mile['prop'])) {
                $cache_link =& $cache[$mile['id']]['body'];
            } else {
                $cache_link =& $cache[$mile['prop']][$mile['id']]['body'];
            }

            # Perform branch pointer:
            if (is_array($branch)) {
                $branch_link =& $branch[$mile['index']]->$road[0]['prop'];
            } else {
                $branch_link =& $branch->$road[0]['prop'];
            }

            # Fire:
            $this->applyRecord($branch_link, $cache_link, $Record, $road);
        } else {
            # Apply record:
            $count = 0;
            if (is_array($branch)) {
                # Many children:
                $count = array_push($branch, $Record);
            } else {
                # One child:
                $branch = $Record;
            }

            # Store array index in cache:
            $index = $count > 0 ? $count - 1 : 0;
            if (is_null($mile['prop'])) { // ie root
                $cache[$mile['id']] = ['index' => $index, 'body' => []];
            } else {
                $cache[$mile['prop']][$mile['id']] = ['index' => $index, 'body' => []];
            }
        }
    }

}
