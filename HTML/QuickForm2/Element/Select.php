<?php
/**
 * Class for <select> elements
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

declare(strict_types=1);

use HTML\QuickForm2\Element\Select\SelectOption;

/**
 * Class representing a <select> element
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_Select extends HTML_QuickForm2_Element
{
    public const SETTING_INTRINSIC_VALIDATION = 'intrinsic_validation';

    protected bool $persistent = true;

   /**
    * Values for the select element (i.e. values of the selected options)
    * @var  array
    */
    protected array $values = array();

   /**
    * Possible values for select elements
    *
    * A value is considered possible if it is present as a value attribute of
    * some option and that option is not disabled.
    * @var array
    */
    protected array $possibleValues = array();


   /**
    * Object containing options for the <select> element
    * @var  HTML_QuickForm2_Element_Select_OptionContainer
    */
    protected HTML_QuickForm2_Element_Select_OptionContainer $optionContainer;

   /**
    * Enable intrinsic validation by default
    * @var  array
    */
    protected $data = array(self::SETTING_INTRINSIC_VALIDATION => true);

   /**
    * Class constructor
    *
    * Select element can understand the following keys in $data parameter:
    *   - 'options': data to populate element's options with. Passed to
    *     {@link loadOptions()} method.
    *   - 'intrinsic_validation': setting this to false will disable
    *     that validation, {@link getValue()} will then return all submit
    *     values, not just those corresponding to options present in the
    *     element. May be useful in AJAX scenarios.
    *
    * @param string       $name       Element name
    * @param string|array $attributes Attributes (either a string or an array)
    * @param array        $data       Additional element data
    *
    * @throws   HTML_QuickForm2_InvalidArgumentException    if junk is given in $options
    */
    public function __construct($name = null, $attributes = null, array $data = array())
    {
        $options = $data['options'] ?? array();
        unset($data['options']);
        parent::__construct($name, $attributes, $data);
        $this->loadOptions($options);
        $this->initSelect();
    }

    /**
     * Overridable by extending classes to avoid having
     * to redeclare the constructor.
     *
     * @return void
     */
    protected function initSelect() : void
    {
    }

    public function getType() : string
    {
        return 'select';
    }

    public function __toString()
    {
        if ($this->frozen) {
            return $this->getFrozenHtml();
        }

        if (empty($this->attributes['multiple'])) {
            $attrString = $this->getAttributes(true);
        } else {
            $this->attributes['name'] .= '[]';
            $attrString = $this->getAttributes(true);
            $this->attributes['name']  = substr($this->attributes['name'], 0, -2);
        }

        $indent = $this->getIndent();

        return $indent . '<select' . $attrString . '>' .
               self::getOption('linebreak') .
               $this->optionContainer->__toString() .
               $indent . '</select>';
    }

    protected function getFrozenHtml() : string
    {
        if (null === ($value = $this->getValue())) {
            return '&nbsp;';
        }

        $valueHash = is_array($value)? array_flip($value): array($value => true);
        $options   = array();

        foreach ($this->optionContainer->getRecursiveIterator() as $child)
        {
            if (
                $child instanceof SelectOption
                && isset($valueHash[$child['attr']['value']])
                && empty($child['attr']['disabled'])
            ) {
                $options[] = $child['text'];
            }
        }

        $html = implode('<br />', $options);
        if ($this->persistent) {
            $name = $this->attributes['name'] .
                    (empty($this->attributes['multiple'])? '': '[]');
            // Only use id attribute if doing single hidden input
            $idAttr = (1 === count($valueHash))? array('id' => $this->getId()): array();
            foreach ($valueHash as $key => $item) {
                $html .= '<input type="hidden"' . self::getAttributesString(array(
                             'name'  => $name,
                             'value' => $key
                         ) + $idAttr) . ' />';
            }
        }
        return $html;
    }

   /**
    * Returns the value of the <select> element
    *
    * Please note that the returned value may not necessarily be equal to that
    * passed to {@link setValue()}. It passes "intrinsic validation" confirming
    * that such value could possibly be submitted by this <select> element.
    * Specifically, this method will return null if the elements "disabled"
    * attribute is set, it will not return values if there are no options having
    * such a "value" attribute or if such options' "disabled" attribute is set.
    * It will also only return a scalar value for single selects, mimicking
    * the common browsers' behaviour.
    *
    * @return   mixed   "value" attribute of selected option in case of single
    *                   select, array of selected options' "value" attributes in
    *                   case of multiple selects, null if no options selected
    */
    public function getRawValue()
    {
        if (
            !empty($this->attributes['disabled']) || 0 === count($this->values)
            ||
            (
                $this->isValidationIntrinsic()
                &&
                (
                    0 === count($this->optionContainer)
                    ||
                    0 === count($this->possibleValues)
                )
            )
        ) {
            return null;
        }

        $values = array();
        foreach ($this->values as $value)
        {
            // OptionContainer::addOption() casts option values to string on write,
            // so the read side must match to avoid a null-array-offset deprecation.
            if (!empty($this->possibleValues[(string)$value]) || !$this->isValidationIntrinsic()) {
                $values[] = $value;
            }
        }

        if (0 === count($values)) {
            return null;
        }

        if (!empty($this->attributes['multiple'])) {
            return $values;
        }

        if (1 === count($values)) {
            return $values[0];
        }

        // The <select> is not multiple, but several options are to be
        // selected. At least IE and Mozilla select the last selected
        // option in this case, we should do the same
        $lastValue = null;
        foreach ($this->optionContainer->getRecursiveIterator() as $child) {
            if (is_array($child) && in_array($child['attr']['value'], $values, true)) {
                $lastValue = $child['attr']['value'];
            }
        }
        return $lastValue;
    }

    /**
     * Whether intrinsic validation is enabled for the select.
     * Default is <code>true</code>.
     *
     * @return bool
     */
    public function isValidationIntrinsic() : bool
    {
        return $this->data[self::SETTING_INTRINSIC_VALIDATION];
    }

    /**
     * Sets whether intrinsic validation is enabled. For details
     * on what this means, see {@see getRawValue()}.
     *
     * @param bool $enabled Default is <code>true</code>
     * @return $this
     */
    public function setIntrinsicValidation(bool $enabled) : self
    {
        $this->data[self::SETTING_INTRINSIC_VALIDATION] = $enabled;
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue($value) : self
    {
        if (is_array($value)) {
            $this->values = array_values($value);
        } else {
            $this->values = array($value);
        }

        return $this;
    }

   /**
    * Loads <option>s (and <optgroup>s) for select element
    *
    * The method expects an array of options and optgroups:
    * <pre>
    * array(
    *     'option value 1' => 'option text 1',
    *     ...
    *     'option value N' => 'option text N',
    *     'optgroup label 1' => array(
    *         'option value' => 'option text',
    *         ...
    *     ),
    *     ...
    * )
    * </pre>
    * If value is a scalar, then array key is treated as "value" attribute of
    * <option> and value as this <option>'s text. If value is an array, then
    * key is treated as a "label" attribute of <optgroup> and value as an
    * array of <option>s for this <optgroup>.
    *
    * If you need to specify additional attributes for <option> and <optgroup>
    * tags, then you need to use {@link addOption()} and {@link addOptgroup()}
    * methods instead of this one.
    *
    * @param array $options
    *
    * @throws   HTML_QuickForm2_InvalidArgumentException    if junk is given in $options
    * @return   $this
    */
    public function loadOptions(array $options) : self
    {
        $this->possibleValues  = array();
        $this->optionContainer = new HTML_QuickForm2_Element_Select_OptionContainer(
            $this->values, $this->possibleValues
        );
        $this->loadOptionsFromArray($this->optionContainer, $options);
        return $this;
    }


    /**
     * Adds options from given array into given container
     *
     * @param HTML_QuickForm2_Element_Select_OptionContainer $container options will be
     *           added to this container
     * @param array $options options array
     * @throws HTML_QuickForm2_InvalidArgumentException
     */
    protected function loadOptionsFromArray(
        HTML_QuickForm2_Element_Select_OptionContainer $container, array $options
    ) : void {
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $optgroup = $container->addOptgroup($key);
                $this->loadOptionsFromArray($optgroup, $value);
            } else {
                $container->addOption($value, $key);
            }
        }
    }


    /**
     * Adds a new option
     *
     * Please note that if you pass 'selected' attribute in the $attributes
     * parameter then this option's value will be added to <select>'s values.
     *
     * @param string|int|float|Stringable $text Option text
     * @param string|int|float|Stringable|NULL $value 'value' attribute for <option> tag
     * @param string|array<string,string>|NULL $attributes Additional attributes for <option> tag
     *                     (either as a string or as an associative array)
     * @return SelectOption
     * @throws HTML_QuickForm2_InvalidArgumentException {@see HTML_QuickForm2_Element_Select_OptionContainer::ERROR_INVALID_OPTION_CLASS}
     */
    public function addOption($text, $value, $attributes = null) : SelectOption
    {
        return $this->optionContainer->addOption($text, $value, $attributes);
    }

    /**
     * Prepends an option to the beginning of the option collection.
     *
     * @param string|int|float $text
     * @param string|int|float|NULL $value
     * @param string|array|NULL $attributes
     * @return SelectOption
     * @throws HTML_QuickForm2_InvalidArgumentException {@see HTML_QuickForm2_Element_Select_OptionContainer::ERROR_INVALID_OPTION_CLASS}
     */
    public function prependOption($text, $value, $attributes = null) : SelectOption
    {
        return $this->optionContainer->prependOption($text, $value, $attributes);
    }

    /**
     * Adds a new optgroup
     *
     * @param string $label 'label' attribute for optgroup tag
     * @param string|array|NULL $attributes Additional attributes for <optgroup> tag
     *                     (either as a string or as an associative array)
     *
     * @return HTML_QuickForm2_Element_Select_Optgroup
     * @throws HTML_QuickForm2_InvalidArgumentException
     */
    public function addOptgroup(string $label, $attributes = null) : HTML_QuickForm2_Element_Select_Optgroup
    {
        return $this->optionContainer->addOptgroup($label, $attributes);
    }

    public function getSelectedOption() : ?SelectOption
    {
        return $this->getOptionByValue((string)$this->getValue());
    }

    public function getOptionByValue(string $value) : ?SelectOption
    {
        return $this->optionContainer->getOptionByValue($value);
    }

    protected function updateValue() : void
    {
        $name = $this->getName();

        if (!$this->getAttribute('multiple'))
        {
            $ds = $this->resolveDataSourceByName($name, true);

            if($ds) {
                $this->setValue($ds->getValue($name));
            }
            return;
        }

        foreach ($this->getDataSources() as $ds)
        {
            $value = $ds->getValue($name);

            if (
                $value !== null
                ||
                $ds instanceof HTML_QuickForm2_DataSource_Submit
                ||
                (
                    $ds instanceof HTML_QuickForm2_DataSource_NullAware
                    &&
                    $ds->hasValue($name)
                )
            ) {
                $this->setValue((array)$value);
                return;
            }
        }
    }

    /**
     * Get the select element's option container, e.g. for rendering purposes.
     *
     * @return HTML_QuickForm2_Element_Select_OptionContainer
     */
    public function getOptionContainer() : HTML_QuickForm2_Element_Select_OptionContainer
    {
        return $this->optionContainer;
    }
    
    public function countOptions(bool $recursive=true) : int
    {
        return $this->optionContainer->countOptions($recursive);
    }

    /**
     * Sets the "multiple" attribute.
     * @return $this
     */
    public function makeMultiple() : self
    {
        return $this->setAttribute('multiple');
    }

    public function isMultiple() : bool
    {
        return $this->getAttribute('multiple') === 'multiple';
    }

    public function setSize(?int $size) : self
    {
        if($size === null) {
            return $this->removeAttribute('size');
        }

        return $this->setAttribute('size', $size);
    }

    public function getSize() : ?int
    {
        $size = $this->getAttribute('size');

        if($size !== null) {
            return (int)$size;
        }

        return null;
    }
}
