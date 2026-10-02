<?php

namespace Meritum\BusModule\Test;

use Georgeff\Bus\DispatcherInterface;
use Georgeff\Bus\MiddlewareAwareDispatcher;
use Georgeff\Kernel\DI\TagRegistryInterface;
use Meritum\BusModule\MiddlewareAwareDecorator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class MiddlewareAwareDecoratorTest extends TestCase
{
    public function test_wraps_inner_dispatcher_with_middleware_aware_dispatcher(): void
    {
        $inner = $this->createMock(DispatcherInterface::class);
        $container = $this->containerWithTags([]);

        $result = (new MiddlewareAwareDecorator())($inner, $container);

        $this->assertInstanceOf(MiddlewareAwareDispatcher::class, $result);
    }

    public function test_middleware_tagged_bus_middleware_is_invoked_on_dispatch(): void
    {
        $command = new \stdClass();
        $called = false;

        $middleware = function (object $cmd, callable $next) use (&$called): mixed {
            $called = true;
            return $next($cmd);
        };

        $inner = $this->createMock(DispatcherInterface::class);
        $inner->expects($this->once())->method('dispatch')->with($command);

        $container = $this->containerWithTags(['bus.middleware' => [$middleware]]);

        $dispatcher = (new MiddlewareAwareDecorator())($inner, $container);
        $dispatcher->dispatch($command);

        $this->assertTrue($called);
    }

    public function test_non_bus_middleware_tagged_services_are_not_invoked(): void
    {
        $called = false;
        $other = function () use (&$called): void {
            $called = true;
        };

        $inner = $this->createMock(DispatcherInterface::class);
        $inner->expects($this->once())->method('dispatch');

        $container = $this->containerWithTags(['other.tag' => [$other]]);

        $dispatcher = (new MiddlewareAwareDecorator())($inner, $container);
        $dispatcher->dispatch(new \stdClass());

        $this->assertFalse($called);
    }

/**
     * @param array<string, callable[]> $tags
     */
    private function containerWithTags(array $tags): ContainerInterface
    {
        $registry = $this->createStub(TagRegistryInterface::class);
        $registry->method('getTagged')->willReturnCallback(fn(string $tag): array => $tags[$tag] ?? []);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(TagRegistryInterface::class)->willReturn($registry);

        return $container;
    }
}
