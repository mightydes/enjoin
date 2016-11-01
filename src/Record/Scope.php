<?php

namespace Enjoin\Record;

use Enjoin\Enjoin;

class Scope
{

    public $modelName;
    public $type;
    public $id = null;

    /**
     * Scope constructor.
     * @param string $modelName
     * @param string $type
     * @param null|int $id
     */
    public function __construct($modelName, $type, $id = null)
    {
        $this->modelName = $modelName;
        $this->type = $type;
        $this->id = $id;
    }

}
