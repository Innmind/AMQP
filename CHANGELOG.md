# Changelog

## [Unreleased]

### Changed

- Requires `innmind/foundation:~1.6`
- `Innmind\AMQP\Factory::make()` timeout argument is now expressed via `Innmind\TimeContinuum\Period`
- `Innmind\AMQP\Model\Basic\Message` expiration is now expressed via `Innmind\TimeContinuum\Period`
- `Innmind\AMQP\Failure` is now an exception that wraps each possible failure object
- `Innmind\AMQP\Client::run()` now returns an `Innmind\Immutable\Attempt`
- `Innmind\AMQP\Command::__invoke()` now must return an `Innmind\Immutable\Attempt`

### Fixed

- PHP `8.4` deprecations

## 5.1.0 - 2024-06-16

### Changed

- Requires `innmind/immutable:~5.7`
- The `Sequence` of frames to publish a message is only lazy when the message content is a lazy sequence of chunks

## 5.0.0 - 2024-03-10

### Changed

- Requires `innmind/immutable:~5.2`
- Requires `innmind/operating-system:~5.0`
- Requires `innmind/filesystem:~7.0`
- Requires `innmind/io:~2.6`
- Carried state inside a `Innmind\AMQP\Command` is now wrapped inside a `Innmind\AMQP\Client\State`
- `Innmind\AMQP\Client::of()` now requires an instance of `Innmind\OperatingSystem\Filesystem` as a second argument

## 4.3.0 - 2023-09-23

### Added

- Support for `innmind/immutable:~5.0`

### Removed

- Support for PHP `8.1`

## 4.2.0 - 2023-01-29

### Changed

- Requires `innmind/stream:~4.0`

## 4.1.0 - 2022-12-18

### Added

- Support for `innmind/filesystem:~6.0`
