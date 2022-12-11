# Handle state

As described in the [philosophy](README.md#philosophy) of this package you can manage state in the commands. There are only 2 that allows you to change the initial state: `Get` and `Consume`.

## `Get`

```php
use Innmind\AMQP\Command\Get;

$state = $client
    ->with(Get::of('my-queue')->handle(static function(int $state, $message, $continuation) {
        return $continuation->ack($state + 1);
    }))
    ->run(0)
    ->match(
        static fn($state) => $state,
        static fn($failure) => throw new \RuntimeException($failure::class),
    );
```

When trying to get a message from a queue the server can respond with one or no message. In this example we increment `$state` when we handle a message with an initial state set to `0`. Meaning that if we got a message the returned `$state` value will be `1` or `0` if no message was sent.

This is a very basic example where we use an `int` as a state but you can use any type you wish.

## `Consume`

```php
use Innmind\AMQP\Command\Consume;

$state = $client
    ->with(Consume::of('my-queue')->handle(static function(int $state, $message, $continuation) {
        if ($state === 42) {
            return $continuation->cancel($state);
        }

        return $continuation->ack($state + 1);
    }))
    ->run(0)
    ->match(
        static fn($state) => $state,
        static fn($failure) => throw new \RuntimeException($failure::class),
    );
```

This example is similar to the `Get` one except for one exception. Since a consumer is meant to run forever it would never reach the returned `$state` if it wasn't for the call to `$continuation->cancel($state)` that cancel the consumption of messages. This means that in this case the returned `$state` will always be `42`.

As with `Get` the state here can be of any type.

## State type

As mentionned above you can use any type of data you wish for the state.

However you can't change the type inside the handlers. Since you can't predict the number of messages that will be handled, trying to change the state type would mean you can't predict the returned type.
