<?php

namespace Enjoin\Record;

use Enjoin\Enjoin;
use Enjoin\Exceptions\Error;
use Enjoin\Model\Model;
use Enjoin\Factory;
use Carbon\Carbon;

class Engine
{

    const PERSISTENT = 'PERSISTENT';
    const NON_PERSISTENT = 'NON_PERSISTENT';

    # BITWISE:
    const SOFT_SAVE = 1;

    /**
     * @var Record
     */
    public $Record;

    /**
     * @var Model
     */
    public $Model;

    public $type = null;
    public $id = null;

    /**
     * @param Record $Record
     * @param Model $Model
     * @param string $type
     * @param null|int $id
     */
    public function __construct(Record $Record, Model $Model, $type = self::NON_PERSISTENT, $id = null)
    {
        $this->Record = $Record;
        $this->Model = $Model;
        $this->type = $type;
        $this->id = $id;
    }

    public function save(array $pick = null, $flags = 0)
    {
        $defAttributes = $this->Model->Definition->getAttributes();
        $list = $this->prepSaveList($pick);
        $volume = [];

        if ($this->Model->isTimestamps()) {
            $this->prepSaveTimestamps($list, $volume);
        }

        # Perform setters and validation:
        $validate = [];
        $record = $this->Record->__toArray();
        foreach ($list as $k => $v) {
            if (array_key_exists($k, $defAttributes)) {
                $volume[$k] = Factory::getSetters()->perform($record, $defAttributes[$k], $k);
                if (isset($defAttributes[$k]['validate'])) {
                    $validate [] = [$k, $volume[$k], $defAttributes[$k]['validate']];
                }
            }
        }
        !$validate ?: Factory::getSetters()->validate($validate);

        if (!($flags & self::SOFT_SAVE)) {
            $id = $this->saveEntry($volume);
            $this->id = $id;
            $this->Record->id = $id;
        }

        return $this->mapSaved($defAttributes, $volume);
    }

    /**
     * @param array $volume
     * @return int|mixed
     */
    private function saveEntry(array $volume)
    {
        // TODO: flush cache...

        if ($this->type === self::NON_PERSISTENT) {
            $this->type = self::PERSISTENT;
            return $this->Model->queryBuilder()->insertGetId($volume);
        }

        if ($volume['id'] === $this->id) {
            unset($volume['id']);
        }
        $this->Model->CacheJar->flush();
        $this->Model->queryBuilder()
            ->where('id', $this->id)// use constructed id
            ->take(1)
            ->update($volume); // id can be changed
        return isset($volume['id']) ? $volume['id'] : $this->id;
    }

    /**
     * @param array|null $pick
     * @return array
     */
    private function prepSaveList(array $pick = null)
    {
        $list = [];
        foreach ($this->Record as $key => $val) {
            if ($pick && !in_array($key, $pick)) {
                continue;
            }
            if ($val instanceof Record) {
                $val->save();
            } elseif (is_array($val) || is_object($val)) {
                foreach ($val as $k => $v) {
                    if ($v instanceof Record) {
                        $v->save();
                    }
                }
            } else {
                $list[$key] = $val;
            }
        }
        if ($this->type === self::PERSISTENT && $pick && !in_array('id', $list)) {
            $list = array_merge(['id' => $this->Record->id], $list);
        }
        return $list;
    }

    /**
     * @param array $list
     * @param array $volume
     */
    private function prepSaveTimestamps(array &$list, array &$volume)
    {
        $Setters = Factory::getSetters();
        # Created at:
        $createdAtAttr = $this->Model->getCreatedAtAttr();
        if (array_key_exists($createdAtAttr, $list)) {
            $volume[$createdAtAttr] = $Setters->getCreatedAt($list[$createdAtAttr]);
        } elseif ($this->type === self::NON_PERSISTENT) {
            $volume[$createdAtAttr] = $Setters->getCreatedAt();
        }
        unset($list[$createdAtAttr]);
        # Updated at:
        $updatedAtAttr = $this->Model->getUpdatedAtAttr();
        $volume[$updatedAtAttr] = $Setters->getUpdatedAt();
        $this->touchUpdatedAt($updatedAtAttr, $volume[$updatedAtAttr]);
        unset($list[$updatedAtAttr]);
    }

    /**
     * @param string $updatedAtAttr
     * @param string $dateTimeString
     * @return Carbon|string
     */
    private function touchUpdatedAt($updatedAtAttr, $dateTimeString)
    {
        $cur = isset($this->Record->$updatedAtAttr)
            ? $this->Record->$updatedAtAttr : null;
        if (!$cur || $cur instanceof Carbon) {
            return new Carbon($dateTimeString);
        }
        return $dateTimeString;
    }

    /**
     * @param array $defAttributes
     * @param array $volume
     * @return Record
     */
    private function mapSaved(array $defAttributes, array $volume)
    {
        $Getters = Factory::getGetters();
        $createdAtAttr = $this->Model->getCreatedAtAttr();
        $updatedAtAttr = $this->Model->getUpdatedAtAttr();
        foreach ($volume as $k => $v) {
            if ($k === $createdAtAttr) {
                $getter = $Getters->getCreatedAt();
            } elseif ($k === $updatedAtAttr) {
                $getter = $Getters->getUpdatedAt();
            } else {
                $getter = $Getters->perform($defAttributes[$k]);
            }
            $this->Record->$k = $getter($k, function ($prop) use ($volume) {
                return $volume[$prop];
            });
        }
        return $this->Record;
    }

}
