<?php

namespace Enjoin\Model;

abstract class Definition
{

    /**
     * @var null|string
     */
    public $connection;

    /**
     * @var null|string
     */
    public $table;

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var null|string
     */
    public $createdAt;

    /**
     * @var null|string
     */
    public $updatedAt;

    /**
     * @var bool
     */
    public $cache = false;

    public $expanseModel = null;

    public $expanseRecord = null;

    /**
     * @todo: Rename to `getFields`.
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

}
