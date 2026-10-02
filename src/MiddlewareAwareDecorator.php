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

        /** @var TagRegistryInterface $tags */
        $tags = $container->get(TagRegistryInterface::class);

        return new MiddlewareAwareDispatcher(
            $inner,
            $this->getMiddleware($tags)
        );
    }

    /**
     * @return callable[]
     */
    private function getMiddleware(TagRegistryInterface $tags): array
    {
        /** @var callable[] $middleware */
        $middleware = $tags->getTagged(BusOption::MiddlewareTag->value);

        return $middleware;
    }
}
