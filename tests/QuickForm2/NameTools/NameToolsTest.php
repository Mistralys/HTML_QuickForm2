<?php

declare(strict_types=1);

namespace tests\QuickForm2\NameTools;

use assets\QuickFormTestCase;
use HTML\QuickForm2\NameTools;
use HTML_QuickForm2_Container_Group;

final class NameToolsTest extends QuickFormTestCase
{
    /**
     * By default, reducing the element name works with
     * any container name. Element names without container
     * names are ignored.
     */
    public function test_reduceName() : void
    {
        $this->assertSame('foo', NameTools::reduceName('foo'));
        $this->assertSame('bar', NameTools::reduceName('foo[bar]'));
        $this->assertSame('foo[bar]', NameTools::reduceName('container[foo][bar]'));
    }

    /**
     * When specifying a container name, only names with
     * this container must be reduced. Other names must
     * stay unchanged.
     */
    public function test_reduceNameWithContainer() : void
    {
        // Correct container name
        $this->assertSame('bar', NameTools::reduceName('foo[bar]', 'foo'));
        $this->assertSame('foo[bar]', NameTools::reduceName('container[foo][bar]', 'container'));
        $this->assertSame('foo', NameTools::reduceName('foo', 'foo'));

        // Mismatched container name
        $this->assertSame('foo[bar]', NameTools::reduceName('foo[bar]', 'bar'));
        $this->assertSame('foo', NameTools::reduceName('foo', 'bar'));
    }

    public function test_getContainerName() : void
    {
        $this->assertNull(NameTools::getContainerName('foo'));
        $this->assertSame('foo', NameTools::getContainerName('foo[bar]'));
        $this->assertSame('foo', NameTools::getContainerName('foo[bar][sub]'));
    }

    public function test_parseName() : void
    {
        $tests = array(
            array(
                'name' => 'foo',
                'containerName' => null,
                'reduced' => 'foo',
                'levels' => array(),
                'namePath' => array('foo')
            ),
            array(
                'name' => 'foo[bar]',
                'containerName' => 'foo',
                'reduced' => 'bar',
                'levels' => array('bar'),
                'namePath' => array('foo', 'bar')
            ),
            array(
                'name' => 'foo[bar][sub]',
                'containerName' => 'foo',
                'reduced' => 'bar[sub]',
                'levels' => array('bar', 'sub'),
                'namePath' => array('foo', 'bar', 'sub')
            )
        );

        foreach ($tests as $test)
        {
            $parsed = NameTools::parseName($test['name']);

            $this->assertSame($test['containerName'], $parsed->getContainerName());
            $this->assertSame($test['levels'], $parsed->getSubLevels());
            $this->assertSame($test['reduced'], $parsed->reduce()->getName());
            $this->assertSame($test['namePath'], $parsed->getNamePath());
        }
    }

    public function test_generateName() : void
    {
        $tests = array(
            array(
                'name' => null,
                'container' => null,
                'expected' => null
            ),
            array(
                'name' => 'foo',
                'container' => null,
                'expected' => 'foo'
            ),
            array(
                'name' => 'foo[bar]',
                'container' => null,
                'expected' => 'foo[bar]'
            ),
            array(
                'name' => 'foo',
                'container' => new HTML_QuickForm2_Container_Group(),
                'expected' => 'foo'
            ),
            array(
                'name' => 'foo',
                'container' => new HTML_QuickForm2_Container_Group('prefix'),
                'expected' => 'prefix[foo]'
            ),
            array(
                'name' => 'foo',
                'container' => new HTML_QuickForm2_Container_Group('prefix[sub_prefix]'),
                'expected' => 'prefix[sub_prefix][foo]'
            )
        );

        foreach($tests as $test)
        {
            $result = NameTools::generateName(
                $test['name'],
                $test['container']
            );

            $this->assertSame(
                $test['expected'],
                $result,
                'Test setup:'.PHP_EOL.
                print_r($test, true)
            );
        }
    }
}
