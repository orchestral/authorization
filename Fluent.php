<?php namespace Orchestra\Authorization;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Fluent
{
    /**
     * Collection name.
     *
     * @var string
     */
    protected $name;

    /**
     * Collection of this instance.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Construct a new instance.
     *
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Add a key to collection.
     *
     * @param  string  $key
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function add($key)
    {
        if (is_null($key)) {
            throw new InvalidArgumentException("Can't add NULL to {$this->name}.");
        }

        // Type-hint the attribute value of an Eloquent result, if it was
        // given instead of a string.
        if ($key instanceof Eloquent) {
            $key = $key->getAttribute('name');
        }

        if ($this->has($key)) {
            return false;
        }

        array_push($this->items, Keyword::make($key)->getSlug());

        return true;
    }

    /**
     * Add multiple key to collection.
     *
     * @param  array  $keys
     *
     * @return bool
     */
    public function attach(array $keys)
    {
        foreach ($keys as $key) {
            $this->add($key);
        }

        return true;
    }

    /**
     * Remove multiple key to collection.
     *
     * @param  array  $keys
     *
     * @return bool
     */
    public function detach(array $keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }

        return true;
    }

    /**
     * Check if an id is set in the collection.
     *
     * @param  int  $id
     *
     * @return bool
     */
    public function exist($id)
    {
        return Keyword::make($id)->hasIn($this->items);
    }

    /**
     * Filter request.
     *
     * @param  string|array  $request
     *
     * @return array
     */
    public function filter($request)
    {
        if (is_array($request)) {
            return $request;
        } elseif ($request === '*') {
            return $this->get();
        } elseif ($request[0] === '!') {
            return array_diff($this->get(), [substr($request, 1)]);
        }

        return [$request];
    }

    /**
     * Find collection key from a name.
     *
     * @param  mixed  $name
     *
     * @return int|null
     */
    public function findKey($name)
    {
        if (! (is_numeric($name) && $this->exist($name))) {
            $name = (string) $this->search($name);
        }

        return $name;
    }

    /**
     * Get the items.
     *
     * @return array
     */
    public function get()
    {
        return $this->items;
    }

    /**
     * Determine whether a key exists in collection.
     *
     * @param  string  $key
     *
     * @return bool
     */
    public function has($key)
    {
        $key = strval($key);
        $key = Keyword::make($key)->getSlug();

        return (! empty($key) && in_array($key, $this->items));
    }

    /**
     * Remove a key from collection.
     *
     * @param  string  $key
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function remove($key)
    {
        if (is_null($key)) {
            throw new InvalidArgumentException("Can't remove NULL from {$this->name}.");
        }

        if (! is_null($id = $this->search($key))) {
            unset($this->items[$id]);
            return true;
        }

        return false;
    }

    /**
     * Rename a key from collection.
     *
     * @param  string  $from
     * @param  string  $to
     *
     * @return bool
     */
    public function rename($from, $to)
    {
        $key = $this->search($from);

        if (is_null($key)) {
            return false;
        }

        $this->items[$key] = Keyword::make($to)->getSlug();

        return true;
    }

    /**
     * Get the ID from a key.
     *
     * @param  string  $key
     *
     * @return int
     */
    public function search($key)
    {
        $id = Keyword::make($key)->searchIn($this->items);

        if (false === $id) {
            return;
        }

        return $id;
    }
}
