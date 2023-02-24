<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Traits\Events;

use HTML\QuickForm2\Interfaces\Events\ContainerArgInterface;
use HTML_QuickForm2_Container;
use HTML_QuickForm2_NotFoundException;

trait ContainerArgTrait
{
    public function getContainer() : HTML_QuickForm2_Container
    {
        if(isset($this->args[ContainerArgInterface::KEY_CONTAINER]) && $this->args[ContainerArgInterface::KEY_CONTAINER] instanceof HTML_QuickForm2_Container)
        {
            return $this->args[ContainerArgInterface::KEY_CONTAINER];
        }

        throw new HTML_QuickForm2_NotFoundException(
            'The container argument was not specified.',
            ContainerArgInterface::ERROR_CONTAINER_NOT_SPECIFIED
        );
    }
}
