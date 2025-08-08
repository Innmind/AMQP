# Get a batch of messages

In some cases you may prefer getting a fixed set of messages instead of using a consumer (to avoid keeping a persistent connection). You can easily do so with the `Get` command:

```php
use Innmind\AMQP\Command\Get;

$state = $client
    ->with(
        Get::of('my-queue')
            ->take(10) // <- the number of messages you want
            ->handle(static function(int $state, $message, $continuation) {
                return $continuation->ack($state + 1);
            }),
    )
    ->run(0)
    ->unwrap();
```

This will try to get `10` messages but since the server may not respond with messages the returned `$state` is **at most** `10`.
