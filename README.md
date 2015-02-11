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
  * [Getters and setters](#getters-and-setters)
  * [Validations](#validations)
  * [Data retrieval / Finders](#data-retrieval--finders)
    * [find](#find)
    * [findOrCreate](#findorcreate)
    * [findAndCountAll](#findandcountall)
    * [findAll](#findall)
    * [Complex filtering / OR queries](#complex-filtering--or-queries)
    * [Manipulating the dataset with limit, offset, order and group](#manipulating-the-dataset-with-limit-offset-order-and-group)
    * [Raw queries](#raw-queries)
    * [*TODO count*](#count)
    * [*TODO max*](#max)
    * [*TODO min*](#min)
    * [*TODO sum*](#sum)
  * [Eager loading](#eager-loading)
  * [Ordering Eager Loaded Associations](#ordering-eager-loaded-associations)
  * [Nested eager loading](#nested-eager-loading)

## Installation

### Warning

Do not use this package in production because it still in heavy development phase.  

### Requirements

* PHP 5.4+
* Laravel 5.0+

### Via composer

Add `mightydes\enjoin` as a requirement to composer.json:
```json
{
    "require": {
        "mightydes/enjoin": "dev-master@dev"
    }
}
```

By default, all models files should be in `Models` namespace.
You can change it in `app/Providers/ConfigServiceProvider.php`:
```php
    public function register()
    {
        config([
            'enjoin' => [
                'models_namespace' => 'App\Models'
            ]
        ]);
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

### Getters and setters

It is possible to define 'object-property' getters and setters functions on your models,
these can be used both for 'protecting' properties that map to database fields and for defining 'pseudo' properties.

To define getter or setter, add `get` or `set` closure to model field:

```php
public function getAttributes()
{
    return [
    
        'latlong' => [
            'type' => Enjoin::String(),
            'allowNull' => false,
            'get' => function ($attr, $getValue) {
                    $latlong = $getValue($attr);
                    if (is_string($latlong) && strlen($latlong) > 0) {
                        $latlong = explode(',', $latlong);
                        if (count($latlong) === 2) {
                            return array_map('floatval', $latlong);
                        }
                    }
                    return null;
                },
            'set' => function ($attr, $getValue) {
                    $latlong = $getValue($attr);
                    if (is_array($latlong)) {
                        return implode(',', $latlong);
                    }
                    return $latlong;
                }
        ],

    ];
}
```

Getter (setter) closure has string `$attr` parameter (table column name),
and closure `$getValue` parameter, which returns value for given attribute.

### Validations

You can specify validation on model field.
Validation will be called when executing the `build()` or `create()` functions.
For example:

```php
public function getAttributes()
{
    return [
    
        'name' => [
            'type' => Enjoin::String(),
            'allowNull' => false,
            'validate' => 'between:1,255'
        ],

    ];
}
```

On validation fail error would be dropped.
For mor information about available methods and notations
look at corresponded [Laravel section](http://laravel.com/docs/4.2/validation#available-validation-rules).

### Data retrieval / Finders

Finder methods are designed to get data from the database.
The returned data is an active record object.
Take a look at available model finders:

#### find

Search for one specific element in the database:

```php

// search for known ids
$project = Enjoin::get('Project')->find(123);
// $project will be an instance of Record and stores the content of the table entry
// with id 123. if such an entry is not defined you will get null
 
// search for attributes
$project = Enjoin::get('Project')->find([
    'where' => ['title' => 'aProject']
]);
// $project will be the first entry of the Projects table with the title 'aProject' || null
 
// select some attributes
$project = Enjoin::get('Project')->find([
    'where' => ['title' => 'aProject'],
    'attributes' => ['id', 'name']
]);
// $project will be the first entry of the Projects table with the title 'aProject' || null
// picked only 'id' and 'name' columns

```

*TODO: attribute renaming*

#### findOrCreate

Search for a specific element or create it if not available.
The method `findOrCreate` can be used to check if a certain element is already existing in the database.
If that is the case the method will result in a respective instance.
If the element does not yet exist, it will be created.

Let's assume we have an empty database with a `User` model which has a `username` and a `job`:

```php
$user = Enjoin::get('User')
    ->findOrCreate([ 'username' => 'Bob', 'job' => 'Technical Lead JavaScript' ]);
```

The code created a new instance.

So when we already have an instance,

```php
Enjoin::get('User')->create([ 'username' => 'Alice', 'job' => 'Ultramarine' ]);
$user = Enjoin::get('User')
    ->findOrCreate([ 'username' => 'Alice', 'job' => 'Ultramarine' ]);
```

the existing entry will not be changed. See the `job` of the second user, and the fact that created was false.

#### findAndCountAll

Search for multiple elements in the database, returns both data and total count.

This is a convenience method that combines `findAll()` and `count()` (see below),
this is useful when dealing with queries related to pagination where you want to retrieve data
with a `limit` and `offset` but also need to know the total number of records that match the query.

The success handler will always receive an object with two properties:

* `count` - an integer, total number records (matching the where clause).
* `rows` - an array of objects, the records (matching the where clause) within the limit/offset range.

```php
$r = Enjoin::get('Project')->findAndCountAll([
    'where' => [ 'title' => ['like' => '%foo%'] ],
    'offset' => 10,
    'limit' => 2
]);
```
The options list that you pass to `findAndCountAll()` is the same as for `findAll()` (described below).

#### findAll

Search for multiple elements in the database:

```php
// find multiple entries
$projects = Enjoin::get('Project')->findAll();
// $projects will be an array of all Project instances

// search for specific attributes - hash usage
$projects = Enjoin::get('Project')->findAll([ 'where' => [ 'name' => 'A Project' ] ]);
// $projects will be an array of Project instances with the specified name

// search within a specific range
$projects = Enjoin::get('Project')->findAll([ 'where' => ['id' => [1, 2, 3]] ]);
// projects will be an array of Projects having the id 1, 2 or 3
// this is actually doing an `IN` query

$projects = Enjoin::get('Project')->findAll([
    'where' => [
        'id' => [
            'gt' => 6,              // id > 6
            'gte' => 6,             // id >= 6
            'lt' => 10,             // id < 10
            'lte' => 10,            // id <= 10
            'ne' => 20,             // id != 20
            'between' => [6, 10],   // BETWEEN 6 AND 10
            'nbetween' => [6, 10],  // NOT BETWEEN 11 AND 15
            'like' => '%1%',        // LIKE '%1%'
        ]
    ]
]);
```

*TODO: between, nbetween*

#### Complex filtering / OR queries

It is possible to do complex where queries with multiple levels of nested `AND` and `OR` conditions.
In order to do that you can use `Enjoin::sqlOr` and `Enjoin::sqlAnd` and pass an arbitrary amount of arguments to it.
Every argument will get transformed into a proper SQL condition and gets joined with the either `AND` or `OR`.

```php
Enjoin::get('Project')->find([
    'where' => Enjoin::sqlAnd(
        ['name' => 'a project'],
        Enjoin::sqlOr(
            ['id' => [1, 2, 3]],
            ['id' => ['lt' => 10]]
        )
    )
]);
```

This code will generate the following query:

```sql
SELECT *
FROM `Projects`
WHERE (
  `Projects`.`name`='a project'
   AND (`Projects`.`id` IN (1,2,3) OR `Projects`.`id` < 10)
)
LIMIT 1;
```

Notice, that instead of `Enjoin::sqlAnd` you can also use a plain array which will be treated as `Enjoin::sqlAnd`
if it contains objects or hashes or other complex data types.

#### Manipulating the dataset with limit, offset, order and group

To get more relevant data, you can use limit, offset, order and grouping:

```php
// limit the results of the query
Enjoin::get('Project')->findAll([ 'limit' => 10 ]);

// step over the first 10 elements
Enjoin::get('Project')->findAll([ 'offset' => 10 ]);

// step over the first 10 elements, and take 2
Enjoin::get('Project')->findAll([ 'offset' => 10, 'limit' => 2 ]);
```

The syntax for grouping and ordering are equal, so below it is only explained with a single example for group,
and the rest for order. Everything you see below can also be done for group:

```php
Enjoin::get('Project')->findAll([ 'order' => 'title DESC' ]);
// yields ORDER BY title DESC
 
Enjoin::get('Project')->findAll([ 'group' => 'name' ]);
// yields GROUP BY name
```

Notice how in the two examples above, the string provided is inserted verbatim into the query,
i.e. column names are not escaped. If you want to escape column names, you should provide an array of arguments,
even though you only want to order / group by a single column:

```php
Enjoin::get('Foo')->find([
    'order' => [
        'name',
        // will return `name`
        
        'username DESC',
        // will return `username DESC` -- i.e. don't do it!
        
        ['username', 'DESC'],
        // will return `username` DESC
    ]
]);
```

*TODO: aggregate methods*

#### Raw queries

Supported by Laravel Database component, [see](http://laravel.com/docs/4.2/database).

#### count

*TODO: Count the occurrences of elements in the database*

#### max

*TODO: Get the greatest value of a specific attribute within a specific table*

#### min

*TODO: Get the least value of a specific attribute within a specific table*

#### sum

*TODO: Sum the value of specific attributes*

### Eager loading

When you are retrieving data from the database there is a fair chance that you also want to get their associations.
This is possible and is called eager loading. The basic idea behind that, is the use of the attribute
`include` when you are calling `find` or `findAll`. Lets assume the following setup:

```php
// app/models/Task.php

public function getRelations()
{
    return [
        Enjoin::belongsTo(Enjoin::get('User'))
    ];
}


// app/models/User.php

public function getRelations()
{
    return [
        Enjoin::hasMany(Enjoin::get('Task')),
        Enjoin::hasMany(Enjoin::get('Tool'), ['as' => 'Instruments'])
    ];
}
```

OK. So, first of all, let's load all tasks with their associated user:

```php
$r = Enjoin::get('Task')->findAll([ 'include' => Enjoin::get('User') ]);

/*
[{
    "name": "A Task",
    "id": 1,
    "createdAt": "2013-03-20T20:31:40.000Z",
    "updatedAt": "2013-03-20T20:31:40.000Z",
    "UserId": 1,
    "user": {
        "name": "John Doe",
        "id": 1,
        "createdAt": "2013-03-20T20:31:45.000Z",
        "updatedAt": "2013-03-20T20:31:45.000Z"
    }
}]
*/
```

Notice that the accessor of the associated data is the name of the model in camel-case with lower-cased first character.
Also the accessor is singular as the association is one-to-something.

Next thing: Loading of data with many-to-something associations:

```php
$r = Enjoin::get('User')->findAll([ 'include' => Enjoin::get('Task') ]);

/*
[{
    "name": "John Doe",
    "id": 1,
    "createdAt": "2013-03-20T20:31:45.000Z",
    "updatedAt": "2013-03-20T20:31:45.000Z",
    "tasks": [{
        "name": "A Task",
        "id": 1,
        "createdAt": "2013-03-20T20:31:40.000Z",
        "updatedAt": "2013-03-20T20:31:40.000Z",
        "UserId": 1
    }]
}]
*/
```

Notice that the accessor is plural. This is because the association is many-to-something.

If an association is aliased (using the `as` option), you **must** specify this alias when including the model.
Notice how the user's `Tool`s are aliased as `Instruments` above.
In order to get that right you have to specify the model you want to load, as well as the alias:

```php
$r = Enjoin::get('User')
    ->findAll([ 'include' => ['model' => Enjoin::get('Tool'), 'as' => 'Instruments'] ]);

/*
[{
    "name": "John Doe",
    "id": 1,
    "createdAt": "2013-03-20T20:31:45.000Z",
    "updatedAt": "2013-03-20T20:31:45.000Z",
    "instruments": [{
        "name": "Toothpick",
        "id": 1,
        "createdAt": null,
        "updatedAt": null,
        "UserId": 1
    }]
}]
*/
```

### Ordering Eager Loaded Associations

In the case of a one-to-many relationship:

```php
Enjoin::get('Company')->findAll([
    'include' => Enjoin::get('Division'),
    'order' => [ Enjoin::get('Division'), 'name' ]
]);
Enjoin::get('Company')->findAll([
    'include' => Enjoin::get('Division'),
    'order' => [ Enjoin::get('Division'), ['name', 'DESC'] ]
]);
Enjoin::get('Company')->findAll([
    'include' => [ ['model' => Enjoin::get('Division'), 'as' => 'Div'] ],
    'order' => [ ['model' => Enjoin::get('Division'), 'as' => 'Div'], 'name' ]
]);
Enjoin::get('Company')->findAll([
    'include' => [ ['model' => Enjoin::get('Division'), 'include' => Enjoin::get('Department')] ],
    'order' => [ Enjoin::get('Division'), Enjoin::get('Department'), 'name' ]
]);
```

*TODO: sort by attributes in the `through` table*

### Nested eager loading

```php
$r = Enjoin::get('User')->findAll([
    'include' => [
        ['model' => Enjoin::get('Tool'), 'as' => 'Instruments', 'include' => [
            ['model' => Enjoin::get('Teacher'), 'include' => [
                /* etc */
            ]]
        ]]
    ]
]);

/*
[{
    "name": "John Doe",
    "id": 1,
    "createdAt": "2013-03-20T20:31:45.000Z",
    "updatedAt": "2013-03-20T20:31:45.000Z",
    "instruments": [{ // 1:M and N:M association
        "name": "Toothpick",
        "id": 1,
        "createdAt": null,
        "updatedAt": null,
        "UserId": 1,
        "Teacher": { // 1:1 association
            "name": "Jimi Hendrix"
        }
    }]
}]
*/
```

**Final note**: If you include an object which is not associated, Enjoin will throw an error.
