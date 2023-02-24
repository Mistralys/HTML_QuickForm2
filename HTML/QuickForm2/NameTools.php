<?php

declare(strict_types=1);

namespace HTML\QuickForm2;

use HTML\QuickForm2\NameTools\ElementName;
use HTML_QuickForm2_Container;

class NameTools
{
    /**
     * If the element name has a container name, retrieves
     * the container name.
     *
     * Examples:
     *
     * - name = null
     * - name[foo] = name
     * - name[foo][bar] = name
     *
     * @param string $elementName
     * @return string|null
     */
    public static function getContainerName(string $elementName) : ?string
    {
        return self::parseName($elementName)->getContainerName();
    }

    /**
     * Strips the container's name prefix from the
     * specified element name.
     *
     * Examples:
     *
     * - name[foo] = foo
     * - name[foo][bar] = foo[bar]
     *
     * @param string $elementName
     * @param string|null $containerName
     * @return string
     */
    public static function reduceName(string $elementName, ?string $containerName=null) : string
    {
        $name = self::parseName($elementName);

        if(!$name->hasContainer())
        {
            return $elementName;
        }

        // Container name not found in the name
        if($containerName !== null && $name->getContainerName() !== $containerName)
        {
            return $elementName;
        }

        return $name->reduce()->getName();
    }

    public static function parseName(string $elementName) : ElementName
    {
        return new ElementName($elementName);
    }

    public static function generateName(?string $baseName, ?HTML_QuickForm2_Container $container=null) : ?string
    {
        $path = array();

        if($container !== null && $container->prependsName())
        {
            $path = $container->getNamePath();
        }

        if($baseName !== null)
        {
            $path = array_merge($path, self::parseName($baseName)->getNamePath());
        }

        if(empty($path))
        {
            return null;
        }

        $prefix = array_shift($path);

        if(empty($path))
        {
            return $prefix;
        }

        return $prefix.'['.implode('][', $path).']';
    }
}
