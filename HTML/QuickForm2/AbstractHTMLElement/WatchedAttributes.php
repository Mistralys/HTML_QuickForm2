<?php

declare(strict_types=1);

namespace HTML\QuickForm2\AbstractHTMLElement;

use HTML_QuickForm2_InvalidArgumentException;

/**
 * Handles watched and readonly attributes.
 *
 * @package HTML_QuickForm2
 * @subpackage HTMLElement
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class WatchedAttributes
{
    public const TYPE_WATCHED = 1;
    public const TYPE_READONLY = 2;

    public const ERROR_ATTRIBUTE_IS_READONLY = 102701;

    /**
     * @var array<string,array{type:int,callback:callable|NULL}>
     */
    private array $attributes = array();

    /**
     * @var callable|NULL
     */
    private $changeCallback = null;

    /**
     * The callback gets a single parameter:
     *
     * - string|NULL $value
     *
     * @param string $name
     * @param callable $callback
     * @return $this
     */
    public function setWatched(string $name, callable $callback) : self
    {
        return $this->setType($name, self::TYPE_WATCHED, $callback);
    }

    public function setReadonly(string $name) : self
    {
        return $this->setType($name, self::TYPE_READONLY);
    }

    public function removeAttribute(string $name) : self
    {
        $name = strtolower($name);

        if(isset($this->attributes[$name]))
        {
            unset($this->attributes[$name]);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param int $type
     * @param callable|null $callback
     * @return $this
     */
    private function setType(string $name, int $type, ?callable $callback=null) : self
    {
        $name = strtolower($name);

        $this->attributes[$name] = array(
            'type' => $type,
            'callback' => $callback
        );

        return $this;
    }

    public function getType(string $name) : ?int
    {
        return $this->attributes[$name]['type'] ?? null;
    }

    public function isWatched(string $name) : bool
    {
        return $this->isType($name, self::TYPE_WATCHED);
    }

    public function isReadonly(string $name) : bool
    {
        return $this->isType($name, self::TYPE_READONLY);
    }

    private function isType(string $name, int $type) : bool
    {
        $name = strtolower($name);

        return isset($this->attributes[$name]) && $this->attributes[$name]['type'] === $type;
    }

    public function isHandled(string $name) : bool
    {
        return $this->isWatched($name) || $this->isReadonly($name);
    }

    /**
     * @param string $name
     * @param string|null $newValue
     * @param string|null $oldValue
     * @return void
     * @throws HTML_QuickForm2_InvalidArgumentException
     */
    public function handleChanged(string $name, ?string $newValue=null, ?string $oldValue=null) : void
    {
        if($newValue === $oldValue)
        {
            return;
        }

        if($this->isReadonly($name))
        {
            throw new HTML_QuickForm2_InvalidArgumentException(
                sprintf(
                    "The attribute [%s] is read-only.",
                    strtolower($name)
                ),
                self::ERROR_ATTRIBUTE_IS_READONLY
            );
        }

        if($this->isWatched($name))
        {
            $callback = $this->attributes[$name]['callback'];

            if ($callback !== null)
            {
                $callback($newValue);
            }
        }

        if(isset($this->changeCallback))
        {
            call_user_func($this->changeCallback, $name, $oldValue, $newValue);
        }
    }

    public function getWatched() : array
    {
        return $this->getNamesByType(self::TYPE_WATCHED);
    }

    public function getReadonly() : array
    {
        return $this->getNamesByType(self::TYPE_READONLY);
    }

    public function getNamesByType(int $type) : array
    {
        $result = array();
        $names = array_keys($this->attributes);

        foreach($names as $name)
        {
            if($this->attributes[$name]['type'] === $type)
            {
                $result[] = $name;
            }
        }

        return $result;
    }

    /**
     * Sets a callback to trigger every time a watched attribute's
     * value changes.
     *
     * @param callable $callback
     * @return $this
     */
    public function setChangeCallback(callable $callback) : self
    {
        $this->changeCallback = $callback;

        return $this;
    }
}
