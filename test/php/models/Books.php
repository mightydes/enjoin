<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'authors_id' => ['type' => Enjoin::Integer()],
            'title' => ['type' => Enjoin::String()],
            'year' => ['type' => Enjoin::Integer(), 'validate' => 'integer|max:2020']
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Authors'), ['foreignKey' => 'authors_id']),
            Enjoin::hasMany(Enjoin::get('Reviews'), ['foreignKey' => 'books_id']),
            Enjoin::hasMany(Enjoin::get('PublishersBooks'), ['foreignKey' => 'books_id'])
        ];
    }

}
