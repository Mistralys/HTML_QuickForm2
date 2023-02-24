<?php

declare(strict_types=1);

use HTML\QuickForm2\Interfaces\Events\ContainerArgInterface;
use HTML\QuickForm2\Interfaces\Events\NameChangesArgInterface;
use HTML\QuickForm2\Traits\Events\ContainerArgTrait;
use HTML\QuickForm2\Traits\Events\NameChangesArgTrait;

class HTML_QuickForm2_Event_ContainerNameChanged
    extends HTML_QuickForm2_Event
    implements
    ContainerArgInterface,
    NameChangesArgInterface
{
    use ContainerArgTrait;
    use NameChangesArgTrait;
}
