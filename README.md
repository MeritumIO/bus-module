# meritum/bus-module

Meritum module for bootstrapping [`georgeff/bus`](https://github.com/MikeGeorgeff/bus) into the kernel ecosystem.

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

The module registers `HandlerLocatorInterface`, `HandlerResolverInterface`, and `DispatcherInterface`. Resolve the dispatcher from the container to dispatch commands:

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

## Middleware

Tag services with `bus.middleware` to add them to the dispatch pipeline:

```php
$kernel->define(
    LoggingMiddleware::class,
    fn(ContainerInterface $c) => new LoggingMiddleware($c->get(LoggerInterface::class)),
)->tag('bus.middleware');
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

## Options

To bypass the middleware pipeline and use the plain dispatcher:

```php
$kernel->addModule(new BusModule(useMiddlewareAwareDispatcher: false));
```
