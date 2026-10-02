# Changelog

All notable changes to `meritum/bus-module` are documented here.

---

## [2.0.0] — 2026-10-02

2.0 migrates to `georgeff/kernel` ^2.0.

### Added
- `BusOption` enum — `BusOption::MiddlewareTag` holds the `bus.middleware` tag name, so middleware can be registered via `->tag(BusOption::MiddlewareTag->value)` instead of hardcoding the string. The tag value is unchanged, so existing `->tag('bus.middleware')` registrations keep working

### Changed
- **Breaking:** migrated to `georgeff/kernel` ^2.0 — `BusModule` now implements `Georgeff\Kernel\Contract\ModuleInterface`, so it can only be added to a 2.0 kernel
- `HandlerLocatorInterface` (`ClassNameLocator`) and `HandlerResolverInterface` (`PsrContainerResolver`) are registered as kernel fallback definitions, so a plain `define()` of either id replaces the default, whether it's registered in the bootstrap or from any module, regardless of order. `DispatcherInterface` stays a regular definition: defining it yourself throws `DefinitionException`, and `override()` is the way to replace it intentionally
- The dispatcher is always wrapped in `MiddlewareAwareDispatcher`. Tagging middleware is what turns the pipeline on; with nothing tagged, commands pass straight through to their handlers

### Removed
- **Breaking:** the `BusModule` constructor parameter `$useMiddlewareAwareDispatcher`. It only switched off a pipeline that already does nothing when no middleware is tagged
