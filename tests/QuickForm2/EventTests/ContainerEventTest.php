<?php
/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */

declare(strict_types=1);

namespace QuickForm2\EventTests;

use assets\QuickFormTestCase;
use HTML_QuickForm2_Container_Group;
use HTML_QuickForm2_Event_ContainerNameChanged;
use HTML_QuickForm2_Event_ContainerNodeAdded;

/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
final class ContainerEventTest extends QuickFormTestCase
{
    // region: _Tests

    /**
     * Whenever an element is added to a container,
     * the node added event must be triggered.
     */
    public function test_nodeAdded() : void
    {
        $container = new HTML_QuickForm2_Container_Group();
        $listenerID = $container->onNodeAdded(array($this, 'callback_nodeAdded'), array('bar'));

        $container->addText('foo');

        $this->assertNotNull($this->nodeAddedEventCalled);
        $this->assertSame('bar', $this->eventArg);
        $this->assertSame($listenerID, $this->nodeAddedListener);
    }

    /**
     * Whenever the container's name is modified, the
     * name changed event must be triggered.
     */
    public function test_nameChanged() : void
    {
        $container = new HTML_QuickForm2_Container_Group('old-name');
        $listenerID = $container->onNameChanged(array($this, 'callback_nameChanged'));

        $container->setName('new-name');

        $this->assertNotNull($this->nameChangeEvent);
        $this->assertSame('old-name', $this->nameChangeEvent->getOldName());
        $this->assertSame('new-name', $this->nameChangeEvent->getNewName());
        $this->assertSame($listenerID, $this->nameChangedListener);
    }

    public function test_nameChangedNULL() : void
    {
        // Group without a name
        $container = new HTML_QuickForm2_Container_Group();
        $listenerID = $container->onNameChanged(array($this, 'callback_nameChanged'));

        $container->setName('new-name');

        $this->assertNotNull($this->nameChangeEvent);
        $this->assertNull($this->nameChangeEvent->getOldName());
        $this->assertSame('new-name', $this->nameChangeEvent->getNewName());
        $this->assertSame($listenerID, $this->nameChangedListener);
    }

    // endregion

    // region: Support methods

    private ?HTML_QuickForm2_Event_ContainerNodeAdded $nodeAddedEventCalled = null;
    private ?HTML_QuickForm2_Event_ContainerNameChanged $nameChangeEvent = null;
    private string $eventArg = '';
    private int $nodeAddedListener = 0;
    private int $nameChangedListener = 0;

    public function callback_nodeAdded(HTML_QuickForm2_Event_ContainerNodeAdded $event, int $listenerID, string $parameter) : void
    {
        $this->nodeAddedEventCalled = $event;
        $this->eventArg = $parameter;
        $this->nodeAddedListener = $listenerID;
    }

    public function callback_nameChanged(HTML_QuickForm2_Event_ContainerNameChanged $event, int $listenerID) : void
    {
        $this->nameChangeEvent = $event;
        $this->nameChangedListener = $listenerID;
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->nodeAddedEventCalled = null;
        $this->nameChangeEvent = null;
        $this->nodeAddedListener = 0;
        $this->nameChangedListener = 0;
        $this->eventArg = '';
    }

    // endregion
}
