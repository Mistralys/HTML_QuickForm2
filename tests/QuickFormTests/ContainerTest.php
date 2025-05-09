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

namespace QuickFormTests;

use HTML_QuickForm2_Container;
use HTML_QuickForm2_InvalidArgumentException;
use HTML_QuickForm2_NotFoundException;
use HTML_QuickForm2_Renderer;
use HTML_QuickForm2_Rule;
use PHPUnit\Framework\TestCase;
use QuickFormTests\CustomClasses\TestElementImpl2;
use QuickFormTests\CustomClasses\RuleRequest17576;
use QuickFormTests\CustomClasses\TestContainerImpl;

/**
 * Unit test for HTML_QuickForm2_Container class
 */
class ContainerTest extends TestCase
{
    public function testCanSetName(): void
    {
        $obj = new TestContainerImpl();
        $this->assertNotNull($obj->getName(), 'Containers should always have \'name\' attribute');

        $obj = new TestContainerImpl('foo');
        $this->assertEquals('foo', $obj->getName());

        $this->assertSame($obj, $obj->setName('bar'));
        $this->assertEquals('bar', $obj->getName());

        $obj->setAttribute('name', 'baz');
        $this->assertEquals('baz', $obj->getName());

    }


    public function testCanSetId(): void
    {
        $obj = new TestContainerImpl(null, array('id' => 'manual'));
        $this->assertEquals('manual', $obj->getId());

        $this->assertSame($obj, $obj->setId('another'));
        $this->assertEquals('another', $obj->getId());

        $obj->setAttribute('id', 'yet-another');
        $this->assertEquals('yet-another', $obj->getId());
    }


    public function testAutogenerateId(): void
    {
        $obj = new TestContainerImpl('somename');
        $this->assertNotEquals('', $obj->getId(), 'Should have an auto-generated \'id\' attribute');

        $obj2 = new TestContainerImpl('somename');
        $this->assertNotEquals($obj2->getId(), $obj->getId(), 'Auto-generated \'id\' attributes should be unique');
    }


    public function testCanNotRemoveNameOrId(): void
    {
        $obj = new TestContainerImpl('somename', array(), array('id' => 'someid'));
        try
        {
            $obj->removeAttribute('name');
        }
        catch (HTML_QuickForm2_InvalidArgumentException $e)
        {
            $this->assertMatchesRegularExpression('/Required attribute(.*)can not be removed/', $e->getMessage());
            try
            {
                $obj->removeAttribute('id');
            }
            catch (HTML_QuickForm2_InvalidArgumentException $e)
            {
                $this->assertMatchesRegularExpression('/Required attribute(.*)can not be removed/', $e->getMessage());
                return;
            }
        }
        $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
    }


    public function testAddAndGetElements(): void
    {
        $e1 = new TestElementImpl2('e1');
        $e2 = new TestElementImpl2('e2');
        $c1 = new TestContainerImpl('c1');
        $c1->appendChild($e1);
        $c1->appendChild($e2);
        $this->assertEquals(2, count($c1), 'Element count is incorrect');
        $this->assertSame($e1, $c1->getElementById($e1->getId()));
        $this->assertSame($e2, $c1->getElementById($e2->getId()));
    }


    public function testNestedAddAndGetElements(): void
    {
        $e1 = new TestElementImpl2('a1');
        $e2 = new TestElementImpl2('a2');
        $c1 = new TestContainerImpl('b1');
        $c1->appendChild($e1);
        $c1->appendChild($e2);

        $e3 = new TestElementImpl2('a3');
        $e4 = new TestElementImpl2('a4');
        $c2 = new TestContainerImpl('b2');
        $c2->appendChild($e3);
        $c2->appendChild($e4);
        $c2->appendChild($c1);

        $this->assertEquals(3, count($c2), 'Element count is incorrect');
        $this->assertSame($e1, $c2->getElementById($e1->getId()));
        $this->assertSame($e2, $c2->getElementById($e2->getId()));
    }


    public function testCannotSetContainerOnSelf(): void
    {
        $e1 = new TestElementImpl2('d1');
        $e2 = new TestElementImpl2('d2');
        $c1 = new TestContainerImpl('f1');
        $c1->appendChild($e1);
        $c1->appendChild($e2);
        try
        {
            $c1->appendChild($c1);
        }
        catch (HTML_QuickForm2_InvalidArgumentException $e)
        {
            $this->assertEquals('Cannot set an element or its child as its own container', $e->getMessage());
            $c2 = new TestContainerImpl('f2');
            $c2->appendChild($c1);
            try
            {
                $c1->appendChild($c2);
            }
            catch (HTML_QuickForm2_InvalidArgumentException $e)
            {
                $this->assertEquals('Cannot set an element or its child as its own container', $e->getMessage());
                return;
            }
        }
        $this->fail('Expected HTML_QuickForm2_InvalidArgumentException was not thrown');
    }


    public function testAddSameElementMoreThanOnce(): void
    {
        $e1 = new TestElementImpl2('g1');
        $e2 = new TestElementImpl2('g2');
        $c1 = new TestContainerImpl('h1');
        $c1->appendChild($e1);
        $c1->appendChild($e2);
        $c1->appendChild($e1);

        $this->assertEquals(2, count($c1), 'Element count is incorrect');
        $this->assertSame($e1, $c1->getElementById($e1->getId()));
        $this->assertSame($e2, $c1->getElementById($e2->getId()));
    }

    public function testMoveElement(): void
    {
        $e1 = new TestElementImpl2('move1');

        $c1 = new TestContainerImpl('cmove1');
        $c2 = new TestContainerImpl('cmove2');

        $c1->appendChild($e1);
        $this->assertSame($e1, $c1->getElementById($e1->getId()));
        $this->assertNull($c2->getElementById($e1->getId()), 'Element should not be found in container');

        $c2->appendChild($e1);
        $this->assertNull($c1->getElementById($e1->getId()), 'Element should be removed from container');
        $this->assertSame($e1, $c2->getElementById($e1->getId()));
    }

    public function testRemoveElement(): void
    {
        $e1 = new TestElementImpl2('i1');
        $e2 = new TestElementImpl2('i2');

        $c1 = new TestContainerImpl('j1');

        $c1->appendChild($e1);
        $c1->appendChild($e2);

        $removed = $c1->removeChild($e1);
        $this->assertEquals(1, count($c1), 'Element count is incorrect');
        $this->assertNull($c1->getElementById($e1->getId()), 'Element should be removed from container');
        $this->assertSame($e1, $removed, 'removeChild() should return the old child');
    }

    public function testCannotRemoveNonExisting(): void
    {
        $e1 = new TestElementImpl2('remove1');
        $e2 = new TestElementImpl2('remove2');

        $c1 = new TestContainerImpl('cremove1');
        $c2 = new TestContainerImpl('cremove2');

        $c1->appendChild($c2);
        $c2->appendChild($e1);

        try
        {
            $c1->removeChild($e1);
        }
        catch (HTML_QuickForm2_NotFoundException $e)
        {
            $this->assertEquals(
                HTML_QuickForm2_Container::ERROR_REMOVE_CHILD_HAS_OTHER_CONTAINER,
                $e->getCode()
            );

            try
            {
                $c1->removeChild($e2);
            }
            catch (HTML_QuickForm2_NotFoundException $e)
            {
                $this->assertEquals(
                    HTML_QuickForm2_Container::ERROR_REMOVE_CHILD_HAS_OTHER_CONTAINER,
                    $e->getCode()
                );
                return;
            }
        }

        $this->fail('Expected HTML_QuickForm2_NotFoundException was not thrown');
    }

    public function testInsertBefore(): void
    {
        $e1 = new TestElementImpl2('k1');
        $e2 = new TestElementImpl2('k2');
        $e3 = new TestElementImpl2('k3');
        $e4 = new TestElementImpl2('k4');

        $c1 = new TestContainerImpl('l1');
        $c2 = new TestContainerImpl('l2');

        $c1->appendChild($e1);
        $c1->appendChild($e2);
        $c2->appendChild($e4);

        $e3Insert = $c1->insertBefore($e3, $e1);
        $c1->insertBefore($e4, $e1);
        $c1->insertBefore($e2, $e3);

        $this->assertSame($e3, $e3Insert, 'insertBefore() should return the inserted element');
        $this->assertNull($c2->getElementById($e4->getId()), 'Element should be removed from container');

        $test = array($e2, $e3, $e4, $e1);
        $i = 0;
        foreach ($c1 as $element)
        {
            $this->assertSame($test[$i++], $element, 'Elements are in the wrong order');
        }
    }

    public function testInsertBeforeNonExistingElement(): void
    {
        $e1 = new TestElementImpl2('m1');
        $e2 = new TestElementImpl2('m2');
        $e3 = new TestElementImpl2('m3');

        $c1 = new TestContainerImpl('n1');
        $c1->appendChild($e1);
        $c2 = new TestContainerImpl('n2');
        $c2->appendChild($c1);
        try
        {
            $c1->insertBefore($e2, $e3);
        }
        catch (HTML_QuickForm2_NotFoundException $e)
        {
            $this->assertEquals(
                $e->getCode(),
                HTML_QuickForm2_Container::ERROR_CANNOT_FIND_CHILD_ELEMENT_INDEX,
                'Not the expected error code'
            );
            try
            {
                $c2->insertBefore($e2, $e1);
            }
            catch (HTML_QuickForm2_NotFoundException $e)
            {
                $this->assertEquals(
                    $e->getCode(),
                    HTML_QuickForm2_Container::ERROR_CANNOT_FIND_CHILD_ELEMENT_INDEX,
                    'Not the expected error code'
                );
                return;
            }
        }
        $this->fail('Expected HTML_QuickForm2_NotFoundException was not thrown');
    }

    public function testGetElementsByName(): void
    {
        $e1 = new TestElementImpl2('foo');
        $e2 = new TestElementImpl2('bar');
        $e3 = new TestElementImpl2('foo');
        $e4 = new TestElementImpl2('baz');
        $e5 = new TestElementImpl2('foo');

        $c1 = new TestContainerImpl('fooContainer1');
        $c2 = new TestContainerImpl('fooContainer2');

        $c1->appendChild($e1);
        $c1->appendChild($e2);
        $c1->appendChild($e3);

        $c2->appendChild($e4);
        $c2->appendChild($e5);
        $c2->appendChild($c1);

        $this->assertEquals(array($e1, $e3), $c1->getElementsByName('foo'));
        $this->assertEquals(array($e5, $e1, $e3), $c2->getElementsByName('foo'));
    }

    public function testDuplicateIdHandling(): void
    {
        $e1 = new TestElementImpl2('dup1', array('id' => 'dup'));
        $e2 = new TestElementImpl2('dup2', array('id' => 'dup'));

        $c1 = new TestContainerImpl('dupContainer1');
        $c2 = new TestContainerImpl('dupContainer2');

        $c1->appendChild($e1);
        $c1->appendChild($e2);
        $this->assertEquals(2, count($c1), 'Element count is incorrect');
        $c1->removeChild($e1);
        $this->assertEquals(1, count($c1), 'Element count is incorrect');
        $this->assertSame($e2, $c1->getElementById('dup'));

        $c2->appendChild($e1);
        $c2->appendChild($e2);
        $c2->removeChild($e2);
        $this->assertEquals(1, count($c2), 'Element count is incorrect');
        $this->assertSame($e1, $c2->getElementById('dup'));
    }

    public function testFrozenStatusPropagates(): void
    {
        $cFreeze = new TestContainerImpl('cFreeze');
        $elFreeze = $cFreeze->appendChild(new TestElementImpl2('elFreeze'));

        $cFreeze->toggleFrozen(true);
        $this->assertTrue($cFreeze->toggleFrozen(), 'Container should be frozen');
        $this->assertTrue($elFreeze->toggleFrozen(), 'Contained element should be frozen');

        $cFreeze->toggleFrozen(false);
        $this->assertFalse($cFreeze->toggleFrozen(), 'Container should not be frozen');
        $this->assertFalse($elFreeze->toggleFrozen(), 'Contained element should not be frozen');
    }

    public function testPersistentFreezePropagates(): void
    {
        $cPers = new TestContainerImpl('cPersistent');
        $elPers = $cPers->appendChild(new TestElementImpl2('elPersistent'));

        $cPers->persistentFreeze(true);
        $this->assertTrue($cPers->persistentFreeze(), 'Container should have persistent freeze behaviour');
        $this->assertTrue($elPers->persistentFreeze(), 'Contained element should have persistent freeze behaviour');

        $cPers->persistentFreeze(false);
        $this->assertFalse($cPers->persistentFreeze(), 'Container should not have persistent freeze behaviour');
        $this->assertFalse($elPers->persistentFreeze(), 'Contained element should not have persistent freeze behaviour');
    }

    public function testGetValue(): void
    {
        $c1 = new TestContainerImpl('hasValues');
        $this->assertNull($c1->getValue());

        $c2 = $c1->appendChild(new TestContainerImpl('sub'));
        $this->assertNull($c1->getValue());

        $el1 = $c1->appendChild(new TestElementImpl2('foo[idx]'));
        $el2 = $c1->appendChild(new TestElementImpl2('bar'));
        $el3 = $c2->appendChild(new TestElementImpl2('baz'));
        $this->assertNull($c1->getValue());

        $el1->setValue('a value');
        $el2->setValue('other value');
        $el3->setValue('yet another value');
        $this->assertEquals(array(
            'foo' => array('idx' => 'a value'),
            'bar' => 'other value',
            'baz' => 'yet another value'
        ), $c1->getValue());
    }

    public function testGetRawValue(): void
    {
        $c = new TestContainerImpl('filtered');

        $foo = $c->appendChild(new TestElementImpl2('foo'));
        $bar = $c->appendChild(new TestElementImpl2('bar'));

        $foo->setValue(' foo value ');
        $bar->setValue(' BAR VALUE ');
        $this->assertEquals(array(
            'foo' => ' foo value ',
            'bar' => ' BAR VALUE '
        ), $c->getRawValue());

        $c->addRecursiveFilter('trim');
        $bar->addFilter('strtolower');
        $this->assertEquals(array(
            'foo' => ' foo value ',
            'bar' => ' BAR VALUE '
        ), $c->getRawValue());

        $c->addFilter('count');
        $this->assertEquals(array(
            'foo' => ' foo value ',
            'bar' => ' BAR VALUE '
        ), $c->getRawValue());
    }

    public function testValidate() : void
    {
        $container = new TestContainerImpl('container');
        $el1 = $container->appendChild(new TestElementImpl2('foo'));
        $el2 = $container->appendChild(new TestElementImpl2('bar'));

        $containerRule = $this->getMockBuilder(HTML_QuickForm2_Rule::class)
            ->onlyMethods(array('validateOwner'))
            ->setConstructorArgs(array($container, 'irrelevant message'))
            ->getMock();

        $containerRule
            ->expects($this->once())
            ->method('validateOwner')
            ->will($this->returnValue(true));

        $el1Rule = $this->getMockBuilder(HTML_QuickForm2_Rule::class)
            ->onlyMethods(array('validateOwner'))
            ->setConstructorArgs(array($el1, 'some error'))
            ->getMock();

        $el1Rule
            ->expects($this->once())
            ->method('validateOwner')
            ->will($this->returnValue(false));

        $el2Rule = $this->getMockBuilder(HTML_QuickForm2_Rule::class)
            ->onlyMethods(array('validateOwner'))
            ->setConstructorArgs(array($el2, 'irrelevant message'))
            ->getMock();

        $el2Rule
            ->expects($this->once())
            ->method('validateOwner')
            ->will($this->returnValue(true));

        $container->addRule($containerRule);
        $el1->addRule($el1Rule);
        $el2->addRule($el2Rule);

        $this->assertFalse($container->validate());
        $this->assertNull($container->getError());
    }

    /**
     * Container rules should be called after element rules
     *
     * @link http://pear.php.net/bugs/17576
     */
    public function testRequest17576() : void
    {
        $container = new TestContainerImpl('container');
        $element = $container->appendChild(new TestElementImpl2('foo'));

        $ruleChange = $this->getMockBuilder(HTML_QuickForm2_Rule::class)
            ->onlyMethods(array('validateOwner'))
            ->setConstructorArgs(array($element, 'a message'))
            ->getMock();

        // Call the validation several times to trigger the
        // specific case. Two calls is not enough, because of
        // the second pass integrated in validate() when the
        // container itself is valid.
        $ruleChange->expects($this->exactly(3))->method('validateOwner')
            ->will($this->onConsecutiveCalls(
                true,
                true,
                false
            ));

        $element->addRule($ruleChange);

        $container->addRule(new RuleRequest17576(
            $container, 'a contained element is invalid'
        ));

        // first call (actually counts as 2)
        $this->assertTrue($container->validate());

        // second call (= third children validation)
        $this->assertFalse($container->validate());
        $this->assertEquals('a contained element is invalid', $container->getError());
    }

    /**
     * Checks that JS for container rules comes after js for rules on contained elements
     */
    public function testRequest17576Client(): void
    {
        $container = new TestContainerImpl('aContainer');
        $element = $container->appendChild(new TestElementImpl2('anElement'));

        $ruleContainer = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($container))
            ->getMock();
        $ruleContainer->expects($this->once())->method('getJavascriptCallback')
            ->will($this->returnValue('containerCallback'));
        $ruleElement = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($element))
            ->getMock();
        $ruleElement->expects($this->once())->method('getJavascriptCallback')
            ->will($this->returnValue('elementCallback'));

        $container->addRule($ruleContainer, HTML_QuickForm2_Rule::CLIENT);
        $element->addRule($ruleElement, HTML_QuickForm2_Rule::CLIENT);
        $this->assertMatchesRegularExpression(
            '/elementCallback.*containerCallback/s',
            $container->render(HTML_QuickForm2_Renderer::createDefault())
                ->getJavascriptBuilder()->getFormJavascript()
        );
    }

    public function testFrozenContainersHaveNoClientValidation(): void
    {
        $container = new TestContainerImpl('aContainer');
        $ruleContainer = $this->getMockBuilder('HTML_QuickForm2_Rule')
            ->setMethods(array('validateOwner', 'getJavascriptCallback'))
            ->setConstructorArgs(array($container))
            ->getMock();
        $ruleContainer->expects($this->never())->method('getJavascriptCallback');

        $container->addRule($ruleContainer, HTML_QuickForm2_Rule::CLIENT);
        $container->toggleFrozen(true);

        $renderer = HTML_QuickForm2_Renderer::createDefault();
        $container->render($renderer);

        $this->assertEquals(
            '',
            $renderer->getJavascriptBuilder()->getFormJavascript()
        );
    }

    public function testGetValueBrackets(): void
    {
        $c = new TestContainerImpl('withBrackets');
        $el1 = $c->appendChild(new TestElementImpl2('foo[]'));
        $el2 = $c->appendChild(new TestElementImpl2('foo[]'));

        $el1->setValue('first');
        $el2->setValue('second');
        $this->assertEquals(array('foo' => array('first', 'second')), $c->getValue());
    }
}

?>
