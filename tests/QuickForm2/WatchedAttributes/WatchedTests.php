<?php

declare(strict_types=1);

namespace QuickForm2\WatchedAttributes;

use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;
use PHPUnit\Framework\TestCase;

class WatchedTests extends TestCase
{
    // region: _Tests

    public function test_readonlyException() : void
    {
        $attributes = new WatchedAttributes();
        $attributes->setReadonly('foo');

        $this->expectExceptionCode(WatchedAttributes::ERROR_ATTRIBUTE_IS_READONLY);

        $attributes->handleChanged('foo', 'bla');
    }

    public function test_watchedChanged() : void
    {
        $attributes = new WatchedAttributes();
        $attributes->setWatched('foo', array($this, 'callback_attributeChanged'));

        $attributes->handleChanged('foo', 'new');

        $this->assertTrue($this->callbackCalled);
        $this->assertSame('new', $this->callbackValue);
    }

    public function test_watchedUnchanged() : void
    {
        $attributes = new WatchedAttributes();
        $attributes->setWatched('foo', array($this, 'callback_attributeChanged'));

        $attributes->handleChanged('foo', null, null);

        $this->assertFalse($this->callbackCalled);
    }

    public function test_watchedChangedEmpty() : void
    {
        $attributes = new WatchedAttributes();
        $attributes->setWatched('foo', array($this, 'callback_attributeChanged'));

        $attributes->handleChanged('foo', null, '');

        $this->assertTrue($this->callbackCalled);
        $this->assertNull($this->callbackValue);
    }

    // endregion

    // region: Support methods

    private bool $callbackCalled = false;
    private ?string $callbackValue = null;

    protected function setUp() : void
    {
        parent::setUp();

        $this->callbackCalled = false;
        $this->callbackValue = null;
    }

    public function callback_attributeChanged(?string $value) : void
    {
        $this->callbackCalled = true;
        $this->callbackValue = $value;
    }

    // endregion
}
