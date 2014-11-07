<?php

namespace Enjoin;

use Illuminate\Support\Facades\Facade;

class EnjoinFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'enjoin';
    }

} // end of class
