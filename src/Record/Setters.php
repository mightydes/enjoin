<?php

namespace Enjoin\Record;

use Carbon\Carbon;
use Enjoin\Extras;
use Enjoin\Factory;
use Enjoin\Exceptions\Error;
use Enjoin\Model\Model;

class Setters
{

    /**
     * @param Model $Model
     * @param array $record
     * @param array $descAttr
     * @param string $attr
     * @return string
     * @throws \Enjoin\Exceptions\ModelException
     */
    public function perform(Model $Model, array $record, array $descAttr, $attr)
    {
        $type = $descAttr['type']['key'];

        # Handle user defined setter:
        if (isset($descAttr['set']) && is_callable($descAttr['set'])) {
            $getValue = function ($attr) use ($record) {
                return $record[$attr];
            };
            return $descAttr['set']($attr, $getValue);
        }

        switch ($type) {
            case Extras::DATE_TYPE:
                return $this->getDate($Model, $record[$attr]);
            case Extras::BOOL_TYPE:
                return intval($record[$attr]) > 0 ? 1 : null;
            case Extras::STR_TYPE:
            case Extras::TEXT_TYPE:
                $v = $record[$attr];
                is_null($v) ?: $v = strval($v);
                if (isset($descAttr['allowNull']) && !$descAttr['allowNull']) {
                    $v = strval($v);
                }
                return $v;
            case Extras::INT_TYPE:
                $v = $record[$attr];
                is_null($v) ?: $v = intval($v);
                if (isset($descAttr['allowNull']) && !$descAttr['allowNull']) {
                    $v = intval($v);
                }
                return $v;
            case Extras::FLOAT_TYPE:
                $v = $record[$attr];
                is_null($v) ?: $v = floatval($v);
                if (isset($descAttr['allowNull']) && !$descAttr['allowNull']) {
                    $v = floatval($v);
                }
                return $v;
        }
        return $record[$attr];
    }

    /**
     * @param array $validate [ [attr, value, rules], ... ]
     * @throws \Enjoin\Exceptions\ValidationException
     */
    public function validate(array $validate)
    {
        $data = [];
        $rules = [];
        foreach ($validate as $it) {
            $data[$it[0]] = $it[1];
            $rules[$it[0]] = $it[2];
        }
        $validator = Factory::getValidator()
            ->make($data, $rules);
        if ($validator->fails()) {
            $out = [];
            foreach ($validator->messages()->toArray() as $attr => $list) {
                $out [] = join(' ', $list);
            }
            Error::dropValidationException(join("\n", $out));
        }
    }

    /**
     * @param Model $Model
     * @param mixed $value
     * @return mixed
     * @throws \Enjoin\Exceptions\ModelException
     */
    public function getCreatedAt(Model $Model, $value = null)
    {
        if ($value) {
            return $value instanceof Carbon
                ? $value->format($Model->dialectify()->getDateFormat())
                : $value;
        }
        return Carbon::now()->format($Model->dialectify()->getDateFormat());
    }

    /**
     * @param Model $Model
     * @param mixed $value
     * @return mixed
     * @throws \Enjoin\Exceptions\ModelException
     */
    public function getUpdatedAt(Model $Model, $value = null)
    {
        return $this->getCreatedAt($Model, $value);
    }

    /**
     * Handle date/datetime.
     * @param Model $Model
     * @param mixed $value
     * @return mixed
     * @throws \Enjoin\Exceptions\ModelException
     */
    private function getDate(Model $Model, $value)
    {
        if ($value instanceof Carbon) {
            return $value->format($Model->dialectify()->getDateFormat());
        }
        return $value;
    }

}
