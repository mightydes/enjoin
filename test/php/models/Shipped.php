<?php

namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Shipped extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'publishers_books_id' => ['type' => Enjoin::Integer()],
            'destination' => ['type' => Enjoin::Text()],
            'quantity' => ['type' => Enjoin::Integer()],
            'sent_at' => ['type' => Enjoin::Date()]
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('PublishersBooks'), ['foreignKey' => 'publishers_books_id'])
        ];
    }

}
