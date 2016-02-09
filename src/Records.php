<?php

namespace Enjoin;

class Records
{

    /**
     * @var Handler
     */
    private $Handler;

    /**
     * Extends handler tree with `getters` and `save`.
     * @param \Enjoin\Handler $Handler
     */
    public function __construct($Handler)
    {
        $Handler->walkTree(function (&$item) {
            $item['getters'] = [];
            $skip = [];

            # Perform timestamps
            if ($item['model']->isTimestamps()) {
                ## Created at
                $created_at = $item['model']->getCreatedAtAttr();
                if (in_array($created_at, $item['attributes'])) {
                    $item['getters'][$created_at] = Getters::getCreatedAt();
                    $skip [] = $created_at;
                }
                ## Updated at
                $updated_at = $item['model']->getUpdatedAtAttr();
                if (in_array($updated_at, $item['attributes'])) {
                    $item['getters'][$updated_at] = Getters::getUpdatedAt();
                    $skip [] = $updated_at;
                }
            }

            # Perform getters
            $contextAttrs = $item['model']->Context->getAttributes();
            foreach ($item['attributes'] as $attr) {
                if (!in_array($attr, $skip)) {
                    $item['getters'][$attr] = Getters::perform($contextAttrs[$attr]);
                }
            }
        });
        $this->Handler = $Handler;
    }

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
        return $this->records;
    }

    /**
     * @param array $item
     * @param $row
     * @return Record
     */
    private function getRecord(array $item, $row)
    {
        $id = $item['prefix']
            ? $row->{$item['prefix'] . Extras::$GLUE_CHAR . 'id'}
            : $row->id;
        $recordClass = $item['model']->Context->expanseRecord ?: Record::class;
        $Record = new $recordClass($item['model'], Extras::$PERSISTENT_RECORD, (int)$id);

        # Handle attributes
        $getValue = function ($attr) use ($item, $row) {
            $col = $attr;
            if ($item['prefix']) {
                $col = $item['prefix'] . Extras::$GLUE_CHAR . $attr;
            }
            return property_exists($row, $col) ? $row->$col : null;
        };
        foreach ($item['attributes'] as $attr) {
            if (!in_array($attr, $item['skip']) && !property_exists($Record, $attr)) {
                $Record->$attr = $item['getters'][$attr]($attr, $getValue);
            }
        }

        # Handle relations
        if (array_key_exists('relations', $item)) {
            foreach ($item['relations'] as $relation) {
                if (!property_exists($Record, $relation['rel']['record_as'])) {
                    if ($relation['rel']['type'] === Extras::$HAS_MANY) {
                        $Record->$relation['rel']['record_as'] = [];
                    } else {
                        $Record->$relation['rel']['record_as'] = null;
                    }
                }
            }
        }

        return $Record;
    }

    /**
     * @param array $cache
     * @param $id
     * @param $prop
     * @return array[$body[], $index]
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

        # Root
        if (!array_key_exists($id, $cache)) {
            return $nan;
        }
        return [$cache[$id]['body'], $cache[$id]['index']];
    }

    /**
     * @param \stdClass $row
     */
    private function handleRow($row)
    {
        $this->Handler->walkTree(function ($item, $path) use ($row) {
            # Check is this part already handled. Perform road.
            $road = [];
            $cache = $this->cache;
            foreach ($path as $it) {
                $id = $it['prefix']
                    ? $row->{$it['prefix'] . Extras::$GLUE_CHAR . 'id'}
                    : $row->id;
                if (is_null($id)) {
                    # Skip handle on `id = NULL` in sql result
                    // TODO: break walk
                    return null;
                }
                $prop = array_key_exists('rel', $it) ? $it['rel']['record_as'] : null;
                $mile = ['id' => $id, 'prop' => $prop];
                list($cache, $mile['index']) = $this->getIndex($cache, $id, $prop);
                $road [] = $mile;
            }

            # Check last mile cache index
            if (is_null($mile['index'])) {
                $this->applyRecord($this->records, $this->cache,
                    $this->getRecord($item, $row), $road);
            }
        });
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
            # Go on the road
            ## Perform cache pointer
            if (is_null($mile['prop'])) {
                $cache_link =& $cache[$mile['id']]['body'];
            } else {
                $cache_link =& $cache[$mile['prop']][$mile['id']]['body'];
            }
            ## Perform branch pointer
            if (is_array($branch)) {
                $branch_link =& $branch[$mile['index']]->$road[0]['prop'];
            } else {
                $branch_link =& $branch->$road[0]['prop'];
            }
            ## Fire
            $this->applyRecord($branch_link, $cache_link, $Record, $road);
        } else {
            # Apply record
            $count = 0;
            if (is_array($branch)) {
                # Many children
                $count = array_push($branch, $Record);
            } else {
                # One child
                $branch = $Record;
            }

            # Store array index in cache
            $index = $count > 0 ? $count - 1 : 0;
            if (is_null($mile['prop'])) {
                ## Root
                $cache[$mile['id']] = ['index' => $index, 'body' => []];
            } else {
                $cache[$mile['prop']][$mile['id']] = ['index' => $index, 'body' => []];
            }
        }
    }

} // end of class
