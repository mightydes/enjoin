<?php

namespace Enjoin\Record;

use Enjoin\Builder\Tree;
use Enjoin\Factory;
use Enjoin\Extras;
use Enjoin\Exceptions\LeftJoinNullIdException;
use stdClass;

class Records
{

    const INDEX_KEY = 0;
    const CHILDREN_KEY = 1;

    /**
     * @var Tree
     */
    protected $Tree;

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
            foreach ($node->attributes as &$attr) {
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
    public function handleRows(array &$rows)
    {
        $indexes = [];
        $records = [];
        for ($k = 0; $k < count($rows); $k++) {
            try {
                $this->handleRow($rows[$k], $indexes, $records);
            } catch (LeftJoinNullIdException $e) {
                // Thrown on eager loading with `LEFT JOIN` condition...
            }
            $rows[$k] = null;
        }
        $rows = [];
        return $records;
    }

    protected function handleRow(stdClass $row, array &$indexes, array &$records)
    {
        $this->Tree->walk(function (stdClass $node, array &$path) use ($row, &$indexes, &$records) {
            $idx_ref =& $indexes;
            $rec_ref =& $records;
            foreach ($path as $pathIx => $it) { // $it -- stdClass...
                $isRoot = is_null($it->prefix);

                $id = $isRoot
                    ? $row->id
                    : $row->{$it->prefix . Extras::GLUE_CHAR . 'id'};

                if (is_null($id)) {
                    throw new LeftJoinNullIdException;
                }

                # NULL for root node, and STRING for other nodes...
                $prop = isset($it->asProp) ? $it->asProp : null;

                $oneToMany = true;
                if (!$isRoot && $it->relation->type !== Extras::HAS_MANY) {
                    $oneToMany = false;
                }

                $isCache = $isRoot
                    ? isset($idx_ref[$id])
                    : isset($idx_ref[$prop][$id]);

                if (!$isCache) { // Create array on new id occurrence...
                    $addIx = $oneToMany
                        ? 0 // ono-to-many relation...
                        : null; // ono-to-one relation...
                    if ($isRoot) {
                        $idx_ref[$id] = [
                            self::INDEX_KEY => $addIx,
                            self::CHILDREN_KEY => []
                        ];
                        // Always one-to-many...
                        $idx_ref[$id][self::INDEX_KEY] = count($idx_ref) - 1;
                    } else {
                        $idx_ref[$prop][$id] = [
                            self::INDEX_KEY => $addIx,
                            self::CHILDREN_KEY => []
                        ];
                        if ($oneToMany) {
                            $idx_ref[$prop][$id][self::INDEX_KEY] = count($idx_ref[$prop]) - 1;
                        }
                    }
                }

                $ix = $isRoot
                    ? $idx_ref[$id][self::INDEX_KEY]
                    : $idx_ref[$prop][$id][self::INDEX_KEY];

                if (!$isCache) { // Apply record...
                    $Record = $this->getRecord($node, $row);
                    if ($isRoot) {
                        // Always one-to-many...
                        $rec_ref[$ix] = $Record;
                    } else {
                        is_null($ix) // ono-to-one relation...
                            ? $rec_ref->{$prop} = $Record
                            : $rec_ref->{$prop}[$ix] = $Record;
                    }
                }

                if ($isRoot) {
                    $idx_ref =& $idx_ref[$id][self::CHILDREN_KEY];
                    $rec_ref =& $rec_ref[$ix];
                } else {
                    $idx_ref =& $idx_ref[$prop][$id][self::CHILDREN_KEY];
                    is_null($ix) // ono-to-one relation...
                        ? $rec_ref =& $rec_ref->{$prop}
                        : $rec_ref =& $rec_ref->{$prop}[$ix];
                }
            } // end `$path` foreach...
        });
    }

    /**
     * @param stdClass $node
     * @param stdClass $row
     * @return Record
     */
    protected function getRecord(stdClass $node, stdClass $row)
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
        foreach ($node->attributes as &$attr) {
            if (!in_array($attr, $node->skip) && !isset($Record->$attr)) {
                $Record->$attr = $node->getters[$attr]($attr, $getValue);
            }
        }

        # Handle relations:
        if (isset($node->children)) {
            foreach ($node->children as $child) { // $child -- Model...
                $asProp = $child->asProp;
                if (!isset($Record->$asProp)) {
                    $Record->$asProp = $child->relation->type === Extras::HAS_MANY
                        ? [] : null;
                }
            }
        }

        return $Record;
    }

}
