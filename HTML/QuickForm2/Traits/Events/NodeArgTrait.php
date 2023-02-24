<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Traits\Events;

use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;
use HTML_QuickForm2_Node;
use HTML_QuickForm2_NotFoundException;

trait NodeArgTrait
{
    /**
     * Retrieves the node instance that was added.
     * @return HTML_QuickForm2_Node
     * @throws HTML_QuickForm2_NotFoundException
     */
    public function getNode() : HTML_QuickForm2_Node
    {
        if(isset($this->args[NodeArgInterface::KEY_NODE]) && $this->args[NodeArgInterface::KEY_NODE] instanceof HTML_QuickForm2_Node)
        {
            return $this->args[NodeArgInterface::KEY_NODE];
        }

        throw new HTML_QuickForm2_NotFoundException(
            'The event argument was not specified.',
            NodeArgInterface::ERROR_NODE_NOT_SPECIFIED
        );
    }
}
