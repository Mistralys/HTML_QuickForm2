<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Interfaces\Events;

use HTML_QuickForm2_Node;

interface NodeArgInterface
{
    public const ERROR_NODE_NOT_SPECIFIED = 103301;
    public const KEY_NODE = 'node';

    public function getNode() : HTML_QuickForm2_Node;
}
