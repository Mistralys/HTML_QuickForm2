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
 * Unit test for HTML_QuickForm2_Controller_Action_Jump class
 */
class HTML_QuickForm2_Controller_Action_JumpTest
    extends TestCase
{
    protected $mockJump;

    protected function setUp() : void
    {
        $this->mockJump = $this->getMockBuilder('HTML_QuickForm2_Controller_Action_Jump')
            ->setMethods(array('doRedirect'))
            ->getMock();
        $this->mockJump->expects($this->atLeastOnce())->method('doRedirect')
             ->will($this->returnArgument(0));

        // see RFC 3986, section 5.4
        $_SERVER['HTTP_HOST']   = 'a';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/b/c/d;p?q';
    }

   /**
    * Requirement of RFC 2616, section 14.30
    *
    * @link http://pear.php.net/bugs/bug.php?id=13087
    */
    public function testRedirectToAbsoluteUrl(): void
    {
        // Examples from RFC 3986 section 5.4, except those with fragments
        $rfc3986tests = array(
            ""              =>  "http://a/b/c/d;p?q",
            "g:h"           =>  "g:h",
            "g"             =>  "http://a/b/c/g",
            "./g"           =>  "http://a/b/c/g",
            "g/"            =>  "http://a/b/c/g/",
            "/g"            =>  "http://a/g",
            "//g"           =>  "http://g",
            "?y"            =>  "http://a/b/c/d;p?y",
            "g?y"           =>  "http://a/b/c/g?y",
            ";x"            =>  "http://a/b/c/;x",
            "g;x"           =>  "http://a/b/c/g;x",
            ""              =>  "http://a/b/c/d;p?q",
            "."             =>  "http://a/b/c/",
            "./"            =>  "http://a/b/c/",
            ".."            =>  "http://a/b/",
            "../"           =>  "http://a/b/",
            "../g"          =>  "http://a/b/g",
            "../.."         =>  "http://a/",
            "../../"        =>  "http://a/",
            "../../g"       =>  "http://a/g",
            "../../../g"    =>  "http://a/g",
            "../../../../g" =>  "http://a/g",
            "/./g"          =>  "http://a/g",
            "/../g"         =>  "http://a/g",
            "g."            =>  "http://a/b/c/g.",
            ".g"            =>  "http://a/b/c/.g",
            "g.."           =>  "http://a/b/c/g..",
            "..g"           =>  "http://a/b/c/..g",
            "./../g"        =>  "http://a/b/g",
            "./g/."         =>  "http://a/b/c/g/",
            "g/./h"         =>  "http://a/b/c/g/h",
            "g/../h"        =>  "http://a/b/c/h",
            "g;x=1/./y"     =>  "http://a/b/c/g;x=1/y",
            "g;x=1/../y"    =>  "http://a/b/c/y",
            "g?y/./x"       =>  "http://a/b/c/g?y/./x",
            "g?y/../x"      =>  "http://a/b/c/g?y/../x",
            "http:g"        =>  "http:g",
        );

        $controller = new HTML_QuickForm2_Controller('rfc3986', true);
        $mockPage = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('relative')))
            ->getMock();
        $mockPage->addHandler('jump', $this->mockJump);
        $controller->addPage($mockPage);

        foreach ($rfc3986tests as $relative => $absolute) {
            $mockPage->getForm()->setAttribute('action', $relative);
            $this->assertEquals($absolute, preg_replace('/[&?]_qf(.*)$/', '', $mockPage->handle('jump')));
        }
    }

    public function testCannotRedirectPastInvalidPageInWizard(): void
    {
        $controller = new HTML_QuickForm2_Controller('twopagewizard', true);
        $controller->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('first')))
                ->getMock()
        );
        $controller->addPage(
            $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
                ->setMethods(array('populateForm'))
                ->setConstructorArgs(array(new HTML_QuickForm2('second')))
                ->getMock()
        );
        $controller->addHandler('jump', $this->mockJump);

        $this->assertStringContainsString(
            $controller->getPage('first')->getButtonName('display'),
            $controller->getPage('second')->handle('jump')
        );
    }

    public function testPropagateControllerId(): void
    {
        $noPropPage = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('noPropagateForm')))
            ->getMock();
        $noPropController = new HTML_QuickForm2_Controller('foo', true, false);
        $noPropController->addPage($noPropPage);
        $noPropController->addHandler('jump', $this->mockJump);
        $this->assertStringNotContainsString(
            HTML_QuickForm2_Controller::KEY_ID . '=',
            $noPropPage->handle('jump')
        );

        $propPage = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('propagateForm')))
            ->getMock();
        $propController = new HTML_QuickForm2_Controller('bar', true, true);
        $propController->addPage($propPage);
        $propController->addHandler('jump', $this->mockJump);
        $this->assertStringContainsString(
            HTML_QuickForm2_Controller::KEY_ID . '=bar',
            $propPage->handle('jump')
        );
    }

   /**
    * Uppercase 'OFF' in $_SERVER['HTTPS'] could cause a bogus redirect to https:// URL
    *
    * @link http://pear.php.net/bugs/bug.php?id=16328
    */
    public function testBug16328(): void
    {
        $_SERVER['HTTPS'] = 'OFF';

        $controller = new HTML_QuickForm2_Controller('bug16328');
        $mockPage   = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('unsecure')))
            ->getMock();
        $controller->addPage($mockPage);
        $controller->addHandler('jump', $this->mockJump);
        $mockPage->getForm()->setAttribute('action', '/foo');

        $this->assertDoesNotMatchRegularExpression('/^https:/i', $mockPage->handle('jump'));
    }

   /**
    * Use HTTP_HOST as the default, falling back to SERVER_NAME (and SERVER_ADDR)
    *
    * @link http://pear.php.net/bugs/bug.php?id=19216
    */
    public function testBug19216(): void
    {
        $controller = new HTML_QuickForm2_Controller('bug19216');
        $mockPage   = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('testhost')))
            ->getMock();
        $controller->addPage($mockPage);
        $controller->addHandler('jump', $this->mockJump);
        $mockPage->getForm()->setAttribute('action', '/foo');

        $_SERVER['HTTP_HOST']   = 'example.org';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_ADDR'] = '1.2.3.4';
        $this->assertStringStartsWith('http://example.org/foo?', $mockPage->handle('jump'));

        $_SERVER['HTTP_HOST'] = '';
        $this->assertStringStartsWith('http://example.com/foo?', $mockPage->handle('jump'));

        $_SERVER['SERVER_NAME'] = '';
        $this->assertStringStartsWith('http://1.2.3.4/foo?', $mockPage->handle('jump'));
    }

    public function testHttpHostWithPortNumber(): void
    {
        $controller = new HTML_QuickForm2_Controller('weirdhost');
        $mockPage   = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('weirdhost')))
            ->getMock();
        $controller->addPage($mockPage);
        $controller->addHandler('jump', $this->mockJump);
        $mockPage->getForm()->setAttribute('action', '/foo');

        $_SERVER['HTTP_HOST'] = 'example.org:80';
        $this->assertStringStartsWith('http://example.org/foo?', $mockPage->handle('jump'));
    }

    public function testHttpXForwardedHost(): void
    {
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'example.org, example.com';
        $_SERVER['HTTP_HOST']             = 'localhost';

        $controller = new HTML_QuickForm2_Controller('forwarded');
        $mockPage   = $this->getMockBuilder('HTML_QuickForm2_Controller_Page')
            ->setMethods(array('populateForm'))
            ->setConstructorArgs(array(new HTML_QuickForm2('forwarded')))
            ->getMock();
        $controller->addPage($mockPage);
        $controller->addHandler('jump', $this->mockJump);
        $mockPage->getForm()->setAttribute('action', '/foo');

        $this->assertStringStartsWith('http://localhost/foo?', $mockPage->handle('jump'));

        $trustingJump = $this->getMockBuilder('HTML_QuickForm2_Controller_Action_Jump')
            ->setMethods(array('doRedirect'))
            ->setConstructorArgs(array(true))
            ->getMock();
        $trustingJump->expects($this->atLeastOnce())->method('doRedirect')
            ->will($this->returnArgument(0));
        $controller->addHandler('jump', $trustingJump);

        $this->assertStringStartsWith('http://example.com/foo?', $mockPage->handle('jump'));
    }
}
?>