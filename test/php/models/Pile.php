<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Pile extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'on_state' => ['type' => Enjoin::Boolean()],
            'date_till' => ['type' => Enjoin::Date()],
            'name' => ['type' => Enjoin::String()]
        ];
    }

}
