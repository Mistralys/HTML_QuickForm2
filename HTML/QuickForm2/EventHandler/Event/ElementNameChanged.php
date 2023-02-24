<?php

declare(strict_types=1);

use HTML\QuickForm2\Interfaces\Events\NameChangesArgInterface;
use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;
use HTML\QuickForm2\Traits\Events\NameChangesArgTrait;
use HTML\QuickForm2\Traits\Events\NodeArgTrait;

class HTML_QuickForm2_Event_ElementNameChanged
    extends HTML_QuickForm2_Event
    implements
    NodeArgInterface,
    NameChangesArgInterface
{
    use NodeArgTrait;
    use NameChangesArgTrait;
}
