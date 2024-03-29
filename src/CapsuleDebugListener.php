<?php
namespace FusionsPim\PhpCapsuleListener;

use Closure;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CapsuleDebugListener
{
    protected static array $instances     = [];
    protected Connection|null $connection = null;
    protected int $count                  = 0;

    public function __construct()
    {
        $this->setConnection(Capsule::connection());
    }

    public static function getInstance($name = 'default'): self
    {
        if (! isset(static::$instances[$name]) || static::$instances[$name] === null) {
            static::$instances[$name] = new static;
        }

        return static::$instances[$name];
    }

    public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function enable(Closure|null $function = null): void
    {
        $function = ($function ?: function ($trace): void {
            dump($trace);
        })->bindTo($this);

        $this->disable();
        $this->count = 0;

        $this->connection->listen(function ($query) use ($function): void {
            $this->count++;

            $trace = [
                'count'   => $this->count,
                'sql'     => $this->prepareQuery($query),
                'time'    => $query->time,
                'callees' => [],
            ];

            foreach ($this->getQueryCallee(debug_backtrace()) as $stack) {
                $trace['callees'][] = sprintf(
                    '%s:%s in %s:%s',
                    $stack['class'],
                    $stack['function'],
                    $stack['file'],
                    $stack['line']
                );
            }

            $function($trace);
        });
    }

    public function disable(): void
    {
        $this->connection->getEventDispatcher()->forget(QueryExecuted::class);
    }

    protected function getQueryCallee(array $backtrace): array
    {
        $stack = [];

        foreach ($backtrace as $trace) {
            if (! isset($trace['file'], $trace['class']) || $this->isEloquent($trace) || $this->isMagic($trace)) {
                continue;
            }

            if ($this->builderTriggeredQuery($trace)) { // We need another level of the stack
                $stack[] = $trace;
                continue;
            }

            $stack[] = $trace;
            break;
        }

        return $stack;
    }

    protected function isEloquent(array $trace): bool
    {
        return (
            mb_strstr($trace['file'], 'vendor/illuminate/') ||
            mb_strstr($trace['file'], 'vendor/laravel/framework/src/Illuminate/')
        );
    }

    protected function isMagic(array $trace): bool
    {
        return (
            mb_substr($trace['function'], 0, 2) === '__' &&
            $trace['function'] !== '__get'
        );
    }

    protected function builderTriggeredQuery(array $trace): bool
    {
        return (
            mb_strstr($trace['class'], QueryBuilder::class) ||
            mb_strstr($trace['class'], EloquentBuilder::class)
        );
    }

    protected function prepareQuery(QueryExecuted $query): string
    {
        if (count($query->bindings) > 0) {
            return vsprintf(str_replace('?', '%s', $query->sql), array_map(fn ($value) => (is_numeric($value) ? $value : "'" . $value . "'"), $query->bindings));
        }

        return $query->sql;
    }
}
