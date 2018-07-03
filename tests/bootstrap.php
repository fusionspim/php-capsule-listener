<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;

// Setup Eloquent connection to use an in-memory SQLite3 database.
$capsule = new Capsule;
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Drop old test schemas.
Capsule::schema()->dropIfExists('authors');
Capsule::schema()->dropIfExists('articles');

// Create test schemas.
Capsule::schema()->create('authors', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name', 255);
    $table->string('email', 255);
    $table->timestamps();
});

Capsule::schema()->create('articles', function (Blueprint $table) {
    $table->increments('id');
    $table->string('title', 255);
    $table->text('description');
    $table->json('tags');
    $table->unsignedInteger('views');
    $table->unsignedInteger('author_id');
    $table->timestamps();
});
