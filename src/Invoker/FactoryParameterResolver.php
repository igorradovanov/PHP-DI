<?php

declare(strict_types=1);

namespace DI\Invoker;

use DI\Factory\RequestedEntry;
use Invoker\ParameterResolver\ParameterResolver;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;

/**
 * Inject the container, the definition or any other service using type-hints.
 *
 * {@internal This class is similar to TypeHintingResolver and TypeHintingContainerResolver,
 *            we use this instead for performance reasons}
 *
 * @author Quim Calpe <quim@kalpe.com>
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class FactoryParameterResolver implements ParameterResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) : array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterClass = $parameter->getClass();

            if (!$parameterClass) {
                continue;
            }

            if ($parameterClass->name === ContainerInterface::class) {
                $resolvedParameters[$index] = $this->container;
            } elseif ($parameterClass->name === RequestedEntry::class) {
                $resolvedParameters[$index] = $providedParameters[RequestedEntry::class];
            } elseif ($this->container->has($parameterClass->name)) {
                $resolvedParameters[$index] = $this->container->get($parameterClass->name);
            }
        }

        return $resolvedParameters;
    }
}
