<?php

namespace Meritum\BusModule;

use Georgeff\Bus\Dispatcher;
use Georgeff\Kernel\KernelInterface;
use Psr\Container\ContainerInterface;
use Georgeff\Bus\DispatcherInterface;
use Georgeff\Bus\HandlerLocatorInterface;
use Georgeff\Bus\HandlerResolverInterface;
use Georgeff\Bus\Locator\ClassNameLocator;
use Georgeff\Kernel\Contract\ModuleInterface;
use Georgeff\Bus\Resolver\PsrContainerResolver;

final class BusModule implements ModuleInterface
{
    public function register(KernelInterface $kernel): void
    {
        $kernel->defineFallback(HandlerLocatorInterface::class, fn() => new ClassNameLocator());

        $kernel->defineFallback(
            HandlerResolverInterface::class,
            fn(ContainerInterface $c) => new PsrContainerResolver($c, $c->get(HandlerLocatorInterface::class))
        );

        $kernel->define(
            DispatcherInterface::class,
            fn(ContainerInterface $c) => new Dispatcher($c->get(HandlerResolverInterface::class))
        )->share();

        $kernel->decorate(DispatcherInterface::class, new MiddlewareAwareDecorator());
    }
}
