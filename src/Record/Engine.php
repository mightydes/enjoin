<?php

namespace Enjoin\Record;

use Enjoin\Enjoin;
use Enjoin\Exceptions\Error;
use Enjoin\Factory;
use Enjoin\Extras;
use Carbon\Carbon;

class Engine
{

    const PERSISTENT = 'PERSISTENT';
    const NON_PERSISTENT = 'NON_PERSISTENT';
    const DESTROYED = 'DESTROYED';

    # BITWISE:
    const SOFT_SAVE = 1;

    /**
     * @param \Enjoin\Record\Record $Record
     * @param array|null $params
     * @param int $flags
     * @return \Enjoin\Record\Record
     */
    public static function save(Record $Record, array $params = null, $flags = 0)
    {
        $scope = $Record->scope();
        $Model = Enjoin::get($scope->modelName);

        if ($Model->isTimestamps()) {
            $createdAtField = $Model->getCreatedAtField();
            if ($scope->type === self::NON_PERSISTENT ||
                $scope->type === self::PERSISTENT && !isset($Record->$createdAtField)
            ) {
                $Record->$createdAtField = Carbon::now();
            }

            $updatedAtField = $Model->getUpdatedAtField();
            $Record->$updatedAtField = Carbon::now();
        }

        $defAttributes = $Model->getDefinition()->getAttributes();
        $pick = isset($params['fields']) ? $params['fields'] : null;
        $volume = [];
        $validate = [];
        $record = $Record->__toArray();
        $Setters = Factory::getSetters();
        foreach ($Record as $field => $recordVal) {
            if ($recordVal instanceof Record) {
                # We can start recursive saving here...
                #$recordVal->save();
            } elseif (array_key_exists($field, $defAttributes)) {
                $saveVal = $Setters->perform($Model, $record, $defAttributes[$field], $field);
                if (isset($defAttributes[$field]['validate'])) {
                    $validate [] = [$field, $saveVal, $defAttributes[$field]['validate']];
                }
                if (!$pick || in_array($field, $pick)) {
                    $volume[$field] = $saveVal;
                }
            } elseif ($Model->isTimestamps() && $field === $createdAtField) {
                if (!$pick || in_array($field, $pick)) {
                    $volume[$field] = $Setters->getCreatedAt($Model, $recordVal);
                }
            } elseif ($Model->isTimestamps() && $field === $updatedAtField) {
                $volume[$field] = $Setters->getUpdatedAt($Model, $recordVal);
            }
        }

        !$validate ?: $Setters->validate($validate);

        if (!($flags & self::SOFT_SAVE)) {
//            $id = $this->saveEntry($volume);
            $id = $scope->type === self::NON_PERSISTENT
                ? static::saveNonPersistent($Record, $volume)
                : static::savePersistent($Record, $volume);
            $scope->id = $id;
            $Record->id = $id;
        }

        return $Record;
    }

    /**
     * @param \Enjoin\Record\Record $Record
     * @param array $collection
     * @param array|null $params
     * @return \Enjoin\Record\Record
     */
    public static function update(Record $Record, array $collection, array $params = null)
    {
        if (isset($params['fields'])) {
            $collection = Extras::pick($collection, $params['fields']);
        }
        foreach ($collection as $field => $value) {
            $Record->$field = $value;
        }
        $flags = $Record->scope()->type === self::NON_PERSISTENT ? self::SOFT_SAVE : 0;
        return static::save($Record, $params, $flags);
    }

    /**
     * @param \Enjoin\Record\Record $Record
     * @return bool
     */
    public static function destroy(Record $Record)
    {
        $scope = $Record->scope();
        $Model = Enjoin::get($scope->modelName);
        $Model->queryBuilder()->where('id', $scope->id)->take(1)->delete();
        $Model->cache()->flush();
        foreach ($Record as $prop => $v) {
            unset($Record->$prop);
        }
        $scope->type = self::DESTROYED;
        return true;
    }

    /**
     * @param \Enjoin\Record\Record $Record
     * @return array
     */
    public static function toArray(Record $Record)
    {
        $out = [];
        foreach ($Record as $prop => $value) {
//            if ($value instanceof Engine) {
//                continue;
//            }
            if ($value instanceof Record) {
                $out[$prop] = $value->__toArray();
            } elseif (is_array($value)) {
                $out[$prop] = [];
                foreach ($value as $k => $v) {
                    if ($v instanceof Record) {
                        $out[$prop][$k] = $v->__toArray();
                    } else {
                        $out[$prop][$k] = $v;
                    }
                }
            } else {
                $out[$prop] = $value;
            }
        }
        return $out;
    }

    /**
     * @param \Enjoin\Record\Record $Record
     * @param array $volume
     * @return mixed
     */
    private static function saveNonPersistent(Record $Record, array $volume)
    {
        $scope = $Record->scope();
        $Model = Enjoin::get($scope->modelName);
        $DB = $Model->connection();
        return $DB->transaction(function () use ($volume, $scope, $Model, $DB) {
            $scope->type = self::PERSISTENT;
            if ($volume) {
                $Model->queryBuilder()->insert($volume)
                    ?: Error::dropRecordException('Unable to insert record!');
            } else {
                $DB->insert($Model->dialectify()->getInsertEmptyQuery())
                    ?: Error::dropRecordException('Unable to insert empty record!');
            }
            $Model->cache()->flush();
            $id = $DB->getPdo()->lastInsertId($Model->dialectify()->getIdSequence());
            return (int)$id;
        });
    }

    /**
     * @param \Enjoin\Record\Record $Record
     * @param array $volume
     * @return mixed
     */
    private static function savePersistent(Record $Record, array $volume)
    {
        $scope = $Record->scope();
        $Model = Enjoin::get($scope->modelName);
        $DB = $Model->connection();
        return $DB->transaction(function () use ($volume, $scope, $Model) {
            if (isset($volume['id']) && $volume['id'] === $scope->id) {
                unset($volume['id']);
            }
            $Model->queryBuilder()
                ->where('id', $scope->id)// use constructed id
                ->take(1)
                ->update($volume);       // id can be changed
            $Model->cache()->flush();
            return isset($volume['id']) ? $volume['id'] : $scope->id;
        });
    }

}
