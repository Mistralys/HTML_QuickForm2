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

use PHPUnit\Framework\TestCase;

/**
 * Unit test for HTML_QuickForm2_Controller class
 */
class HTML_QuickForm2_ControllerTest extends TestCase
{
    public function testSetExplicitID(): void
    {
        $controller = new HTML_QuickForm2_Controller('foo', false, false);
        $this->assertEquals('foo', $controller->getId());
        $this->assertFalse($controller->isWizard());
        $this->assertFalse($controller->propagateId());
    }

    public function testFindID1(): void
    {
        $_SESSION = array();
        $_REQUEST = array();
        
        $this->expectException(HTML_QuickForm2_NotFoundException::class);
        
        new HTML_QuickForm2_Controller();
    }
    
    public function testFindID2(): void
    {
        $_SESSION = array();
        $_REQUEST = array(
            HTML_QuickForm2_Controller::KEY_ID => 'foo'
        );
        
        $this->expectException(HTML_QuickForm2_NotFoundException::class);
        
        new HTML_QuickForm2_Controller();
    }
    
    public function testFindID3(): void
    {
        $_REQUEST = array(
            HTML_QuickForm2_Controller::KEY_ID => 'foo'
        );
        
        $_SESSION = array(
            sprintf(HTML_QuickForm2_Controller::KEY_CONTAINER, 'foo') => 
            array(
                'datasources' => array(),
                'values'      => array(),
                'valid'       => array()
            )
        );
        
        $controller = new HTML_QuickForm2_Controller(null, true, false);
        
        $this->assertEquals('foo', $controller->getId());
        $this->assertTrue($controller->isWizard());
        $this->assertTrue($controller->propagateId());
    }

    public function testContainer(): void
    {
        $_SESSION = array();
        $_REQUEST = array();

        $controller = new HTML_QuickForm2_Controller('foo');
        $controller->getSessionContainer();
        
        $this->assertNotEquals(array(), $_SESSION);

        $controller->destroySessionContainer();
        $this->assertEquals(array(), $_SESSION);
    }

    public function testAddPage(): void
    {
        $firstPage  = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('firstPage')))
            ->getMock();
        $controller = new HTML_QuickForm2_Controller('foo');

        try {
            $page = $controller->getPage('firstPage');
            $this->fail('Expected HTML_QuickForm2_NotFoundException was not thrown');
        } catch (HTML_QuickForm2_NotFoundException $e) {}

        $controller->addPage($firstPage);
        $this->assertSame($firstPage, $controller->getPage('firstPage'));
        $this->assertSame($controller, $firstPage->getController());

        try {
            $controller->addPage(
                $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                    ->setMethods(array('populateForm'))
                    ->setConstructorArgs(array(new HTML_QuickForm2('firstPage')))
                    ->getMock()
            );
            $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {}
    }

    public function testDefaultActionName(): void
    {
        $controller = new HTML_QuickForm2_Controller('foo');

        try {
            $actionName = $controller->getActionName();
            $this->fail('Expected HTML_QuickForm2_NotFoundException was not thrown');
        } catch(HTML_QuickForm2_NotFoundException $e) {}

        $controller->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('aPage')))
                ->getMock()
        );
        $this->assertEquals(array('aPage', 'display'), $controller->getActionName());
    }

    public function testGetActionName(): void
    {
        $_SESSION = array();
        $_REQUEST = array(
            sprintf(HTML_QuickForm2_Controller_Page::KEY_NAME, 'foo', 'bar')         => 'Button value',
            sprintf(HTML_QuickForm2_Controller_Page::KEY_NAME, 'baz', 'quux') . '_x' => 15
        );

        $controller1 = new HTML_QuickForm2_Controller('first');
        $controller1->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('foo')))
                ->getMock()
        );
        $this->assertEquals(array('foo', 'bar'), $controller1->getActionName());

        $controller2 = new HTML_QuickForm2_Controller('second');
        $controller2->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('baz')))
                ->getMock()
        );
        $this->assertEquals(array('baz', 'quux'), $controller2->getActionName());

        $_REQUEST = array();
        $this->assertEquals(array('foo', 'bar'), $controller1->getActionName());
    }

    public function testIsValidSimple(): void
    {
        $controller = new HTML_QuickForm2_Controller('simpleIsValid');
        $controller->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('first')))
                ->getMock()
        );
        $second = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('second')))
            ->getMock();
        $controller->addPage($second);
        $controller->getSessionContainer()->storeValidationStatus('first', true);
        $controller->getSessionContainer()->storeValidationStatus('second', false);

        $this->assertFalse($controller->isValid());
        $this->assertTrue($controller->isValid($second));
    }

    public function testIsValidNotVisited(): void
    {
        $controller = new HTML_QuickForm2_Controller('isValidUnseen', false);
        $controller->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('seen')))
                ->getMock()
        );
        $mockUnseen = $this->getMockBuilder('HTML_QuickForm2')
            ->setMethods(array('validate', 'getValue'))
            ->setConstructorArgs(array('unseen'))
            ->getMock();
        $mockUnseen->expects($this->once())->method('validate')
                   ->will($this->returnValue(true));
        $mockUnseen->expects($this->once())->method('getValue')
                   ->will($this->returnValue(array('foo' => 'bar')));
        $controller->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array($mockUnseen))
                ->getMock()
        );
        $controller->getSessionContainer()->storeValidationStatus('seen', true);

        $this->assertTrue($controller->isValid());
    }

   /**
    * Default values for checkboxes and multiselects were ignored when validating an unseen page
    *
    * Unlikely that this bug will resurface, but just in case.
    *
    * @see http://pear.php.net/bugs/bug.php?id=8687
    */
    public function testBug8687(): void
    {
        $mockForm = $this->getMockBuilder('HTML_QuickForm2')
            ->setMethods(array('validate'))
            ->setConstructorArgs(array('invalid'))
            ->getMock();
        $mockForm->expects($this->once())->method('validate')
                 ->will($this->returnValue(false));
        $select = $mockForm->addElement('select', 'foo', array('multiple'))
                           ->loadOptions(array('one' => 'First label', 'two' => 'Second label'));
        $box    = $mockForm->addElement('checkbox', 'bar');
        $mockPage = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array($mockForm))
            ->getMock();
        $controller = new HTML_QuickForm2_Controller('bug8687', false);
        $controller->addPage($mockPage);
        $controller->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'foo' => array('two'),
            'bar' => '1'
        )));

        $this->assertFalse($controller->isValid());
        $this->assertEquals(array('two'), $select->getValue());
        $this->assertEquals('1', $box->getValue());
    }
}
?>