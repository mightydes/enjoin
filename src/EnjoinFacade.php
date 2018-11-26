<?php

namespace Enjoin;

use Illuminate\Support\Facades\Facade;

class EnjoinFacade extends Facade
{

    const SQL = Enjoin::SQL;
    const CACHE = Enjoin::CACHE;
    const NO_CACHE = Enjoin::NO_CACHE;
    const UNBUFFERED_QUERY = Enjoin::UNBUFFERED_QUERY;

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'enjoin';
    }

}
