<?php
/**
 * Unit tests for HTML_QuickForm2 package
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

use PHPUnit\Framework\TestCase;

/**
 * Unit test for HTML_QuickForm2_Element_InputCheckbox class
 */
class HTML_QuickForm2_Element_InputPasswordTest extends TestCase
{
    public function testFrozenOutputIsMasked(): void
    {
        $input = new HTML_QuickForm2_Element_InputPassword('foo');
        $input->setValue('bar');
        $input->toggleFrozen(true);
        // wow, never used lookbehind assertions before
        $this->assertDoesNotMatchRegularExpression('/(?<!value=")bar/', $input->__toString());

        $input->persistentFreeze(false);
        $this->assertDoesNotMatchRegularExpression('/bar/', $input->__toString());
    }
}
?>
