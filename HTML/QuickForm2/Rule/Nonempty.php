<?php
/**
 * Rule checking that the field is not empty
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
 * Rule checking that the field is not empty
 *
 * Handles simple form fields, file uploads and Containers.
 *
 * When validating <select multiple> fields and Containers it may use an
 * optional configuration parameter for minimum number of nonempty values,
 * defaulting to 1. It can be passed either to
 * {@link HTML_QuickForm2_Rule::__construct() the Rule constructor} as local
 * configuration or to {@link HTML_QuickForm2_Factory::registerRule()} as
 * global one. As usual, global configuration overrides local.
 *
 * <code>
 * // Required rule is 'nonempty' with a bit of special handling
 * $login->addRule('required', 'Please provide your login');
 * $multiSelect->addRule('required', 'Please select at least two options', 2);
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
class HTML_QuickForm2_Rule_Nonempty extends HTML_QuickForm2_Rule
{
    protected function validateOwner()
    {
        $owner = $this->getOwner();

        if ($owner instanceof HTML_QuickForm2_Container) {
            $nonempty = 0;
            foreach ($owner->getRecursiveIterator(RecursiveIteratorIterator::LEAVES_ONLY) as $child) {
                $rule = new self($child);
                if ($rule->validateOwner()) {
                    $nonempty++;
                }
            }
            return $nonempty >= $this->getConfig();
        }

        $value = $owner->getValue();
        if ($owner instanceof HTML_QuickForm2_Element_InputFile) {
            return isset($value['error']) && (UPLOAD_ERR_OK === (int)$value['error']);
        }

        if (is_array($value)) {
            return count(array_filter($value, 'strlen')) >= $this->getConfig();
        }

        $value = (string)$value;

        return $value !== '';
    }

   /**
    * Sets minimum number of nonempty values
    *
    * This is useful for multiple selects and Containers, will be ignored for
    * all other elements. Defaults to 1, thus multiple select will be
    * considered not empty if at least one option is selected, Container will
    * be considered not empty if at least one contained element is not empty.
    *
    * @param int|string|NULL $config Minimum number of nonempty values
    *
    * @return   HTML_QuickForm2_Rule
    * @throws   HTML_QuickForm2_InvalidArgumentException    if a bogus limit was provided
    */
    public function setConfig($config)
    {
        if (is_null($config)) {
            $config = 1;
        }

        $config = (int)$config;

        if (1 > $config) {
            throw new HTML_QuickForm2_InvalidArgumentException(
                'Nonempty Rule accepts a positive count of nonempty values, ' .
                preg_replace('/\s+/', ' ', var_export($config, true)) . ' given'
            );
        }
        return parent::setConfig($config);
    }

    protected function getJavascriptCallback()
    {
        return "function() { return qf.rules.nonempty(" . $this->getOwner()->getJavascriptValue()
               . ", " . $this->getConfig() . "); }";
    }
}
