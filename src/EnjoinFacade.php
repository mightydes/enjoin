<?php

namespace Enjoin;

use Illuminate\Support\Facades\Facade;

class EnjoinFacade extends Facade
{

    const SQL = Enjoin::SQL;
    const WITH_CACHE = Enjoin::WITH_CACHE;
    const NO_CACHE = Enjoin::NO_CACHE;

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'enjoin';
    }

}
