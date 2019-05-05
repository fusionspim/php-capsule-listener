<?php
namespace FusionsPim\PhpCapsuleListener;

use Closure;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;

class CapsuleDebugListener
{
    protected static $instances = [];

    protected $connection;
    protected $count = 0;

    public static function getInstance($name = 'default'): self
    {
        if (! isset(static::$instances[$name]) || static::$instances[$name] === null) {
            static::$instances[$name] = new static;
        }

        return static::$instances[$name];
    }

    public function __construct()
    {
        $this->setConnection(Capsule::connection());
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

    public function enable(Closure $function = null): void
    {
        $function = ($function ?: function($trace) {
            dump($trace);
        })->bindTo($this);

        $this->disable();
        $this->count = 0;

        $this->connection->listen(function ($query) use ($function) {
            $this->count++;

            $trace = [
                'count'   => $this->count,
                'sql'     => $this->prepareQuery($query),
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
        return strstr($trace['file'], 'vendor/illuminate/');
    }

    protected function isMagic(array $trace): bool
    {
        return substr($trace['function'], 0, 2) === '__';
    }

    protected function builderTriggeredQuery(array $trace): bool
    {
        return strstr($trace['class'], 'Illuminate\Database\Eloquent\Builder');
    }

    protected function prepareQuery($query): string
    {
        if (count($query->bindings) > 0) {
            return vsprintf(str_replace('?', '%s', $query->sql), array_map(function ($value) {
                return (is_numeric($value) ? $value : "'" . $value . "'");
            }, $query->bindings));
        }

        return $query->sql;
    }
}
