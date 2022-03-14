<?php

declare(strict_types=1);

use HTML\QuickForm2\Interfaces\Events\FormArgInterface;
use HTML\QuickForm2\Traits\Events\FormArgTrait;

class HTML_QuickForm2_Event_FormNodeAdded
    extends HTML_QuickForm2_Event_ContainerNodeAdded
    implements
    FormArgInterface
{
    use FormArgTrait;
}
