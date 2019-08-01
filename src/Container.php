<?php
declare(strict_types=1);

namespace harlequiin\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    public function set($id, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }

        $this->instances[$id] = $concrete;
    }

    public function has($id): bool
    {
        return isset($this->instances[$id]);        
    }

    public function get($id, $params = [])
    {
        if (!isset($this->instances[$id])) {
            $this->set($id);
        }

        return $this->resolve($this->instances[$id], $params);  
    }

    public function resolve($concrete, $params)
    {
        if ($concrete instanceOf Closure) {
            return $concrete($this, $params);
        }

        $reflector = new \ReflectionClass($concrete);
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class `{$concrete}` is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return $reflector->newInstance();
        } 

        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    public function getDependencies(array $parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if ($dependency === null) {
                if ($dependency->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Can not resolve class dependency '{$parameter->name}'");
                }
            } else {
                $dependencies[] = $this->get($dependency->getName());
            }
        }

        return $dependencies;
    }
}
