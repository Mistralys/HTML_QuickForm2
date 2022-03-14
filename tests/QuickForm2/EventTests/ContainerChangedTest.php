<?php

declare(strict_types=1);

namespace QuickForm2\EventTests;

use assets\QuickFormTestCase;
use HTML_QuickForm2_Container_Group;
use HTML_QuickForm2_Element_InputText;
use HTML_QuickForm2_Event_ContainerChanged;
use HTML_QuickForm2_Node;

final class ContainerChangedTest extends QuickFormTestCase
{
    // region: _Tests

    /**
     * Trigger the event when an element does not have
     * a container set yet.
     */
    public function test_triggerSetFromNULL() : void
    {
        $container = new HTML_QuickForm2_Container_Group();
        $text = new HTML_QuickForm2_Element_InputText('foo');

        $text->onContainerChanged(array($this, 'callback_containerChanged'));

        $this->assertNotEmpty($text->getId());
        $this->assertFalse($this->eventCalled);
        $this->assertFalse($container->hasChild($text));
        $this->assertNull($text->getContainer());

        $container->appendChild($text);

        $this->assertTrue($this->eventCalled);
        $this->assertSame($container, $text->getContainer());
        $this->assertTrue($container->hasChild($text));
    }

    public function test_triggerReplaceExisting() : void
    {
        $container1 = new HTML_QuickForm2_Container_Group('c1');
        $container2 = new HTML_QuickForm2_Container_Group('c2');

        $text = $container1->addText('foo');
        $text->onContainerChanged(array($this, 'callback_containerChanged'));

        $container2->appendChild($text);

        $this->assertFalse($container1->hasChild($text));
        $this->assertTrue($container2->hasChild($text));
        $this->assertSame($container2, $text->getContainer());
        $this->assertTrue($this->eventCalled);
    }

    /**
     * The setContainer method is public, so one may
     * try to use it outside appending or inserting
     * elements in containers. An error check is in place
     * that ensures that the element is already a child
     * of the target container.
     */
    public function test_notAddedToContainerException() : void
    {
        $container = new HTML_QuickForm2_Container_Group();
        $text = new HTML_QuickForm2_Element_InputText('foo');

        $this->expectExceptionCode(HTML_QuickForm2_Node::ERROR_CANNOT_SET_CONTAINER_WITHOUT_ADDING_ELEMENT);

        $text->setContainer($container);
    }

    /**
     * Removing the element from a container must
     * trigger the change event as well.
     */
    public function test_removeContainer() : void
    {
        $container = new HTML_QuickForm2_Container_Group();
        $text = $container->addText('foo');

        $text->onContainerChanged(array($this, 'callback_containerChanged'));

        $container->removeChild($text);

        $this->assertFalse($container->hasChild($text));
        $this->assertNull($text->getContainer());
        $this->assertTrue($this->eventCalled);
    }

    // endregion

    // region: Support methods

    private bool $eventCalled = false;

    public function callback_containerChanged(HTML_QuickForm2_Event_ContainerChanged $event, int $listenerID) : void
    {
        $this->eventCalled = true;
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->eventCalled = false;
    }

    // endregion
}
