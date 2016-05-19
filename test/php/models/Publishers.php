<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Publishers extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'name' => ['type' => Enjoin::String()]
        ];
    }

}
