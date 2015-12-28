<?php

namespace Enjoin;

abstract class BaseModel
{

    public $connection;

    public $table;

    public $timestamps = true;

    public $createdAt;

    public $updatedAt;

    public $cache = false;

    public $expanseModel = null;

    /**
     * @return array
     */
    public function getAttributes()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return [];
    }

} // end of class
