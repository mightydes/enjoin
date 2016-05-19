<?php

namespace Enjoin;

use Exception;

class Groups
{

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    private $DB;

    /**
     * @var Model
     */
    private $Invoker;

    /**
     * @param $DB
     * @param $Invoker
     */
    public function __construct($DB, $Invoker)
    {
        $this->DB = $DB;
        $this->Invoker = $Invoker;
    }

    /**
     * Input examples:
     *      - string `type`
     *      - [
     *          'type',
     *          [ Enjoin::get('Foo'), 'bar' ]
     *        ]
     *
     * @param mixed $options
     * @return \Illuminate\Support\Facades\DB
     */
    public function handle($options)
    {
        if (is_string($options)) {
            # Simplest notation (ie `'group' => 'type'`)
            $this->applyString($options);
        } elseif (is_array($options)) {
            # List of instructions
            foreach ($options as $v) {
                if (is_string($v)) {
                    $this->applyString($v);
                } elseif (is_array($v)) {
                    # Eager order
                    $this->applyEager($v);
                }
            }
        }

        return $this->DB;
    }

    /**
     * @param array $path
     * @throws \Exception
     */
    private function applyEager(array $path)
    {
        $attr = array_pop($path);
        $itemA = null;
        $prefix = [];
        foreach ($path as $v) {
            if ($v instanceof Model) {
                # Ie `[ Enjoin::get('Foo'), ... ]`
                $itemB = Handler::performItem($v);
            } elseif (is_array($v)) {
                # Ie `[ ['model' => Enjoin::get('Foo'), 'as' => 'foo'], ... ]`
                $itemB = Handler::performItem($v['model'], Extras::omit($v, ['model']));
            } else {
                throw new Exception('Unknown eager order notation: ' . var_export($v, true));
            }
            if (is_null($itemA)) {
                # First iteration
                $itemA = Handler::performItem($this->Invoker);
            }
            $prefix [] = Handler::getRelation($itemA, $itemB)['as'];

            $itemA = $itemB;
        }
        $prefix = implode(Extras::$GLUE_CHAR, $prefix);
        if (is_string($attr)) {
            $this->applyString($attr, $prefix);
        }
    }

    /**
     * For example: 'group' => 'type'.
     * @param $input
     * @param string $prefix
     */
    private function applyString($input, $prefix = '')
    {
        if ($prefix) {
            $input = $prefix . '.' . $input;
        }
        $this->DB = call_user_func([$this->DB, 'groupBy'], $input);
    }

} // end of class
