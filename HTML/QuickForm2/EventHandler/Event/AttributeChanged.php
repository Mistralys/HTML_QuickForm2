<?php

declare(strict_types=1);

use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;
use HTML\QuickForm2\Traits\Events\NodeArgTrait;

class HTML_QuickForm2_Event_AttributeChanged
    extends HTML_QuickForm2_Event
    implements NodeArgInterface
{
    use NodeArgTrait;

    public const KEY_ATTRIBUTE_NAME = 'attribute_name';
    public const KEY_OLD_VALUE = 'old_attribute_value';
    public const KEY_NEW_VALUE = 'new_attribute_value';

    public function getName() : string
    {
        return $this->requireStringArgument(self::KEY_ATTRIBUTE_NAME);
    }

    public function getOldValue() : ?string
    {
        return $this->args[self::KEY_OLD_VALUE] ?? null;
    }

    public function getNewValue() : ?string
    {
        return $this->args[self::KEY_NEW_VALUE] ?? null;
    }
}
