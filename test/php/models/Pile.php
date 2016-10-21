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
            'date_till' => ['type' => Enjoin::Date()]
        ];
    }

}
