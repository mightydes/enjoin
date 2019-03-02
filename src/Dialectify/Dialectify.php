<?php

namespace Enjoin\Dialectify;

use \Enjoin\Model\Model;

class Dialectify
{

    const DATE_FORMAT_NOMS_NOTZ = 'Y-m-d H:i:s';
    const DATE_FORMAT_NOMS_TZ = 'Y-m-d H:i:s P';
    const DATE_FORMAT_MS_TZ = 'Y-m-d H:i:s.uO';
    const DATE_FORMAT_MS = 'Y-m-d H:i:s.u';

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
        return self::DATE_FORMAT_NOMS_NOTZ;
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

    /**
     * @return array
     */
    public function getWhereControls()
    {
        return [
            'and' => 'AND',
            'or' => 'OR',
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
            'ne' => '!=',
            'in' => 'IN',
            'notIn' => 'NOT IN',
            'like' => 'LIKE',
            'notLike' => 'NOT LIKE',
            'between' => 'BETWEEN',
            'notBetween' => 'NOT BETWEEN'
        ];
    }

}
