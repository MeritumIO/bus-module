<?php

namespace Meritum\BusModule;

use Psr\Container\ContainerInterface;
use Georgeff\Bus\DispatcherInterface;
use Georgeff\Bus\MiddlewareAwareDispatcher;
use Georgeff\Kernel\DI\TagRegistryInterface;

final class MiddlewareAwareDecorator
{
    public function __invoke(mixed $inner, ContainerInterface $container): DispatcherInterface
    {
        assert($inner instanceof DispatcherInterface);

        return new MiddlewareAwareDispatcher(
            $inner,
            $this->getMiddleware($container->get(TagRegistryInterface::class))
        );
    }

    /**
     * @return callable[]
     */
    private function getMiddleware(TagRegistryInterface $tags): array
    {
        /** @var callable[] $middleware */
        $middleware = $tags->getTagged('bus.middleware');

        return $middleware;
    }
}
