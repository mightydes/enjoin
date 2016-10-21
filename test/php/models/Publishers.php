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
            'pid' => ['type' => Enjoin::Integer()],
            'name' => ['type' => Enjoin::String()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::hasMany(Enjoin::get('PublishersBooks'), ['foreignKey' => 'publishers_id']),
            Enjoin::belongsTo(Enjoin::get('Publishers'), ['foreignKey' => 'pid', 'as' => 'parent']),
            Enjoin::hasMany(Enjoin::get('Publishers'), ['foreignKey' => 'pid', 'as' => 'child'])
        ];
    }

}
