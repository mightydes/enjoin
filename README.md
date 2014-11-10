Enjoin
======

# Introduction

Enjoin is another PHP ORM (Object Relational Mapping) designed for [Laravel 4](http://laravel.com/) framework
and inspired by [Sequelize.js](http://sequelizejs.com/) ORM.

Laravel has built in ORM, called [Eloquent](http://laravel.com/docs/4.2/eloquent).
Unfortunately, there are some major disadvantages in Eloquent:

* Unable to construct associations via `join` clause
(see [Unable to Get Eloquent to Automatically Create Joins](http://stackoverflow.com/questions/11099570/unable-to-get-eloquent-to-automatically-create-joins)).
* Unable to built in validation for model.
* Unable to order by eager loaded records. 

Enjoin relies on Laravel components, such as `Database` and `Cache`. 
Yau can find documentation [here](http://enjoin.readthedocs.org/en/latest/).
