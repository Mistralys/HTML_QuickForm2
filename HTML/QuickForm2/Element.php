<?php
/**
 * Base class for simple HTML_QuickForm2 elements (not Containers)
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
 * Abstract base class for simple QuickForm2 elements (not Containers)
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
abstract class HTML_QuickForm2_Element extends HTML_QuickForm2_Node
{
    public function setName(?string $name) : self
    {
        $this->attributes['name'] = (string)$name;
        $this->updateValue();
        return $this;
    }

   /**
    * Generates hidden form field containing the element's value
    *
    * This is used to pass the frozen element's value if 'persistent freeze'
    * feature is on
    *
    * @return string
    */
    protected function getPersistentContent()
    {
        if (!$this->persistent || null === ($value = $this->getValue())) {
            return '';
        }
        return '<input type="hidden"' . self::getAttributesString(array(
            'name'  => $this->getName(),
            'value' => $value,
            'id'    => $this->getId()
        )) . ' />';
    }

   /**
    * Called when the element needs to update its value from form's data sources
    *
    * The default behaviour is to go through the complete list of the data
    * sources until the non-null value is found.
    */
    protected function updateValue() : void
    {
        $name = $this->getName();
        foreach ($this->getDataSources() as $ds) {
            if (null !== ($value = $ds->getValue($name))
                || ($ds instanceof HTML_QuickForm2_DataSource_NullAware && $ds->hasValue($name))
            ) {
                $this->setValue($value);
                return;
            }
        }
    }

   /**
    * Renders the element using the given renderer
    *
    * @param HTML_QuickForm2_Renderer $renderer
    *
    * @return   HTML_QuickForm2_Renderer
    */
    public function render(HTML_QuickForm2_Renderer $renderer) : HTML_QuickForm2_Renderer
    {
        $renderer->renderElement($this);
        $this->renderClientRules($renderer->getJavascriptBuilder());
        return $renderer;
    }

   /**
    * Returns Javascript code for getting the element's value
    *
    * @param bool $inContainer Whether it should return a parameter
    *                          for qf.form.getContainerValue()
    *
    * @return string
    */
    public function getJavascriptValue(bool $inContainer = false) : string
    {
        return $inContainer? "'{$this->getId()}'": "qf.\$v('{$this->getId()}')";
    }

    public function getJavascriptTriggers() : array
    {
        return array($this->getId());
    }

    /**
     * Applies recursive and non-recursive filters on element value
     *
     * @param mixed $value Element value
     *
     * @return   mixed   Filtered value
     */
    protected function applyFilters($value)
    {
        $recursive = $this->recursiveFilters;
        $container = $this->getContainer();
        while (!empty($container)) {
            $recursive = array_merge($container->recursiveFilters, $recursive);
            $container = $container->getContainer();
        }
        foreach ($recursive as $filter) {
            if (is_array($value)) {
                array_walk_recursive(
                    $value, array('HTML_QuickForm2_Node', 'applyFilter'), $filter
                );
            } else {
                self::applyFilter($value, null, $filter);
            }
        }
        return parent::applyFilters($value);
    }
}
?>
