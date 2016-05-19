<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Authors extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'name' => ['type' => Enjoin::String()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::hasMany(Enjoin::get('Books'), ['foreignKey' => 'authors_id'])
        ];
    }

}
