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
 * Unit test for HTML_QuickForm2_Element_Button class
 */
class HTML_QuickForm2_Element_ButtonTest extends TestCase
{
    public const TEST_FORM_ID = 'buttons';

    protected function setUp() : void
    {
        $_POST = array(
            'foo' => 'A button clicked',
            'bar' => 'Another button clicked'
        );

        $_REQUEST[HTML_QuickForm2::generateTrackingVarName(self::TEST_FORM_ID)] = 'yes';
    }

    public function testConstructorSetsContent()
    {
        $button = new HTML_QuickForm2_Element_Button('foo');
        $button->setContent('Some string');

        $this->assertMatchesRegularExpression(
            '!<button[^>]*>Some string</button>!',
            $button->__toString()
        );
    }

    public function testCannotBeFrozen()
    {
        $button = new HTML_QuickForm2_Element_Button('foo');
        $this->assertFalse($button->toggleFrozen(true));
        $this->assertFalse($button->toggleFrozen());
    }

    public function testSetValueFromSubmitDataSource()
    {
        $form = new HTML_QuickForm2(self::TEST_FORM_ID, 'post');

        $foo = $form->appendChild(new HTML_QuickForm2_Element_Button('foo', array('type' => 'submit')));
        $bar = $form->appendChild(new HTML_QuickForm2_Element_Button('bar', array('type' => 'button')));
        $baz = $form->appendChild(new HTML_QuickForm2_Element_Button('baz', array('type' => 'submit')));

        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'foo' => 'Default for foo',
            'bar' => 'Default for bar',
            'baz' => 'Default for baz'
        )));

        $this->assertEquals('A button clicked', $foo->getValue());
        $this->assertNull($bar->getValue());
        $this->assertNull($baz->getValue());

        $foo->setAttribute('disabled');
        $this->assertNull($foo->getValue());
    }

    public function testIsSubmitConstructorParams() : void
    {
        $el = new HTML_QuickForm2_Element_Button('foo', array('type' => 'submit'));
        $this->assertSame('1', $el->getAttribute('value'));
        $this->assertNull($el->getValue());
        $this->assertTrue($el->isSubmit());
    }

    public function testIsSubmitMethod() : void
    {
        $el = new HTML_QuickForm2_Element_Button('foo');
        $el->makeSubmit('custom');
        $this->assertSame('custom', $el->getAttribute('value'));
        $this->assertNull($el->getValue());
        $this->assertTrue($el->isSubmit());
    }

    public function testValueNullIfDisabled() : void
    {
        $form = new HTML_QuickForm2(self::TEST_FORM_ID, 'post');

        $foo = $form->addButton('foo');
        $foo->makeSubmit();

        $this->assertEquals('A button clicked', $foo->getValue());

        $foo->setAttribute('disabled');
        $this->assertNull($foo->getValue());
    }
}
