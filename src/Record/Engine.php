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

        $defAttributes = $this->Model->getDefinition()->getAttributes();
        $pick = isset($params['fields']) ? $params['fields'] : null;
        $volume = [];
        $validate = [];
        $record = $this->Record->__toArray();
        $Setters = Factory::getSetters();
        foreach ($this->Record as $field => $recordVal) {
            if ($recordVal instanceof Record) {
                # We can start recursive saving here...
                #$recordVal->save();
            } elseif (array_key_exists($field, $defAttributes)) {
                $saveVal = $Setters->perform($this->Model, $record, $defAttributes[$field], $field);
                if (isset($defAttributes[$field]['validate'])) {
                    $validate [] = [$field, $saveVal, $defAttributes[$field]['validate']];
                }
                if (!$pick || in_array($field, $pick)) {
                    $volume[$field] = $saveVal;
                }
            } elseif ($this->Model->isTimestamps() && $field === $createdAtField) {
                if (!$pick || in_array($field, $pick)) {
                    $volume[$field] = $Setters->getCreatedAt($this->Model, $recordVal);
                }
            } elseif ($this->Model->isTimestamps() && $field === $updatedAtField) {
                $volume[$field] = $Setters->getUpdatedAt($this->Model, $recordVal);
            }
        }

        !$validate ?: $Setters->validate($validate);

        if (!($flags & self::SOFT_SAVE)) {
//            $id = $this->saveEntry($volume);
            $id = $this->type === self::NON_PERSISTENT
                ? $this->saveNonPersistent($volume)
                : $this->savePersistent($volume);
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
        $this->Model->cache()->flush();
        return true;
    }

    /**
     * @param array $volume
     * @return mixed
     */
    private function saveNonPersistent(array $volume)
    {
        $DB = $this->Model->connection();
        return $DB->transaction(function () use ($volume, $DB) {
            $this->type = self::PERSISTENT;
            if ($volume) {
                $this->Model->queryBuilder()->insert($volume)
                    ?: Error::dropRecordException('Unable to insert record!');
            } else {
                $DB->insert($this->Model->dialectify()->getInsertEmptyQuery())
                    ?: Error::dropRecordException('Unable to insert empty record!');
            }
            $this->Model->cache()->flush();
            $id = $DB->getPdo()->lastInsertId($this->Model->dialectify()->getIdSequence());
            return (int)$id;
        });
    }

    /**
     * @param array $volume
     * @return mixed
     */
    private function savePersistent(array $volume)
    {
        $DB = $this->Model->connection();
        return $DB->transaction(function () use ($volume) {
            if (isset($volume['id']) && $volume['id'] === $this->id) {
                unset($volume['id']);
            }
            $this->Model->queryBuilder()
                ->where('id', $this->id)// use constructed id
                ->take(1)
                ->update($volume); // id can be changed
            $this->Model->cache()->flush();
            return isset($volume['id']) ? $volume['id'] : $this->id;
        });
    }

}
