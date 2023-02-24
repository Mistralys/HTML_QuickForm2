<?php
/**
 * Base class for checkboxes and radios
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
// pear-package-only  * Base class for <input> elements
// pear-package-only  */
// pear-package-only require_once 'HTML/QuickForm2/Element/Input.php';
use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;

/**
 * Base class for <input> elements having 'checked' attribute (checkboxes and radios)
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_InputCheckable extends HTML_QuickForm2_Element_Input
{
    protected $persistent = true;

   /**
    * HTML to represent the element in "frozen" state
    *
    * Array index "checked" contains HTML for element's "checked" state,
    * "unchecked" for not checked
    * @var  array
    */
    protected $frozenHtml = array(
        'checked'   => 'On',
        'unchecked' => 'Off'
    );

   /**
    * Contains options and data used for the element creation
    * - content: Label "glued" to a checkbox or radio
    * @var  array
    */
    protected $data = array('content' => '');

    protected function initWatchedAttributes(WatchedAttributes $attributes) : void
    {
        parent::initWatchedAttributes($attributes);

        $attributes->setWatched('value', Closure::fromCallable(array($this, 'onValueChanged')));
    }

    /**
     * The "checked" attribute should be updated on changes to "value" attribute
     *  see bug #15708
     *
     * @param string|null $value
     * @return void
     */
    protected function onValueChanged(?string $value) : void
    {
        if ($value === null)
        {
            unset($this->attributes['value'], $this->attributes['checked']);
            return;
        }

        $this->attributes['value'] = $value;

        $this->updateValue();
    }

   /**
    * Sets the label to be rendered glued to the element
    *
    * This label is returned by {@link __toString()} method with the element's
    * HTML. It is automatically wrapped into the <label> tag.
    *
    * @param string $content
    *
    * @return $this
    */
    public function setContent($content)
    {
        $this->data['content'] = $content;
        return $this;
    }

   /**
    * Returns the label that will be "glued" to element's HTML
    *
    * @return   string
    */
    public function getContent()
    {
        return $this->data['content'];
    }

    public function setValue($value) : self
    {
        if ((string)$value === $this->getAttribute('value'))
        {
            return $this->setChecked(true);
        }

        return $this->setChecked(false);
    }

    /**
     * Using `setValue()` does not modify the checkbox' `value`
     * attribute, but sets its checked status if the value being
     * set matches the `value` attribute.
     *
     * This method updates the checkbox' `value` attribute.
     *
     * @param string $value
     * @return $this
     */
    public function setValueAttribute(string $value) : self
    {
        return $this->setAttribute('value', $value);
    }

    public function getRawValue() : string
    {
        if ($this->isChecked() && !$this->isDisabled())
        {
            return (string)$this->getAttribute('value');
        }

        return '';
    }

    public function isDisabled() : bool
    {
        return $this->hasAttribute('disabled');
    }

    public function isChecked() : bool
    {
        return $this->hasAttribute('checked');
    }

    public function setChecked(bool $checked) : self
    {
        if($checked === true)
        {
            return $this->setAttribute('checked');
        }

        return $this->removeAttribute('checked');
    }

    public function __toString()
    {
        if (0 == strlen($this->data['content'])) {
            $label = '';
        } elseif ($this->frozen) {
            $label = $this->data['content'];
        } else {
            $label = '<label for="' . htmlspecialchars(
                $this->getId(), ENT_QUOTES, GlobalOptions::getCharset()
            ) . '">' . $this->data['content'] . '</label>';
        }
        return parent::__toString() . $label;
    }

    public function getFrozenHtml()
    {
        if ($this->getAttribute('checked')) {
            return $this->frozenHtml['checked'] . $this->getPersistentContent();
        } else {
            return $this->frozenHtml['unchecked'];
        }
    }
}
?>
