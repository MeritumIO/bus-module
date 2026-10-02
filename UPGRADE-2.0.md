# Upgrading from 1.x to 2.0

2.0 migrates `meritum/bus-module` onto `georgeff/kernel` ^2.0. Dispatching commands, the handler naming convention, and how middleware is written are unchanged. **Read [`georgeff/kernel`'s own `UPGRADE-2.0.md`](https://github.com/MikeGeorgeff/kernel/blob/main/UPGRADE-2.0.md) first**; this guide only covers what's specific to `meritum/bus-module`.

See `CHANGELOG.md` for the full list of changes.

## Requirements

- [ ] **`georgeff/kernel` ^2.0.** `composer.json` now requires `"georgeff/kernel": "^2.0"`. `BusModule` implements kernel 2.0's `Contract\ModuleInterface`, so it can't be added to a 1.x kernel.

## 1. `BusModule` no longer takes a constructor argument

The `$useMiddlewareAwareDispatcher` parameter is gone. Passing it now fails with an "unknown named parameter" or "too many arguments" error.

- [ ] Remove the argument:

  ```php
  // Before
  $kernel->addModule(new BusModule(useMiddlewareAwareDispatcher: false));

  // After
  $kernel->addModule(new BusModule());
  ```

- [ ] If you passed `false` to run without middleware, don't tag any services with `bus.middleware`. With nothing tagged, the pipeline is empty and commands go straight to their handlers.
- [ ] `DispatcherInterface` now always resolves to a `MiddlewareAwareDispatcher`. Code that depends on `DispatcherInterface` is unaffected.

## 2. Replacing the dispatcher needs `override()`

Kernel 2.0's `define()` throws `DefinitionException` when an id is already defined. `BusModule` defines `DispatcherInterface`, so defining it again yourself now fails instead of silently winning.

- [ ] If you replace the dispatcher, switch to `override()`:

  ```php
  // Before
  $kernel->define(DispatcherInterface::class, fn($c) => new MyDispatcher(...));

  // After
  $kernel->override(DispatcherInterface::class, fn($c) => new MyDispatcher(...))->share();
  ```

- [ ] Replacing `HandlerLocatorInterface` or `HandlerResolverInterface` needs no change. They're registered as fallbacks, so a plain `define()` still replaces them, and it no longer has to come after `BusModule`.

## Not required, but worth adopting

- **`BusOption::MiddlewareTag`** — use it instead of the `bus.middleware` string when tagging middleware. The string value is unchanged, so existing registrations keep working:

  ```php
  use Meritum\BusModule\BusOption;

  $kernel->define(LoggingMiddleware::class, fn($c) => new LoggingMiddleware(...))
         ->tag(BusOption::MiddlewareTag->value);
  ```

## Verifying the upgrade

- [ ] `composer test` — full suite passes
- [ ] `composer analyze` — PHPStan clean at `level: max`
- [ ] Grep your own codebase for `new BusModule(` with an argument, and for `useMiddlewareAwareDispatcher` — any match needs section 1.
- [ ] Grep for `define(DispatcherInterface::class` — any match needs section 2.
- [ ] Also run through [`georgeff/kernel`'s own verification checklist](https://github.com/MikeGeorgeff/kernel/blob/main/UPGRADE-2.0.md#verifying-the-upgrade) for base-kernel-level changes.
