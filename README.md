# ezDB

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![ezDB Tests][ico-tests]][link-tests]
[![Software License][ico-license]](LICENSE.md)

ezDB is a lightweight library that provides an easy and fast to deal with databases in PHP. It manages connections, provides a query builder, and a lightweight ORM.

_This project was inspired by [ezSQL](https://github.com/ezSQL/ezsql) and [Laravel Eloquent](https://github.com/illuminate/database). It borrows most of its syntax from Eloquent and I would like to thank all the awesome developers that worked have worked on it._

The ORM in this library is lightweight and focuses on providing basic functionalities. If you require more functions you can easily extend the library  or use a PHP ORM like [DoctrineORM](https://github.com/doctrine/orm).

# Install

## With Composer

ezDB can be installed using composer by running the command below,

`composer require terminusstudio/ezdb`

and require the `autoload.php` file in your script.

```php
require_once __DIR__ . '/vendor/autoload.php';
```

## Without Composer

ezDB can also be included manually. This is very useful for simple projects that only wants to use ezDB for managing database connections.

Follow the steps below:

1. Download the latest [release from here](https://github.com/TerminusStudio/ezDB/releases/).
2. Extract the **ezDB** folder to your project.
3. In your program include the following line,

```php
require_once '<PATH TO ezDB>/load.php';
```

4. This will include all the files that are necessary for ezDB to function. To improve performance, create a copy of load.php and include only the classes that you will use.

# How ezDB Works

ezDB can be seperated into three different layers, 

1. [Connection](https://github.com/TerminusStudio/ezDB/wiki/1.-Connection)
2. [Builder](https://github.com/TerminusStudio/ezDB/wiki/2.-Builder)
3. [Model](https://github.com/TerminusStudio/ezDB/wiki/3.-Model)

Each successive layer depends on the previous layer to function. The list below shows the purpose of each of the layer,

1. Manage Database Connections using multiple drivers. Support queries, prepared statements etc. A global class manages as many connection to as many database as you need.

2. Query Database Tables without writing SQL using the Builder class. Each builder instance is basically an SQL statement that is executed using prepared statements. The builder class also supports querying relationships easily.

3. Model provides a basic ORM and is also capable of managing relationships.

# License
Copyright © Terminus Studio

Licensed under the MIT license, see [LICENSE.md](https://github.com/TerminusStudio/ezDB/blob/dev/License.md) for details.

[ico-version]: https://img.shields.io/packagist/v/TerminusStudio/ez-db.svg?style=flat-square
[ico-tests]: https://github.com/TerminusStudio/ezDB/workflows/ezDB%20Tests/badge.svg?branch=dev
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/TerminusStudio/ez-db.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/TerminusStudio/ez-db
[link-tests]: https://github.com/TerminusStudio/ezDB/actions/?query=branch:dev
[link-downloads]: https://packagist.org/packages/TerminusStudio/ez-db
[link-author]: https://github.com/TerminusStudio
