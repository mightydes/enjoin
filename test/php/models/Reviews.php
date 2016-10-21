<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Reviews extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'books_id' => ['type' => Enjoin::Integer()],
            'resource' => ['type' => Enjoin::String()],
            'content' => ['type' => Enjoin::String()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Books'), ['foreignKey' => 'books_id']),
        ];
    }

}
