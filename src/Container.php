<?php

namespace Rlaravel\ServiceContainer;

use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Rlaravel\ServiceContainer\Exceptions\ContainerException;

/**
 * Class Container
 * @package Rlaravel\ServiceContainer
 */
class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var array
     */
    protected $shared = [];

    /**
     * @param string $key
     * @param $resolver
     */
    public function bind(string $key, $resolver)
    {
        $this->bindings[$key] = [
            'resolver' => $resolver
        ];
    }

    /**
     * @param string $key
     * @param $object
     */
    public function instance(string $key, $object)
    {
        $this->shared[$key] = $object;
    }

    /**
     * @param string $key
     * @return mixed|object
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function make(string $key, array $arguments = [])
    {
        if (isset($this->shared[$key])) {
            return $this->shared[$key];
        }

        if (isset($this->bindings[$key])) {
            $resolver = $this->bindings[$key]['resolver'];
        } else {
            $resolver = $key;
        }

        if ($resolver instanceof Closure) {
            return $resolver($this);
        }

        return $this->build($resolver, $arguments);
    }

    /**
     * @param $name
     * @return mixed|object
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function build($name, array $arguments = [])
    {
        $reflection = new ReflectionClass($name);

        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("[{$name}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return new $name;
        }

        $constructorParameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($constructorParameters as $constructorParameter) {

            $paramenterName = $constructorParameter->getName();

            if (isset($arguments[$paramenterName])) {
                $dependencies[] = $arguments[$paramenterName];

                continue;
            }

            try {
                $parameterClass = $constructorParameter->getClass();
            } catch (ReflectionException $exception) {
                throw new ContainerException("Unable to build [{$name}]: " . $exception->getMessage(), null, $exception);
            }

            if ($parameterClass != null) {
                $parameterClassName = $parameterClass->getName();
                $dependencies[] = $this->build($parameterClassName);
            } else {
                throw new ContainerException("Please provide the value of the parameter [{$paramenterName}].");
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     */
    public function get($id)
    {
        return $this->make($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->bindings[$id]);
    }
}