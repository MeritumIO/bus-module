<?php

namespace Meritum\BusModule\Test;

use Georgeff\Bus\DispatcherInterface;
use Georgeff\Bus\HandlerLocatorInterface;
use Georgeff\Bus\HandlerResolverInterface;
use Georgeff\Bus\Locator\ClassNameLocator;
use Georgeff\Bus\MiddlewareAwareDispatcher;
use Georgeff\Bus\Resolver\PsrContainerResolver;
use Georgeff\Kernel\Contract\ModuleInterface;
use Georgeff\Kernel\Environment\Testing;
use Georgeff\Kernel\Kernel;
use Georgeff\Kernel\KernelInterface;
use Meritum\BusModule\BusModule;
use Meritum\BusModule\BusOption;
use PHPUnit\Framework\TestCase;

final class BusModuleTest extends TestCase
{
    private function bootKernel(ModuleInterface ...$modules): Kernel
    {
        $kernel = new Kernel(new Testing());

        foreach ($modules as $module) {
            $kernel->addModule($module);
        }

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

    public function test_handler_locator_can_be_replaced_with_define(): void
    {
        $locator = new class implements HandlerLocatorInterface {
            public function locate(string $commandName): string
            {
                return $commandName . 'Handler';
            }
        };

        $kernel = $this->bootKernel(new BusModule(), new class ($locator) implements ModuleInterface {
            public function __construct(private readonly HandlerLocatorInterface $locator) {}

            public function register(KernelInterface $kernel): void
            {
                $kernel->define(HandlerLocatorInterface::class, fn() => $this->locator);
            }
        });

        $this->assertSame($locator, $kernel->getContainer()->get(HandlerLocatorInterface::class));
    }

    public function test_handler_resolver_can_be_replaced_with_define(): void
    {
        $resolver = $this->createStub(HandlerResolverInterface::class);

        $kernel = $this->bootKernel(new BusModule(), new class ($resolver) implements ModuleInterface {
            public function __construct(private readonly HandlerResolverInterface $resolver) {}

            public function register(KernelInterface $kernel): void
            {
                $kernel->define(HandlerResolverInterface::class, fn() => $this->resolver);
            }
        });

        $this->assertSame($resolver, $kernel->getContainer()->get(HandlerResolverInterface::class));
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

    public function test_wraps_dispatcher_with_middleware_aware_dispatcher(): void
    {
        $kernel = $this->bootKernel(new BusModule());

        $this->assertInstanceOf(MiddlewareAwareDispatcher::class, $kernel->getContainer()->get(DispatcherInterface::class));
    }

    public function test_tagged_middleware_runs_before_the_handler(): void
    {
        $calls = new \ArrayObject();

        $kernel = $this->bootKernel(new BusModule(), new class ($calls) implements ModuleInterface {
            public function __construct(private readonly \ArrayObject $calls) {}

            public function register(KernelInterface $kernel): void
            {
                $kernel->define(HandlerResolverInterface::class, fn() => new class ($this->calls) implements HandlerResolverInterface {
                    public function __construct(private readonly \ArrayObject $calls) {}

                    public function resolve(string $commandName): callable
                    {
                        return function (object $command): string {
                            $this->calls[] = 'handler';

                            return 'handled';
                        };
                    }
                });

                $kernel->define('test.middleware', fn() => function (object $command, callable $next): mixed {
                    $this->calls[] = 'middleware';

                    return $next($command);
                })->tag(BusOption::MiddlewareTag->value);
            }
        });

        $result = $kernel->getContainer()->get(DispatcherInterface::class)->dispatch(new \stdClass());

        $this->assertSame('handled', $result);
        $this->assertSame(['middleware', 'handler'], $calls->getArrayCopy());
    }
}
