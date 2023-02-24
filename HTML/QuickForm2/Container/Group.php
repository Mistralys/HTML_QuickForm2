<?php
/**
 * Base class for HTML_QuickForm2 groups
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
 * Base class for QuickForm2 groups of elements
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Container_Group extends HTML_QuickForm2_Container
{
    public function getType()
    {
        return 'group';
    }

    public function prependsName() : bool
    {
        return true;
    }

    protected function getChildValues(bool $filtered = false) : array
    {
        $value = parent::getChildValues($filtered);

        if (!$this->prependsName())
        {
            return $value;
        }

        if (!strpos($this->getName(), '['))
        {
            return $value[$this->getName()] ?? array();
        }

        $tokens   =  explode('[', str_replace(']', '', $this->getName()));
        $valueAry =& $value;

        do
        {
            $token = array_shift($tokens);
            if (!isset($valueAry[$token]))
            {
                return array();
            }
            $valueAry =& $valueAry[$token];
        }
        while ($tokens);

        return $valueAry;
    }

   /**
    * Sets string(s) to separate grouped elements
    *
    * @param string|array $separator Use a string for one separator, array for
    *                                alternating separators
    *
    * @return $this
    */
    public function setSeparator($separator)
    {
        $this->data['separator'] = $separator;
        return $this;
    }

   /**
    * Returns string(s) to separate grouped elements
    *
    * @return   string|array    Separator, null if not set
    */
    public function getSeparator()
    {
        return isset($this->data['separator'])? $this->data['separator']: null;
    }

   /**
    * Renders the group using the given renderer
    *
    * @param HTML_QuickForm2_Renderer $renderer
    *
    * @return   HTML_QuickForm2_Renderer
    */
    public function render(HTML_QuickForm2_Renderer $renderer)
    {
        $renderer->startGroup($this);
        foreach ($this as $element) {
            $element->render($renderer);
        }
        $this->renderClientRules($renderer->getJavascriptBuilder());
        $renderer->finishGroup($this);
        return $renderer;
    }

    public function __toString()
    {
        $renderer = $this->render(
            HTML_QuickForm2_Renderer::factory('default')
                ->setTemplateForId($this->getId(), '{content}')
        );

        $js = $renderer->getJavascriptBuilder()->getSetupCode(null, true);

        if(method_exists($renderer, '__toString'))
        {
            return $renderer . ' ' . $js;
        }

        return '';
    }

    public function isNameNullable() : bool
    {
        return true;
    }
}
