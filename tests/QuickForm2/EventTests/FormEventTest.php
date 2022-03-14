<?php

declare(strict_types=1);

namespace QuickForm2\EventTests;

use assets\QuickFormTestCase;
use HTML_QuickForm2_Event_ContainerNodeAdded;
use HTML_QuickForm2_Event_FormNodeAdded;

/**
 * Tests event handlers specific to the form itself.
 *
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
final class FormEventTest extends QuickFormTestCase
{
    // region: _Tests

    /**
     * Adding a node to the form: This must call both
     * the "form node added" and "container node added"
     * events.
     */
    public function test_formNodeAdded() : void
    {
        $form = $this->createForm();
        $form->onFormNodeAdded(array($this, 'callback_formNodeAdded'));
        $form->onNodeAdded(array($this, 'callback_nodeAdded'));

        $form->addText('foo');

        $this->assertTrue($this->formEventCalled);
        $this->assertTrue($this->containerEventCalled);
    }

    /**
     * Adding a node to one of the form's children:
     * The "form node added" event must be triggered,
     * but not the "node added" (which is for the form
     * itself).
     */
    public function test_childNodeAdded() : void
    {
        $form = $this->createForm();
        $group = $form->addGroup('group');

        $this->assertFalse($this->formEventCalled);
        $this->assertFalse($this->containerEventCalled);

        $form->onFormNodeAdded(array($this, 'callback_formNodeAdded'));
        $form->onNodeAdded(array($this, 'callback_nodeAdded'));

        $group->addText('foo');

        $this->assertTrue($this->formEventCalled);
        $this->assertFalse($this->containerEventCalled);
    }

    // endregion

    // region: Support methods

    private bool $formEventCalled = false;
    private bool $containerEventCalled = false;

    public function callback_formNodeAdded(HTML_QuickForm2_Event_FormNodeAdded $event, int $listenerID) : void
    {
        $this->formEventCalled = true;
    }

    public function callback_nodeAdded(HTML_QuickForm2_Event_ContainerNodeAdded $event, int $listenerID) : void
    {
        $this->containerEventCalled = true;
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->containerEventCalled = false;
        $this->formEventCalled = false;
    }

    // endregion
}
