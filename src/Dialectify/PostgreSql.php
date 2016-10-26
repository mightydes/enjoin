<?php

namespace Enjoin\Dialectify;

class PostgreSql extends Dialectify
{

    /**
     * @return null|string
     */
    public function getIdSequence()
    {
        return $this->Model->getTableName() . '_id_seq';
    }

    /**
     * @return string
     */
    public function getInsertEmptyQuery()
    {
        $table = $this->Model->getTableName();
        return "INSERT INTO \"$table\" (\"id\") VALUES (DEFAULT) RETURNING *";
    }

    /**
     * @return string
     */
    public function getEscapeChar()
    {
        return '"';
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s P';
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
        return "LIMIT $limit OFFSET $offset";
    }

    /**
     * @return array
     */
    public function getWhereControls()
    {
        $out = parent::getWhereControls();
        $out['iLike'] = 'ILIKE';
        return $out;
    }

}
