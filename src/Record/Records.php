<?php

namespace Enjoin\Record;

use Enjoin\Builder\Tree;
use Enjoin\Factory;
use Enjoin\Extras;
use Enjoin\Enjoin;
use Illuminate\Database\Connection;
use stdClass, PDO;

class Records
{

    /**
     * @var Tree
     */
    protected $Tree;

    protected $triggers = [];

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
     * @param Connection $connection
     * @param string $query
     * @param array $place
     * @param int $flags
     * @return array
     */
    public function handleRows(Connection $connection, &$query, array &$place = [], $flags = 0)
    {
        if ($flags & Enjoin::UNBUFFERED_QUERY) {
            if (strtolower($connection->getConfig('driver')) === 'mysql') {
                return $this->handleMysqlUnbuffered($connection, $query, $place);
            }
        }

        $rows = $connection->select($query, $place);
        return $this->handleBufferedRows($rows);
    }

    /**
     * @param Connection $connection
     * @param string $query
     * @param array $place
     * @return array
     */
    protected function handleMysqlUnbuffered(Connection $connection, &$query, array &$place = [])
    {
        $records = [];
        $pdo = $connection->getPdo();
        $origFlag = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        try {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $query = $pdo->prepare($query);
            $query->execute($place);
            while ($row = $query->fetch(PDO::FETCH_OBJ)) {
                $this->handleRow($row, $records);
            }
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $origFlag);
        }
        $this->fixRecordsIndexes($records);
        return $records;
    }

    /**
     * @param array $rows
     * @return array
     */
    protected function handleBufferedRows(array &$rows)
    {
        $records = [];
        for ($k = 0; $k < count($rows); $k++) {
            $this->handleRow($rows[$k], $records);
            $rows[$k] = null;
        }
        $rows = [];
        $this->fixRecordsIndexes($records);
        return $records;
    }

    protected function handleRow(stdClass $row, array &$records)
    {
        $this->Tree->walk(function (stdClass $node, array &$path) use ($row, &$records) {
            $rec_ref =& $records;
            foreach ($path as $pathIx => $it) { // $it -- stdClass...
                $isRoot = is_null($it->prefix);

                $id = $isRoot
                    ? $row->id
                    : $row->{$it->prefix . Extras::GLUE_CHAR . 'id'};

                if (is_null($id)) {
                    continue;
                }
                $tmp_id = '_' . $id; // Must be string...

                # NULL for root node, and STRING for other nodes...
                $prop = isset($it->asProp) ? $it->asProp : null;

                $oneToMany = true;
                if (!$isRoot && $it->relation->type !== Extras::HAS_MANY) {
                    $oneToMany = false;
                }

                // Handle is record exists...
                if ($oneToMany) {
                    $isExists = $isRoot
                        ? isset($rec_ref[$tmp_id])
                        : isset($rec_ref->{$prop}[$tmp_id]);
                } else {
                    $isExists = isset($rec_ref->{$prop});
                }

                if (!$isExists) { // Apply record...
                    $Record = $this->getRecord($node, $row);
                    if ($isRoot) {
                        // Always one-to-many...
                        $rec_ref[$tmp_id] = $Record;
                    } else {
                        $oneToMany
                            ? $rec_ref->{$prop}[$tmp_id] = $Record
                            : $rec_ref->{$prop} = $Record;
                    }
                }

                if ($isRoot) {
                    $rec_ref =& $rec_ref[$tmp_id];
                } else {
                    $oneToMany
                        ? $rec_ref =& $rec_ref->{$prop}[$tmp_id]
                        : $rec_ref =& $rec_ref->{$prop};
                }

                if (!$isRoot) {
                    $this->triggers[$pathIx][$prop] = ['oneToMany' => $oneToMany];
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

    /**
     * @param array $records
     * @param int $lvl
     */
    protected function fixRecordsIndexes(array &$records, $lvl = 0)
    {
        $keys = array_keys($records);
        $ix = 0;
        $nextLvl = $lvl + 1;
        foreach ($keys as &$tmp_id) {
            if (isset($this->triggers[$nextLvl])) {
                foreach ($this->triggers[$nextLvl] as $prop => $opt) {
                    if (isset($records[$tmp_id]->{$prop})) {
                        if ($opt['oneToMany']) {
                            $this->fixRecordsIndexes($records[$tmp_id]->{$prop}, $nextLvl);
                        } else {
                            $this->fixOneToOneIndexes($records[$tmp_id]->{$prop}, $nextLvl);
                        }
                    }
                }
            }

            $records[$ix] = $records[$tmp_id];
            unset($records[$tmp_id]);
            $ix++;
        }
    }

    /**
     * @param Record $record
     * @param int $lvl
     */
    protected function fixOneToOneIndexes(Record $record, $lvl = 0)
    {
        $nextLvl = $lvl + 1;
        if (isset($this->triggers[$nextLvl])) {
            foreach ($this->triggers[$nextLvl] as $prop => $opt) {
                if (isset($record->{$prop})) {
                    if ($opt['oneToMany']) {
                        $this->fixRecordsIndexes($record->{$prop}, $nextLvl);
                    } else {
                        $this->fixOneToOneIndexes($record->{$prop}, $nextLvl);
                    }
                }
            }
        }
    }

}
