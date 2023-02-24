<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Interfaces\Events;

interface NameChangesArgInterface
{
    public const KEY_OLD_NAME = 'old_name';
    public const KEY_NEW_NAME = 'new_name';

    public function getOldName() : ?string;
    public function getNewName() : ?string;
}
