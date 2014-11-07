# Installation

## Warning

Do not use this package in production because it still in heavy development phase.  

## Requirements

* PHP 5.4+
* Laravel 4.2+

## Via composer

Add `mightydes\enjoin` as a requirement to composer.json:
```
{
    "require": {
        "mightydes\enjoin": "0.*"
    }
}
```

All models files should be in `Models` namespace, so create folder `app/models`,
and add to composer.json `autoload` section:
```
"psr-4": {
    "Models\\": "app/models"
}
```

Update your packages with `composer update` or install with `composer install`.

Once Composer has installed or updated your packages you need to register Enjoin with Laravel itself.
Open up `app/config/app.php` and find the providers key towards the bottom and add:
```
'Enjoin\EnjoinServiceProvider'
```

Then add Enjoin Facade for easier access:
```
'Enjoin' => 'Enjoin\EnjoinFacade'
```

Now you can access Enjoin in global namespace or in other namespace by adding:
```
use Enjoin;
```
