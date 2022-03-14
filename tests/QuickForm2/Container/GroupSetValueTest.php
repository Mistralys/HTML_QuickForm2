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

namespace QuickForm2\Container;

use HTML_QuickForm2_Container_Group;
use PHPUnit\Framework\TestCase;

class GroupSetValueTest extends TestCase
{
    /**
     * Setting values for elements of a group, when
     * the group itself has a name.
     */
    public function testSetValueNamedGroup() : void
    {
        $group = new HTML_QuickForm2_Container_Group('root');
        $group->setId('root');

        $e1 = $group->addText('e1');
        $e2 = $group->addText('e2[sub1]');
        $e3 = $group->addText('e3[sub1][sub2][sub3]');

        // Group with name, which will fetch values
        // only out of the according string key.
        $g2 = $group->addGroup('g2')->setId('g2');
        $g2e1 = $g2->addText('g2e1');
        $g2e2 = $g2->addText('g2e2[sub1]');

        // Group without name, wich will fetch values
        // out of indexed array values.
        $g3 = $group->addGroup()->setId('g3');
        $g3e1 = $g3->addText('g3e1');
        $g3e2 = $g3->addText('g3e2');

        $g4 = $group->addGroup('g4[sub1]')->setId('g4');
        $g4e1 = $g4->addText('g4e1');

        $g5 = $group->addGroup('g5[sub1][sub2][sub3]')->setId('g5');
        $g5e1 = $g5->addText('g5e1');

        echo '------------------------------------------------------'.PHP_EOL;
        echo 'SETTING VALUES'.PHP_EOL;
        echo '------------------------------------------------------'.PHP_EOL;

        $group->setValue(array(
            'e1' => 'value-e1',
            'e2' => array(
                'sub1' => 'value-e2'
            ),
            'e3' => array(
                'sub1' => array(
                    'sub2' => array(
                        'sub3' => 'value-e3'
                    )
                )
            ),
            'g2' => array(
                'g2e1' => 'value-g2e1',
                'g2e2' => array(
                    'sub1' => 'value-g2e2'
                )
            ),
            array(
                'g3e1' => 'value-g3e1'
            ),
            array(
                'g3e2' => 'value-g3e2'
            ),
            'g4' => array(
                'sub1' => array(
                    'g4e1' => 'value-g4e1'
                )
            ),
            'g5' => array(
                'sub1' => array(
                    'sub2' => array(
                        'sub3' => array(
                            'g5e1' => 'value-g5e1'
                        )
                    )
                )
            )
        ));

        $this->assertSame('value-e1', $e1->getValue());
        $this->assertSame('value-e2', $e2->getValue());
        $this->assertSame('value-e3', $e3->getValue());
        $this->assertSame('value-g2e1', $g2e1->getValue());
        $this->assertSame('value-g2e2', $g2e2->getValue());
        $this->assertSame('value-g3e1', $g3e1->getValue());
        $this->assertSame('value-g3e2', $g3e2->getValue());
        $this->assertSame('value-g4e1', $g4e1->getValue());
        $this->assertSame('value-g5e1', $g5e1->getValue());

        echo '------------------------------------------------------'.PHP_EOL;
        echo 'GROUP VALUES'.PHP_EOL;
        echo '------------------------------------------------------'.PHP_EOL;

        $g5->setValue(array(
            'sub1' => array(
                'sub2' => array(
                    'sub3' => array(
                        'g5e1' => 'value-g5e1-new'
                    )
                )
            )
        ));

        $this->assertSame('value-g5e1-new', $g5e1->getValue());
    }

    /**
     * Setting values for elements of a group, when
     * the group itself does not have a name.
     */
    public function testSetValueUnnamedGroup() : void
    {
        $group = new HTML_QuickForm2_Container_Group();

        $e1 = $group->addText('e1');
        $e2 = $group->addText('e2[i1]');
        $e3 = $group->addGroup('g1')->addText('e3');
        $g2 = $group->addGroup();
        $g2e1 = $g2->addText('e4');
        $g2e2 = $g2->addText('e5');

        $group->setValue(array(
            'e1' => 'first value',
            'e2' => array('i1' => 'second value'),
            'g1' => array('e3' => 'third value'),
            array('e4' => 'fourth value'),
            array('e5' => 'fifth value')
        ));

        $this->assertEquals('first value', $e1->getValue());
        $this->assertEquals('second value', $e2->getValue());
        $this->assertEquals('third value', $e3->getValue());
        $this->assertEquals('fourth value', $g2e1->getValue());
        $this->assertEquals('fifth value', $g2e2->getValue());
    }

    /**
     * Should be possible to use setValue() fluently
     *
     * @link https://pear.php.net/bugs/bug.php?id=19307
     */
    public function testBug19307() : void
    {
        $foo = new HTML_QuickForm2_Container_Group('foo');
        $foo->addText('bar');

        $this->assertSame($foo, $foo->setValue(array('bar' => 'a value')));
    }

    /**
     * Similar to bug #16806, properly set value for group of checkboxes having names like foo[]
     * @link http://pear.php.net/bugs/bug.php?id=16806
     */
    public function testCheckboxGroupSetValue() : void
    {
        $group = new HTML_QuickForm2_Container_Group('boxGroup');

        $e1 = $group->addCheckbox('', array('value' => 'red'));
        $e2 = $group->addCheckbox('', array('value' => 'green'));
        $e3 = $group->addCheckbox('', array('value' => 'blue'));

        $group->setValue(array('red', 'blue'));

        $this->assertSame('red', $e1->getValue());
        $this->assertSame('', $e2->getValue());
        $this->assertSame('blue', $e3->getValue());

        $this->assertTrue($e1->isChecked());
        $this->assertFalse($e2->isChecked());
        $this->assertTrue($e3->isChecked());

        $this->assertEquals(array('red', 'blue'), $group->getValue());
    }

    /**
     * Special case for a setValue() on a group of radiobuttons
     * @link http://pear.php.net/bugs/bug.php?id=20103
     */
    public function testRadioGroupSetValueUnnamedGroup() : void
    {
        $group = new HTML_QuickForm2_Container_Group();

        $group->addRadio('request20103', array('value' => 'first'));
        $group->addRadio('request20103', array('value' => 'second'));
        $group->addRadio('request20103', array('value' => 'third'));

        $group->setValue(array('request20103' => 'second'));
        $this->assertEquals(array('request20103' => 'second'), $group->getValue());
    }

    public function testRadioGroupSetValueNamedGroup() : void
    {
        $group = new HTML_QuickForm2_Container_Group('named');
        $group->addRadio('request20103[sub]', array('value' => 'first'));
        $group->addRadio('request20103[sub]', array('value' => 'second'));
        $group->addRadio('request20103[sub]', array('value' => 'third'));

        $group->setValue(array('request20103' => array('sub' => 'third')));
        $this->assertEquals(array('request20103' => array('sub' => 'third')), $group->getValue());
    }

    public function testScalarValueBug() : void
    {
        $group = new HTML_QuickForm2_Container_Group();
        $text  = $group->addText('foo[bar]');
        $group->setValue(array('foo' => 'foo value'));

        $this->assertEquals('', $text->getValue());
    }

    public function testSetValueUpdatesAllElements() : void
    {
        $group = new HTML_QuickForm2_Container_Group();
        $foo   = $group->addText('foo')->setValue('foo value');
        $bar   = $group->addText('bar')->setValue('bar value');

        $group->setValue(array('foo' => 'new foo value'));
        $this->assertEquals('new foo value', $foo->getValue());
        $this->assertEquals('', $bar->getValue());

        $group->setValue(null);
        $this->assertEquals('', $foo->getValue());
        $this->assertEquals('', $bar->getValue());
    }
}
