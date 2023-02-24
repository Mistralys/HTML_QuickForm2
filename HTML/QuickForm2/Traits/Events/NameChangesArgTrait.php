<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Traits\Events;

use HTML\QuickForm2\Interfaces\Events\NameChangesArgInterface;

trait NameChangesArgTrait
{
    public function getOldName() : ?string
    {
        return $this->args[NameChangesArgInterface::KEY_OLD_NAME] ?? null;
    }

    public function getNewName() : ?string
    {
        return $this->args[NameChangesArgInterface::KEY_NEW_NAME] ?? null;
    }
}
