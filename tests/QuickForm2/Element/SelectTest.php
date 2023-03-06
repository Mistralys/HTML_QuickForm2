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

use HTML\QuickForm2\Element\Select\SelectOption;
use PHPUnit\Framework\TestCase;
use QuickFormTests\CustomClasses\TestCustomOptGroup;
use QuickFormTests\CustomClasses\TestSelectWithCustomGroups;

/** Sets up includes */
require_once dirname(dirname(__DIR__)) . '/TestHelper.php';

/**
 * Let's just make parseAttributes() public rather than copy and paste regex
 */
abstract class HTML_QuickForm2_Element_SelectTest_AttributeParser extends BaseHTMLElement
{
    public static function parseAttributes($attrString)
    {
        return parent::parseAttributes($attrString);
    }
}

/**
 * Unit test for HTML_QuickForm2_Element_Select class
 */
class HTML_QuickForm2_Element_SelectTest extends TestCase
{
    protected function setUp() : void
    {
        $_POST = array(
            'single1' => '1'
        );
        $_GET = array();
    }

    public function testSelectIsEmptyByDefault()
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $this->assertNull($sel->getValue());
        $this->assertMatchesRegularExpression(
            '!^<select[^>]*>\\s*</select>$!',
            $sel->__toString()
        );
    }

    public function testSelectSingleValueIsScalar()
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Text', 'Value');
        $this->assertSame($sel, $sel->setValue('Value'));
        $this->assertEquals('Value', $sel->getValue());

        $this->assertSame($sel, $sel->setValue('Nonextistent'));
        $this->assertNull($sel->getValue());

        $sel2 = new HTML_QuickForm2_Element_Select();
        $sel2->addOption('Text', 'Value');
        $sel2->addOption('Other Text', 'Other Value');
        $sel2->addOption('Different Text', 'Different Value');

        $sel2->setValue(array('Different value', 'Value'));
        $this->assertEquals('Value', $sel2->getValue());
    }

    public function testSelectMultipleValueIsArray()
    {
        $sel = new HTML_QuickForm2_Element_Select('mult', array('multiple'));
        $sel->addOption('Text', 'Value');
        $sel->addOption('Other Text', 'Other Value');
        $sel->addOption('Different Text', 'Different Value');

        $this->assertSame($sel, $sel->setValue('Other Value'));
        $this->assertEquals(array('Other Value'), $sel->getValue());

        $this->assertSame($sel, $sel->setValue('Nonexistent'));
        $this->assertNull($sel->getValue());

        $this->assertSame($sel, $sel->setValue(array('Value', 'Different Value', 'Nonexistent')));
        $this->assertEquals(array('Value', 'Different Value'), $sel->getValue());
    }

    public function testDisabledSelectHasNoValue()
    {
        $sel = new HTML_QuickForm2_Element_Select('disableMe', array('disabled'));
        $sel->addOption('Text', 'Value');
        $sel->setValue('Value');

        $this->assertNull($sel->getValue());
    }

    public function testDisabledOptionsDoNotProduceValues()
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Disabled Text', 'Disabled Value', array('disabled'));
        $sel->setValue('Disabled Value');

        $this->assertNull($sel->getValue());
    }


    public function testAddOption()
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Text', 'Value');
        $this->assertMatchesRegularExpression(
            '!^<select[^>]*>\\s*<option[^>]+value="Value"[^>]*>Text</option>\\s*</select>!',
            $sel->__toString()
        );

        $sel2 = new HTML_QuickForm2_Element_Select();
        $sel2->addOption('Text', 'Value', array('class' => 'bar'));
        $this->assertMatchesRegularExpression(
            '!<option[^>]+class="bar"[^>]*>Text</option>!',
            $sel2->__toString()
        );

        $sel3 = new HTML_QuickForm2_Element_Select();
        $sel3->addOption('Text', 'Value', array('selected'));
        $this->assertEquals('Value', $sel3->getValue());
        $this->assertMatchesRegularExpression(
            '!<option[^>]+selected="selected"[^>]*>Text</option>!',
            (string)$sel3
        );
    }

    public function testPrependOption() : void
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Text', 'Value');
        $sel->prependOption('First', 'Value');

        $list = $sel->getOptionContainer()->getOptions();
        $this->assertNotEmpty($list);
        $this->assertArrayHasKey(0, $list);
        $this->assertArrayHasKey('text', $list[0]);
        $this->assertSame('First', $list[0]['text']);
    }

    public function testEmptyValue() : void
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Text', '');

        $this->assertStringContainsString('value=""', (string)$sel);
    }

    public function testNULLValue() : void
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Text', null);

        $this->assertStringContainsString('value=""', (string)$sel);
    }

    public function testCountOptions() : void
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $sel->addOption('Text 1', 'text1');
        $sel->addOption('Text 2', 'text2');
        $sel->addOption('Text 3', 'text3');

        $this->assertSame(3, $sel->countOptions());
    }

    public function testCountOptionsRecursive() : void
    {
        $sel = new HTML_QuickForm2_Element_Select();

        $group = $sel->addOptgroup('Group 1');
        $group->addOption('Text 1', 'text1');
        $group->addOption('Text 2', 'text2');
        $group->addOption('Text 3', 'text3');

        $this->assertSame(3, $sel->countOptions());
    }

    public function testAddOptgroup()
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $optgroup = $sel->addOptgroup('Label');
        $this->assertInstanceOf('HTML_QuickForm2_Element_Select_Optgroup', $optgroup);
        $this->assertMatchesRegularExpression(
            '!^<select[^>]*>\\s*<optgroup[^>]+label="Label"[^>]*>\\s*</optgroup>\\s*</select>!',
            $sel->__toString()
        );

        $sel2 = new HTML_QuickForm2_Element_Select();
        $optgroup2 = $sel2->addOptgroup('Label', array('class' => 'bar'));
        $this->assertMatchesRegularExpression(
            '!<optgroup[^>]+class="bar"[^>]*>\\s*</optgroup>!',
            $sel2->__toString()
        );
    }

    public function testAddOptionToOptgroup()
    {
        $sel = new HTML_QuickForm2_Element_Select();
        $optgroup = $sel->addOptgroup('Label');
        $optgroup->addOption('Text', 'Value');
        $this->assertMatchesRegularExpression(
            '!^<select[^>]*>\\s*<optgroup[^>]+label="Label"[^>]*>\\s*' .
            '<option[^>]+value="Value"[^>]*>Text</option>\\s*</optgroup>\\s*</select>!',
            $sel->__toString()
        );

        $sel2 = new HTML_QuickForm2_Element_Select();
        $optgroup2 = $sel2->addOptgroup('Label');
        $optgroup2->addOption('Text', 'Value', array('class' => 'bar'));
        $this->assertMatchesRegularExpression(
            '!<optgroup[^>]+label="Label"[^>]*>\\s*<option[^>]+class="bar"[^>]*>Text</option>\\s*</optgroup>!',
            $sel2->__toString()
        );

        $sel3 = new HTML_QuickForm2_Element_Select();
        $optgroup3 = $sel3->addOptgroup('Label');
        $optgroup3->addOption('Text', 'Value', array('selected'));
        $this->assertEquals('Value', $sel3->getValue());
        $this->assertMatchesRegularExpression(
            '!<optgroup[^>]+label="Label"[^>]*>\\s*<option[^>]+selected="selected"[^>]*>Text</option>\\s*</optgroup>!',
            $sel3->__toString()
        );
    }

    public function testLoadOptions()
    {
        $sel = new HTML_QuickForm2_Element_Select('loadOptions', array('multiple'));
        $this->assertSame($sel, $sel->loadOptions(array('one' => 'First', 'two' => 'Second')));
        $sel->setValue(array('one', 'two'));
        $this->assertMatchesRegularExpression(
            '!<option[^>]+value="one"[^>]*>First</option>\\s*<option[^>]+value="two"[^>]*>Second</option>!',
            $sel->__toString()
        );
        $this->assertEquals(array('one', 'two'), $sel->getValue());

        $sel->loadOptions(array('Label' => array('two' => 'Second', 'three' => 'Third')));
        $this->assertMatchesRegularExpression(
            '!<optgroup[^>]+label="Label"[^>]*>\\s*<option[^>]+value="two"[^>]*>Second</option>\\s*' .
            '<option[^>]+value="three"[^>]*>Third</option>\\s*</optgroup>!',
            $sel->__toString()
        );
        $this->assertDoesNotMatchRegularExpression(
            '!<option[^>]+value="one"[^>]*>First</option>!',
            $sel->__toString()
        );
        $this->assertEquals(array('two'), $sel->getValue());
    }

    public function testSelectMultipleName()
    {
        $sel = new HTML_QuickForm2_Element_Select('foo', array('multiple'));
        $this->assertMatchesRegularExpression('/name="foo\\[\\]"/', $sel->__toString());
    }

    public function testFrozenHtmlGeneration()
    {
        $sel = new HTML_QuickForm2_Element_Select('foo');
        $sel->addOption('Text', 'Value');
        $sel->setValue('Value');
        $sel->toggleFrozen(true);

        $sel->persistentFreeze(false);
        $this->assertDoesNotMatchRegularExpression('/[<>]/', $sel->__toString());
        $this->assertMatchesRegularExpression('/Text/', $sel->__toString());

        $sel->persistentFreeze(true);
        $this->assertMatchesRegularExpression('/Text/', $sel->__toString());
        $this->assertMatchesRegularExpression('!<input[^>]+type="hidden"[^>]*/>!', $sel->__toString());

        preg_match('!<input([^>]+)/>!', $sel->__toString(), $matches);
        $this->assertEquals(
            array('id' => $sel->getId(), 'name' => 'foo', 'value' => 'Value', 'type' => 'hidden'),
            HTML_QuickForm2_Element_SelectTest_AttributeParser::parseAttributes($matches[1])
        );

        $sel->setValue('Nonexistent');
        $this->assertDoesNotMatchRegularExpression('/Text/', $sel->__toString());
        $this->assertDoesNotMatchRegularExpression('/[<>]/', $sel->__toString());
    }

    public function testSelectMultipleFrozenHtmlGeneration()
    {
        $sel = new HTML_QuickForm2_Element_Select('foo', array('multiple'));
        $sel->addOption('FirstText', 'FirstValue');
        $sel->addOption('SecondText', 'SecondValue');
        $sel->setValue(array('FirstValue', 'SecondValue'));
        $sel->toggleFrozen(true);

        $this->assertMatchesRegularExpression('/FirstText.*SecondText/s', $sel->__toString());
        $this->assertMatchesRegularExpression('!<input[^>]+type="hidden"[^>]*/>!', $sel->__toString());

        preg_match_all('!<input([^>]+)/>!', $sel->__toString(), $matches, PREG_SET_ORDER);
        $this->assertEquals(
            array('name' => 'foo[]', 'value' => 'FirstValue', 'type' => 'hidden'),
            HTML_QuickForm2_Element_SelectTest_AttributeParser::parseAttributes($matches[0][1])
        );
        $this->assertEquals(
            array('name' => 'foo[]', 'value' => 'SecondValue', 'type' => 'hidden'),
            HTML_QuickForm2_Element_SelectTest_AttributeParser::parseAttributes($matches[1][1])
        );
    }

    public function testSelectMultipleNoOptionsSelectedOnSubmit()
    {
        $options = array('1' => 'Option 1', '2' => 'Option 2');

        $formPost = new HTML_QuickForm2('multiple', 'post', null, false);
        $single1  = $formPost->appendChild(new HTML_QuickForm2_Element_Select('single1', null, array('options' => $options)));
        $single2  = $formPost->appendChild(new HTML_QuickForm2_Element_Select('single2', null, array('options' => $options)));
        $multiple = $formPost->appendChild(new HTML_QuickForm2_Element_Select('mult', array('multiple'), array('options' => $options)));
        $this->assertEquals('1', $single1->getValue());
        $this->assertNull($single2->getValue());
        $this->assertNull($multiple->getValue());

        $formPost->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'single1' => '2',
            'single2' => '2',
            'mult' => array('1', '2')
        )));
        $this->assertEquals('1', $single1->getValue());
        $this->assertEquals('2', $single2->getValue());
        $this->assertNull($multiple->getValue());

        $formGet   = new HTML_QuickForm2('multiple2', 'get', null, false);
        $multiple2 = $formGet->appendChild(new HTML_QuickForm2_Element_Select('mult2', array('multiple'), array('options' => $options)));
        $this->assertNull($multiple2->getValue());

        $formGet->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'mult2' => array('1', '2')
        )));
        $this->assertEquals(array('1', '2'), $multiple2->getValue());
    }

    public function testBug11138()
    {
        $options = array('2' => 'TwoWithoutZero', '02' => 'TwoWithZero');

        $sel = new HTML_QuickForm2_Element_Select('bug11138');
        $sel->loadOptions($options);
        $sel->setValue('02');

        $selHtml = $sel->__toString();
        $this->assertMatchesRegularExpression(
            '!selected="selected"[^>]*>TwoWithZero!', $selHtml
        );
        $this->assertDoesNotMatchRegularExpression(
            '!selected="selected"[^>]*>TwoWithoutZero!', $selHtml
        );

        $sel->toggleFrozen(true);
        $selFrozen = $sel->__toString();
        $this->assertStringContainsString('TwoWithZero', $selFrozen);
        $this->assertStringContainsString('value="02"', $selFrozen);
        $this->assertStringNotContainsString('TwoWithoutZero', $selFrozen);
        $this->assertStringNotContainsString('value="2"', $selFrozen);
    }

   /**
    * Disable possibleValues checks in getValue()
    *
    * For lazy people who add options to selects on client side and do not
    * want to add the same stuff server-side
    *
    * @link http://pear.php.net/bugs/bug.php?id=13088
    * @link http://pear.php.net/bugs/bug.php?id=16974
    */
    public function testDisableIntrinsicValidation()
    {
        $selectSingle = new HTML_QuickForm2_Element_Select(
            'foo', null, array('intrinsic_validation' => false)
        );
        $selectSingle->setValue('foo');
        $this->assertEquals('foo', $selectSingle->getValue());

        $selectSingle->loadOptions(array('one' => 'First', 'two' => 'Second'));
        $selectSingle->setValue('three');
        $this->assertEquals('three', $selectSingle->getValue());

        $selectMultiple = new HTML_QuickForm2_Element_Select(
            'bar', array('multiple'), array('intrinsic_validation' => false)
        );
        $selectMultiple->loadOptions(array('one' => 'First', 'two' => 'Second'));
        $selectMultiple->setValue(array('two', 'three'));
        $this->assertEquals(array('two', 'three'), $selectMultiple->getValue());
    }

    /**
     * If data source contains explicitly provided null values, those should be used
     * @link http://pear.php.net/bugs/bug.php?id=20295
     */
    public function testBug20295()
    {
        $form = new HTML_QuickForm2('bug20295');
        $ms   = $form->addSelect('multiselect', array('multiple'))
                    ->loadOptions(array('one' => 'First option', 'two' => 'Second option'))
                    ->setValue(array('two'));

        // data source searching should stop on finding this null
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'multiselect' => null
        )));
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'multiselect' => array('one')
        )));

        $this->assertNull($ms->getValue());
    }

    /**
     * Test select elements with custom option group classes.
     */
    public function testCustomOptGroupClass() : void
    {
        $select = new TestSelectWithCustomGroups('foo');

        $this->assertInstanceOf(
            TestCustomOptGroup::class,
            $select->addOptgroup('Label')
        );
    }

    public function testGetOptionByValue() : void
    {
        $select = new HTML_QuickForm2_Element_Select('foo');
        $select->addOption('Label', 'value');

        $option = $select->getOptionByValue('value');

        $this->assertInstanceOf(SelectOption::class, $option);
        $this->assertSame('value', $option['attr']['value']);
    }

    public function testGetSelectedOption() : void
    {
        $select = new HTML_QuickForm2_Element_Select('foo');
        $select->addOption('Label', 'value');

        $this->assertNull($select->getSelectedOption());

        $select->setValue('value');

        $this->assertNotNull($select->getSelectedOption());
    }

    public function testGetSelectedOptionWithEmptyValue() : void
    {
        $select = new HTML_QuickForm2_Element_Select('foo');
        $select->addOption('Please select...', '');
        $select->addOption('Label', 'value');

        $select->setValue('');

        $this->assertNotNull($select->getSelectedOption());
    }
}
