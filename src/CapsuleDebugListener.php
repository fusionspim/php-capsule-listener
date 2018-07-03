<?php
namespace FusionsPim\PhpCapsuleListener;

use Closure;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;

class CapsuleDebugListener
{
    protected static $instance;

    protected $connection;
    protected $count = 0;

    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new static(...func_get_args());
        }

        return static::$instance;
    }

    public function __construct(Connection $connection = null)
    {
        if ($connection === null) {
            $this->connection = Capsule::connection();
        }
    }

    public function enable(Closure $function = null): void
    {
        $function = ($function ?: $this->defaultOutputFunction())->bindTo($this);

        $this->disable();
        $this->count = 0;

        $this->connection->listen(function ($query) use ($function) {
            $this->count++;

            $function($this->prepareQuery($query), $this->count, $this->getQueryCallee(debug_backtrace()));
        });
    }

    public function disable(): void
    {
        $events = $this->connection->getEventDispatcher();
        $events->forget(QueryExecuted::class);
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

    protected function defaultOutputFunction(): Closure
    {
        return function (string $sql, int $count, array $stack = []) {
            $output = ['Query' . $count => $sql];

            foreach ($stack as $trace) {
                $output['Callees'][] = sprintf(
                    '%s:%s in %s:%s',
                    $trace['class'],
                    $trace['function'],
                    $trace['file'],
                    $trace['line']
                );
            }

            dump($output);
        };
    }
}
