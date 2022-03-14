<?php
/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */

namespace QuickForm2\ElementTests;

use HTML\QuickForm2\AbstractHTMLElement\GlobalOptions;
use HTML_QuickForm2;
use HTML_QuickForm2_DataSource_Array;
use HTML_QuickForm2_DataSource_NullAware;
use HTML_QuickForm2_ElementImpl;
use PHPUnit\Framework\TestCase;

/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
final class DataSourceTest extends TestCase
{
    // region: _Tests

    public function test_setValueFromSubmitDatasource() : void
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
    public function test_dataSourcePriority() : void
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

    public function test_updateValueFromNewDataSource() : void
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

    /**
     * If data source contains explicitly provided null values, those should be used
     * @link http://pear.php.net/bugs/bug.php?id=20295
     */
    public function test_bug20295() : void
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
