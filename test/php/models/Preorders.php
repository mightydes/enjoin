<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Preorders extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'publishers_books_id' => ['type' => Enjoin::Integer()],
            'person' => ['type' => Enjoin::String()],
            'quantity' => ['type' => Enjoin::Integer()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('PublishersBooks'), ['foreignKey' => 'publishers_books_id'])
        ];
    }

}
