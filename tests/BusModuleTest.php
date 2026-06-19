<?php

namespace Meritum\BusModule\Test;

use Georgeff\Bus\Dispatcher;
use Georgeff\Bus\DispatcherInterface;
use Georgeff\Bus\HandlerLocatorInterface;
use Georgeff\Bus\HandlerResolverInterface;
use Georgeff\Bus\Locator\ClassNameLocator;
use Georgeff\Bus\MiddlewareAwareDispatcher;
use Georgeff\Bus\Resolver\PsrContainerResolver;
use Georgeff\Kernel\Environment;
use Georgeff\Kernel\Kernel;
use Meritum\BusModule\BusModule;
use PHPUnit\Framework\TestCase;

final class BusModuleTest extends TestCase
{
    private function bootKernel(BusModule $module): Kernel
    {
        $kernel = new Kernel(Environment::Testing);
        $kernel->addModule($module);
        $kernel->boot();

        return $kernel;
    }

    public function test_registers_handler_locator_as_class_name_locator(): void
    {
        $kernel = $this->bootKernel(new BusModule());

        $this->assertInstanceOf(ClassNameLocator::class, $kernel->getContainer()->get(HandlerLocatorInterface::class));
    }

    public function test_registers_handler_resolver_as_psr_container_resolver(): void
    {
        $kernel = $this->bootKernel(new BusModule());

        $this->assertInstanceOf(PsrContainerResolver::class, $kernel->getContainer()->get(HandlerResolverInterface::class));
    }

    public function test_dispatcher_is_shared(): void
    {
        $kernel = $this->bootKernel(new BusModule());
        $container = $kernel->getContainer();

        $this->assertSame(
            $container->get(DispatcherInterface::class),
            $container->get(DispatcherInterface::class),
        );
    }

    public function test_wraps_dispatcher_with_middleware_aware_dispatcher_by_default(): void
    {
        $kernel = $this->bootKernel(new BusModule());

        $this->assertInstanceOf(MiddlewareAwareDispatcher::class, $kernel->getContainer()->get(DispatcherInterface::class));
    }

    public function test_does_not_wrap_dispatcher_when_middleware_aware_disabled(): void
    {
        $kernel = $this->bootKernel(new BusModule(false));
        $dispatcher = $kernel->getContainer()->get(DispatcherInterface::class);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
    }
}
