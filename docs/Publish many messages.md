# Publish many messages

The example below reads all the valid users from a file and publish them to an exchange.

```php
use Innmind\AMQP\{
    Client,
    Factory,
    Command\Publish,
    Model\Basic\Message,
};
use Innmind\OperatingSystem\Factory as OSFactory;
use Innmind\Filesystem\Name;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @param Sequence<array{email: string}> $users
 */
function publish(Client $client, Sequence $users): void {
    $client
        ->with(Publish::many(
            $users
                ->map(\json_encode(...))
                ->map(Str::of(...))
                ->map(Message::of(...))
                ->map(static fn($message) => $message->withContentType(
                    Message\ContentType::of('application', 'json'),
                )),
        )->to('my-exchange'))
        ->run(null)
        ->match(
            static fn() => null, // all users published
            static fn($failure) => throw new \RuntimeException($failure::class),
        );
}

$os = OSFactory::build();
$client = Factory::of($os)->make(/* details */);
$os
    ->filesystem()
    ->mount(Path::of('/path/to/some/directory/'))
    ->get(Name::of('leads.csv'))
    ->map(
        static fn($file) => $file
            ->content()
            ->lines()
            ->map(static fn($line) => \str_getcsv($line->toString()))
            ->filter(\is_array(...))
            ->filter(static fn($array) => \is_string(
                \filter_var($array['email'] ?? null, \FILTER_VALIDATE_EMAIL),
            )),
    )
    ->match(
        static fn($users) => publish($client, $users),
        static fn() => null, // no file found
    );
```

Since reading a file is lazy evaluated you can read a file that can't fit in memory and publish all of it to the AMQP server.
