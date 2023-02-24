<?php
/**
 * Class for <textarea> elements
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
// pear-package-only  * Base class for simple HTML_QuickForm2 elements
// pear-package-only  */
// pear-package-only require_once 'HTML/QuickForm2/Element.php';
use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;

/**
 * Class for <textarea> elements
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_Textarea extends HTML_QuickForm2_Element
{
    protected $persistent = true;

   /**
    * Value for textarea field
    * @var  string
    */
    protected $value = null;

    public function getType()
    {
        return 'textarea';
    }

    public function setValue($value) : self
    {
        $this->value = $value;
        return $this;
    }

    public function getRawValue()
    {
        return empty($this->attributes['disabled'])? $this->value: null;
    }

    public function __toString()
    {
        if ($this->frozen) {
            return $this->getFrozenHtml();
        }

        return $this->getIndent() .
            '<textarea' . $this->getAttributes(true) .'>' .
                $this->valueToHTML($this->value) .
            '</textarea>';
    }

    public function getFrozenHtml()
    {
        $value = $this->valueToHTML($this->value);

        if ('off' === $this->getAttribute('wrap'))
        {
            $html = $this->getIndent() . '<pre>' . $value .
                    '</pre>' . GlobalOptions::getLineBreak();
        } else {
            $html = nl2br($value) . GlobalOptions::getLineBreak();
        }

        return $html . $this->getPersistentContent();
    }

   /**
    * Sets the columns attribute of the textarea.
    * @param int $cols
    * @return HTML_QuickForm2_Element_Textarea
    */
    public function setColumns($cols)
    {
        $this->setAttribute('cols', $cols);
        return $this;
    }

   /**
    * Sets the rows attribute of the textarea.
    * @param int $rows
    * @return HTML_QuickForm2_Element_Textarea
    */
    public function setRows($rows)
    {
        $this->setAttribute('rows', $rows);
        return $this;
    }
    
   /**
    * Adds a filter for the "trim" function.
    * @return HTML_QuickForm2_Element_Textarea
    */
    public function addFilterTrim()
    {
        return $this->addFilter('trim');
    }
}
?>
