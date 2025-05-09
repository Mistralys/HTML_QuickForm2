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
 * Unit test for HTML_QuickForm2_Container_Repeat class
 */
class HTML_QuickForm2_Container_RepeatTest extends TestCase
{
    public function testCannotAddRepeatToRepeat(): void
    {
        $repeatOne = new HTML_QuickForm2_Container_Repeat();
        $repeatTwo = new HTML_QuickForm2_Container_Repeat();

        $this->expectException(HTML_QuickForm2_Exception::class);

        $repeatOne->setPrototype($repeatTwo);
    }
    
    public function testCannotAddRepeatToContainer(): void
    {
        $repeatOne = new HTML_QuickForm2_Container_Repeat();
        $repeatTwo = new HTML_QuickForm2_Container_Repeat();
        
        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        
        $repeatOne->setPrototype($fieldset);
        
        $this->expectException(HTML_QuickForm2_Exception::class);
        
        $fieldset->appendChild($repeatTwo);
    }

    public function testPrototypeRequiredForDOMAndOutput1(): void
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $text   = new HTML_QuickForm2_Element_InputText('aTextBox');

        $this->expectException(HTML_QuickForm2_NotFoundException::class);
        
        $repeat->appendChild($text);
    }
    
    public function testPrototypeRequiredForDOMAndOutput2(): void
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $text   = new HTML_QuickForm2_Element_InputText('aTextBox');
        
        $this->expectException(HTML_QuickForm2_NotFoundException::class);
        
        $repeat->insertBefore($text);
    }
    
    public function testPrototypeRequiredForDOMAndOutput3(): void
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        
        $this->expectException(HTML_QuickForm2_NotFoundException::class);
        
        $repeat->render(HTML_QuickForm2_Renderer::createDefault());
    }

    public function testElementsAreAddedToPrototype(): void
    {
        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat   = new HTML_QuickForm2_Container_Repeat(
            null, null, array('prototype' => $fieldset)
        );
        $textOne  = new HTML_QuickForm2_Element_InputText('firstText');
        $textTwo  = new HTML_QuickForm2_Element_InputText('secondText');

        $repeat->appendChild($textOne);
        $this->assertSame($textOne->getContainer(), $fieldset);

        $repeat->insertBefore($textTwo, $textOne);
        $this->assertSame($textTwo->getContainer(), $fieldset);

        $repeat->removeChild($textOne);
        $this->assertNull($textOne->getContainer());
    }

    public function testSetIndexesExplicitly(): void
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $this->assertEquals(array(), $repeat->getIndexes());

        $repeat->setIndexes(array('foo', 'bar', 'baz', 'qu\'ux', 'baz', 25));
        $this->assertEquals(array('foo', 'bar', 'baz', 25), $repeat->getIndexes());
    }

    public function testSetIndexFieldExplicitly(): void
    {
        $form = new HTML_QuickForm2('testIndexField');
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'blah' => array(
                'blergh'    => 'a',
                'blurgh'    => 'b',
                'ba-a-a-ah' => 'c',
                42          => 'd'
            ),
            'argh' => array(
                'a'    => 'e',
                'b\'c' => 'f',
                'd'    => 'g'
            )
        )));

        $repeat = new HTML_QuickForm2_Container_Repeat();
        $repeat->setIndexField('blah');
        $repeat->setIndexes(array('foo', 'bar'));
        $form->appendChild($repeat);
        $this->assertEquals(array('blergh', 'blurgh', 42), $repeat->getIndexes());

        $repeat->setIndexField('argh');
        $this->assertEquals(array('a', 'd'), $repeat->getIndexes());
    }

    public function testGuessIndexField(): void
    {
        $form = new HTML_QuickForm2('guessIndexField');
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'blah'   => array('foo' => 1),
            'bzz'    => array('bar' => array('a', 'b')),
            'aaargh' => array('foo' => ''),
            'blergh' => array('foo' => '', 'bar' => 'bar value')
        )));

        $repeat = new HTML_QuickForm2_Container_Repeat();
        $form->appendChild($repeat);

        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat->setPrototype($fieldset);
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addCheckbox('blah');
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addSelect('bzz', array('multiple'));
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addText('aaargh', array('disabled'));
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addText('blergh');
        $this->assertEquals(array('foo', 'bar'), $repeat->getIndexes());
    }

    public function testGetValue(): void
    {
        $values = array(
            'foo' => array('a' => 'a value', 'b' => 'b value', 'c' => 'c value'),
            'bar' => array(
                'baz' => array('a' => 'aa', 'b' => 'bb', 'c' => 'cc')
            )
        );

        $form   = new HTML_QuickForm2('repeatValue');
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($values));
        $form->appendChild($repeat);

        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat->setPrototype($fieldset);

        $fieldset->addText('foo');
        $fieldset->addText('bar[baz]');

        $this->assertEquals($values, $repeat->getValue());

        $repeat->setIndexes(array('a', 'c'));
        unset($values['foo']['b'], $values['bar']['baz']['b']);
        $this->assertEquals($values, $repeat->getValue());
    }

    public function testFrozenRepeatShouldNotContainJavascript(): void
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $repeat->setPrototype(new HTML_QuickForm2_Container_Fieldset());
        $repeat->toggleFrozen(true);

        $this->assertStringNotContainsString('<script', $repeat->__toString());
    }

    public function testServerSideValidationErrors() : void
    {
        $form = new HTML_QuickForm2('repeatValidate');

        $form->addDataSource(new HTML_QuickForm2_DataSource_Session(array(
            'foo' => array('', 'blah', '')
        )));

        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $text     = new HTML_QuickForm2_Element_InputText('foo');
        $repeat   = new HTML_QuickForm2_Container_Repeat();
        $repeat->setPrototype($fieldset);
        $fieldset->appendChild($text);
        $form->appendChild($repeat);

        $text->addRule('required', 'a message');
        $this->assertFalse($form->validate());

        $ary = $repeat->renderToArray();

        $this->assertSame('', $ary['elements'][1]['elements'][0]['value']);
        $this->assertSame('blah', $ary['elements'][2]['elements'][0]['value']);
        $this->assertSame('', $ary['elements'][3]['elements'][0]['value']);

        $this->assertEquals('a message', $ary['elements'][1]['elements'][0]['error']);
        $this->assertArrayNotHasKey('error', $ary['elements'][2]['elements'][0], $ary['elements'][2]['elements'][0]['error'] ?? '');
        $this->assertEquals('a message', $ary['elements'][3]['elements'][0]['error']);

        $text->setId('blah-:idx:');
        $ary = $repeat->renderToArray();
        $this->assertEquals('a message', $ary['elements'][1]['elements'][0]['error']);
        $this->assertArrayNotHasKey('error', $ary['elements'][2]['elements'][0]);
        $this->assertEquals('a message', $ary['elements'][3]['elements'][0]['error']);
    }

    public function testForeachWarningOnGetValue(): void
    {
        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat   = new HTML_QuickForm2_Container_Repeat(
            null, null, array('prototype' => $fieldset)
        );
        $fieldset->addText('foo');
        $repeat->setIndexes(array(1));

        $this->assertEquals(null, $repeat->getValue());
    }

    /**
     * Contents of static elements within repeat erroneously cleared
     * @link http://pear.php.net/bugs/bug.php?id=19802
     */
    public function testBug19802(): void
    {
        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat   = new HTML_QuickForm2_Container_Repeat(
            null, null, array('prototype' => $fieldset)
        );
        $fieldset->addStatic()
            ->setContent('Content of static element')
            ->setTagName('p');

        $arrayOne = $repeat->renderToArray();
        $arrayTwo = $repeat->renderToArray();

        $this->assertEquals(
            $arrayOne['elements'][0]['elements'][0]['html'],
            $arrayTwo['elements'][0]['elements'][0]['html']
        );
    }

    /**
     * If defaults contain null values, previous values are reused
     * @link http://pear.php.net/bugs/bug.php?id=20295
     */
    public function testBug20295(): void
    {
        $form = new HTML_QuickForm2('repeat-bug');
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'buggy' => array(
                'name'  => array(1 => 'First', 2 => 'Second'),
                'extra' => array(1 => 'Has extra', 2 => null)
            )
        )));

        $group = new HTML_QuickForm2_Container_Group('buggy');
        $group->addText('name');
        $group->addText('extra');

        $repeat = $form->addRepeat(null, array('id' => 'buggy-repeat'), array('prototype' => $group));

        $value = $repeat->getValue();
        $this->assertEquals('', $value['buggy']['extra'][2]);
    }

    public function testValidatorAlwaysPresentWhenClientRulesAdded(): void
    {
        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat   = new HTML_QuickForm2_Container_Repeat(
            null, null, array('prototype' => $fieldset)
        );

        $fieldset->addText('foo')
            ->addRule('required', 'Required!', null, HTML_QuickForm2_Rule::CLIENT_SERVER);

        $repeat->setIndexes(array());
        $renderer = HTML_QuickForm2_Renderer::createArray();
        $renderer->getJavascriptBuilder()->setFormId('fake-repeat');

        $repeat->render($renderer);

        $this->assertStringContainsString('new qf.Validator', $renderer->getJavascriptBuilder()->getValidator());
    }
}
