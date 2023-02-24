<?php
/**
 * Class for handling form events.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006-2014, Alexey Borzov <avb@php.net>,
 *                          Bertrand Mansion <golgote@mamasam.com>,
 *                          Sebastian Mordziol <s.mordziol@mistralys.eu>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @subpackage EventHandler
 * @author   Sebastian Mordziol <s.mordziol@mistralys.eu>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://pear.php.net/package/HTML_QuickForm2
 */

/**
 * Class for handling form events.
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @subpackage EventHandler
 * @author   Sebastian Mordziol <s.mordziol@mistralys.eu>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_EventHandler
{
    public const ERROR_EVENT_CLASS_NOT_FOUND = 103201;

   /**
    * @var HTML_QuickForm2_Node
    */
    protected HTML_QuickForm2_Node $node;
    
   /**
    * Container for all added event handling callbacks.
    * @var array<string,array<int,array{id:int,callback:callable,params:array<int,mixed>}>>
    */
    protected array $handlers = array();
    
   /**
    * Counter for the event handler IDs.
    * @var integer
    */
    protected static int $idCounter = 0;
    
    public function __construct(HTML_QuickForm2_Node $node)
    {
        $this->node = $node;
    }

    /**
     * Adds an event handler for the specified event name,
     * and returns the handler ID. The ID can be used to
     * reference the handler again later, for example to
     * remove it again.
     *
     * @param string $eventName
     * @param callable $callback
     * @param array $params Optional parameters that are passed on to the callback when the event is triggered
     * @return int
     *
     * @throws HTML_QuickForm2_InvalidEventException
     */
    public function addHandler(string $eventName, callable $callback, array $params=array()) : int
    {
        // to make the class available as soon as the event has been registered
        $this->includeEventClass($eventName);
        
        if(!isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = array();
        }
        
        $id = $this->nextHandlerID();
        
        $this->handlers[$eventName][$id] = array(
            'id' => $id,
            'callback' => $callback,
            'params' => $params
        );
        
        return $id;
    }
    
    protected function nextHandlerID() : int
    {
        self::$idCounter++;
        return self::$idCounter;
    }

    /**
     * Removes a handler by its ID. Has no effect
     * if no corresponding handler is found.
     *
     * @param int $handlerID
     * @return $this
     */
    public function removeHandler(int $handlerID) : self
    {
        foreach($this->handlers as $name => $handlers) {
            if(isset($handlers[$handlerID])) {
                unset($this->handlers[$name][$handlerID]);
                break;
            }
        }

        return $this;
    }

    /**
     * Triggers the specified event. Returns an event instance
     * regardless of whether there were any handlers registered
     * for it.
     *
     * @param string $eventName
     * @param array<int,mixed> $args
     * @return HTML_QuickForm2_Event
     *
     * @throws HTML_QuickForm2_InvalidEventException
     */
    public function triggerEvent(string $eventName, array $args=array()) : HTML_QuickForm2_Event
    {
        $class = $this->includeEventClass($eventName);
        
        $event = new $class($this, $args);

        if(isset($this->handlers[$eventName]))
        {
            foreach($this->handlers[$eventName] as $handler)
            {
                $params = array();
                if(is_array($handler['params']))
                {
                    $params = $handler['params'];
                }

                array_unshift($params, $handler['id']);
                array_unshift($params, $event);

                call_user_func_array($handler['callback'], $params);
            }
        }
        
        return $event;
    }

    /**
     * Includes the event-specific class.
     *
     * @param string $eventName
     * @return string The name of the event class
     *
     * @throws HTML_QuickForm2_InvalidEventException
     */
    protected function includeEventClass(string $eventName) : string
    {
        $className = HTML_QuickForm2_Event::class.'_'.$eventName;

        if(class_exists($className))
        {
            return $className;
        }

        throw new HTML_QuickForm2_InvalidEventException(
            sprintf(
                'Event class [%s] could not be found.',
                $className
            ),
            self::ERROR_EVENT_CLASS_NOT_FOUND
        );
    }
}
