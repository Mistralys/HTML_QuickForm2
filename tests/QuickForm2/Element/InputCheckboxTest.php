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
class HTML_QuickForm2_Element_InputCheckboxTest extends TestCase
{
    public const TEST_FORM_ID = 'boxed';
    public const TEST_NAME_CHECKBOXES = 'vegetable';
    public const TEST_NAME_SINGLE_CHECKBOX = 'box1';
    public const TEST_VALUE_SINGLE = '1';

    protected function setUp() : void
    {
        $_POST = array(
            self::TEST_NAME_SINGLE_CHECKBOX => self::TEST_VALUE_SINGLE,
            self::TEST_NAME_CHECKBOXES => array('1', '3')
        );

        $_GET = array();

        $_REQUEST[HTML_QuickForm2::generateTrackingVarName(self::TEST_FORM_ID)] = 'yes';
    }

    /**
     * By default, the value of a standalone checkbox is `1`.
     *
     * @see HTML_QuickForm2_Element_InputCheckbox::initValue()
     */
    public function testDefaultValueAttributeIs1() : void
    {
        $box = new HTML_QuickForm2_Element_InputCheckbox();
        $this->assertSame('1', $box->getAttribute('value'));
    }

    public function testSetValueAttributeConstructor() : void
    {
        $box = new HTML_QuickForm2_Element_InputCheckbox(null, array('value' => 'custom'));
        $this->assertSame('custom', $box->getAttribute('value'));
    }

    public function testSetValueAttributeMethod() : void
    {
        $box = new HTML_QuickForm2_Element_InputCheckbox();
        $box->setValueAttribute('custom');
        $this->assertSame('custom', $box->getAttribute('value'));
    }

    /**
     * Setting the value of a checkbox is like setting
     * its checked status, depending on whether the value
     * corresponds to the checkbox' value attribute. It
     * automatically adds or removes the `checked` attribute.
     */
    public function testSetValue() : void
    {
        $box = new HTML_QuickForm2_Element_InputCheckbox();
        $box->setValueAttribute('custom');

        $this->assertSame('custom', $box->getAttribute('value'));

        $this->assertFalse($box->setValue('no_match')->isChecked());
        $this->assertTrue($box->setValue('custom')->isChecked());
    }

    /**
     * Adding additional data sources must not have any effect
     * on checkbox values if the form has been submitted.
     */
    public function testCheckboxUncheckedOnSubmit() : void
    {
        $formPost = new HTML_QuickForm2(self::TEST_FORM_ID, 'post');

        $this->assertTrue($formPost->isSubmitted());

        $box1 = $formPost->addCheckbox(self::TEST_NAME_SINGLE_CHECKBOX);
        $box2 = $formPost->addCheckbox('box2');

        $this->assertEquals(self::TEST_VALUE_SINGLE, $box1->getValue());
        $this->assertNull($box2->getValue());

        $formPost->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'box2' => '99'
        )));

        $this->assertEquals(self::TEST_VALUE_SINGLE, $box1->getValue());
        $this->assertNull($box2->getValue());
    }

    /**
     * The checkbox must have a value if the form has not been
     * submitted, and a data source is available with a value.
     */
    public function testCheckboxUncheckedOnSubmitNoData() : void
    {
        $formGet = new HTML_QuickForm2('boxed2', 'get');

        $box3 = $formGet->addCheckbox('box3');
        $this->assertNull($box3->getValue());

        $formGet->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'box3' => '1'
        )));

        $this->assertEquals('1', $box3->getValue());
    }

   /**
    * Allow to properly set values for checkboxes named like 'box[]'
    * @see http://pear.php.net/bugs/bug.php?id=16806
    */
    public function testRequest16806() : void
    {
        $form = new HTML_QuickForm2(self::TEST_FORM_ID, 'post');
        $name = self::TEST_NAME_CHECKBOXES;

        $e1 = $form->addCheckbox($name.'[]', array('value' => 1), array('label' => 'carrot'));
        $e2 = $form->addCheckbox($name.'[]', array('value' => 2), array('label' => 'pea'));
        $e3 = $form->addCheckbox($name.'[]', array('value' => 3), array('label' => 'bean'));

        $this->assertTrue($e1->isChecked());
        $this->assertFalse($e2->isChecked());
        $this->assertTrue($e3->isChecked());

        $this->assertEquals('checked', $e1->getAttribute('checked'));
        $this->assertNotEquals('checked', $e2->getAttribute('checked'));
        $this->assertEquals('checked', $e3->getAttribute('checked'));
    }

   /**
    * Notices were emitted when 'content' key was missing from $data
    * @see http://pear.php.net/bugs/bug.php?id=16816
    */
    public function testBug16816() : void
    {
        $box = new HTML_QuickForm2_Element_InputCheckbox(
            self::TEST_NAME_CHECKBOXES.'[1]',
            array(
                'value' => 2,
                'checked' => 1
            ),
            array(
                'label' => 'pea'
            )
        );

        $this->assertIsString($box->__toString());
    }

   /**
    * Explicitly setting value to 0 resulted in value="1"
    * @see http://news.php.net/php.pear.general/31496
    */
    public function testValue0() : void
    {
        $box = new HTML_QuickForm2_Element_InputCheckbox(
            'testBox', array('value' => 0)
        );

        $this->assertStringContainsString('value="0"', $box->__toString());
    }

    /**
     * If a form contained only non-submit data sources, 'checked' attribute was unlikely to be ever cleared
     */
    public function testCheckedAttributeShouldBeCleared() : void
    {
        $formNoSubmit = new HTML_QuickForm2('neverSubmitted');
        $box1 = new HTML_QuickForm2_Element_InputCheckbox(self::TEST_NAME_SINGLE_CHECKBOX, array('checked' => null));
        $box2 = new HTML_QuickForm2_Element_InputCheckbox('box2');
        $formNoSubmit->appendChild($box1);
        $formNoSubmit->appendChild($box2);

        $this->assertNotNull($box1->getAttribute('checked'));
        $this->assertNull($box2->getAttribute('checked'));

        $formNoSubmit->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'box2' => true
        )));

        $this->assertNotNull($box2->getAttribute('checked'));
        $this->assertNull($box1->getAttribute('checked'));

        $box2->setName('box3');
        $this->assertNull($box2->getAttribute('checked'));
    }

    /**
     * If data source contains explicitly provided null values, those should be used
     * @link http://pear.php.net/bugs/bug.php?id=20295
     */
    public function testBug20295() : void
    {
        $form = new HTML_QuickForm2('bug20295');
        $box  = $form->addCheckbox('box', array('value' => 'yep', 'checked' => 'checked'));

        // data source searching should stop on finding this null
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'box' => null
        )));
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'box' => 'yep'
        )));

        $this->assertNull($box->getValue());
    }
}
