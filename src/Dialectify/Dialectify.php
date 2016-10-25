<?php

namespace Enjoin\Dialectify;

use \Enjoin\Model\Model;

class Dialectify
{

    protected $Model;

    /**
     * Dialectify constructor.
     * @param Model $Model
     */
    public function __construct(Model $Model)
    {
        $this->Model = $Model;
    }

    /**
     * @return null|string
     */
    public function getIdSequence()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getInsertEmptyQuery()
    {
        $table = $this->Model->getTableName();
        return "INSERT INTO `$table` (`id`) VALUES (DEFAULT)";
    }

    /**
     * @return string
     */
    public function getEscapeChar()
    {
        return '`';
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function getLimitStatement($limit, $offset)
    {
        if ($offset === 0 && $limit === 1) {
            return 'LIMIT 1';
        }
        return "LIMIT $offset, $limit";
    }

}
