# meritum/bus-module

Meritum module for bootstrapping [`georgeff/bus`](https://github.com/MikeGeorgeff/bus) into the kernel ecosystem.

## Requirements

- PHP 8.4+
- [`georgeff/kernel`](https://github.com/MikeGeorgeff/kernel) ^2.0
- [`georgeff/bus`](https://github.com/MikeGeorgeff/bus) ^1.0

## Installation

```bash
composer require meritum/bus-module
```

## Usage

Register the module with your kernel before boot:

```php
use Meritum\BusModule\BusModule;

$kernel->addModule(new BusModule());
```

The module registers `HandlerLocatorInterface`, `HandlerResolverInterface`, and a shared `DispatcherInterface`. Resolve the dispatcher from the container to dispatch commands:

```php
use Georgeff\Bus\DispatcherInterface;

$dispatcher = $container->get(DispatcherInterface::class);
$dispatcher->dispatch(new PlaceOrderCommand($data));
```

## Handler convention

`BusModule` wires `ClassNameLocator` as the handler locator. Handler classes must be named after their command with a `Handler` suffix and must be invokable:

```php
final class PlaceOrderCommandHandler
{
    public function __invoke(PlaceOrderCommand $command): void
    {
        // ...
    }
}
```

Handlers are resolved from the container, so register each handler as a service:

```php
$kernel->define(PlaceOrderCommandHandler::class, fn() => new PlaceOrderCommandHandler());
```

## Replacing the locator or resolver

`ClassNameLocator` and `PsrContainerResolver` are registered as fallbacks. To use a different naming convention or resolution strategy, define your own `HandlerLocatorInterface` or `HandlerResolverInterface`, either in your bootstrap or from any module. Your definition replaces the default regardless of module order:

```php
use Georgeff\Bus\HandlerLocatorInterface;

$kernel->define(HandlerLocatorInterface::class, fn() => new MyHandlerLocator());
```

`DispatcherInterface` is not a fallback. Defining it yourself throws `DefinitionException`; use `$kernel->override(DispatcherInterface::class, ...)` if you really mean to replace the dispatcher.

## Middleware

The dispatcher is always wrapped in `MiddlewareAwareDispatcher`. Tag services with `BusOption::MiddlewareTag` (`bus.middleware`) to add them to the dispatch pipeline; with nothing tagged, commands go straight to their handlers:

```php
use Meritum\BusModule\BusOption;

$kernel->define(
    LoggingMiddleware::class,
    fn(ContainerInterface $c) => new LoggingMiddleware($c->get(LoggerInterface::class)),
)->tag(BusOption::MiddlewareTag->value);
```

Middleware receive the command and a `$next` callable. Call `$next($command)` to continue the pipeline:

```php
final class LoggingMiddleware
{
    public function __invoke(object $command, callable $next): mixed
    {
        $this->logger->info('Dispatching', ['command' => $command::class]);
        $result = $next($command);
        $this->logger->info('Dispatched', ['command' => $command::class]);

        return $result;
    }
}
```

Middleware runs in the order services are tagged.

Treat the command as immutable. Middleware should pass the command it received to `$next`. The handler always receives the command that was originally dispatched, even if a middleware passes a different object to `$next`.
