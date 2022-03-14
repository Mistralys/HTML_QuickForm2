<?php

declare(strict_types=1);

abstract class HTML_QuickForm2_Event
{
    /**
     * @var array<string,mixed>
     */
    protected array $args;
    private HTML_QuickForm2_EventHandler $handler;

    /**
     * @param HTML_QuickForm2_EventHandler $handler
     * @param array<string,mixed> $args
     */
    public function __construct(HTML_QuickForm2_EventHandler $handler, array $args=array())
    {
        $this->args = $args;
        $this->handler = $handler;
    }

    /**
     * @return HTML_QuickForm2_EventHandler
     */
    public function getEventHandler() : HTML_QuickForm2_EventHandler
    {
        return $this->handler;
    }

    public function removeListener(int $listenerID) : void
    {
        $this->handler->removeHandler($listenerID);
    }

    /**
     * @return array<string,mixed>
     */
    public function getArguments() : array
    {
        return $this->args;
    }
}
