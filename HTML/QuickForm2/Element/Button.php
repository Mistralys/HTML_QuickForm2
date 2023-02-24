<?php
/**
 * Class for <button> elements
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

use HTML\QuickForm2\Interfaces\Element\DisableAbleInterface;
use HTML\QuickForm2\Traits\Element\DisableAbleTrait;

/**
 * Class for <button> elements
 *
 * Note that this element was named 'xbutton' in previous version of QuickForm,
 * the name 'button' being used for current 'inputbutton' element.
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_Button
    extends HTML_QuickForm2_Element
    implements DisableAbleInterface
{
    use DisableAbleTrait;

   /**
    * Contains options and data used for the element creation
    * - content: Content to be displayed between <button></button> tags
    * @var  array
    */
    protected $data = array('content' => '');

   /**
    * Element's submit value
    * @var  string|null
    */
    protected $submitValue = null;

    protected function initNode()
    {
        parent::initNode();

        if($this->isSubmit() && $this->isAttributeEmpty('value'))
        {
            $this->setAttribute('value', '1');
        }
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

   /**
    * Buttons can not be frozen
    *
    * @param bool $freeze Whether element should be frozen or editable. This
    *                     parameter is ignored in case of buttons
    *
    * @return   bool    Always returns false
    */
    public function toggleFrozen($freeze = null)
    {
        return false;
    }

   /**
    * Sets the contents of the button element
    *
    * @param string $content Button content (HTML to add between <button></button> tags)
    *
    * @return $this
    */
    public function setContent(string $content) : self
    {
        $this->data['content'] = $content;
        return $this;
    }
    
   /**
    * Sets the button's type attribute.
    * @param string $type
    * @return $this
    */
    public function setType(string $type) : self
    {
        return $this->setAttribute('type', $type);
    }
    
   /**
    * Sets the button label. This is an alias for the {@link setContent()} method.
    * @param string $label Can contain HTML.
    * @return HTML_QuickForm2_Element_Button
    */
    public function setLabel($label)
    {
        return $this->setContent($label);
    }

   /**
    * Button's value cannot be set via this method
    *
    * @param mixed $value Element's value, this parameter is ignored
    *
    * @return $this
    */
    public function setValue($value) : self
    {
        return $this;
    }

    /**
     * Turns the button into a submit button, with the
     * specified value to transmit if the button is
     * clicked.
     *
     * @param string $value
     * @return $this
     */
    public function makeSubmit(string $value='1') : self
    {
        $this->setType('submit');
        $this->setAttribute('value', $value);
        return $this;
    }

    public function isSubmit() : bool
    {
        return $this->getAttribute('type') === 'submit';
    }

   /**
    * Returns the element's value
    *
    * The value is only returned if the following is true
    *  - button has 'type' attribute set to 'submit' (or no 'type' attribute)
    *  - the form was submitted by clicking on this button
    *
    * This method returns the actual value submitted by the browser. Note that
    * different browsers submit different values!
    *
    * @return    string|null
    */
    public function getRawValue() : ?string
    {
        if($this->isDisabled())
        {
            return null;
        }

        if ($this->isAttributeEmpty('type') || $this->isSubmit())
        {
            return $this->submitValue;
        }

        return null;
    }

    public function __toString()
    {
        return $this->getIndent() . '<button' . $this->getAttributes(true) .
               '>' . $this->data['content'] . '</button>';
    }

    protected function updateValue() : self
    {
        $name = $this->getName();

        foreach ($this->getDataSources() as $ds)
        {
            $value = $ds->getValue($name);

            if ($value !== null && $ds instanceof HTML_QuickForm2_DataSource_Submit)
            {
                $this->submitValue = $value;
                return $this;
            }
        }

        $this->submitValue = null;

        return $this;
    }
}
