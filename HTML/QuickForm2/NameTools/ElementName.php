<?php

declare(strict_types=1);

namespace HTML\QuickForm2\NameTools;

class ElementName
{
    private string $name;
    private ?string $containerName = null;

    /**
     * @var string[]
     */
    private array $subLevels = array();

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->parse();
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Retrieves the name of the key in a values array
     * that this element's value must be stored in.
     *
     * Examples:
     *
     * - foo > array('foo')
     * - foo[bar] > array('foo', 'bar')
     * - foo[bar][sub] > array('foo', 'bar', 'sub')
     *
     * @return string[]
     */
    public function getNamePath() : array
    {
        $path = $this->subLevels;

        if(isset($this->containerName))
        {
            array_unshift($path, $this->containerName);
        }
        else
        {
            array_unshift($path, $this->name);
        }

        return $path;
    }

    public function getContainerName() : ?string
    {
        return $this->containerName;
    }

    public function hasContainer() : bool
    {
        return isset($this->containerName);
    }

    /**
     * @return string[]
     */
    public function getSubLevels() : array
    {
        return $this->subLevels;
    }

    public function countSubLevels() : int
    {
        return count($this->subLevels);
    }

    public function hasSubLevels() : bool
    {
        return !empty($this->subLevels);
    }

    private function parse() : void
    {
        $bracketPos = strpos($this->name, '[');

        if($bracketPos === false)
        {
            return;
        }

        $tokens = explode('[', $this->name);

        $this->containerName = array_shift($tokens);

        foreach($tokens as $token)
        {
            $this->subLevels[] = rtrim($token, ']');
        }
    }

    /**
     * Reduces the element name one level, if
     * possible.
     *
     * - foo > foo
     * - foo[bar] > bar
     * - foo[bar][sub] > bar[sub]
     *
     * @return ElementName
     */
    public function reduce() : ElementName
    {
        if(!isset($this->containerName))
        {
            return new ElementName($this->name);
        }

        $tokens = $this->subLevels;
        $name = array_shift($tokens);

        if(!empty($tokens))
        {
            $name .= '['.implode('][', $tokens).']';
        }

        return new ElementName($name);
    }

    public function __toString() : string
    {
        return $this->getName();
    }
}
