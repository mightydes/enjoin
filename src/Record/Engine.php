<?php

namespace Enjoin\Record;

use Enjoin\Enjoin;
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

    /**
     * @param array|null $params
     * @param int $flags
     * @return Record
     */
    public function save(array $params = null, $flags = 0)
    {
        if ($this->Model->isTimestamps()) {
            $createdAtField = $this->Model->getCreatedAtField();
            if ($this->type === self::NON_PERSISTENT ||
                $this->type === self::PERSISTENT && !isset($this->Record->$createdAtField)
            ) {
                $this->Record->$createdAtField = Carbon::now();
            }

            $updatedAtField = $this->Model->getUpdatedAtField();
            $this->Record->$updatedAtField = Carbon::now();
        }

        $defAttributes = $this->Model->Definition->getAttributes();
        $pick = isset($params['fields']) ? $params['fields'] : null;
        $volume = [];
        $validate = [];
        $record = $this->Record->__toArray();
        $Setters = Factory::getSetters();
        foreach ($this->Record as $field => $recordVal) {
            if ($recordVal instanceof Record) {
                $recordVal->save();
            } elseif (array_key_exists($field, $defAttributes)) {
                $saveVal = $Setters->perform($record, $defAttributes[$field], $field);
                if (isset($defAttributes[$field]['validate'])) {
                    $validate [] = [$field, $saveVal, $defAttributes[$field]['validate']];
                }
                if (!$pick || in_array($field, $pick)) {
                    $volume[$field] = $saveVal;
                }
            } elseif ($this->Model->isTimestamps() && $field === $createdAtField) {
                if (!$pick || in_array($field, $pick)) {
                    $volume[$field] = $Setters->getCreatedAt($recordVal);
                }
            } elseif ($this->Model->isTimestamps() && $field === $updatedAtField) {
                $volume[$field] = $Setters->getUpdatedAt($recordVal);
            }
        }

        !$validate ?: $Setters->validate($validate);

        if (!($flags & self::SOFT_SAVE)) {
            $id = $this->saveEntry($volume);
            $this->id = $id;
            $this->Record->id = $id;
            $volume['id'] = $id;
        }

        return $this->Record;
    }

    /**
     * @return bool
     */
    public function destroy()
    {
        $this->Model->queryBuilder()->where('id', $this->id)->take(1)->delete();
        $this->Model->CacheJar->flush();
        return true;
    }

    /**
     * @param array $volume
     * @return int|mixed
     */
    private function saveEntry(array $volume)
    {
        if ($this->type === self::NON_PERSISTENT) {
            $this->type = self::PERSISTENT;
            return $this->Model->queryBuilder()->insertGetId($volume);
        }

        if (isset($volume['id']) && $volume['id'] === $this->id) {
            unset($volume['id']);
        }
        $this->Model->CacheJar->flush();
        $this->Model->queryBuilder()
            ->where('id', $this->id)// use constructed id
            ->take(1)
            ->update($volume); // id can be changed
        return isset($volume['id']) ? $volume['id'] : $this->id;
    }

}
