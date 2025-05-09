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
 * @package   HTML_QuickForm2
 * @author    Alexey Borzov <avb@php.net>
 * @author    Bertrand Mansion <golgote@mamasam.com>
 * @category  HTML
 * @copyright 2006-2020 Alexey Borzov <avb@php.net>, Bertrand Mansion <golgote@mamasam.com>
 * @license   https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      https://pear.php.net/package/HTML_QuickForm2
 */

declare(strict_types=1);

namespace QuickFormTests\Form;

use HTML_QuickForm2;
use HTML_QuickForm2_DataSource_Array;
use HTML_QuickForm2_Exception;
use HTML_QuickForm2_InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QuickFormTests\CustomClasses\TestFormRule;

/**
 * Unit test for HTML_QuickForm2 class
 */
class QuickFormTests extends TestCase
{
    protected function setUp() : void
    {
        $_REQUEST = array(
            HTML_QuickForm2::resolveTrackVarName('track') => ''
        );

        $_GET = array(
            'key' => 'value'
        );

        $_POST = array();

        $_FILES = array();
    }

    public function testTrackSubmit() : void
    {
        $tests = array(
            array(
                'label' => 'Tracking enabled, and POST empty.',
                'method' => 'post',
                'id' => 'track',
                'tracking' => true,
                'count' => 1,
                'trackVarFound' => true,
                'getNotEmpty' => false,
                'postNotEmpty' => false
            ),
            array(
                'label' => 'Tracking disabled, and POST empty.',
                'method' => 'post',
                'id' => 'track',
                'tracking' => false,
                'count' => 0,
                'trackVarFound' => true,
                'getNotEmpty' => false,
                'postNotEmpty' => false
            ),
            array(
                'label' => 'Tracking enabled, GET not empty.',
                'method' => 'get',
                'id' => 'track',
                'tracking' => true,
                'count' => 1,
                'trackVarFound' => true,
                'getNotEmpty' => true,
                'postNotEmpty' => false
            ),
            array(
                'label' => 'Tracking disabled, but GET not empty.',
                'method' => 'get',
                'id' => 'track',
                'tracking' => false,
                'count' => 1,
                'trackVarFound' => true,
                'getNotEmpty' => true,
                'postNotEmpty' => false
            ),
        );

        $number = 1;
        foreach ($tests as $def)
        {
            $form = new HTML_QuickForm2($def['id'], $def['method'], null, $def['tracking']);

            $data = $form->getDataReason();

            $descr = 'Test #' . $number . ' - ' . strtoupper($def['method']) . ': ' . $def['label'];

            $this->assertCount(
                $def['count'],
                $form->getDataSources(),
                'Datasource count does not match. ' . $descr
            );

            $this->assertEquals($def['trackVarFound'], $data['trackVarFound'], 'Tracking var should have been found. ' . $descr);
            $this->assertEquals($def['postNotEmpty'], $data['postNotEmpty'], 'Post should be empty. ' . $descr);
            $this->assertEquals($def['getNotEmpty'], $data['getNotEmpty'], 'Get should not be empty. ' . $descr);

            $number++;
        }
    }

    public function testConstructorSetsIdAndMethod() : void
    {
        $form1 = new HTML_QuickForm2();
        $this->assertSame('post', $form1->getAttribute('method'));
        $this->assertSame('post', $form1->getMethod());
        $this->assertNotEquals(0, strlen($form1->getAttribute('id')));

        $form2 = new HTML_QuickForm2('foo', 'get');
        $this->assertEquals('get', $form2->getAttribute('method'));
        $this->assertEquals('foo', $form2->getAttribute('id'));

        $form3 = new HTML_QuickForm2('bar', 'post', array('method' => 'get', 'id' => 'whatever'));
        $this->assertEquals('post', $form3->getAttribute('method'));
        $this->assertEquals('bar', $form3->getAttribute('id'));
    }

    public function testConstructorSetsDefaultAction() : void
    {
        $form1 = new HTML_QuickForm2('test');
        $this->assertEquals($_SERVER['PHP_SELF'], $form1->getAttribute('action'));

        $form2 = new HTML_QuickForm2('test2', 'post', array('action' => '/foobar.php'));
        $this->assertEquals('/foobar.php', $form2->getAttribute('action'));
    }

    public function testIdIsReadonly() : void
    {
        $form = new HTML_QuickForm2('foo', 'get');

        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);

        $form->removeAttribute('id');
    }

    public function testMethodIsReadonly() : void
    {
        $form = new HTML_QuickForm2('foo', 'get');

        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);

        $form->setAttribute('method', 'post');
    }

    public function testSetIdIsReadonly() : void
    {
        $form = new HTML_QuickForm2('foo', 'get');

        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);

        $form->setId('newId');
    }

    public function testCannotAddToContainer() : void
    {
        $form1 = new HTML_QuickForm2('form1');
        $form2 = new HTML_QuickForm2('form2');

        $this->expectException(HTML_QuickForm2_Exception::class);

        $form1->appendChild($form2);
    }

    public function testSetDataSources() : void
    {
        $ds1 = new HTML_QuickForm2_DataSource_Array(array('key' => 'value'));
        $ds2 = new HTML_QuickForm2_DataSource_Array(array('another key' => 'foo'));

        $form = new HTML_QuickForm2('dstest');
        $this->assertEquals(0, count($form->getDataSources()));
        $form->addDataSource($ds2);
        $this->assertEquals(1, count($form->getDataSources()));

        $form->setDataSources(array($ds1, $ds2));
        $this->assertEquals(2, count($form->getDataSources()));

        $this->expectExceptionCode(HTML_QuickForm2::ERROR_DATA_SOURCES_ARRAY_INVALID);

        $form->setDataSources(array($ds1, 'bogus', $ds2));
    }

    public function testValidateChecksWhetherFormIsSubmitted() : void
    {
        $form1 = new HTML_QuickForm2('notrack', 'post');
        $this->assertFalse($form1->validate());

        $form2 = new HTML_QuickForm2('track', 'post');
        $this->assertTrue($form2->validate());
    }

    public function testFormRule() : void
    {
        $form = new HTML_QuickForm2('track', 'post');
        $foo = $form->addElement('text', 'foo', array('id' => 'foo'));
        $form->addRule(new TestFormRule($form));

        $this->assertFalse($form->validate());
        $this->assertEquals('an error message', $foo->getError());
    }

    /**
     * Do not return values for automatically added elements from getValue()
     * @link http://pear.php.net/bugs/bug.php?id=19403
     */
    public function testRequest19403() : void
    {
        $formName = 'track';
        $trackVarName = HTML_QuickForm2::resolveTrackVarName($formName);

        $_POST = array($trackVarName => '');
        $form = new HTML_QuickForm2($formName);

        $this->assertArrayHasKey($trackVarName, $form->getRawValue());
        $this->assertArrayNotHasKey($trackVarName, $form->getValue());
    }
}
