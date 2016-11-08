<?php

namespace Enjoin\Model;

class EmptyCache
{

    private $value = null;

    /**
     * EmptyCache constructor.
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

}
