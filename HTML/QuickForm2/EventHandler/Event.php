<?php

declare(strict_types=1);

use HTML\QuickForm2\EventHandler\EventException;

abstract class HTML_QuickForm2_Event
{
    public const ERROR_ARGUMENT_NULL_OR_NOT_STRING = 103701;

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

    /**
     * @param string $name
     * @return string
     * @throws EventException
     */
    protected function requireStringArgument(string $name) : string
    {
        if(isset($this->args[$name]) && is_string($this->args[$name]))
        {
            return $this->args[$name];
        }

        throw new EventException(
            'An event argument is missing or not a string as expected.',
            self::ERROR_ARGUMENT_NULL_OR_NOT_STRING
        );
    }
}
