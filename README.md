# PHP Capsule Debug Listener

Capsule Debug Listener is a simple debugging tool for usage with [Laravel's Eloquent ORM](https://laravel.com/docs/master/eloquent). It's named "Capsule" after the [standalone version of Eloquent](https://github.com/illuminate/database). 

It will dump out what queries are being executed by Eloquent in your code and show where in your codebase they originate from.

## Installation:

`composer --require-dev fusionspim/php-capsule-listener`

## Usage

You can start using it by creating a new instance and enabling listening: 
```
$listener = new CapsuleDebugListener;
$listener->enable();
// Run some SQL.
$listener->disable();
```

If you need to start and stop the debugger across files, you can use it as a singleton:
```
CapsuleDebugListener::getInstance()->enable();
// Run some SQL.
CapsuleDebugListener::getInstance()->disable();
```

There are also some handy helper function pairs. You can dump the trace like this:  
```
start_dumping_queries();
// Run some SQL.
stop_dumping_queries();
```

And you can capture a trace like this:
```
start_capturing_queries();
// Run some SQL.
$logs = stop_capturing_queries();
var_dump($logs);
```

By default the output is dumped out using [Symfony's VarDumper](https://symfony.com/doc/current/components/var_dumper.html), but you can also specify your own output handling function when calling `enable()`:
```
(new CapsuleDebugListener)->enable(function (array $stack) {
    // Your custom output handling here.
});
```

A trace array is structured like this:
```
[
    // A running count of how many queries have run.
    'count' => 1,
                        
    // The exact SQL run, with parameters replaced.
    'sql' => 'SELECT * FROM foo', 
    
    // A cleaned up stack trace that shows where the function originates.
    // Excludes Eloquent's codebase and any magic functions.
    'callees' => [
        'Foo\Bar\Model:do_something in /src/Foo/Bar/Model.php:256',
    ],
]
```

If you need to debug a specific connection you can pass it to the constructor:
```
$listener = new CapsuleDebugListener($connection);
$listener->enable();
// Run some SQL.
$listener->disable();
```

You can also pass it to the singleton:
```
CapsuleDebugListener::getInstance($connection)->enable();
// Run some SQL.
CapsuleDebugListener::getInstance()->disable();
```
