<?php
/**
 * Rule checking the value via a callback function (method)
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.githubusercontent.com/pear/HTML_QuickForm2/trunk/docs/LICENSE
 *
 * @category  HTML
 * @package   HTML_QuickForm2
 * @author    Alexey Borzov <avb@php.net>
 * @author    Bertrand Mansion <golgote@mamasam.com>
 * @copyright 2006-2020 Alexey Borzov <avb@php.net>, Bertrand Mansion <golgote@mamasam.com>
 * @license   https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      https://pear.php.net/package/HTML_QuickForm2
 */

/**
 * Rule checking the value via a callback function (method)
 *
 * The Rule needs a valid callback as a configuration parameter for its work, it
 * may also be given additional arguments to pass to the callback alongside the
 * element's value. See {@link mergeConfig()} for description of possible ways
 * to pass configuration parameters.
 *
 * The callback will be called with element's value as the first argument, if
 * additional arguments were provided they'll be passed as well. It is expected
 * to return false if the value is invalid and true if it is valid.
 *
 * Checking that the value is not empty:
 * <code>
 * $str->addRule('callback', 'The field should not be empty', 'strlen');
 * </code>
 * Checking that the value is in the given array:
 * <code>
 * $meta->addRule('callback', 'Unknown variable name',
 *                array('callback' => 'in_array',
 *                      'arguments' => array(array('foo', 'bar', 'baz'))));
 * </code>
 * The same, but with rule registering first:
 * <code>
 * HTML_QuickForm2_Factory::registerRule(
 *     'in_array', 'HTML_QuickForm2_Rule_Callback',
 *     'HTML/QuickForm2/Rule/Callback.php', 'in_array'
 * );
 * $meta->addRule('in_array', 'Unknown variable name', array(array('foo', 'bar', 'baz')));
 * </code>
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Rule_Callback extends HTML_QuickForm2_Rule
{
    public const ERROR_INVALID_CALLBACK = 140201;
    public const ERROR_INVALID_CALLBACK_ARGUMENTS = 140202;
    public const ERROR_CANNOT_GENERATE_JS_CALLBACK = 140203;

    /**
    * Validates the owner element
    *
    * @return   bool    the value returned by a callback function
    */
    protected function validateOwner()
    {
        $value  = $this->getOwner()->getValue();
        $config = $this->getConfig();
       
        if(!is_array($config['arguments'])) {
           $config['arguments'] = array();
        }

        // the rule itself is always the last parameter, except for
        // regular function callbacks.
        if(is_array($config['callback'])) {
            $config['arguments'][] = $this;
        }
       
        return (bool)call_user_func_array(
            $config['callback'], array_merge(array($value), $config['arguments'])
        );
    }

    protected function getJavascriptCallback()
    {
        $config    = $this->getConfig();
        $arguments = array($this->getOwner()->getJavascriptValue());
        foreach ($config['arguments'] as $arg) {
            $arguments[] = HTML_QuickForm2_JavascriptBuilder::encode($arg);
        }
        return "function() { return " . $this->findJavascriptName() .
               "(" . implode(', ', $arguments) . "); }";
    }

    /**
     * Finds a name for Javascript callback function
     *
     * The method first checks whether javascript callback 'js_callback' was
     * provided to the Rule and returns that if found.
     *
     * If an explicit 'js_callback' was not provided, it tries to generate a name
     * equal (for a given value of "equal") to PHP callback name. This may be useful
     * if e.g. using HTML_AJAX package to generate class stubs in JS and in similar
     * circumstances.
     *
     * If a callback does not have a name (it is a lambda function or a closure)
     * then an exception will be raised.
     *
     * @return string
     * @throws HTML_QuickForm2_InvalidArgumentException {@see self::ERROR_CANNOT_GENERATE_JS_CALLBACK} if the name cannot be determined automatically.
     */
    protected function findJavascriptName() : string
    {
        $config = $this->getConfig();

        // oh joy! we have an explicitly given JS callback!
        if (isset($config['js_callback'])) {
            return $config['js_callback'];
        }

        // no luck, try to come up with a name similar to PHP one
        // function name, filter lambdas created via create_function...
        if (is_string($config['callback']) && chr(0) !== $config['callback'][0])
        {
            return str_replace('::', '.', $config['callback']);
        }

        // object instance method
        if (is_array($config['callback']) && is_object($config['callback'][0]))
        {
            return '(new ' . get_class($config['callback'][0]) . ').' . $config['callback'][1];
        }

        // static class method
        if (is_array($config['callback']) && is_string($config['callback'][0]))
        {
            return $config['callback'][0] . '.' . $config['callback'][1];
        }

        // lambda, closure, whatever: no sane way to translate

        throw new HTML_QuickForm2_InvalidArgumentException(
            "Cannot generate Javascript callback name, please provide one",
            self::ERROR_CANNOT_GENERATE_JS_CALLBACK
        );
    }

   /**
    * Tests whether a given value is an array containing at least one of the given keys
    *
    * @param mixed $search if not an array, method will return false
    * @param array $keys   keys to test for
    *
    * @return   bool
    */
    protected static function arrayHasSomeKeys($search, array $keys): bool
    {
        if (is_array($search)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $search)) {
                    return true;
                }
            }
        }
        return false;
    }

   /**
    * Merges local configuration with that provided for registerRule()
    *
    * "Global" configuration may be passed to
    * {@link HTML_QuickForm2_Factory::registerRule()} in either of the
    * following formats
    *  - callback
    *  - array(['callback' => callback][, 'arguments' => array(...)][, 'js_callback' => string])
    *
    * "Local" configuration may be passed to the constructor in either of
    * the following formats
    *  - callback or arguments (interpretation depends on whether the global
    *    configuration already contains the callback)
    *  - array(['callback' => callback][, 'arguments' => array(...)][, 'js_callback' => string])
    *
    * As usual, global config overrides local one. It is a good idea to use the
    * associative array format to prevent ambiguity.
    *
    * @param mixed $localConfig  Local configuration
    * @param mixed $globalConfig Global configuration
    *
    * @return   mixed   Merged configuration
    */
    public static function mergeConfig($localConfig, $globalConfig)
    {
        if (!isset($globalConfig)) {
            $config = $localConfig;

        } else {
            if (!self::arrayHasSomeKeys($globalConfig, array('callback', 'arguments', 'js_callback'))) {
                $config = array('callback' => $globalConfig);
            } else {
                $config = $globalConfig;
            }
            if (self::arrayHasSomeKeys($localConfig, array('callback', 'arguments', 'js_callback'))) {
                $config += $localConfig;
            } elseif (isset($localConfig)) {
                $config += array('callback' => $localConfig, 'arguments' => $localConfig);
            }
        }
        return $config;
    }

   /**
    * Sets the callback to use for validation and its additional arguments
    *
    * @param callable|array $config Callback or array ('callback' => validation callback
    *                                                  [, 'arguments' => additional arguments]
    *                                                  [, 'js_callback' => javascript callback
    *                                                               for client-side validation])
    *
    * @return   $this
    * @throws   HTML_QuickForm2_InvalidArgumentException if callback is missing or invalid
    *               or additional arguments is not an array
    */
    public function setConfig($config) : self
    {
        if (!is_array($config) || !isset($config['callback'])) {
            $config = array('callback' => $config);
        }
       
        $callbackName = null;

        if (!is_callable($config['callback'], false, $callbackName))
        {
            throw new HTML_QuickForm2_InvalidArgumentException(
                sprintf(
                    'Callback Rule requires a valid callback, [%s] was given.',
                    $callbackName
                ),
                self::ERROR_INVALID_CALLBACK
            );
        }

        if (array_key_exists('arguments', $config) && !is_array($config['arguments']))
        {
            throw new HTML_QuickForm2_InvalidArgumentException(
                sprintf(
                    'Callback Rule expects additional callback arguments to be an array, [%s] given.',
                    preg_replace('/\s+/', ' ', var_export($config['arguments'], true))
                ),
                self::ERROR_INVALID_CALLBACK_ARGUMENTS
            );
        }
        return parent::setConfig($config + array('arguments' => array()));
    }

   /**
    * Retrieves the rule's callback.
    * @return array|string
    */
    public function getCallback()
    {
        $config = $this->getConfig();
        return $config['callback'];
    }

    /**
     * Sets the arguments for the callback. Every item in the array
     * is an argument added to the callback, in the exact order they
     * are found in the array. The first item stays the value to validate,
     * and the last item is the rule instance itself.
     *
     * @param mixed|NULL $args
     * @return HTML_QuickForm2_Rule_Callback
     * @throws HTML_QuickForm2_InvalidArgumentException
     */
    public function setArguments($args) : self
    {
        if($args === null) {
            return $this;
        }

        // ensure we always have an array
        if(!is_array($args)) {
            $args = array($args);
        }
        
        $config = $this->getConfig();
        $config['arguments'] = $args;
        
        $this->setConfig($config);
        
        return $this;
    }
   
   /**
    * Retrieves the arguments list of the callback, if any.
    *  
    * @return array
    */
    public function getArguments()
    {
        $config = $this->getConfig();
        if(isset($config['arguments']) && !empty($config['arguments'])) {
           return $config['arguments'];
        }
          
        return array();
    }
}
?>
