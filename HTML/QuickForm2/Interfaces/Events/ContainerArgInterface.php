<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Interfaces\Events;

use HTML_QuickForm2_Container;

interface ContainerArgInterface
{
    public const KEY_CONTAINER = 'container';
    public const ERROR_CONTAINER_NOT_SPECIFIED = 103401;

    public function getContainer() : HTML_QuickForm2_Container;
}
