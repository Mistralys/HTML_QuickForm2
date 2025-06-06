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

use HTML\QuickForm2\ElementFactory;
use PHPUnit\Framework\TestCase;
use QuickFormTests\CustomClasses\TestContainerFilterImpl;


/**
 * A filter that modifies the value on every iteration
 * To make sure it is not called more times than it should.
 */
function repeatFilter($value)
{
    return substr($value, 0, 1).$value;
}

/**
 * Unit test for HTML_QuickForm2_Rule class
 */
class HTML_QuickForm2_FilterTest extends TestCase
{
    protected function setUp() : void
    {
        $_REQUEST[HTML_QuickForm2::resolveTrackVarName('filters')] = '';
        $_POST = array(
            'foo' => '  ',
            'bar' => 'VALUE',
            'baz' => array('VALUE1', 'VALUE2'),
            'sel' => 'VALUE2'
        );
    }

    public function testFiltersShouldPreserveNulls(): void
    {
        $mockElement = $this->getMockBuilder(HTML_QuickForm2_Element::class)
            ->onlyMethods(array(
                array(HTML_QuickForm2_Element::class, 'getType')[1],
                array(HTML_QuickForm2_Element::class, 'getRawValue')[1],
                array(HTML_QuickForm2_Element::class, 'setValue')[1],
                array(HTML_QuickForm2_Element::class, '__toString')[1]
            ))
                ->getMock();

        $mockElement
            ->expects($this->atLeastOnce())
            ->method(array(HTML_QuickForm2_Element::class, 'getRawValue')[1])
            ->willReturn(null);

        $mockElement->addFilter('trim');
        $this->assertNull($mockElement->getValue());

        $mockContainer = $this->getMockBuilder(HTML_QuickForm2_Container::class)
            ->onlyMethods(array(
                array(HTML_QuickForm2_Container::class, 'getType')[1],
                array(HTML_QuickForm2_Container::class, 'setValue')[1],
                array(HTML_QuickForm2_Container::class, '__toString')[1]
            ))
                ->getMock();

        $mockContainer->appendChild($mockElement);
        $mockContainer->addRecursiveFilter('intval');
        $mockContainer->addFilter('count');

        $this->assertNull($mockContainer->getValue());
    }

    public function testContainerValidation(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);
        $form->addRecursiveFilter('trim');

        $username = $form->addElement('text', 'foo');
        $username->addRule('required', 'Username is required');

        $this->assertFalse($form->validate());
        $this->assertSame('', $username->getValue());
    }

    public function testSelect(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);

        $select = $form->addSelect('sel')->loadOptions(
            array('VALUE1' => 'VALUE1', 'VALUE2' => 'VALUE2', 'VALUE3' => 'VALUE3'));
        $select->addFilter('strtolower');

        $this->assertEquals('value2', $select->getValue());
    }

    public function testSelectMultipleRecursive(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);

        $select = $form->addSelect('baz')
            ->makeMultiple()
            ->loadOptions(array(
                'VALUE1' => 'VALUE1',
                'VALUE2' => 'VALUE2',
                'VALUE3' => 'VALUE3'
            ))
            ->addRecursiveFilter('strtolower');

        $this->assertEquals(array('value1', 'value2'), $select->getValue());
    }

    public function testSelectMultipleNonRecursive(): void
    {
        $s = ElementFactory::select('foo')
            ->makeMultiple()
            ->setIntrinsicValidation(false)
            ->setValue(array('foo', 'bar'))
            ->addFilter('count');

        $this->assertEquals(2, $s->getValue());
    }

    public function testInputCheckable(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);

        $check = new HTML_QuickForm2_Element_InputCheckable('bar');
        $check->setAttribute('value', 'VALUE');
        $check->addFilter('strtolower');

        $form->appendChild($check);

        $this->assertEquals('value', $check->getValue());

        // in order to be set, the value must be equal to the one in
        // the value attribute
        $check->setValue('value');
        $this->assertNull($check->getValue());
        $check->setValue('VALUE');
        $this->assertEquals('value', $check->getValue());
    }

    public function testButton(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'bar' => 'VALUE'
        )));

        $button = $form->addButton('bar')
            ->makeSubmit()
            ->addFilter('strtolower');

        $this->assertEquals('value', $button->getValue());
    }

    public function testInput(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);
        $foo = $form->addText('foo');

        $this->assertEquals($_POST['foo'], $foo->getValue());

        $foo->addFilter('trim');

        $this->assertEquals(trim($_POST['foo']), $foo->getValue());
    }

    public function testTextarea(): void
    {
        $form = new HTML_QuickForm2('filters', 'post', null, false);
        $area = $form->addTextarea('bar');
        $area->addFilter('strtolower');
        $this->assertEquals('value', $area->getValue());
    }

    public function testContainer(): void
    {
        $c1 = new TestContainerFilterImpl('filter');
        $this->assertNull($c1->getValue());

        $el1 = $c1->addText('foo');
        $el2 = $c1->addText('bar');
        $el3 = $c1->addText('baz');
        $this->assertNull($c1->getValue());

        $el1->setValue('A');
        $el1->addFilter('repeatFilter');

        $el2->setValue('B');
        $el3->setValue('C');

        $this->assertEquals(array(
            'foo' => 'AA',
            'bar' => 'B',
            'baz' => 'C'
        ), $c1->getValue());

        $c1->addRecursiveFilter('strtolower');

        $this->assertEquals('aa', $el1->getValue());
        $this->assertEquals('b',  $el2->getValue());
        $this->assertEquals('c',  $el3->getValue());

        $c1->addRecursiveFilter('trim');
        $c1->addRecursiveFilter('repeatFilter');

        $this->assertEquals('aaa', $el1->getValue());
        $this->assertEquals('bb',  $el2->getValue());
        $this->assertEquals('cc',  $el3->getValue());
        // Second run, just to make sure...
        $this->assertEquals('aaa', $el1->getValue());
        $this->assertEquals('bb',  $el2->getValue());
        $this->assertEquals('cc',  $el3->getValue());
    }

    public function testGroup(): void
    {
        $value1     = array('foo' => 'foo');
        $value1F    = array('foo' => 'F');
        $value2     = array('bar' => 'bar', 'baz' => array('quux' => 'baz'));
        $value2F    = array('bar' => 'Bar', 'baz' => array('quux' => 'Baz'));
        $valueAnon  = array('e1' => 'e1');
        $valueAnonF = array('e1' => '1');
        $formValue  = array('g1' => $value1, 'g2' => array('i2' => $value2)) + $valueAnon;
        $formValueF = array('g1' => $value1F, 'g2' => array('i2' => $value2F)) + $valueAnonF;

        $form = new HTML_QuickForm2('testGroupGetValue');
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($formValue));

        $g1 = $form->addGroup('g1');
        $g1->addRecursiveFilter('strtoupper');

        $el1 = $g1->addText('foo');
        // Trim O *after* strtoupper
        $el1->addFilter('trim', array('O'));

        $g2 = $form->addGroup('g2[i2]');
        $g2->addRecursiveFilter('ucfirst');
        $g2->addText('bar');
        $g2->addText('baz[quux]');

        $anon = $form->addGroup();
        $anon->addText('e1');
        $anon->addRecursiveFilter('substr', array(1, 1));

        $this->assertEquals($formValueF, $form->getValue());
    }

    public function testContainerNonRecursive(): void
    {
        $c = new TestContainerFilterImpl('nonrecursive');
        $c->addRecursiveFilter('trim');
        $c->addFilter('count');

        $el1 = $c->addText('el1')->setValue(' foo');
        $el2 = $c->addText('el2')->setValue('bar ');

        $this->assertEquals(2, $c->getValue());
        $this->assertEquals('foo', $el1->getValue());
        $this->assertSame('bar', $el2->getValue());
    }
}

