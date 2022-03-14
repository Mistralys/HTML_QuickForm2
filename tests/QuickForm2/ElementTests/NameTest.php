<?php
/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */

namespace QuickForm2\ElementTests;

use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;
use HTML_QuickForm2_Container_Group;
use HTML_QuickForm2_Element_InputText;
use HTML_QuickForm2_ElementImpl;
use PHPUnit\Framework\TestCase;

/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
final class NameTest extends TestCase
{
    public function test_nameNotNull() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl();
        $this->assertNotNull($obj->getName(), 'Elements should always have \'name\' attribute');
    }

    public function test_setNameInConstructor() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl('initial_name');
        $this->assertEquals('initial_name', $obj->getName());
    }

    public function test_setNameViaMethod() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl();

        $this->assertSame($obj, $obj->setName('bar'));
        $this->assertEquals('bar', $obj->getName());
    }

    public function test_setNameViaAttribute() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl();

        $obj->setAttribute('name', 'baz');
        $this->assertEquals('baz', $obj->getName());
    }

    public function test_canNotRemoveName() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl('foo', array(), array('id' => 'foobar'));

        $this->expectExceptionCode(WatchedAttributes::ERROR_ATTRIBUTE_IS_READONLY);

        $obj->removeAttribute('name');
    }

    public function test_updateValueOnNameChange() : void
    {
        $form = new HTML_QuickForm2('form1');
        $elFoo = $form->appendChild(new HTML_QuickForm2_ElementImpl(self::DEFAULT_NAME));

        $elFoo->setName(self::ALTERNATE_NAME);

        $this->assertEquals(self::ALTERNATE_VALUE, $elFoo->getValue());
    }

    public function test_getArrayKey() : void
    {
        $this->assertSame(
            (new HTML_QuickForm2_Element_InputText('el1'))->getArrayPath(),
            'el1'
        );

        // name and value can be on different levels:
        // Element = el1
        // Value = el1 > sub1
        //
        // Have a method that returns the path to the value,
        // which is then traversed until it's found, for each
        // element separately.
        //
        // Path must be relative to the container.
        //
        // g1[el1][sub1][sub2]
        // g1 > el1 > sub1 > sub2
        //

        $this->assertSame(
            (new HTML_QuickForm2_Element_InputText('el1[sub1]'))->getArrayPath(),
            'el1' // ????
        );

        $g1 = new HTML_QuickForm2_Container_Group('g1');
        $g1e1 = $g1->addText('g1e1');

        $this->assertSame('g1', $g1->getArrayPath());
        $this->assertSame('g1e1', $g1e1->getArrayPath());
    }
}
