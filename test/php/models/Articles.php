<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Articles extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'authors_id' => ['type' => Enjoin::Integer()],
            'year' => ['type' => Enjoin::Integer()],
            'title' => ['type' => Enjoin::String()],
            'content' => ['type' => Enjoin::Text()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Authors'), ['foreignKey' => 'authors_id'])
        ];
    }

}
