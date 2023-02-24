<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Traits\Events;

use HTML\QuickForm2\Interfaces\Events\ContainerArgInterface;
use HTML\QuickForm2\Interfaces\Events\FormArgInterface;
use HTML_QuickForm2;
use HTML_QuickForm2_Container;
use HTML_QuickForm2_NotFoundException;

trait FormArgTrait
{
    public function getForm() : HTML_QuickForm2
    {
        if(isset($this->args[FormArgInterface::KEY_FORM]) && $this->args[FormArgInterface::KEY_FORM] instanceof HTML_QuickForm2)
        {
            return $this->args[FormArgInterface::KEY_FORM];
        }

        throw new HTML_QuickForm2_NotFoundException(
            'The form argument was not specified.',
            FormArgInterface::ERROR_FORM_NOT_SPECIFIED
        );
    }
}
