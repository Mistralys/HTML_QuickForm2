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

use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for HTML_QuickForm2_Element class,
 */
class HTML_QuickForm2_ElementTest extends TestCase
{
    // region: _Tests

    public function testCanSetName() : void
    {
        $obj = new HTML_QuickForm2_ElementImpl();
        $this->assertNotNull($obj->getName(), 'Elements should always have \'name\' attribute');

        $obj = new HTML_QuickForm2_ElementImpl(self::DEFAULT_NAME);
        $this->assertEquals(self::DEFAULT_NAME, $obj->getName());

        $this->assertSame($obj, $obj->setName('bar'));
        $this->assertEquals('bar', $obj->getName());

        $obj->setAttribute('name', 'baz');
        $this->assertEquals('baz', $obj->getName());
    }


    public function testCanSetId()
    {
        $obj = new HTML_QuickForm2_ElementImpl(null, array('id' => 'manual'));
        $this->assertEquals('manual', $obj->getId());

        $this->assertSame($obj, $obj->setId('another'));
        $this->assertEquals('another', $obj->getId());

        $obj->setAttribute('id', 'yet-another');
        $this->assertEquals('yet-another', $obj->getId());
    }


    public function testCanNotRemoveNameOrId()
    {
        $obj = new HTML_QuickForm2_ElementImpl('somename', array(), array('id' => 'someid'));
        try {
            $obj->removeAttribute('name');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertRegExp('/Required attribute(.*)can not be removed/', $e->getMessage());
            try {
                $obj->removeAttribute('id');
            } catch (HTML_QuickForm2_InvalidArgumentException $e) {
                $this->assertRegExp('/Required attribute(.*)can not be removed/', $e->getMessage());
                return;
            }
        }
        $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
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

    public function testSetValueFromSubmitDatasource() : void
    {
        $form = new HTML_QuickForm2('form1');
        $elA = $form->appendChild(new HTML_QuickForm2_ElementImpl(self::DEFAULT_NAME));
        $elB = $form->appendChild(new HTML_QuickForm2_ElementImpl('bar'));

        $this->assertEquals(self::DEFAULT_VALUE, $elA->getValue());
        $this->assertNull($elB->getValue());
    }

    /**
     * Even if a datasource is added, the submitted data
     * must take precedence.
     */
    public function testDataSourcePriority() : void
    {
        $form = new HTML_QuickForm2('form1');

        $otherName = $this->getUniqueName();

        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            self::DEFAULT_NAME => 'overwritten value',
            $otherName => 'default value'
        )));

        $elA = $form->appendChild(new HTML_QuickForm2_ElementImpl(self::DEFAULT_NAME));
        $elB = $form->appendChild(new HTML_QuickForm2_ElementImpl($otherName));

        $this->assertEquals(self::DEFAULT_VALUE, $elA->getValue(), 'The element has a submitted value, this must take precedence.');
        $this->assertEquals('default value', $elB->getValue(), 'The element has no submitted value, so it must use the data source value.');
    }

    public function testUpdateValueFromNewDataSource() : void
    {
        $form = new HTML_QuickForm2('unsubmitted');
        $name = $this->getUniqueName();

        $el = $form->appendChild(new HTML_QuickForm2_ElementImpl($name));
        $this->assertNull($el->getValue());

        $ds = new HTML_QuickForm2_DataSource_Array(array(
            $name => 'updated value'
        ));

        $form->addDataSource($ds);

        $this->assertFalse($form->isSubmitted());
        $this->assertCount(1, $el->getDataSources());
        $this->assertCount(1, $form->getDataSources());

        $this->assertEquals('updated value', $el->getValue());
    }

    public function testUpdateValueOnNameChange() : void
    {
        $form = new HTML_QuickForm2('form1');
        $elFoo = $form->appendChild(new HTML_QuickForm2_ElementImpl(self::DEFAULT_NAME));

        $elFoo->setName(self::ALTERNATE_NAME);

        $this->assertEquals(self::ALTERNATE_VALUE, $elFoo->getValue());
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

        $this->assertNotRegExp('/^\d/', $el->getId());
    }

    /**
     * If data source contains explicitly provided null values, those should be used
     * @link http://pear.php.net/bugs/bug.php?id=20295
     */
    public function testBug20295() : void
    {
        $form = new HTML_QuickForm2('bug20295');
        $name = $this->getUniqueName();

        $el = $form->appendChild(new HTML_QuickForm2_ElementImpl($name));
        $el->setValue('not empty');

        $ds = new HTML_QuickForm2_DataSource_Array(array(
            $name => null
        ));

        $this->assertInstanceOf(HTML_QuickForm2_DataSource_NullAware::class, $ds);

        $form->addDataSource($ds);

        $this->assertNull($el->getValue());
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

    // endregion

    // region: Support methods

    public const DEFAULT_VALUE = 'default value';
    public const ALTERNATE_VALUE = 'alternate value';
    public const DEFAULT_NAME = 'default_element';
    public const ALTERNATE_NAME = 'alternate_element';

    private static int $nameCounter = 0;

    private function getUniqueName() : string
    {
        self::$nameCounter++;

        return 'unique'.self::$nameCounter;
    }

    protected function setUp() : void
    {
        GlobalOptions::setIDAppendEnabled(true);

        $_REQUEST = array(
            '_qf__form1' => ''
        );

        $_POST = array(
            self::DEFAULT_NAME => self::DEFAULT_VALUE,
            self::ALTERNATE_NAME => self::ALTERNATE_VALUE
        );
    }

    protected function tearDown() : void
    {
        GlobalOptions::setIDAppendEnabled(true);
    }

    // endregion
}
