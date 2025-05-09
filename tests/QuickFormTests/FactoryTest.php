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

namespace QuickFormTests;

use HTML_QuickForm2_Factory;
use HTML_QuickForm2_InvalidArgumentException;
use HTML_QuickForm2_Loader;
use HTML_QuickForm2_Node;
use HTML_QuickForm2_NotFoundException;
use QuickFormTests\CaseClasses\QuickFormCase;
use QuickFormTests\CustomClasses\FakeElement;
use QuickFormTests\CustomClasses\FakeRule;

/**
 * Unit test for HTML_QuickForm2_Factory class
 */
class FactoryTest extends QuickFormCase
{
    // region: _Tests

    public function testNotRegisteredElement() : void
    {
        $this->assertFalse(HTML_QuickForm2_Factory::isElementRegistered('foo_' . random_int(0, mt_getrandmax())));
    }

    public function testElementTypeCaseInsensitive() : void
    {
        HTML_QuickForm2_Factory::registerElement('fOo', 'Classname');
        $this->assertTrue(HTML_QuickForm2_Factory::isElementRegistered('foo'));
        $this->assertTrue(HTML_QuickForm2_Factory::isElementRegistered('FOO'));
    }

    public function testCreateNotRegisteredElement() : void
    {
        $this->expectExceptionCode(HTML_QuickForm2_Factory::ERROR_ELEMENT_TYPE_UNKNOWN);

        HTML_QuickForm2_Factory::createElement('foo2');
    }

    public function testCreateElementNonExistingClass() : void
    {
        HTML_QuickForm2_Factory::registerElement('foo3', 'NonexistentClass');

        $this->expectExceptionCode(HTML_QuickForm2_Loader::ERROR_CLASS_DOES_NOT_EXIST);

        HTML_QuickForm2_Factory::createElement('foo3');
    }

    public function testCreateElementValid() : void
    {
        HTML_QuickForm2_Factory::registerElement(
            'fakeelement',
            FakeElement::class
        );

        $this->expectExceptionCode(HTML_QuickForm2_Loader::ERROR_OBJECT_NOT_INSTANCE_OF);

        HTML_QuickForm2_Factory::createElement(
            'fakeelement',
            'fake',
            'attributes',
            array(
                'options' => '',
                'label' => 'fake label'
            )
        );
    }

    public function testNotRegisteredRule() : void
    {
        $this->assertFalse(HTML_QuickForm2_Factory::isRuleRegistered('foo_' . random_int(0, mt_getrandmax())));
    }

    public function testRuleNameCaseInsensitive() : void
    {
        HTML_QuickForm2_Factory::registerRule('fOo', 'RuleClassname');
        $this->assertTrue(HTML_QuickForm2_Factory::isRuleRegistered('FOO'));
        $this->assertTrue(HTML_QuickForm2_Factory::isRuleRegistered('foo'));
    }

    public function testCreateNotRegisteredRule() : void
    {
        $mockNode = $this->getMockBuilder(HTML_QuickForm2_Node::class)
            ->onlyMethods($this->nodeAbstractMethods)
            ->getMock();
        try
        {
            $rule = HTML_QuickForm2_Factory::createRule('foo2', $mockNode);
        }
        catch (HTML_QuickForm2_InvalidArgumentException $e)
        {
            $this->assertMatchesRegularExpression('/Rule(.*)is not known/', $e->getMessage());
            return;
        }
        $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
    }

    public function testCreateRuleNonExistingClass() : void
    {
        $mockNode = $this->getMockBuilder(HTML_QuickForm2_Node::class)
            ->onlyMethods($this->nodeAbstractMethods)
            ->getMock();

        HTML_QuickForm2_Factory::registerRule('foo3', 'NonexistentClass');

        $this->expectExceptionCode(HTML_QuickForm2_Loader::ERROR_CLASS_DOES_NOT_EXIST);

        HTML_QuickForm2_Factory::createRule('foo3', $mockNode);
    }

    public function testCreateRuleNonExistingFile() : void
    {
        $mockNode = $this->getMockBuilder(HTML_QuickForm2_Node::class)
            ->onlyMethods($this->nodeAbstractMethods)
            ->getMock();

        HTML_QuickForm2_Factory::registerRule('foo4', 'NonexistentClass');

        $this->expectExceptionCode(HTML_QuickForm2_Loader::ERROR_CLASS_DOES_NOT_EXIST);

        HTML_QuickForm2_Factory::createRule('foo4', $mockNode);
    }

    public function testCreateRuleValid() : void
    {
        $mockNode = $this->getMockBuilder(HTML_QuickForm2_Node::class)
            ->onlyMethods($this->nodeAbstractMethods)
            ->getMock();

        HTML_QuickForm2_Factory::registerRule('fakerule', FakeRule::class);

        $rule = HTML_QuickForm2_Factory::createRule(
            'fakerule', $mockNode, 'An error message', 'Some options'
        );

        $this->assertInstanceOf(FakeRule::class, $rule);
        $this->assertSame($mockNode, $rule->owner);
        $this->assertEquals('An error message', $rule->getMessage());
        $this->assertEquals('Some options', $rule->getConfig());
    }

    // endregion

    // region: Support methods

    protected string $phpError = '';
    protected bool $errorHandler = false;

    protected function setUp() : void
    {
        $this->phpError = '';
        $this->errorHandler = false;
    }

    protected function tearDown() : void
    {
        if ($this->errorHandler)
        {
            restore_error_handler();
        }
    }

    protected function setErrorHandler() : void
    {
        set_error_handler(array($this, 'handleError'));
        $this->errorHandler = true;
    }

    public function handleError(int $errno, string $errstr) : void
    {
        $this->phpError = $errstr;
    }

    // endregion
}
