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
use QuickFormTests\CustomClasses\TestRuleImplConst;

/**
 * Unit test for HTML_QuickForm2_Rule class
 */
class HTML_QuickForm2_RuleTest extends TestCase
{
    public function testSetAndGetOptions(): void
    {
        $rule = new TestRuleImplConst(
            new HTML_QuickForm2_Element_InputText('foo'), 'a message', 'bar'
        );
        $this->assertEquals('bar', $rule->getConfig());

        $this->assertSame($rule, $rule->setConfig('baz'));
        $this->assertEquals('baz', $rule->getConfig());
    }

    public function testSetAndGetMessage(): void
    {
        $rule = new TestRuleImplConst(
            new HTML_QuickForm2_Element_InputText('foo'), 'a message', 'bar'
        );
        $this->assertEquals('a message', $rule->getMessage());

        $this->assertSame($rule, $rule->setMessage('another message'));
        $this->assertEquals('another message', $rule->getMessage());
    }

    public function testValidateSingle(): void
    {
        $ruleTrue = new TestRuleImplConst(
            new HTML_QuickForm2_Element_InputText('ruleTrue'), 'a message', true
        );
        $this->assertTrue($ruleTrue->validate());

        $ruleFalse = new TestRuleImplConst(
            new HTML_QuickForm2_Element_InputText('ruleFalse'), 'a message', false
        );
        $this->assertFalse($ruleFalse->validate());
    }

    public function testValidateChained(): void
    {
        $elTest = new HTML_QuickForm2_Element_InputText('testAndOr');
        $ruleAnd = new TestRuleImplConst($elTest, 'a message', true);
        $ruleAnd->and_(new TestRuleImplConst($elTest, 'a message', false));
        $this->assertFalse($ruleAnd->validate());

        $ruleOr = new TestRuleImplConst($elTest, 'a message', false);
        $ruleOr->or_(new TestRuleImplConst($elTest, 'a message', true));
        $this->assertTrue($ruleOr->validate());
    }

    public function testOperatorPrecedence(): void
    {
        // true = true or true and false != ((true or true) and false) = false
        $elTest = new HTML_QuickForm2_Element_InputText('testPrecedence');
        $ruleTrue = new TestRuleImplConst($elTest, 'a message', true);
        $ruleFalse = new TestRuleImplConst($elTest, 'a message', false);

        $ruleTrue->or_(clone $ruleTrue)->and_($ruleFalse);
        $this->assertTrue($ruleTrue->validate());
    }

    public function testShortCircuitedEvaluationAnd(): void
    {
        $elTest = new HTML_QuickForm2_Element_InputText('testShortCircuitedAnd');
        $ruleTrue = new TestRuleImplConst($elTest, '...', true);
        $ruleFalse = new TestRuleImplConst($elTest, '...', false);

        $ruleAndTrue = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner'))
            ->setConstructorArgs(array($elTest))
            ->getMock();
        $ruleAndTrue->expects($this->once())->method('validateOwner');
        $ruleTrue->and_($ruleAndTrue);
        $ruleTrue->validate();

        $ruleAndFalse = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner'))
            ->setConstructorArgs(array($elTest))
            ->getMock();
        $ruleAndFalse->expects($this->never())->method('validateOwner');
        $ruleFalse->and_($ruleAndFalse);
        $ruleFalse->validate();
    }

    public function testShortCircuitedEvaluationOr(): void
    {
        $elTest = new HTML_QuickForm2_Element_InputText('testShortCircuitedOr');
        $ruleTrue = new TestRuleImplConst($elTest, '...', true);
        $ruleFalse = new TestRuleImplConst($elTest, '...', false);

        $ruleOrTrue = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner'))
            ->setConstructorArgs(array($elTest))
            ->getMock();
        $ruleOrTrue->expects($this->never())->method('validateOwner');
        $ruleTrue->or_($ruleOrTrue);
        $ruleTrue->validate();

        $ruleOrFalse = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner'))
            ->setConstructorArgs(array($elTest))
            ->getMock();
        $ruleOrFalse->expects($this->once())->method('validateOwner');
        $ruleFalse->or_($ruleOrFalse);
        $ruleFalse->validate();
    }

    public function testSetErrorOnlyOnChainFailure(): void
    {
        $elTest = new HTML_QuickForm2_Element_InputText('valid');
        $chain  = new TestRuleImplConst($elTest, 'bogus error', false);
        $chain->or_(new TestRuleImplConst($elTest, '', true));

        $this->assertTrue($chain->validate());
        $this->assertEquals('', $elTest->getError());

        $chain2 = new TestRuleImplConst($elTest, 'genuine error', false);
        $chain2->or_(new TestRuleImplConst($elTest, '', false));

        $this->assertFalse($chain2->validate());
        $this->assertEquals('genuine error', $elTest->getError());
    }

    public function testDefaultConfigMerging(): void
    {
        $this->assertEquals('foo', HTML_QuickForm2_Rule::mergeConfig('foo', null));
        $this->assertEquals('bar', HTML_QuickForm2_Rule::mergeConfig('foo', 'bar'));

        HTML_QuickForm2_Factory::registerRule('no-config', 'QuickFormTests\CustomClasses\TestRuleImplConst');
        HTML_QuickForm2_Factory::registerRule('with-config', 'QuickFormTests\CustomClasses\TestRuleImplConst',
                                              null, 'bar');
        $el = new HTML_QuickForm2_Element_InputText();
        $this->assertEquals('foo', $el->createRule('no-config', '', 'foo')->getConfig());
        $this->assertEquals('bar', $el->createRule('with-config', '', 'foo')->getConfig());
    }

    public function testValidationTriggers(): void
    {
        $el = new HTML_QuickForm2_Element_InputText('foo', array('id' => 'foo'));
        $rule = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($el))
            ->getMock();
        $rule->expects($this->any())->method('getJavascriptCallback')
             ->will($this->returnValue('a callback'));

        $this->assertStringContainsString('qf.LiveRule', $rule->getJavascript());
        $this->assertStringContainsString('["foo"]', $rule->getJavascript());
        $this->assertStringNotContainsString('qf.LiveRule', $rule->getJavascript(false));
    }

    public function testChainedValidationTriggers(): void
    {
        $foo = new HTML_QuickForm2_Element_InputText('foo', array('id' => 'foo'));
        $bar = new HTML_QuickForm2_Element_InputText('bar', array('id' => 'bar'));
        $baz = new HTML_QuickForm2_Element_InputText('baz', array('id' => 'baz'));

        $ruleFoo = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($foo))
            ->getMock();
        $ruleBar = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($bar))
            ->getMock();
        $ruleBaz = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($baz))
            ->getMock();
        $ruleFoo->expects($this->once())->method('getJavascriptCallback')
                ->will($this->returnValue('a callback'));
        $ruleBar->expects($this->once())->method('getJavascriptCallback')
                ->will($this->returnValue('a callback'));
        $ruleBar->expects($this->once())->method('getJavascriptCallback')
                ->will($this->returnValue('a callback'));

        $script = $ruleFoo->and_($ruleBar->and_($ruleBaz))->getJavascript();
        preg_match('/\[\S+\]/', $script, $m);
        $this->assertStringContainsString('foo', $m[0]);
        $this->assertStringContainsString('bar', $m[0]);
        $this->assertStringContainsString('baz', $m[0]);
    }

    public function testCannotSetErrorsOnHiddenElements1(): void
    {
        $hidden = new HTML_QuickForm2_Element_InputHidden('noError');

        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);
        
        $this->getMockBuilder('HTML_QuickForm2_Rule')
        ->setMethods(array('validateOwner'))
        ->setConstructorArgs(array($hidden, 'an error message'))
        ->getMock();
    }
    
    public function testCannotSetErrorsOnHiddenElements2() : void
    {
        $hidden = new HTML_QuickForm2_Element_InputHidden('noError');
        $text   = new HTML_QuickForm2_Element_InputText('canHaveError');
        
        $this->expectException(HTML_QuickForm2_InvalidArgumentException::class);
        
        $rule = $this->getMockBuilder(HTML_QuickForm2_Rule::class)
            ->onlyMethods(array('validateOwner'))
            ->setConstructorArgs(array($text, 'an error message'))
            ->getMock();
        
        $rule->setOwner($hidden);
    }

    public function testElementHasRulesMethod() : void
    {
        $el = new HTML_QuickForm2_Element_InputText();

        $this->assertFalse($el->hasRules());

        $el->addRuleRequired('Required');

        $this->assertTrue($el->hasRules());
    }
}

