<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Interfaces\Element;

interface DisableAbleInterface
{
    public function setDisabled(bool $disabled) : self;

    public function isDisabled() : bool;
}
