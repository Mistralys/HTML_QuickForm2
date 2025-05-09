<?php
/**
 * Class for adding inline javascript to the form
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
 * Class for adding inline javascript to the form
 *
 * Unlike scripts added to {@link HTML_QuickForm2_JavascriptBuilder} this is
 * intended for "volatile" scripts that can not be put into the separate .js
 * files and should always be rebuilt when the form is rendered. A good
 * example is setting the default values and corresponding visible options for
 * {@link HTML_QuickForm2_Element_Hierselect}
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_Script extends HTML_QuickForm2_Element_Static
{
    public function getType() : string
    {
        return 'script';
    }

   /**
    * Returns the element's content wrapped in <script></script> tags
    *
    * @return string
    */
    public function __toString()
    {
        $cr         = BaseHTMLElement::getOption('linebreak');
        $attributes = ' type="text/javascript"';
        if (null !== ($nonce = BaseHTMLElement::getOption('nonce'))) {
            $attributes .= ' nonce="' . $nonce . '"';
        }
        return "<script{$attributes}>{$cr}//<![CDATA[{$cr}"
               . $this->data['content'] . "{$cr}//]]>{$cr}</script>";
    }

   /**
    * Renders the element as the "hidden" one
    *
    * @param HTML_QuickForm2_Renderer $renderer
    *
    * @return   HTML_QuickForm2_Renderer
    * @see      HTML_QuickForm2_Renderer::renderHidden()
    */
    public function render(HTML_QuickForm2_Renderer $renderer) : HTML_QuickForm2_Renderer
    {
        $renderer->renderHidden($this);

        return $renderer;
    }
}
