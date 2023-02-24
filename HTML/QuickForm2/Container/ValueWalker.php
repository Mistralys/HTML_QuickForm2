<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Container;

use HTML\QuickForm2\NameTools;
use HTML\QuickForm2\NameTools\ElementName;
use HTML_QuickForm2_Container;
use HTML_QuickForm2_Node;

class ValueWalker
{
    private const VALUE_NOT_FOUND = '_:NOT:_:FOUND:_';

    private HTML_QuickForm2_Container $container;
    private array $values;
    private bool $walked = false;

    /**
     * @var array<int,array{element:HTML_QuickForm2_Node,key:string|int}>
     */
    private array $notFound = array();

    /**
     * @var array<int,array{element:HTML_QuickForm2_Node,key:string|int,value:mixed}>
     */
    private array $found = array();
    private int $indexCount;
    private int $depth;

    public function __construct(HTML_QuickForm2_Container $container, array $values)
    {
        $this->container = $container;
        $this->values = $values;
        $this->indexCount = $this->countIndexes();
        $this->depth = $container->getNestingDepth();

        print_r(array(
            'path' => $container->getPath(),
            'depth' => $container->getNestingDepth()
        ));
    }

    private function countIndexes() : int
    {
        $count = 0;
        for($i=0; $i < PHP_INT_MAX; $i++)
        {
            if(!isset($this->values[$i]))
            {
                break;
            }

            $count++;
        }

        return $count;
    }

    public function walk() : self
    {
        if($this->walked === true)
        {
            return $this;
        }

        $elements = $this->container->getElements();

        $this->log(
            'Walking values | Elements: [%s], values: [%s], indexed: [%s], depth: [%s].',
            count($elements),
            count($this->values),
            $this->indexCount,
            $this->depth
        );

        foreach($elements as $element)
        {
            $this->findValueFor($element);
        }

        return $this;
    }

    private function findValueFor(HTML_QuickForm2_Node $element) : void
    {
        $name = $this->container->stripContainerName($element->getName());

        if($element instanceof HTML_QuickForm2_Container)
        {
            $name = $element->stripContainerName($name);
        }

        if($name !== null)
        {
            $this->applyStringKey($element, $name);
            return;
        }

        // A container without name
        if($element instanceof HTML_QuickForm2_Container)
        {
            $this->applyIntKey($element);
        }
    }

    /**
     * Containers without names fetch their values from
     * *any* indexed entries in the values array. For each
     * of these, we pass on the indexed value to the container,
     * so it can extract any values that apply to it.
     *
     * Note: an indexed value array can have values for elements
     * of several containers, which is why it must be done this
     * way.
     *
     * @param HTML_QuickForm2_Container $container
     * @return void
     */
    private function applyIntKey(HTML_QuickForm2_Container $container) : void
    {
        $this->log(
            'Container {%s} | Handling indexed values ([%s]).',
            $container->getName(),
            $this->indexCount
        );

        // No indexed entries in the values array
        if($this->indexCount === 0)
        {
            return;
        }

        for($i=0; $i < $this->indexCount; $i++)
        {
            if(is_array($this->values[$i]))
            {
                $this->log(
                    'Container {%s} | Passing on the indexed value [#%s]',
                    $container->getName(),
                    $i
                );

                $container->setValue($this->values[$i]);
            }
        }
    }

    /**
     * Attempt to find a value for a regular string-based
     * element name.
     *
     * Examples:
     *
     * - foo
     * - foo[bar]
     *
     * @param HTML_QuickForm2_Node $element
     * @param string $name
     * @return void
     */
    private function applyStringKey(HTML_QuickForm2_Node $element, string $name) : void
    {
        $this->log(
            'Element {%s} | Searching for a value under {%s}.',
            $element->getName(),
            $name
        );

        // Ensure that we are looking at the right depth in the array
        $parsed = $this->reduceNameByNestingDepth($element, $name);

        $key = $parsed->getNamePath();
        $value = self::VALUE_NOT_FOUND;

        $this->log(
            'Element {%s} | Looking for value in string key [%s].',
            $element->getName(),
            $key
        );

        // Do a recursive search in the value: This handles elements
        // that have array names without being ordered in groups.
        // For example, a text element with the name "text[a][b][c]".
        if(array_key_exists($key, $this->values))
        {
            $value = $this->searchValue($parsed, $this->values[$key]);
        }

        if($value === self::VALUE_NOT_FOUND)
        {
            $this->registerNotFound($element);
            return;
        }

        $this->log(
            'Element {%s} | Set value of type [%s].',
            $element->getName(),
            gettype($value)
        );

        $element->setValue($value);
        $this->registerFound($element, $name, $value);
    }

    /**
     * For elements whose containers are nested deeper than
     * the main form, we need to strip one additional level
     * from the name.
     *
     * This is because the value returned by searchValue()
     * already returns an array with elements for containers,
     * but the containers will still try to search under their
     * own name.
     *
     * Example: Text element "bar" in Group "foo" will try to
     * get its value from the key "foo" in the values array,
     * but it must search directly under "bar".
     *
     * @param HTML_QuickForm2_Node $element
     * @param string $name
     * @return ElementName
     */
    private function reduceNameByNestingDepth(HTML_QuickForm2_Node $element, string $name) : ElementName
    {
        $container = $element->getContainer();
        $parsed = NameTools::parseName($name);

        if($container === null || $this->depth <= 1)
        {
            return $parsed;
        }

        $parsed = $this->reduceNameByContainer($parsed);

        $this->log(
            'Element {%s} | Nesting depth [%s]: reduced name to {%s}.',
            $element->getName(),
            $container->getNestingDepth(),
            $parsed->getName()
        );

        return $parsed;
    }

    private function reduceNameByContainer(ElementName $name, HTML_QuickForm2_Container $container) : ElementName
    {
        for($i = 0; $i < $this->depth; $i++)
        {
            $name = $name->reduce();
        }

        return $name;
    }

    /**
     * Recursive element value search, based on the element
     * name. In case of array names (foo[a][b][c]), this will
     * recurse through all array levels to try to find the
     * value.
     *
     * @param ElementName $name
     * @param mixed $value
     * @return mixed|null The element value, or NULL if none.
     */
    private function searchValue(ElementName $name, $value)
    {
        $this->log('ValueSearch {%s} | Analysing %s value.', $name, gettype($value));

        if(!is_array($value) || !$name->hasSubLevels())
        {
            $this->log('ValueSearch {%s} | Using value (value not an array, or no sub-levels left)', $name->getName());
            return $value;
        }

        $reduced = $name->reduce();
        $key = $reduced->getNamePath();

        if(array_key_exists($key, $value))
        {
            $this->log('ValueSearch {%s} | Recursing into subvalue: {%s}.', $name->getName(), $reduced->getName());
            return $this->searchValue($reduced, $value[$key]);
        }

        $this->log('ValueSearch {%s} | Nothing found.', $reduced->getName());

        return self::VALUE_NOT_FOUND;
    }

    private function log(string $message, ...$params) : void
    {
        $this->container->log('ValueWalker | '.$message, ...$params);
    }

    public function hasFound() : bool
    {
        return !empty($this->found);
    }

    /**
     * @return array<int,array{element:HTML_QuickForm2_Node,key:string|int,value:mixed}>
     */
    public function getFound() : array
    {
        return $this->found;
    }

    public function hasNotFound() : bool
    {
        return !empty($this->notFound);
    }

    /**
     * @return array<int,array{element:HTML_QuickForm2_Node,key:string|int}>
     */
    public function getNotFound() : array
    {
        return $this->notFound;
    }

    private function registerNotFound(HTML_QuickForm2_Node $element) : void
    {
        $this->log('Element {%s} | No value found.', $element->getName());

        $this->notFound[] = $element;
    }

    private function registerFound(HTML_QuickForm2_Node $element, $key, $value) : void
    {
        $this->log('Element {%s} | Value found.', $element->getName());

        $this->found[] = array(
            'element' => $element,
            'key' => $key,
            'value' => $value
        );
    }
}
