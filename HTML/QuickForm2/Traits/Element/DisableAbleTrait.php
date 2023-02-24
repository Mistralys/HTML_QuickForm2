<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Traits\Element;

trait DisableAbleTrait
{
    public function setDisabled(bool $disabled) : self
    {
        return $this->setPropertyEnabled('disabled', $disabled);
    }

    public function isDisabled() : bool
    {
        return $this->hasProperty('disabled');
    }
}
