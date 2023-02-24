<?php

declare(strict_types=1);

use HTML\QuickForm2\Interfaces\Events\ContainerArgInterface;
use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;
use HTML\QuickForm2\Traits\Events\ContainerArgTrait;
use HTML\QuickForm2\Traits\Events\NodeArgTrait;

class HTML_QuickForm2_Event_ContainerNodeAdded
    extends HTML_QuickForm2_Event
    implements
    ContainerArgInterface,
    NodeArgInterface
{
    use ContainerArgTrait;
    use NodeArgTrait;
}
