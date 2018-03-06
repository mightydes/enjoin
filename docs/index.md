## Getting Started


### Installation

Enjoin is available via Composer:

```sh
$ composer require mightydes/enjoin
```

If you need language locales, then require [laravel-lang](https://github.com/caouecs/laravel-lang) package:

```sh
$ composer require caouecs/laravel-lang
```

Then add `Models` autoload entry to your `composer.json` file:

```json
  "autoload": {
    "psr-4": {
      "Models\\": [
        "app/Models"
      ]
    }
  }
```


### Stand-alone bootstrap

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Enjoin\Factory;

Factory::bootstrap([
    'database' => [
        'default' => 'acme',
        'connections' => [
            'acme' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'acme',
                'username' => 'acme',
                'password' => 'acme',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix' => ''
            ]
        ],
        'redis' => [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 0,
            ]
        ]
    ],
    'enjoin' => [
        'lang_dir' => 'vendor/caouecs/laravel-lang'
    ],
    'cache' => [
        'default' => 'redis',
        'stores' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default'
            ]
        ],
        'prefix' => 'enjoin_'
    ]
]);
```


### Laravel bootstrap

First add `Enjoin\EnjoinServiceProvider` to `providers` list,
and `'Enjoin' => 'Enjoin\EnjoinFacade'` to `aliases` list in `config/app.php`.

Then register config in `app/Providers/ConfigServiceProvider.php`:

```php
<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{

    public function register()
    {
        config([
            'enjoin' => [
                'lang_dir' => 'vendor/caouecs/laravel-lang'
            ]
        ]);
    }

}
```


### Model Definition

You need to define mapping between tables and models.
For example, table `books` with columns:

* id (int, primary, auto-increment)
* authors_id (int, foreign-key)
* title (varchar)
* year (int)

Has definition:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public function getAttributes()
    {
        return [
            'id' => ['type' => Enjoin::Integer()],
            'authors_id' => ['type' => Enjoin::Integer()],
            'title' => ['type' => Enjoin::String()],
            'year' => ['type' => Enjoin::Integer(), 'validate' => 'integer|max:2020']
        ];
    }

    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Authors'), ['foreignKey' => 'authors_id'])
        ];
    }

}
```


#### connection

Connection to use. Equals to `default` connection by default.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $connection = 'acme';

}
```


#### table

Table name. Equals to `Inflector::tableize(<ModelClassName>)` by default.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $table = 'books';

}
```


#### timestamps

Enables or disables timestamps (ie `created_at`, `updated_at`). Equals to `true` by default.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $timestamps = false;

}
```


#### createdAt

CreatedAt column name. Equals to `created_at` by default.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $createdAt = 'createdAt';

}
```


#### updatedAt

UpdatedAt column name. Equals to `updated_at` by default.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $updatedAt = 'updatedAt';

}
```


#### cache

Enables or disables cache. Equals to `false` by default.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $cache = true;

}
```


#### expanseModel

Class name to extend generic model class.
It is useful, if you want to define custom model methods.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $expanseModel = Expanse\BooksModel::class;

}
```


#### expanseRecord

Class name to extend generic record class.
It is useful, if you want to define custom record methods.
Example:

```php
<?php namespace Models;

use Enjoin\Model\Definition;
use Enjoin\Enjoin;

class Books extends Definition
{

    public $expanseRecord = Expanse\BooksRecord::class;

}
```


#### getAttributes()

Returns associative array, where each key is a table column.


#### getAttributes().type

* Enjoin::Integer()
* Enjoin::Boolean()
* Enjoin::String()
* Enjoin::Text()
* Enjoin::Float()
* Enjoin::Date()
* Enjoin::Enum()


#### getAttributes().allowNull

Indicates is `NULL` allowed on field.
By default is `true`.


#### getAttributes().validate

Validation rules for field.
You can read more about validation [here](https://laravel.com/docs/5.2/validation).


#### getAttributes().get

You can define custom getter closure for field:

```php
    'fullname' => [
        'type' => Enjoin::String(),
        'get' => function ($attr, Closure $getValue) {
            $v = $getValue($attr);
            if (is_string($v) && strlen($v) > 0) {
                $r = explode(' ', $v);
                return [
                    'name' => $r[0],
                    'surname' => $r[1]
                ];
            }
            return null;
        }
    ]
```


#### getAttributes().set

You can define custom setter closure for field:

```php
    'fullname' => [
        'type' => Enjoin::String(),
        'set' => function ($attr, Closure $getValue) {
            $v = $getValue($attr);
            if (is_array($v)) {
                return join(' ', $v);
            }
            return $v;
        }
    ]
```


#### getRelations()

Returns array, where each value is a relation.
Supported relations:

* `belongsTo`
* `hasMany`
* `hasOne`

Example:

```php
    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Authors')),
            Enjoin::belongsTo(Enjoin::get('Languages')),
            Enjoin::hasMany(Enjoin::get('Reviews')),
            Enjoin::hasMany(Enjoin::get('PublishersBooks'))
        ];
    }
```


#### getRelations().foreignKey

Foreign key column name.
Example:

```php
    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Authors'), ['foreignKey' => 'authors_id'])
        ];
    }
```


#### getRelations().as

Foreign key column `as` alias.
Example:

```php
    public function getRelations()
    {
        return [
            Enjoin::belongsTo(Enjoin::get('Authors'), ['as' => 'person'])
        ];
    }
```
