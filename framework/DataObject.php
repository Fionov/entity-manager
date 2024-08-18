<?php

namespace Framework;

use AllowDynamicProperties;
use Exception;

/**
 * Universal data container
 * Based on Magento 2 DataObject
 *
 * @see https://github.com/magento/magento2/blob/2.4-develop/lib/internal/Magento/Framework/DataObject.php
 */
#[AllowDynamicProperties]
class DataObject
{
    /**
     * Object attributes
     *
     * @var array
     */
    private array $data;

    /**
     * Setter/Getter underscore transformation cache
     *
     * @var array
     */
    protected static array $_underscoreCache = [];

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Overwrite data in the object.
     *
     * @param string|array $key
     * @param mixed $value
     * @return static
     */
    public function setData(string|array $key, mixed $value = null): static
    {
        if ($key === (array)$key) {
            $this->data = $key;
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Unset data from the object.
     *
     * @param string $key
     * @return static
     */
    public function unsetData(string $key): static
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }

        return $this;
    }

    /**
     * Object data getter
     *
     * @param string $key
     * @return mixed
     */
    public function getData(string $key): mixed
    {
        return $this->_getData($key);
    }

    /**
     * Get value from _data array without parse key
     *
     * @param string $key
     * @return mixed
     */
    protected function _getData(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Convert data to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Set/Get/Uns/Has attribute wrapper
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $method, array $args): mixed
    {
        $key = $this->_underscore(substr($method, 3));
        $value = $args[0] ?? null;

        return match(substr($method, 0, 3)) {
            'get' => $this->getData($key),
            'set' => $this->setData($key, $value),
            'uns' => $this->unsetData($key),
            'has' => isset($this->data[$key]),
            default => throw new Exception(sprintf('Invalid method %1::%2', get_class($this), $method)),
        };
    }

    /**
     * Converts field names for setters and getters
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name
     * @return string
     */
    protected function _underscore(string $name): string
    {
        if (isset(self::$_underscoreCache[$name])) {
            $result = self::$_underscoreCache[$name];
        } else {
            $result = strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "_$1", $name), '_'));
            self::$_underscoreCache[$name] = $result;
        }

        return $result;
    }
}
