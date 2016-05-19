<?php

namespace Enjoin\Record;

use Enjoin\Exceptions\Error;
use Enjoin\Model\Model;
use Enjoin\Factory;
use Carbon\Carbon;

class Engine
{

    const PERSISTENT = 'PERSISTENT';
    const NON_PERSISTENT = 'NON_PERSISTENT';

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

    public function save(array $attributes = null)
    {
        $attributes ?: $attributes = [];
        if ($attributes && $this->type === self::PERSISTENT && !in_array('id', $attributes)) {
            $attributes [] = 'id';
        }
        $defAttributes = $this->Model->Definition->getAttributes();

        $list = $this->prepSaveList($attributes);
        if ($this->type === self::PERSISTENT && !isset($list['id'])) {
            Error::dropRecordException("Missed mandatory 'id' property!");
        }
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

        # Save entry:
//        $this->Model->CC->flush();
        if ($this->type === self::NON_PERSISTENT) {
            $id = $this->Model->queryBuilder()->insertGetId($volume);
            $this->type = self::PERSISTENT;
        } else {
            $this->Model->queryBuilder()
                ->where('id', $this->id)// use constructed id
                ->take(1)
                ->update($volume); // id can be changed
            $id = isset($volume['id']) ? $volume['id'] : $this->Record->id;
        }
        $this->id = $id;
        $this->Record->id = $id;
        return $this->mapSaved($defAttributes, $volume);
    }

    /**
     * @param array $attributes
     * @return array
     */
    private function prepSaveList(array $attributes)
    {
        $list = [];
        foreach ($this->Record as $key => $val) {
            if ($attributes && !in_array($key, $attributes)) {
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
        $volume[$createdAtAttr] = $this->type === self::NON_PERSISTENT
            ? Carbon::now()->toDateTimeString()
            : $Setters->getCreatedAt(isset($list[$createdAtAttr]) ? $list[$createdAtAttr] : null);
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
