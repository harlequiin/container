<?php
declare(strict_types=1);

namespace harlequiin\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    public function set(string $id, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }

        $this->instances[$id] = $concrete;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]);        
    }

    /**
     * @throws NotFoundExceptionInterface Dependency can't be resolved
     * @throws ContainerExceptionInterface Error while retrieving the entry
     *
     * @return mixed Entry
     */
    public function get(string $id, array $params = [])
    {
        // register, if not registered (a basic "autowiring" behavior)
        if (!isset($this->instances[$id])) {
            $this->set($id);
        }

        return $this->resolve($this->instances[$id], $params);  
    }

    /**
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry
     */
    private function resolve($concrete, array $params)
    {
        if ($concrete instanceOf Closure) {
            return $concrete($this, $params);
        }

        $reflector = new \ReflectionClass($concrete);
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class `{$concrete}` is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return $reflector->newInstance();
        } 

        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @throws NotFoundExceptionInterface Dependency can't be resolved
     */
    private function getDependencies(array $params): array
    {
        $dependencies = [];
        foreach ($params as $param) {
            $dependency = $param->getClass();
            if ($dependency === null) {
                if ($dependency->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    throw new NotFoundException("Can not resolve class dependency '{$param->name}'");
                }
            } else {
                $dependencies[] = $this->get($dependency->getName());
            }
        }

        return $dependencies;
    }
}
