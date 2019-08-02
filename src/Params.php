<?php

namespace Nealis\Params;

use Symfony\Component\HttpFoundation\Request;

class Params extends \ArrayObject
{
    protected $namespaceCharacter = '.';

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public static function getInstanceFromRequest(Request $request)
    {
        if (!empty($request->get('json', []))) {
            $options = json_decode($request->get('json'), true);
        } else {
            $options = array_merge($request->query->all(), $request->request->all());
        }

        return new Params($options);
    }

    public function has($name)
    {
        // reference mismatch: if fixed, re-introduced in array_key_exists; keep as it is
        $attributes = $this->resolveAttributePath($name);
        $name = $this->resolveKey($name);

        if (null === $attributes) {
            return false;
        }

        return array_key_exists($name, $attributes);
    }

    public function get($name, $default = null, $callback = null)
    {
        $attributes = $this->resolveAttributePath($name);
        $name = $this->resolveKey($name);

        if (null === $attributes) {
            return $default;
        }

        $value = array_key_exists($name, $attributes) ? $attributes[$name] : $default;

        if ($callback !== null && (is_callable($callback) || is_string($callback))) {
            return $callback($value);
        }

        return $value;
    }

    public function set($name, $value)
    {
        $attributes = &$this->resolveAttributePath($name, true);
        $name = $this->resolveKey($name);
        $attributes[$name] = $value;
        return $value;
    }

    public function remove($name)
    {
        $retval = null;
        $attributes = &$this->resolveAttributePath($name);
        $name = $this->resolveKey($name);
        if (null !== $attributes && array_key_exists($name, $attributes)) {
            $retval = $attributes[$name];
            unset($attributes[$name]);
        }

        return $retval;
    }

    public function removeEmpty($fieldName)
    {
        if(empty($this->get($fieldName))) {
            $this->remove($fieldName);
        }
    }

    protected function &resolveAttributePath($name, $writeContext = false)
    {
        $array = &$this;
        $name = (strpos($name, $this->namespaceCharacter) === 0) ? substr($name, 1) : $name;

        if (!$name) {
            return $array;
        }

        $parts = explode($this->namespaceCharacter, $name);
        if (count($parts) < 2) {
            if (!$writeContext) {
                return $array;
            }

            $array[$parts[0]] = array();

            return $array;
        }

        unset($parts[count($parts) - 1]);

        foreach ($parts as $part) {
            if (null !== $array && !array_key_exists($part, $array)) {
                $array[$part] = $writeContext ? array() : null;
            }

            $array = &$array[$part];
        }

        return $array;
    }

    protected function resolveKey($name)
    {
        if (false !== $pos = strrpos($name, $this->namespaceCharacter)) {
            $name = substr($name, $pos + 1);
        }

        return $name;
    }

    public function init($namespace, $default)
    {
        if (!$this->has($namespace)) {
            $this->set($namespace, $default);
        }
        else if ($this->get($namespace) === null) {
            $this->set($namespace, $default);
        }

        return $this->get($namespace);
    }

    public function setNamespaceCharacter($namespaceCharacter)
    {
        $this->namespaceCharacter = $namespaceCharacter;
    }
}

