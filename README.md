Enjoin ORM
==========

## Introduction

Enjoin is another PHP ORM (Object Relational Mapping) designed for [Laravel 4](http://laravel.com/) framework
and inspired by [Sequelize.js](http://sequelizejs.com/) ORM.
Enjoin uses active-record pattern.

Laravel has built in ORM, called [Eloquent](http://laravel.com/docs/4.2/eloquent).
Unfortunately, there are some major disadvantages in Eloquent:

* Unable to construct associations via `join` clause
(see [Unable to Get Eloquent to Automatically Create Joins](http://stackoverflow.com/questions/11099570/unable-to-get-eloquent-to-automatically-create-joins)).
* Unable to built in validation for model.
* Unable to order by eager loaded records. 

Enjoin relies on Laravel components, such as `Database` and `Cache`.

## Documentation

* [Installation](#installation)
* [Models](#models)
  * [Definition](#definition)
  * [Data types](#data-types)

## Installation

### Warning

Do not use this package in production because it still in heavy development phase.  

### Requirements

* PHP 5.4+
* Laravel 4.2+

### Via composer

Add `mightydes\enjoin` as a requirement to composer.json:
```json
{
    "require": {
        "mightydes/enjoin": "dev-master@dev"
    }
}
```

All models files should be in `Models` namespace, so create folder `app/models`,
and add to composer.json `autoload` section:
```json
"psr-4": {
    "Models\\": "app/models"
}
```

Update your packages with `composer update` or install with `composer install`.

Once Composer has installed or updated your packages you need to register Enjoin with Laravel itself.
Open up `app/config/app.php` and find the providers key towards the bottom and add:
```php
'Enjoin\EnjoinServiceProvider'
```

Then add Enjoin Facade for easier access:
```php
'Enjoin' => 'Enjoin\EnjoinFacade'
```

Now you can access Enjoin in global namespace or in other namespace by adding:
```php
use Enjoin;
```

## Models

### Definition

First of all, create `BaseModel.php` in `app/models` directory:

```php
<?php
// app/models/BaseModel.php

namespace Models;

abstract class BaseModel
{

    // Connection name from `app/config/database` section.
    // You can use `default` string.
    public $connection;

    // Related table name.
    // You may pass this variable, and engine fills it automatically.
    public $table;

    public $timestamps = true;

    // Name for `createdAt` field (`created_at` by default).
    public $createdAt;

    // Name for `updatedAt` field (`updated_at` by default).
    public $updatedAt;

    public $cache = false;

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
```

Each Enjoin model should be placed in `app/models` folder and extends `BaseModel` class.

```php
<?php
// app/models/Project.php

namespace Models;

use Enjoin;

class Project extends BaseModel
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'title' => ['type' => Enjoin::String()],
            'description' => ['type' => Enjoin::Text()]
         ];
    }

} // end of class
```

You can place model file in sub-folder, in this case you can access model like this:

```php
// app/models/alpha/Users.php
Enjoin::get('alpha.Users');
```

### Data types

Enjoin currently supports the following data types:

```php
Enjoin.Integer();   // --> INTEGER
Enjoin.Boolean();   // --> TINYINT(1) (1 or null)
Enjoin.String();    // --> VARCHAR
Enjoin.Text();      // --> TEXT
Enjoin.Float();     // --> FLOAT
Enjoin.Date();      // -->DATETIME
Enjoin.Enum();      // --> ENUM
```
