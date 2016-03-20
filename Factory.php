<?php

namespace Orchestra\Authorization;

use Illuminate\Support\Arr;
use Orchestra\Contracts\Auth\Guard;
use Orchestra\Contracts\Memory\Provider;
use Orchestra\Contracts\Authorization\Factory as FactoryContract;

class Factory implements FactoryContract
{
    /**
     * Auth instance.
     *
     * @var \Orchestra\Contracts\Auth\Guard
     */
    protected $auth;

    /**
     * Cache ACL instance so we can reuse it on multiple request.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Construct a new Environment.
     *
     * @param  \Orchestra\Contracts\Auth\Guard  $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Initiate a new ACL Container instance.
     *
     * @param  string  $name
     * @param  \Orchestra\Contracts\Memory\Provider  $memory
     *
     * @return \Orchestra\Contracts\Authorization\Authorization
     */
    public function make($name = null, Provider $memory = null)
    {
        $name = ! is_null($name) ? $name : 'default';

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = (new Authorization($name, $memory))->setAuthenticator($this->auth);
        }

        return $this->drivers[$name];
    }

    /**
     * Register an ACL Container instance with Closure.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     *
     * @return \Orchestra\Contracts\Authorization\Authorization
     */
    public function register($name, $callback = null)
    {
        if (is_callable($name)) {
            $callback = $name;
            $name     = null;
        }

        $instance = $this->make($name);

        $callback($instance);

        return $instance;
    }

    /**
     * Manipulate and synchronize roles.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        $response = [];

        foreach ($this->drivers as $acl) {
            $response[] = $acl->{$method}(...$parameters);
        }

        return $response;
    }

    /**
     * Shutdown/finish all ACL.
     *
     * @return $this
     */
    public function finish()
    {
        // Re-sync before shutting down.
        foreach ($this->drivers as $acl) {
            $acl->sync();
        }

        $this->drivers = [];

        return $this;
    }

    /**
     * Get all ACL instances.
     *
     * @return array
     */
    public function all()
    {
        return $this->drivers;
    }

    /**
     * Get ACL instance by name.
     *
     * @param  string  $name
     *
     * @return \Orchestra\Contracts\Authorization\Authorization
     */
    public function get($name)
    {
        return Arr::get($this->drivers, $name);
    }
}
