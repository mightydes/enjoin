<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class PublishersBooks extends Definition
{

    public $timestamps = false;

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'publishers_id' => ['type' => Enjoin::Integer()],
            'books_id' => ['type' => Enjoin::Integer()],
            'year' => ['type' => Enjoin::Integer()],
            'pressrun' => ['type' => Enjoin::Integer()],
            'mistakes' => ['type' => Enjoin::Text()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Books'), ['foreignKey' => 'books_id']),
            Enjoin::belongsTo(Enjoin::get('Publishers'), ['foreignKey' => 'publishers_id']),
            Enjoin::hasMany(Enjoin::get('Shipped'), ['foreignKey' => 'publishers_books_id']),
            Enjoin::hasMany(Enjoin::get('Preorders'), ['foreignKey' => 'publishers_books_id'])
        ];
    }

}
