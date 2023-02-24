<?php
/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */

namespace QuickForm2\ElementTests;

use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;
use HTML_QuickForm2;
use HTML_QuickForm2_Container_Group;
use HTML_QuickForm2_Element_InputText;
use HTML_QuickForm2_Event_ElementNameChanged;
use TestAssets\MockElement;
use HTML_QuickForm2_Node;
use PHPUnit\Framework\TestCase;

/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
final class NameTest extends TestCase
{
    // region: _Tests

    public function test_nameNotNullableException() : void
    {
        $this->expectExceptionCode(HTML_QuickForm2_Node::ERROR_NAME_IS_NOT_NULLABLE);

        new MockElement();
    }

    public function test_setNameInConstructor() : void
    {
        $obj = new MockElement('initial_name');
        $this->assertEquals('initial_name', $obj->getName());
    }

    public function test_setNameViaMethod() : void
    {
        $obj = new MockElement('foo');

        $obj->setName('bar');

        $this->assertEquals('bar', $obj->getName());
        $this->assertSame('bar', $obj->getAttribute('name'));
    }

    public function test_setNameViaAttribute() : void
    {
        $obj = new MockElement('foo');

        $obj->setAttribute('name', 'bar');

        $this->assertSame('bar', $obj->getName());
        $this->assertSame('bar', $obj->getAttribute('name'));
    }

    public function test_canNotRemoveName() : void
    {
        $obj = new MockElement('foo');

        $this->expectExceptionCode(HTML_QuickForm2_Node::ERROR_NAME_IS_NOT_NULLABLE);

        $obj->removeAttribute('name');
    }

    public function test_updateValueOnNameChange() : void
    {
        $form = new HTML_QuickForm2('form1');
        $elFoo = $form->appendChild(new MockElement(self::DEFAULT_NAME));

        $elFoo->setName(self::ALTERNATE_NAME);

        $this->assertEquals(self::ALTERNATE_VALUE, $elFoo->getValue());
    }

    public function test_getArrayKey() : void
    {
        $this->assertSame(
            (new HTML_QuickForm2_Element_InputText('el1'))->getNamePathRelative(),
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
            (new HTML_QuickForm2_Element_InputText('el1[sub1]'))->getNamePathRelative(),
            array('el1', 'sub1')
        );

        $g1 = new HTML_QuickForm2_Container_Group('g1');
        $g1e1 = $g1->addText('g1e1');

        $this->assertSame('g1', $g1->getNamePathRelative());
        $this->assertSame('g1e1', $g1e1->getNamePathRelative());
    }

    public function test_event_nameChanged() : void
    {
        $el = new MockElement('foo');
        $el->onNameChanged(array($this, 'callback_nameChanged'));

        $el->setName('bar');

        $this->assertNotNull($this->nameChangedEvent);
        $this->assertSame('foo', $this->nameChangedEvent->getOldName());
        $this->assertSame('bar', $this->nameChangedEvent->getNewName());
    }

    // endregion

    // region: Support methods

    private ?HTML_QuickForm2_Event_ElementNameChanged $nameChangedEvent = null;

    protected function setUp() : void
    {
        parent::setUp();

        $this->nameChangedEvent = null;
    }

    public function callback_nameChanged(HTML_QuickForm2_Event_ElementNameChanged $changed, int $listenerID) : void
    {
        $this->nameChangedEvent = $changed;
    }

    // endregion
}
