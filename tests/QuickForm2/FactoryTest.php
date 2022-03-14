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
 * Unit test for HTML_QuickForm2_Factory class
 */
class HTML_QuickForm2_FactoryTest extends TestCase
{
    protected $nodeAbstractMethods = array(
        'updateValue', 'getId', 'getName', 'getType', 'getRawValue', 'setId',
        'setName', 'setValue', '__toString', 'getJavascriptValue',
        'getJavascriptTriggers', 'render'
    );

    public function testNotRegisteredElement()
    {
        $this->assertFalse(HTML_QuickForm2_Factory::isElementRegistered('foo_' . mt_rand()));
    }

    public function testElementTypeCaseInsensitive()
    {
        HTML_QuickForm2_Factory::registerElement('fOo', 'Classname');
        $this->assertTrue(HTML_QuickForm2_Factory::isElementRegistered('foo'));
        $this->assertTrue(HTML_QuickForm2_Factory::isElementRegistered('FOO'));
    }

    public function testCreateNotRegisteredElement()
    {
        try {
            $el = HTML_QuickForm2_Factory::createElement('foo2');
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertRegexp('/Element type(.*)is not known/', $e->getMessage());
            return;
        }
        $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
    }

    public function testCreateElementNonExistingClass() : void
    {
        HTML_QuickForm2_Factory::registerElement('foo3', 'NonexistentClass');

        try
        {
            HTML_QuickForm2_Factory::createElement('foo3');
        }
        catch (HTML_QuickForm2_NotFoundException $e)
        {
            $this->assertRegexp('/Element class (.*) not found/', $e->getMessage());
            $this->assertStringContainsString('NonexistentClass', $e->getMessage());
            return;
        }

        $this->fail(sprintf('Expected [%s] was not thrown', HTML_QuickForm2_NotFoundException::class));
    }

    public function testCreateElementValid() : void
    {
        HTML_QuickForm2_Factory::registerElement('fakeelement', FakeElement::class);

        $this->expectExceptionCode(HTML_QuickForm2_Factory::ERROR_INSTANCE_NOT_A_NODE);

        HTML_QuickForm2_Factory::createElement(
            'fakeelement',
            'fake',
            null,
            array(
                'options' => '', 'label' => 'fake label'
            )
        );
    }

    public function testNotRegisteredRule()
    {
        $this->assertFalse(HTML_QuickForm2_Factory::isRuleRegistered('foo_' . mt_rand()));
    }

    public function testRuleNameCaseInsensitive()
    {
        HTML_QuickForm2_Factory::registerRule('fOo', 'RuleClassname');
        $this->assertTrue(HTML_QuickForm2_Factory::isRuleRegistered('FOO'));
        $this->assertTrue(HTML_QuickForm2_Factory::isRuleRegistered('foo'));
    }

    public function testCreateNotRegisteredRule()
    {
        $mockNode = $this->getMockBuilder('HTML_QuickForm2_Node')
            ->setMethods($this->nodeAbstractMethods)
            ->getMock();
        try {
            $rule = HTML_QuickForm2_Factory::createRule('foo2', $mockNode);
        } catch (HTML_QuickForm2_InvalidArgumentException $e) {
            $this->assertRegexp('/Rule(.*)is not known/', $e->getMessage());
            return;
        }
        $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
    }

    public function testCreateRuleNonExistingClass()
    {
        $mockNode = $this->getMockBuilder('HTML_QuickForm2_Node')
            ->setMethods($this->nodeAbstractMethods)
            ->getMock();

        HTML_QuickForm2_Factory::registerRule('foo3', 'NonexistentClass');

        try
        {
            HTML_QuickForm2_Factory::createRule('foo3', $mockNode);
        }
        catch (HTML_QuickForm2_NotFoundException $e)
        {
            $this->assertRegexp('/Rule class (.*) not found/', $e->getMessage());
            $this->assertStringContainsString('NonexistentClass', $e->getMessage());
            return;
        }

        $this->fail(sprintf('Expected %s was not thrown', HTML_QuickForm2_NotFoundException::class));
    }

    public function testCreateRuleValid() : void
    {
        $mockNode = $this->getMockBuilder(HTML_QuickForm2_Node::class)
            ->setMethods($this->nodeAbstractMethods)
            ->getMock();

        HTML_QuickForm2_Factory::registerRule(
            'fakerule',
            FakeRule::class
        );

        $this->expectExceptionCode(HTML_QuickForm2_Factory::ERROR_INSTANCE_NOT_A_RULE);

        HTML_QuickForm2_Factory::createRule(
            'fakerule',
            $mockNode,
            'An error message',
            'Some options'
        );
    }
}
