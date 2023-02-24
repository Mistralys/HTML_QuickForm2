<?php

declare(strict_types=1);

use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;
use HTML\QuickForm2\Traits\Events\NodeArgTrait;

class HTML_QuickForm2_Event_ContainerChanged
    extends HTML_QuickForm2_Event
    implements NodeArgInterface
{
    use NodeArgTrait;

    public const KEY_NEW_CONTAINER = 'new_container';
    public const KEY_OLD_CONTAINER = 'old_container';

    public function getOldContainer() : ?HTML_QuickForm2_Container
    {
        return $this->getContainer(self::KEY_OLD_CONTAINER);
    }

    public function getNewContainer() : ?HTML_QuickForm2_Container
    {
        return $this->getContainer(self::KEY_NEW_CONTAINER);
    }

    private function getContainer(string $key) : ?HTML_QuickForm2_Container
    {
        if(isset($this->args[$key]) && $this->args[$key] instanceof HTML_QuickForm2_Container)
        {
            return $this->args[$key];
        }

        return null;
    }
}
