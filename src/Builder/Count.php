<?php

namespace Enjoin\Builder;

class Count extends Find
{

    /**
     * @return string
     */
    protected function resolveSelect()
    {
        return "count(*) AS `count`";
    }

}
