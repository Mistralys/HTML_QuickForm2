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
 * Unit test for HTML_QuickForm2_Rule_Each class
 */
class HTML_QuickForm2_Rule_EachTest extends TestCase
{
    public function testTemplateRuleNeeded(): void
    {
        $mockEl = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        try {
            $each = new HTML_QuickForm2_Rule_Each($mockEl);
            $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertStringContainsString('Each Rule requires a template Rule to validate with', $e->getMessage());
        }
        try {
            $each2 = new HTML_QuickForm2_Rule_Each($mockEl, '', 'A rule?');
            $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertStringContainsString('Each Rule requires a template Rule to validate with', $e->getMessage());
        }
    }

    public function testCannotUseRequiredAsTemplate(): void
    {
        $mockEl = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        try {
            $each = new HTML_QuickForm2_Rule_Each($mockEl, 'an error', $mockEl->createRule('required', 'an error'));
            $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertStringContainsString('Cannot use "required" Rule as a template', $e->getMessage());
        }
    }

    public function testCanOnlyValidateContainers(): void
    {
        $mockEl = $this->getMockBuilder('HTML_QuickForm2_Element')
            ->setMethods(array('getType',
                                 'getRawValue', 'setValue', '__toString'))
            ->getMock();
        try {
            $each = new HTML_QuickForm2_Rule_Each(
                $mockEl, '', $mockEl->createRule('empty')
            );
            $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertStringContainsString('Each Rule can only validate Containers', $e->getMessage());
        }
    }

    public function testValidatesWithTemplateRule(): void
    {
        $mockContainer = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $foo = $mockContainer->addElement('text', 'foo')->setValue('');
        $bar = $mockContainer->addElement('text', 'bar')->setValue('I am not empty');
        $baz = $mockContainer->addElement('text', 'baz')->setValue('');

        $each = new HTML_QuickForm2_Rule_Each(
            $mockContainer, 'an error', $mockContainer->createRule('empty')
        );
        $this->assertFalse($each->validate());

        $mockContainer->removeChild($bar);
        $this->assertTrue($each->validate());
    }

    public function testSetsErrorOnContainer(): void
    {
        $mockContainer = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $foo = $mockContainer->addElement('text', 'foo')->setValue('');
        $bar = $mockContainer->addElement('text', 'bar')->setValue('I am not empty');

        $each = new HTML_QuickForm2_Rule_Each(
            $mockContainer, 'Real error', $mockContainer->createRule('empty', 'Template error')
        );
        $this->assertFalse($each->validate());
        $this->assertEquals('Real error', $mockContainer->getError());
        $this->assertEquals('', $bar->getError());
    }

    public function testChainedRulesAreIgnored(): void
    {
        $mockContainer = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();

        $foo = $mockContainer->addElement('text', 'foo')->setValue('');
        $ruleIgnored = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner'))
            ->setConstructorArgs(array($foo))
            ->getMock();
        $ruleIgnored->expects($this->never())->method('validateOwner');

        $each = new HTML_QuickForm2_Rule_Each(
            $mockContainer, 'an error', $mockContainer->createRule('empty')
                                                      ->and_($ruleIgnored)
        );
        $this->assertTrue($each->validate());
    }

    public function testValidateNestedContainer(): void
    {
        $mockOuter = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $mockInner = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $foo = $mockOuter->addElement('text', 'foo')->setValue('');
        $bar = $mockInner->addElement('text', 'bar')->setValue('not empty');
        $mockOuter->appendChild($mockInner);

        $each = new HTML_QuickForm2_Rule_Each(
            $mockOuter, 'Real error', $mockOuter->createRule('empty')
        );
        $this->assertFalse($each->validate());

        $bar->setValue('');
        $this->assertTrue($each->validate());
    }

    public function testIgnoresStaticServerSide(): void
    {
        $mockContainer = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $mockContainer->addElement('static', 'noValidateServer');

        $rule = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner'))
            ->setConstructorArgs(array($mockContainer, 'a message'))
            ->getMock();
        $rule->expects($this->any())->method('validateOwner')
             ->will($this->returnValue(false));

        $each = new HTML_QuickForm2_Rule_Each($mockContainer, 'an error', $rule);
        $this->assertTrue($each->validate());
    }

    public function testIgnoresStaticClientSide(): void
    {
        $mockContainer = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $mockContainer->addElement('static', 'noValidateClient');

        $rule = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($mockContainer, 'a message'))
            ->getMock();
        $rule->expects($this->any())->method('getJavascriptCallback')
             ->will($this->returnValue('staticCallback'));

        $each = new HTML_QuickForm2_Rule_Each($mockContainer, 'an error', $rule);
        $this->assertStringNotContainsString('staticCallback', $each->getJavascript());
    }

    public function testValidationTriggers(): void
    {
        $mockContainer = $this->getMockBuilder('HTML_QuickForm2_Container')
            ->setMethods(array('getType', 'setValue', '__toString'))
            ->getMock();
        $foo = $mockContainer->addElement('text', 'foo', array('id' => 'foo'));
        $bar = $mockContainer->addElement('text', 'bar', array('id' => 'bar'));

        $rule = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($mockContainer, 'a message'))
            ->getMock();
        $rule->expects($this->any())->method('getJavascriptCallback')
             ->will($this->returnValue('a callback'));
        $each = new HTML_QuickForm2_Rule_Each($mockContainer, 'an error', $rule);
        $this->assertStringContainsString('["foo","bar"]', $each->getJavascript());
    }
}
?>