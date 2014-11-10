# Definition

Each Eloquent model should be placed in `app/models` folder and extends `BaseMode`:

```php
<?php

namespace Enjoin;

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
