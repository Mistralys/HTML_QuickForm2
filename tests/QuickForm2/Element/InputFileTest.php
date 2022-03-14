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
 * Unit test for HTML_QuickForm2_Element_InputFile class
 */
class HTML_QuickForm2_Element_InputFileTest extends TestCase
{
    // region: _Tests

    public function testCannotBeFrozen() : void
    {
        $upload = new HTML_QuickForm2_Element_InputFile('foo');
        $this->assertFalse($upload->toggleFrozen(true));
        $this->assertFalse($upload->toggleFrozen());
    }

    public function testSetValueFromSubmitDataSource() : void
    {
        $form = $this->createTestUploadForm();

        $foo = $form->appendChild(new HTML_QuickForm2_Element_InputFile('foo'));
        $bar = $form->appendChild(new HTML_QuickForm2_Element_InputFile('bar'));

        $this->assertNull($bar->getValue());

        $this->assertEquals(array(
            'name'      => 'file.doc',
            'tmp_name'  => '/tmp/nothing',
            'type'      => 'text/plain',
            'size'      => 1234,
            'error'     => UPLOAD_ERR_OK
        ), $foo->getValue());
    }

    public function testBuiltinValidation() : void
    {
        $form = $this->createTestUploadForm();

        $form->appendChild(new HTML_QuickForm2_Element_InputFile('foo'));
        $this->assertTrue($form->validate());

        $toobig = $form->appendChild(new HTML_QuickForm2_Element_InputFile('toobig'));
        $this->assertFalse($form->validate());
        $this->assertStringContainsString('987654', $toobig->getError());
    }

    public function testInvalidMessageProvider() : void
    {
        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);
        
        new HTML_QuickForm2_Element_InputFile('invalid', null, array('messageProvider' => array()));
    }

    public function testCallbackMessageProvider() : void
    {
        $form = $this->createTestUploadForm();

        $upload = $form->addFile('local', array(), array(
            'messageProvider' => array(__CLASS__, 'callbackMessageProvider')
        ));
        $this->assertFalse($form->validate());
        $this->assertEquals('A nasty error happened!', $upload->getError());
    }

    public static function callbackMessageProvider($messageId, $langId)
    {
        return "A nasty error happened!";
    }

    public function testObjectMessageProvider()
    {
        $mockProvider = $this->getMockBuilder('HTML_QuickForm2_MessageProvider')
            ->setMethods(array('get'))
            ->getMock();

        $mockProvider->expects($this->once())->method('get')
                     ->will($this->returnValue('A nasty error happened!'));

        $form   = $this->createTestUploadForm();

        $upload = $form->addFile('local', array(), array(
            'messageProvider' => $mockProvider
        ));

        $this->assertFalse($form->validate());
        $this->assertEquals('A nasty error happened!', $upload->getError());
    }

   /**
    * File should check that the form has POST method, set enctype to multipart/form-data
    * @see http://pear.php.net/bugs/bug.php?id=16807
    */
    public function testRequest16807() : void
    {
        $form = new HTML_QuickForm2('broken1', 'get');

        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);
        
        $upload = $form->addFile(self::FORM_ID, array('id' => self::FORM_ID));
        
        $upload->getValue();
    }
    
   /**
    * Ensure that the form action is checked on retrieving the value
    */
    public function testRequest16807_2() : void
    {
        $form = new HTML_QuickForm2('broken2', 'get');
        
        /* HTML_QuickForm2_Container_Group */
        $group = $form->addElement('group', 'fileGroup');

        $upload = $group->addFile(self::FORM_ID, array('id' => self::FORM_ID));
        
        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);
        
        $upload->getValue();
    }
    
   /**
    * Ensure that the form action is checked on render
    */
    public function testRequest16807_3() : void
    {
        $form = new HTML_QuickForm2('broken2', 'get');
        
        $form->addFile(self::FORM_ID, array('id' => self::FORM_ID));
        
        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);
        
        $renderer = HTML_QuickForm2_Renderer::factory('Array');
        
        $form->render($renderer);
    }
    
    public function testRequest16807_4() : void
    {
        $post = new HTML_QuickForm2('okform', 'post');
        
        $this->assertNull($post->getAttribute('enctype'));
        
        $upload = $post->addFile(self::FORM_ID);
        
        // the check is done whenever the value is accessed,
        // or the form is validated / rendered.
        $upload->getValue();
        
        $this->assertEquals('multipart/form-data', $post->getAttribute('enctype'));
    }

    // endregion

    // region: Support methods

    public const FORM_ID = 'upload';

    protected function setUp() : void
    {
        $_FILES = array(
            'foo' => array(
                'name'      => 'file.doc',
                'tmp_name'  => '/tmp/nothing',
                'type'      => 'text/plain',
                'size'      => 1234,
                'error'     => UPLOAD_ERR_OK
            ),
            'toobig' => array(
                'name'      => 'ahugefile.zip',
                'tmp_name'  => '',
                'type'      => '',
                'size'      => 0,
                'error'     => UPLOAD_ERR_FORM_SIZE
            ),
            'local' => array(
                'name'      => 'nasty-trojan.exe',
                'tmp_name'  => '',
                'type'      => '',
                'size'      => 0,
                'error'     => UPLOAD_ERR_CANT_WRITE
            )
        );

        // Simulate the submit
        $_REQUEST = array(
            HTML_QuickForm2::generateTrackingVarName(self::FORM_ID) => 'yes'
        );

        $_POST = array(
            'MAX_FILE_SIZE' => '987654'
        );
    }

    private function createTestUploadForm() : HTML_QuickForm2
    {
        $form = new HTML_QuickForm2(self::FORM_ID, 'post', null);

        $this->assertArrayHasKey($form->getTrackingVarName(), $_REQUEST);
        $this->assertTrue($form->isSubmitted());

        return $form;
    }

    // endregion
}
