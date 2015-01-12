<?php

namespace Enjoin;

use Exception, Validator;

class PersistentRecord
{

    /**
     * @param Record $Record
     * @param array $attributes
     * @return bool
     * @throws \Exception
     */
    public static function save(Record $Record, array $attributes = [])
    {
        /**
         * @var Model $Model
         */
        $Model = $Record->_getInternal('model');

        # Collect values
        $values = Extras::omit(get_object_vars($Record), Extras::$RECORD_OMIT);
        if (!$values) {
            throw new Exception('Expected non-empty attributes list');
        }
        if (!$attributes) {
            $attributes = array_keys($values);
            if (($id_idx = array_search('id', $attributes)) !== false) {
                unset($attributes[$id_idx]);
            }
        }

        $update = [];
        $skip = [];

        # Perform timestamps
        if ($Model->isTimestamps()) {
            ## Created at
            $created_at = $Model->getCreatedAtAttr();
            if (array_key_exists($created_at, $values)) {
                $update[$created_at] = Setters::getCreatedAt($values[$created_at]);
            }
            $skip [] = $created_at;
            ## Updated at
            $updated_at = $Model->getUpdatedAtAttr();
            $update[$updated_at] = Setters::getUpdatedAt();
            $skip [] = $updated_at;
        }

        # Perform setters
        $contextAttrs = $Model->Context->getAttributes();
        foreach ($attributes as $attr) {
            if (!array_key_exists($attr, $values) || in_array($attr, $skip)) {
                continue;
            }
            $update[$attr] = Setters::perform($attr, $contextAttrs[$attr], $values);
            # Perform validation
            if (array_key_exists('validate', $contextAttrs[$attr])) {
                self::validate($attr, $update[$attr], $contextAttrs[$attr]['validate']);
            }
        }

        # Update entry
        $Model->flushCache();
        $Model->connect()->where('id', $Record->_getInternal('id'))->take(1)->update($update);

        return true;
    }

    /**
     * @param $attr
     * @param $value
     * @param $rules
     * @throws \Exception
     */
    public static function validate($attr, $value, $rules)
    {
        $validator = Validator::make([$attr => $value], [$attr => $rules]);
        if ($validator->fails()) {
            $messages = [];
            foreach ($validator->messages()->get($attr) as $msg) {
                $messages [] = $msg;
            }
            throw new Exception(implode("\n", $messages));
        }
    }

    /**
     * @param Record $Record
     * @return bool
     */
    public static function destroy(Record $Record)
    {
        /**
         * @var Model $Model
         */
        $Model = $Record->_getInternal('model');

        $Model->flushCache();
        $Model->connect()->where('id', $Record->_getInternal('id'))->take(1)->delete();
        $Record->_setInternal('type', null);
        return true;
    }

} // end of class
