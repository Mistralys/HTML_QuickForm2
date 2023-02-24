<?php
/**
 * PHP >=7.4 port of HTML_Common2
 *
 * LICENSE:
 *
 * Copyright (c) 2004-2021, Alexey Borzov <avb@php.net>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @category HTML
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link     https://pear.php.net/package/HTML_Common2
 */

declare(strict_types=1);

namespace HTML\QuickForm2;

use ArrayAccess;
use Closure;
use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;
use HTML_QuickForm2_EventHandler;
use HTML_QuickForm2_InvalidArgumentException;

/**
 * Base class for HTML classes
 *
 * Implements methods for working with HTML attributes, parsing and generating
 * attribute strings. Port of HTML_Common class for PHP4 originally written by
 * Adam Daniel with contributions from numerous other developers.
 *
 * @package HTML_QuickForm2
 * @author Alexey Borzov <avb@php.net>
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 * @category HTML
 * @license  https://opensource.org/licenses/bsd-license.php New BSD License
 */
abstract class AbstractHTMLElement implements ArrayAccess
{
    /**
     * Constant for predefined 'linebreak' option
     */
    public const OPTION_LINEBREAK = 'linebreak';

    public const OPTION_LOGGING = 'logging';

    /**
     * Associative array of attributes
     * @var array<string,string>
     */
    protected array $attributes = [];

    protected WatchedAttributes $watchedAttributes;

    /**
     * Indentation level of the element
     * @var int
     */
    private int $_indentLevel = 0;

    /**
     * Comment associated with the element
     * @var string
     */
    private string $_comment = '';

    protected static int $elementCounter = 0;
    protected int $instanceID;
    protected bool $initDone = false;

    /**
     * Parses the HTML attributes given as string
     *
     * @param string $attrString HTML attribute string
     *
     * @return array<string|number,string|number> An associative array of attributes
     */
    protected static function parseAttributes(string $attrString) : array
    {
        $attributes = [];
        if (preg_match_all(
            "/(([A-Za-z_:]|[^\\x00-\\x7F])([A-Za-z0-9_:.-]|[^\\x00-\\x7F])*)" .
            "([ \\n\\t\\r]+)?(=([ \\n\\t\\r]+)?(\"[^\"]*\"|'[^']*'|[^ \\n\\t\\r]*))?/",
            $attrString,
            $regs
        ))
        {
            for ($i = 0, $iMax = count($regs[1]); $i < $iMax; $i++)
            {
                $name = trim($regs[1][$i]);
                $check = trim($regs[0][$i]);
                $value = trim($regs[7][$i]);

                if ($name === $check)
                {
                    $attributes[strtolower($name)] = strtolower($name);
                }
                else
                {
                    if (!empty($value) && ($value[0] === '\'' || $value[0] === '"'))
                    {
                        $value = substr($value, 1, -1);
                    }
                    $attributes[strtolower($name)] = $value;
                }
            }
        }
        return $attributes;
    }

    /**
     * Creates a valid attribute array from either a string or an array
     *
     * @param string|array<string|number,string|number>|NULL $attributes Array of attributes or HTML attribute string
     *
     * @return array<string,string> An associative array of attributes
     */
    protected static function prepareAttributes($attributes) : array
    {
        if (is_string($attributes))
        {
            return self::parseAttributes($attributes);
        }

        if (is_array($attributes))
        {
            return self::parseAttributeArray($attributes);
        }

        return array();
    }

    protected static function parseAttributeArray(array $attributes) : array
    {
        $prepared = [];

        foreach ($attributes as $key => $value)
        {
            if (is_int($key))
            {
                $key = strtolower((string)$value);
                $prepared[$key] = $key;
            }
            else
            {
                $prepared[strtolower((string)$key)] = (string)$value;
            }
        }

        return $prepared;
    }

    /**
     * Removes an attribute from an attribute array
     *
     * @param array  &$attributes Attribute array
     * @param string $name Name of attribute to remove
     */
    protected static function removeAttributeArray(array &$attributes, string $name) : void
    {
        unset($attributes[strtolower($name)]);
    }

    /**
     * Creates HTML attribute string from array
     *
     * @param array $attributes Attribute array
     *
     * @return string Attribute string
     */
    protected static function getAttributesString(array $attributes) : string
    {
        $str = '';
        $charset = GlobalOptions::getCharset();

        foreach ($attributes as $key => $value)
        {
            $str .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, $charset) . '"';
        }

        return $str;
    }

    /**
     * Class constructor, sets default attributes
     *
     * @param array|string|NULL $attributes Array of attribute 'name' => 'value' pairs
     *                                 or HTML attribute string
     */
    public function __construct($attributes = null)
    {
        self::$elementCounter++;

        $this->instanceID = self::$elementCounter;
        $this->watchedAttributes = new WatchedAttributes();
        $this->watchedAttributes->setChangeCallback(Closure::fromCallable(array($this, 'handleAttributeChanged')));

        $this->initWatchedAttributes($this->watchedAttributes);
        $this->mergeAttributes($attributes);

        $this->initDone = true;
    }

    /**
     * @return int
     */
    public function getInstanceID() : int
    {
        return $this->instanceID;
    }

    abstract protected function initWatchedAttributes(WatchedAttributes $attributes) : void;
    abstract protected function handleAttributeChanged(string $name, ?string $oldValue, ?string $newValue) : void;

    /**
     * Sets the value of the attribute
     *
     * @param string $name Attribute name
     * @param string|int|float|NULL $value Attribute value (will be set to $name if NULL)
     *
     * @return $this
     */
    public function setAttribute(string $name, $value = null) : self
    {
        if($value !== null)
        {
            $value = (string)$value;
        }

        // Initialization not done, or attribute is not watched:
        // we set it without using the change event handler (which
        // is very likely to cause infinite call loops).
        if(!$this->initDone)
        {
            return $this->_setAttribute($name, $value);
        }

        if(!$this->watchedAttributes->isHandled($name))
        {
            return $this->_setAttribute($name, $value, true);
        }

        // Initialization is done, which means that the attribute
        // collection the element starts with is complete, and that
        // attribute change events can be handled.
        $this->watchedAttributes->handleChanged(
            $name,
            $value,
            $this->getAttribute($name)
        );

        return $this;
    }

    /**
     * Sets an attribute value without checking whether it
     * is part of any watched attributes, to set the value
     * directly in the attribute collection.
     *
     * @param string $name
     * @param string|NULL $value
     * @param bool $triggerEvent
     * @return $this
     */
    protected function _setAttribute(string $name, ?string $value, bool $triggerEvent=false) : self
    {
        $name = strtolower($name);

        if ($value === null)
        {
            $value = $name;
        }

        $oldValue = $this->attributes[$name] ?? null;

        $this->attributes[$name] = $value;

        if($triggerEvent)
        {
            $this->handleAttributeChanged($name, $oldValue, $value);
        }

        return $this;
    }

    /**
     * Adds or removes a property based on the specified enabled status.
     *
     * @param string $name
     * @param bool $enabled
     * @return $this
     */
    public function setPropertyEnabled(string $name, bool $enabled) : self
    {
        if ($enabled)
        {
            return $this->setAttribute($name);
        }

        return $this->removeAttribute($name);
    }

    public function hasProperty(string $name) : bool
    {
        return $this->hasAttribute($name);
    }

    public function addProperty(string $name) : self
    {
        return $this->setAttribute($name);
    }

    public function removeProperty(string $name) : self
    {
        return $this->removeAttribute($name);
    }

    /**
     * Returns the value of an attribute
     *
     * @param string $name Attribute name
     *
     * @return string|null Attribute value, null if attribute does not exist
     */
    public function getAttribute(string $name) : ?string
    {
        $name = strtolower($name);

        return $this->attributes[$name] ?? null;
    }

    /**
     * Sets the attributes
     *
     * @param string|array<string|number,string|number> $attributes Array of attribute 'name' => 'value' pairs
     *                                 or HTML attribute string
     *
     * @return $this
     */
    public function setAttributes($attributes) : self
    {
        $attributes = self::prepareAttributes($attributes);
        $watched = [];

        $names = $this->watchedAttributes->getWatched();

        foreach ($names as $watchedKey)
        {
            if (isset($attributes[$watchedKey]))
            {
                $this->setAttribute($watchedKey, $attributes[$watchedKey]);
                unset($attributes[$watchedKey]);
            }
            else
            {
                $this->removeAttribute($watchedKey);
            }
            if (isset($this->attributes[$watchedKey]))
            {
                $watched[$watchedKey] = $this->attributes[$watchedKey];
            }
        }

        $this->attributes = array_merge($watched, $attributes);
        return $this;
    }

    /**
     * Returns the attribute array or string
     *
     * @param bool $asString Whether to return attributes as string
     *
     * @return array<string|number,string|number>|string
     */
    public function getAttributes(bool $asString = false)
    {
        if ($asString)
        {
            return self::getAttributesString($this->attributes);
        }

        return $this->attributes;
    }

    /**
     * Merges the existing attributes with the new ones
     *
     * @param array<string|number,string|number>|string|NULL $attributes Array of attribute 'name' => 'value' pairs
     *                                 or HTML attribute string
     *
     * @return $this
     */
    protected function mergeAttributes($attributes) : self
    {
        if ($attributes === null)
        {
            return $this;
        }

        $attributes = self::prepareAttributes($attributes);

        // We don't want to merge any of the watched attributes.
        $names = $this->watchedAttributes->getWatched();
        foreach ($names as $watchedKey)
        {
            unset($attributes[$watchedKey]);
        }

        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Removes an attribute
     *
     * @param string $name Name of attribute to remove
     *
     * @return $this
     * @throws HTML_QuickForm2_InvalidArgumentException
     */
    public function removeAttribute(string $name) : self
    {
        // During element initialization, no events are triggered.
        if (!$this->initDone)
        {
            return $this->_removeAttribute($name);
        }

        if($this->watchedAttributes->isHandled($name))
        {
            $this->watchedAttributes->handleChanged(
                $name,
                null,
                $this->getAttribute($name)
            );
        }

        return $this;
    }

    /**
     * Removes an attribute without triggering any change events.
     *
     * @param string $attribute
     * @return $this
     */
    protected function _removeAttribute(string $attribute) : self
    {
        self::removeAttributeArray($this->attributes, $attribute);
        return $this;
    }

    /**
     * Sets the indentation level
     *
     * @param int $level Indentation level
     *
     * @return $this
     */
    public function setIndentLevel(int $level) : self
    {
        if ($level >= 0)
        {
            $this->_indentLevel = $level;
        }

        return $this;
    }

    /**
     * Gets the indentation level
     *
     * @return int
     */
    public function getIndentLevel() : int
    {
        return $this->_indentLevel;
    }

    /**
     * Returns the string to indent the element
     *
     * @return string
     */
    protected function getIndent() : string
    {
        return str_repeat(
            GlobalOptions::getIndentChar(),
            $this->getIndentLevel()
        );
    }

    /**
     * Sets the comment for the element
     *
     * @param string $comment String to output as HTML comment
     *
     * @return $this
     */
    public function setComment(string $comment) : self
    {
        $this->_comment = $comment;
        return $this;
    }

    /**
     * Returns the comment associated with the element
     *
     * @return string
     */
    public function getComment() : string
    {
        return $this->_comment;
    }

    /**
     * Checks whether the element has given CSS class
     *
     * @param string $class CSS Class name
     *
     * @return bool
     */
    public function hasClass(string $class) : bool
    {
        $regex = '/(^|\s)' . preg_quote($class, '/') . '(\s|$)/';
        return (bool)preg_match($regex, $this->getAttribute('class'));
    }

    private function splitClassesString(string $classes) : array
    {
        return preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Adds the given CSS class(es) to the element
     *
     * @param string $classes Class name, multiple class names separated by whitespace.
     *
     * @return $this
     */
    public function addClass(string $classes) : self
    {
        $newNames = $this->splitClassesString($classes);
        $allNames = $this->splitClassesString((string)$this->getAttribute('class'));

        foreach ($newNames as $className)
        {
            if (!in_array($className, $allNames, true))
            {
                $allNames[] = $className;
            }
        }

        $this->setAttribute('class', implode(' ', $allNames));

        return $this;
    }

    public function getClasses() : array
    {
        return $this->splitClassesString((string)$this->getAttribute('class'));
    }

    /**
     * Removes the given CSS class(es) from the element
     *
     * @param string $class Class name, multiple class names separated by whitespace.
     *
     * @return $this
     */
    public function removeClass(string $class) : self
    {
        $newList = array_diff(
            $this->getClasses(),
            $this->splitClassesString($class)
        );

        if (empty($newList))
        {
            $this->removeAttribute('class');
        }
        else
        {
            $this->setAttribute('class', implode(' ', $newList));
        }

        return $this;
    }

    /**
     * Returns the HTML representation of the element
     *
     * This magic method allows using the instances of HTML_Common2 in string
     * contexts
     *
     * @return string
     */
    abstract public function __toString();

    /**
     * Called if trying to change an attribute with name in $watchedAttributes
     *
     * This method is called for each attribute whose name is in the
     * $watchedAttributes array and which is being changed by setAttribute(),
     * setAttributes() or mergeAttributes() or removed via removeAttribute().
     * Note that the operation for the attribute is not carried on after calling
     * this method, it is the responsibility of this method to change or remove
     * (or not) the attribute.
     *
     * @param string $name Attribute name
     * @param string|NULL $value Attribute value, null if attribute is being removed
     */
    protected function onAttributeChange(string $name, ?string $value = null) : void
    {
    }

    /**
     * Checks if the element has the specified attribute,
     * even if it is currently empty.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name) : bool
    {
        return isset($this->attributes[strtolower($name)]);
    }

    public function isAttributeEmpty(string $name) : bool
    {
        $name = strtolower($name);

        return !isset($this->attributes[$name]) || empty($this->attributes[$name]);
    }

    // region: Array access interface

    /**
     * Whether an offset (HTML attribute) exists
     *
     * @param mixed $offset An offset to check for.
     *
     * @return boolean Returns true on success or false on failure.
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset) : bool
    {
        return $this->hasAttribute((string)$offset);
    }

    /**
     * Returns the value at specified offset (i.e. attribute name)
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return string|null
     * @see getAttribute()
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset) : ?string
    {
        return $this->getAttribute((string)$offset);
    }

    /**
     * Assigns a value to the specified offset (i.e. attribute name)
     *
     * @param mixed $offset The offset to assign the value to
     * @param mixed $value The value to set
     *
     * @return void
     * @see setAttribute()
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value) : void
    {
        if ($offset !== null)
        {
            $this->setAttribute((string)$offset, $value);
        }
        else
        {
            // handles $foo[] = 'disabled';
            $this->setAttribute((string)$value);
        }
    }

    /**
     * Unsets an offset (i.e. removes an attribute)
     *
     * @param mixed $offset The offset to unset
     *
     * @return void
     * @see removeAttribute()
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset) : void
    {
        $this->removeAttribute((string)$offset);
    }

    // endregion

    public function log(string $message, ...$params) : void
    {
        if (!GlobalOptions::isLoggingEnabled())
        {
            return;
        }

        if (!empty($params))
        {
            $message = sprintf($message, ...$params);
        }

        $identifier = $this->getLogIdentifier();

        if ($identifier !== '')
        {
            $identifier .= ' | ';
        }

        echo $identifier . $message . PHP_EOL;
    }

    abstract public function getLogIdentifier() : string;

    public static function HTMLSpecialChars(string $value) : string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES,
            GlobalOptions::getCharset()
        );
    }
}
