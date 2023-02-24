<?php
/**
 * Static Factory class for HTML_QuickForm2 package
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

// pear-package-only /**
// pear-package-only  * Class with static methods for loading classes and files
// pear-package-only  */
// pear-package-only require_once 'HTML/QuickForm2/Loader.php';
use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;

/**
 * Static factory class
 *
 * The class handles instantiation of Element and Rule objects as well as
 * registering of new Element and Rule classes.
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Factory
{
    public const ERROR_INSTANCE_NOT_A_NODE = 101201;
    public const ERROR_RULE_CLASS_NOT_FOUND = 101202;
    public const ERROR_INSTANCE_NOT_A_RULE = 101203;

   /**
    * List of element types known to Factory
    * @var array<string,string>
    */
    protected static $elementTypes = array(
        'button'        => HTML_QuickForm2_Element_Button::class,
        'checkbox'      => HTML_QuickForm2_Element_InputCheckbox::class,
        'date'          => HTML_QuickForm2_Element_Date::class,
        'fieldset'      => HTML_QuickForm2_Container_Fieldset::class,
        'group'         => HTML_QuickForm2_Container_Group::class,
        'file'          => HTML_QuickForm2_Element_InputFile::class,
        'hidden'        => HTML_QuickForm2_Element_InputHidden::class,
        'hierselect'    => HTML_QuickForm2_Element_Hierselect::class,
        'image'         => HTML_QuickForm2_Element_InputImage::class,
        'inputbutton'   => HTML_QuickForm2_Element_InputButton::class,
        'password'      => HTML_QuickForm2_Element_InputPassword::class,
        'radio'         => HTML_QuickForm2_Element_InputRadio::class,
        'repeat'        => HTML_QuickForm2_Container_Repeat::class,
        'reset'         => HTML_QuickForm2_Element_InputReset::class,
        'script'        => HTML_QuickForm2_Element_Script::class,
        'select'        => HTML_QuickForm2_Element_Select::class,
        'static'        => HTML_QuickForm2_Element_Static::class,
        'submit'        => HTML_QuickForm2_Element_InputSubmit::class,
        'text'          => HTML_QuickForm2_Element_InputText::class,
        'textarea'      => HTML_QuickForm2_Element_Textarea::class
    );

   /**
    * List of registered rules
    * @var array<string,array{class:string,options:array}>
    */
    protected static array $registeredRules = array(
        'nonempty'      => array('class' => HTML_QuickForm2_Rule_Nonempty::class, 'options' => null),
        'empty'         => array('class' => HTML_QuickForm2_Rule_Empty::class, 'options' => null),
        'required'      => array('class' => HTML_QuickForm2_Rule_Required::class, 'options' => null),
        'compare'       => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => null),
        'eq'            => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => array('operator' => '===')),
        'neq'           => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => array('operator' => '!==')),
        'lt'            => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => array('operator' => '<')),
        'lte'           => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => array('operator' => '<=')),
        'gt'            => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => array('operator' => '>')),
        'gte'           => array('class' => HTML_QuickForm2_Rule_Compare::class, 'options' => array('operator' => '>=')),
        'regex'         => array('class' => HTML_QuickForm2_Rule_Regex::class, 'options' => null),
        'callback'      => array('class' => HTML_QuickForm2_Rule_Callback::class, 'options' => null),
        'length'        => array('class' => HTML_QuickForm2_Rule_Length::class, 'options' => null),
        'minlength'     => array('class' => HTML_QuickForm2_Rule_Length::class, 'options' => array('max' => 0)),
        'maxlength'     => array('class' => HTML_QuickForm2_Rule_Length::class, 'options' => array('min' => 0)),
        'maxfilesize'   => array('class' => HTML_QuickForm2_Rule_MaxFileSize::class, 'options' => null),
        'mimetype'      => array('class' => HTML_QuickForm2_Rule_MimeType::class, 'options' => null),
        'each'          => array('class' => HTML_QuickForm2_Rule_Each::class, 'options' => null),
        'notcallback'   => array('class' => HTML_QuickForm2_Rule_NotCallback::class, 'options' => null),
        'notregex'      => array('class' => HTML_QuickForm2_Rule_NotRegex::class, 'options' => null),
        'email'         => array('class' => HTML_QuickForm2_Rule_Email::class, 'options' => null)
    );


   /**
    * Registers a new element type
    *
    * @param string $type        Type name (treated case-insensitively)
    * @param string $className   Class name
    */
    public static function registerElement(string $type, string $className) : void
    {
        self::$elementTypes[strtolower($type)] = $className;
    }

   /**
    * Checks whether an element type is known to factory
    *
    * @param string $type Type name (treated case-insensitively)
    *
    * @return   bool
    */
    public static function isElementRegistered(string $type) : bool
    {
        return isset(self::$elementTypes[strtolower($type)]);
    }


    /**
     * Creates a new element object of the given type
     *
     * @param string $type Type name (treated case-insensitively)
     * @param string|null $name Element name (passed to element's constructor)
     * @param array|null $attributes Element attributes (passed to element's constructor)
     * @param array $data Element-specific data (passed to element's constructor)
     *
     * @return HTML_QuickForm2_Node A created element
     * @throws HTML_QuickForm2_Exception
     * @throws HTML_QuickForm2_InvalidArgumentException If type name is unknown
     * @throws HTML_QuickForm2_NotFoundException
     */
    public static function createElement(string $type, ?string $name = null, ?array $attributes = null, array $data = array()) : HTML_QuickForm2_Node
    {
        $type = strtolower($type);

        if (!isset(self::$elementTypes[$type]))
        {
            throw new HTML_QuickForm2_InvalidArgumentException(
                "Element type '$type' is not known"
            );
        }

        // Null the name if it is empty.
        if($name === '')
        {
            $name = null;
        }

        $className = self::$elementTypes[$type];

        if(!class_exists($className))
        {
            throw new HTML_QuickForm2_NotFoundException(
                sprintf('Element class [%s] not found.', $className)
            );
        }

        self::log('Creating element of type [%s] (class: [%s])', $type, $className);

        $node = new $className($name, $attributes, $data);

        if($node instanceof HTML_QuickForm2_Node)
        {
            return $node;
        }

        throw new HTML_QuickForm2_Exception(
            'Element class is not an instance of the base node class.',
            self::ERROR_INSTANCE_NOT_A_NODE
        );
    }

    protected static function log(string $message, ...$params) : void
    {
        if(!GlobalOptions::isLoggingEnabled())
        {
            return;
        }

        if(!empty($params))
        {
            $message = sprintf($message, ...$params);
        }

        echo 'Factory | '.$message.PHP_EOL;
    }

   /**
    * Registers a new rule type
    *
    * @param string $type        Rule type name (treated case-insensitively)
    * @param string $className   Class name
    * @param string|NULL $includeFile DEPRECATED
    * @param mixed  $config      Configuration data for rules of the given type
    */
    public static function registerRule(string $type, string $className, ?string $includeFile = null, $config = null) : void
    {
        self::$registeredRules[strtolower($type)] = array(
            'class' => $className,
            'options' => $config
        );
    }

   /**
    * Checks whether a rule type is known to Factory
    *
    * @param string $type Rule type name (treated case-insensitively)
    *
    * @return   bool
    */
    public static function isRuleRegistered(string $type) : bool
    {
        return isset(self::$registeredRules[strtolower($type)]);
    }

   /**
    * Creates a new Rule of the given type
    *
    * @param string               $type    Rule type name (treated case-insensitively)
    * @param HTML_QuickForm2_Node $owner   Element to validate by the rule
    * @param string               $message Message to display if validation fails
    * @param mixed                $config  Configuration data for the rule
    *
    * @return   HTML_QuickForm2_Rule    A created Rule
    * @throws   HTML_QuickForm2_InvalidArgumentException If rule type is unknown
    * @throws   HTML_QuickForm2_NotFoundException        If class for the rule
    *           can't be found and/or loaded from file
    */
    public static function createRule(string $type, HTML_QuickForm2_Node $owner, string $message = '', $config = null) : HTML_QuickForm2_Rule
    {
        $type = strtolower($type);
        if (!isset(self::$registeredRules[$type])) {
            throw new HTML_QuickForm2_InvalidArgumentException("Rule '$type' is not known");
        }

        $className = self::$registeredRules[$type]['class'];
        $options = self::$registeredRules[$type]['options'];

        if(!class_exists($className))
        {
            throw new HTML_QuickForm2_NotFoundException(
                sprintf('Rule class [%s] not found.', $className),
                self::ERROR_RULE_CLASS_NOT_FOUND
            );
        }

        if ($options !== null)
        {
            $config = call_user_func(
                array($className, 'mergeConfig'),
                $config, $options
            );
        }

        $rule = new $className($owner, $message, $config);

        if($rule instanceof HTML_QuickForm2_Rule)
        {
            return $rule;
        }

        throw new HTML_QuickForm2_InvalidArgumentException(
            sprintf('The rule class [%s] is not a rule instance.', $className),
            self::ERROR_INSTANCE_NOT_A_RULE
        );
    }
}

