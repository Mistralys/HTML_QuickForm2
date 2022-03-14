<?php
/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */

namespace QuickForm2\ElementTests;

use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;
use HTML_QuickForm2_ElementImpl;
use PHPUnit\Framework\TestCase;

/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
final class IDTest extends TestCase
{
    // region: _Tests

    public function testCanSetId()
    {
        $obj = new HTML_QuickForm2_ElementImpl(null, array('id' => 'manual'));
        $this->assertEquals('manual', $obj->getId());

        $this->assertSame($obj, $obj->setId('another'));
        $this->assertEquals('another', $obj->getId());

        $obj->setAttribute('id', 'yet-another');
        $this->assertEquals('yet-another', $obj->getId());
    }

    public function testCanNotRemoveId() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl('somename', array(), array('id' => 'someid'));

        $this->expectExceptionCode(WatchedAttributes::ERROR_ATTRIBUTE_IS_READONLY);

        $obj->removeAttribute('id');
    }

    public function testUniqueIdsGenerated() : void
    {
        $names = array(
            '',
            'value',
            'array[]',
            'array[8]',
            'array[60000]',
            'array[20]',
            'array[name][]',
            'bigger[name][5]',
            'bigger[name][]',
            'bigger[name][6]'
        );

        $usedIds = array();

        foreach($names as $name)
        {
            $el = new HTML_QuickForm2_ElementImpl($name);
            $this->assertNotEquals('', $el->getId(), 'Should have an auto-generated \'id\' attribute');
            $usedIds[] = $el->getId();
            $this->assertContains($el->getId(), $usedIds);

            // Duplicate name...
            $el2 = new HTML_QuickForm2_ElementImpl($name);
            $this->assertNotContains($el2->getId(), $usedIds);
            $usedIds[] = $el2->getId();
        }
    }

    public function testManualIdsNotReused() : void
    {
        // use a unique element name for this test, to avoid conflicts
        // with other tests.
        $elName = 'grabby';

        $usedIds = array(
            $elName.'-0',
            $elName.'-2',
            $elName.'-bar-0',
            $elName.'-bar-2',
            $elName.'-baz-0-0'
        );

        $names = array(
            $elName,
            $elName.'[bar]',
            $elName.'[baz][]'
        );

        foreach ($usedIds as $id)
        {
            new HTML_QuickForm2_ElementImpl($elName, array('id' => $id));
        }

        foreach ($names as $name)
        {
            $el = new HTML_QuickForm2_ElementImpl($name);
            $this->assertNotContains($el->getId(), $usedIds);

            $usedIds[] = $el->getId();

            // Duplicate name...
            $el2 = new HTML_QuickForm2_ElementImpl($name);
            $this->assertNotContains($el2->getId(), $usedIds);

            $usedIds[] = $el2->getId();
        }
    }

    public function testUniqueIdsGeneratedWithoutIndexes() : void
    {
        GlobalOptions::setIDAppendEnabled(false);

        $this->testUniqueIdsGenerated();
    }

    /**
     * Prevent generating ids like "0-0" for (grouped) elements named "0"
     * @see http://news.php.net/php.pear.general/31496
     */
    public function testGeneratedIdsShouldNotStartWithNumbers() : void
    {
        $el = new HTML_QuickForm2_ElementImpl('0');

        $this->assertDoesNotMatchRegularExpression('/^\d/', $el->getId());
    }

    // endregion

    // region: Support methods

    protected function setUp() : void
    {
        GlobalOptions::setIDAppendEnabled(true);
    }

    protected function tearDown() : void
    {
        GlobalOptions::setIDAppendEnabled(true);
    }

    // endregion
}
