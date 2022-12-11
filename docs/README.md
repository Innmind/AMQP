# AMQP

## Philosophy

This package tries to abstract away as much as possible the technical details of the protocol to only expose a high level API.

This is achieved via a declarative API. You specify the _commands_ you want to run but are only executed when you call `run()` on the `Client`.

The `run` method asks for a state variable that will be passed to each command and will be returned on the right hand side of the `Either`, you can think of this behaviour as a _reduce_ operation. You can [learn more here](Handle%20state.md).

The `Client` never throws any `Exception`, instead all errors are return on the left hand side of the `Either` returned by `run`. All objects are instances of `Innmind\AMQP\Failure` and you can make sure you handle all of them thanks to the enum `Failure\Kind`.

## Commands

Here are all the commands you can use with the client:

| Command | Description |
|-|-|
| `Innmind\AMQP\Command\DeclareExchange` | Declare a durable exchange |
| `Innmind\AMQP\Command\DeleteExchange` | Delete an exchange |
| `Innmind\AMQP\Command\DeclareQueue` | Declare a durable queue |
| `Innmind\AMQP\Command\DeleteQueue` | Delete an queue |
| `Innmind\AMQP\Command\Bind` | Connect an exchange to a queue |
| `Innmind\AMQP\Command\Unbind` | Deconnect a queue from an exchange |
| `Innmind\AMQP\Command\Publish` | Publish one or more messages to an exchange |
| `Innmind\AMQP\Command\Consume` | Pull the messages from a queue |
| `Innmind\AMQP\Command\Get` | Pull one message from a queue |
| `Innmind\AMQP\Command\Qos` | Specify the number of messages the server pre-send to a consumer |
| `Innmind\AMQP\Command\Purge` | Delete all messages from a queue |
| `Innmind\AMQP\Command\Transaction` | Wrap a command in a transaction |
