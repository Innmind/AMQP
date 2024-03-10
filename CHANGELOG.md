# Changelog

## [Unreleased]

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
