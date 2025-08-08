# Listen to signals

If you run the client in the context of a CLI command the PHP process is subject to receive Signals (`kill`, `interrupt`, etc...). By default this will kill the connection to the AMQP server abruptly but you can change this behaviour by listening to signals to properly close the connection.

This is mainly useful when running the `Consume` command.

```php
use Innmind\OperatingSystem\Factory;

$os = Factory::build();
$client
    ->with(Consume::of('my-queue'))
    ->listenSignals($os->process())
    ->run(null)
    ->match(
        static fn() => null, // not reachable in this case
        static fn($failure) => throw new \RuntimeException($failure::class),
    );
```

In this case if the process receives a signal `$failure` will be an instance of `Failure\ClosedBySignal`.

> [!IMPORTANT]
> When the process is signaled the [state](Handle%20state.md) will be lost.
