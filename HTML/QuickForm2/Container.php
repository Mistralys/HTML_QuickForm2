<?php
/**
 * Base class for simple HTML_QuickForm2 containers
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.githubusercontent.com/pear/HTML_QuickForm2/trunk/docs/LICENSE
 *
 * @category  HTML
 * @package   HTML_QuickForm2
 * @author    Alexey Borzov <avb@php.net>
 * @author    Bertrand Mansion <golgote@mamasam.com>
 * @copyright 2006-2020 Alexey Borzov <avb@php.net>, Bertrand Mansion <golgote@mamasam.com>
 * @license   https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      https://pear.php.net/package/HTML_QuickForm2
 */

use HTML\QuickForm2\Container\ValueWalker;
use HTML\QuickForm2\Interfaces\Events\ContainerArgInterface;
use HTML\QuickForm2\Interfaces\Events\NameChangesArgInterface;
use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;
use HTML\QuickForm2\NameTools;

/**
 * Abstract base class for simple QuickForm2 containers
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 *
 * @method HTML_QuickForm2_Element_Button        addButton(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputCheckbox addCheckbox(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_Date          addDate(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Container_Fieldset    addFieldset(string $name = '', $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Container_Group       addGroup(string $name = '', $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputFile     addFile(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputHidden   addHidden(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_Hierselect    addHierselect(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputImage    addImage(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputButton   addInputButton(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputPassword addPassword(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputRadio    addRadio(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Container_Repeat      addRepeat(string $name = '', $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputReset    addReset(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_Script        addScript(string $name = '', $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_Select        addSelect(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_Static        addStatic(string $name = '', $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputSubmit   addSubmit(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_InputText     addText(string $name, $attributes = null, array $data = array())
 * @method HTML_QuickForm2_Element_Textarea      addTextarea(string $name, $attributes = null, array $data = array())
 */
abstract class HTML_QuickForm2_Container extends HTML_QuickForm2_Node
    implements IteratorAggregate, Countable
{
    public const ERROR_CANNOT_FIND_CHILD_ELEMENT_INDEX = 38501;
    public const ERROR_UNDEFINED_CLASS_METHOD = 38503;
    public const ERROR_INVALID_NODE_ADDED_EVENT_INSTANCE = 38504;

    /**
    * Array of elements contained in this container
    * @var HTML_QuickForm2_Node[]
    */
    protected array $elements = array();

    public function __construct(?string $name = null, ?array $attributes = null, array $data = array())
    {
        parent::__construct($name, $attributes, $data);

        // If the name was not included in the initial attribute
        // collection, clear it: the attribute import mechanism
        // will have set it to "name" by default.
        if($name === null && !isset($attributes['name']))
        {
            $this->attributes['name'] = null;
        }
    }

    // region: Event handling

    public const EVENT_CONTAINER_NODE_ADDED = 'ContainerNodeAdded';
    public const EVENT_CONTAINER_NAME_CHANGED = 'ContainerNameChanged';

    /**
     * Changing the name of a container will trigger
     * an event chain, which allows all its children
     * to update their names depending on the container's
     * new name.
     *
     * To listen to the nameChanged event, use the method
     * {@see HTML_QuickForm2_Container::onNameChanged()}.
     *
     * @param string|null $value
     * @return void
     *
     * @see HTML_QuickForm2_Container::onNameChanged()
     */
    protected function handle_nameAttributeChanged(?string $value) : void
    {
        if(empty($value))
        {
            $value = null;
        }

        $this->previousName = $this->getName();
        $this->attributes['name'] = $value;

        // Name has changed - rename all children
        if($this->previousName !== $value && $this->prependsName())
        {
            $this->triggerNameChanged($this->previousName, $value);

            foreach ($this as $child)
            {
                $this->renameChild($child);
            }
        }
    }

    private function triggerNameChanged(?string $oldName, ?string $newName) : void
    {
        $this->eventHandler->triggerEvent(
            self::EVENT_CONTAINER_NAME_CHANGED,
            array(
                ContainerArgInterface::KEY_CONTAINER => $this,
                NameChangesArgInterface::KEY_OLD_NAME => $oldName,
                NameChangesArgInterface::KEY_NEW_NAME => $newName
            )
        );
    }

    /**
     * Adds an event listener for whenever the container's
     * name is changed.
     *
     * The listener gets the following parameters:
     *
     * - The event instance ({@see HTML_QuickForm2_Event_ContainerNameChanged}).
     * - [Optional] List of event listener function arguments.
     *
     * Example listener function:
     *
     * <pre>
     * function handleNameChanged(HTML_QuickForm2_Event_ContainerNameChanged $event) : void {}
     * </pre>
     *
     * @param callable $callback
     * @param array<int,mixed> $params
     * @return int
     * @throws HTML_QuickForm2_InvalidEventException
     */
    public function onNameChanged(callable $callback, array $params=array()) : int
    {
        return $this->eventHandler->addHandler(
            self::EVENT_CONTAINER_NAME_CHANGED,
            $callback,
            $params
        );
    }

    // endregion

    // region: Name handling

    /**
     * Previous group name
     * Stores the previous group name when the group name is changed.
     * Used to restore children names if necessary.
     * @var string|NULL
     */
    protected ?string $previousName = null;

    /**
     * Strips the container's name prefix from the
     * specified element name, if the container
     * has a name and is configured to prepend
     * its name to children (see {@see HTML_QuickForm2_Container::prependsName()}).
     *
     * Examples:
     *
     * - containerName[foo] = foo
     * - containerName[foo][bar] = foo[bar]
     *
     * @param string|null $elementName
     * @return string|null
     */
    public function stripContainerName(?string $elementName) : ?string
    {
        if($elementName === null)
        {
            return null;
        }

        $parent = $this->getContainer();

        if($parent !== null)
        {
            $elementName = $parent->stripContainerName($elementName);
        }

        if($elementName === null || !$this->prependsName())
        {
            return $elementName;
        }

        $containerName = $this->getName();

        if($containerName === null)
        {
            return $elementName;
        }

        if($elementName === $containerName.'[]')
        {
            return null;
        }

        return NameTools::reduceName($elementName, $containerName);
    }

    /**
     * Prepends group's name to contained element's name
     *
     * Used when adding an element to the group or changing group's name
     *
     * @param HTML_QuickForm2_Node $element
     *
     * @return HTML_QuickForm2_Node
     */
    protected function renameChild(HTML_QuickForm2_Node $element) : HTML_QuickForm2_Node
    {
        $this->log('Renaming child');

        $newName = $this->resolveChildName($element->getName(), $element->getContainer());

        return $element->setName($newName);
    }

    /**
     * Resolves the name of the target element according
     * to the group's name, so its value is stored as a
     * sub-value of the group.
     *
     * @param string|NULL $elementName
     * @param HTML_QuickForm2_Container|null $container
     * @return string
     */
    public function resolveChildName(?string $elementName, ?HTML_QuickForm2_Container $container=null) : ?string
    {
        $prefix = sprintf('ResolveChildName [%s] | ', $elementName);
        $containerName = $this->getName();
        $elementTokens = explode('[', str_replace(']', '', $elementName));

        // Child has already been renamed by its group before
        if (isset($this->previousName) && $this === $container)
        {
            //$this->log($prefix.'Already renamed by the group before.');

            $groupTokens = explode('[', str_replace(']', '', $this->previousName));

            if ($groupTokens === array_slice($elementTokens, 0, count($groupTokens)))
            {
                array_splice($elementTokens, 0, count($groupTokens));
            }
        }

        if ($containerName !== null)
        {
            $result = $containerName . '[' . implode('][', $elementTokens) . ']';

            //$this->log('Group has a name, using that: (%s)', $result);

            return $result;
        }

        if (isset($this->previousName))
        {
            $elname = array_shift($elementTokens);

            foreach ($elementTokens as $token)
            {
                $elname .= '[' . $token . ']';
            }

            /*$this->log(
                'Previous name exists: [%s] | Resolved name: (%s)',
                $this->previousName,
                $elname
            );*/

            return $elname;
        }

        //$this->log('Group has no name | Resolved name: (%s)', $elementName);

        return $elementName;
    }

    // endregion

    public function toggleFrozen($freeze = null)
    {
        if (null !== $freeze) {
            foreach ($this as $child) {
                $child->toggleFrozen($freeze);
            }
        }
        return parent::toggleFrozen($freeze);
    }

    public function persistentFreeze($persistent = null)
    {
        if (null !== $persistent) {
            foreach ($this as $child) {
                $child->persistentFreeze($persistent);
            }
        }
        return parent::persistentFreeze($persistent);
    }

   /**
    * Whether the container prepends its name to names
    * of contained elements, when it has a name set.
    *
    * NOTE: If the name is empty, it will not prepend
    * anything, even if it does so on principle.
    *
    * @return   bool
    */
    public function prependsName() : bool
    {
        return false;
    }

   /**
    * Returns the array containing child elements' values
    *
    * @param bool $filtered Whether child elements should apply filters on values
    *
    * @return array
    */
    protected function getChildValues(bool $filtered = false) : array
    {
        $method = $filtered? 'getValue': 'getRawValue';
        $values = array();
        $forceKeys = array();

        $children = $this->getElements();

        foreach ($children as $child)
        {
            $this->resolveChildValue(
                $child->$method(),
                $child,
                $values,
                $forceKeys
            );
        }

        return $values;
    }

    /**#
     * @param mixed $value
     * @param HTML_QuickForm2_Node $child
     * @param array<string,mixed> $values
     * @param array<string,int> $forceKeys
     * @return void
     */
    private function resolveChildValue($value, HTML_QuickForm2_Node $child, array &$values, array &$forceKeys) : void
    {
        if ($value === null)
        {
            return;
        }

        if ($child instanceof HTML_QuickForm2_Container
            && !$child->prependsName()
        )
        {
            $values = self::arrayMerge($values, $value);
            return;
        }

        $name = $child->getName();

        // It's not a complex array name, so we can
        // use the value directly.
        if (!strpos($name, '['))
        {
            $values[$name] = $value;
            return;
        }

        $tokens = explode('[', str_replace(']', '', $name));
        $valueAry =& $values;

        do
        {
            $token = array_shift($tokens);
            if (!isset($valueAry[$token])) {
                $valueAry[$token] = array();
            }
            $valueAry =& $valueAry[$token];
        }
        while (count($tokens) > 1);

        if ($tokens[0] !== '')
        {
            $valueAry[$tokens[0]] = $value;
        }
        else
        {
            if (!isset($forceKeys[$name]))
            {
                $forceKeys[$name] = 0;
            }

            $valueAry[$forceKeys[$name]++] = $value;
        }
    }

   /**
    * Returns the container's value without filters applied
    *
    * The default implementation for Containers is to return an array with
    * contained elements' values. The array is indexed the same way $_GET and
    * $_POST arrays would be for these elements.
    *
    * @return array
    */
    public function getRawValue() : array
    {
        return $this->getChildValues();
    }

   /**
    * Returns the container's value, possibly with filters applied.
    *
    * The default implementation for Containers is to return an array with
    * contained elements' values. The array is indexed the same way $_GET and
    * $_POST arrays would be for these elements.
    *
    * @return array
    */
    public function getValue() : array
    {
        return $this->applyFilters($this->getChildValues(true));
    }

   /**
    * Merges two arrays
    *
    * Merges two arrays like the PHP function array_merge_recursive does,
    * the difference being that existing integer keys will not be renumbered.
    *
    * @param array $a
    * @param array $b
    *
    * @return   array   resulting array
    */
    public static function arrayMerge(array $a, array $b) : array
    {
        foreach ($b as $key => $value)
        {
            if (!is_array($value) || (isset($a[$key]) && !is_array($a[$key])))
            {
                $a[$key] = $value;
            }
            else
            {
                $a[$key] = self::arrayMerge($a[$key] ?? array(), $value);
            }
        }

        return $a;
    }

   /**
    * Returns an array of this container's elements
    *
    * @return HTML_QuickForm2_Node[] Container elements
    */
    public function getElements() : array
    {
        return $this->elements;
    }

    /**
     * @return HTML_QuickForm2_Container[]
     */
    public function getContainerElements() : array
    {
        $elements = $this->getElements();
        $result = array();

        foreach($elements as $element)
        {
            if($element instanceof HTML_QuickForm2_Container)
            {
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @return HTML_QuickForm2_Container_Group[]
     */
    public function getGroupElements() : array
    {
        $elements = $this->getElements();
        $result = array();

        foreach($elements as $element)
        {
            if($element instanceof HTML_QuickForm2_Container_Group)
            {
                $result[] = $element;
            }
        }

        return $result;
    }
    
    const POSITION_APPEND = 'append';
    
    const POSITION_PREPEND = 'prepend';
    
    const POSITION_INSERT_BEFORE = 'insert_before';

   /**
    * Appends an element to the container
    *
    * If the element was previously added to the container or to another
    * container, it is first removed there.
    *
    * @param HTML_QuickForm2_Node $element Element to add
    *
    * @return   HTML_QuickForm2_Node     Added element
    */
    public function appendChild(HTML_QuickForm2_Node $element) : HTML_QuickForm2_Node
    {
        return $this->insertChildAtPosition($element, self::POSITION_APPEND);
    }
    
    public function prependChild(HTML_QuickForm2_Node $element) : HTML_QuickForm2_Node
    {
        return $this->insertChildAtPosition($element, self::POSITION_PREPEND);
    }
    
   /**
    * Retrieves the numeric index of the specified element in
    * the container's elements collection.
    * 
    * @param HTML_QuickForm2_Node $element
    * @throws HTML_QuickForm2_NotFoundException
    * @return int
    */
    protected function getChildIndex(HTML_QuickForm2_Node $element) : int
    {
        $offset = 0;
        
        foreach($this as $child)
        {
            if($child === $element) {
                return $offset;
            }
            
            $offset++;
        }
        
        throw new HTML_QuickForm2_NotFoundException(
            sprintf(
                "Cannot get child element index: No element with name [%s] could be found.",
                $element->getName()
            ),
            self::ERROR_CANNOT_FIND_CHILD_ELEMENT_INDEX
        );
    }
    
   /**
    * Inserts the specified element at the provided position in the
    * container's elements collection.
    * 
    * @param HTML_QuickForm2_Node $element
    * @param string $position
    * @param HTML_QuickForm2_Node|NULL $target The target element if the position requires one
    * @return HTML_QuickForm2_Node
    * @see HTML_QuickForm2_Container::insertBefore()
    * @see HTML_QuickForm2_Container::prependChild()
    * @see HTML_QuickForm2_Container::appendChild()
    */
    protected function insertChildAtPosition(HTML_QuickForm2_Node $element, string $position, ?HTML_QuickForm2_Node $target=null) : HTML_QuickForm2_Node
    {
        $isAlreadyChildElement = $element->getContainer() === $this;

        switch($position)
        {
            case self::POSITION_APPEND:
                $this->elements[] = $element;
                break;
                
            case self::POSITION_PREPEND:
                array_unshift($this->elements, $element);
                break;

            case self::POSITION_INSERT_BEFORE:
                // Ignore if no target has been specified.
                if($target === null)
                {
                    return $element;
                }
                
                array_splice(
                    $this->elements,
                    $this->getChildIndex($target),
                    0,
                    array($element)
                );
                break;
        }

        $this->invalidateLookup();

        // The element is not just being moved within
        // the container, it is a new element added
        // to the container's elements collection.
        if(!$isAlreadyChildElement)
        {
            $this->registerNewChildNode($element);
        }

        return $element;
    }

    private function registerNewChildNode(HTML_QuickForm2_Node $element) : void
    {
        $element->setContainer($this);

        $element->onContainerChanged(Closure::fromCallable(
            array($this, 'handle_elementContainerChanged')
        ));

        $this->triggerNodeAdded($element);
    }

    /**
     * Called when a child of this container is assigned
     * to a different container: Automatically removes the
     * element from this container.
     *
     * @param HTML_QuickForm2_Event_ContainerChanged $event
     * @param int $listenerID
     * @return void
     * @throws HTML_QuickForm2_NotFoundException
     */
    private function handle_elementContainerChanged(HTML_QuickForm2_Event_ContainerChanged $event, int $listenerID) : void
    {
        // We don't want this element listened to anymore
        $event->removeListener($listenerID);

        // Remove the child without triggering a new event chain.
        $this->_removeChild($event->getNode());
    }

    /**
     * Adds an event listener for whenever a new node is
     * added to the container.
     *
     * The callback method gets the following parameters:
     *
     * - Event instance, {@see HTML_QuickForm2_Event_ContainerNodeAdded}
     * - Optional event listener arguments (from {@see HTML_QuickForm2::onNodeAdded()})
     *
     * Example listener callback:
     *
     * <pre>
     * function(HTML_QuickForm2_Event_ContainerNodeAdded $event, ...$args) {}
     * </pre>
     *
     * @param callable $callback
     * @param array<int,mixed> $params
     * @return int
     *
     * @throws HTML_QuickForm2_InvalidEventException
     */
    public function onNodeAdded(callable $callback, array $params=array()) : int
    {
        return $this->eventHandler->addHandler(
            self::EVENT_CONTAINER_NODE_ADDED,
            $callback,
            $params
        );
    }

    /**
     * Called whenever a new node is added to the container.
     *
     * @param HTML_QuickForm2_Node $node
     * @return HTML_QuickForm2_Event_ContainerNodeAdded
     *
     * @throws HTML_QuickForm2_InvalidArgumentException
     * @throws HTML_QuickForm2_InvalidEventException
     *
     * @see HTML_QuickForm2_Container::ERROR_INVALID_NODE_ADDED_EVENT_INSTANCE
     */
    protected function triggerNodeAdded(HTML_QuickForm2_Node $node) : HTML_QuickForm2_Event_ContainerNodeAdded
    {
        $event = $this->eventHandler->triggerEvent(
            self::EVENT_CONTAINER_NODE_ADDED,
            array(
                NodeArgInterface::KEY_NODE => $node,
                ContainerArgInterface::KEY_CONTAINER => $this
            )
        );

        if(!$event instanceof HTML_QuickForm2_Event_ContainerNodeAdded)
        {
            throw new HTML_QuickForm2_InvalidArgumentException(
                'Invalid event class created.',
                self::ERROR_INVALID_NODE_ADDED_EVENT_INSTANCE
            );
        }

        $form = $this->getForm();

        // Tell the form about the new node. This includes
        // the form itself, since it extends the container.
        // This way the form's node added event gets all
        // node add events.
        if($form !== null)
        {
            $form->handle_nodeAdded($this, $node);
        }

        return $event;
    }

   /**
    * Invalidates (clears) the internal element lookup
    * table, which is used to keep track of all elements
    * available in the container.
    * 
    * @see HTML_QuickForm2_Container::getLookup()
    */
    public function invalidateLookup()
    {
        $this->lookup = null;
        
        $container = $this->getContainer();
        if($container) {
            $container->invalidateLookup();
        }
    }

    /**
     * Appends an element to the container (possibly creating it first)
     *
     * If the first parameter is an instance of HTML_QuickForm2_Node then all
     * other parameters are ignored and the method just calls {@link appendChild()}.
     * In the other case the element is first created via
     * {@link HTML_QuickForm2_Factory::createElement()} and then added via the
     * same method. This is a convenience method to reduce typing and ease
     * porting from HTML_QuickForm.
     *
     * @param string|HTML_QuickForm2_Node $elementOrType Either type name (treated
     *               case-insensitively) or an element instance
     * @param string|null $name Element name
     * @param array<string|number,string|number>|null $attributes Element attributes
     * @param array $data Element-specific data
     *
     * @return   HTML_QuickForm2_Node     Added element
     * @throws HTML_QuickForm2_InvalidArgumentException
     */
    public function addElement($elementOrType, ?string $name = null, ?array $attributes = null, array $data = array()) : HTML_QuickForm2_Node
    {
        if ($elementOrType instanceof HTML_QuickForm2_Node)
        {
            return $this->appendChild($elementOrType);
        }

        return $this->appendChild(HTML_QuickForm2_Factory::createElement(
            $elementOrType, $name, $attributes, $data
        ));
    }
    
   /**
    * Like {@link HTML_Quickform2_Container::addElement()}, but adds the
    * element at the top of the elements list of the container.
    * 
    * @param string|HTML_QuickForm2_Node $elementOrType Either type name (treated
    *               case-insensitively) or an element instance
    * @param string                      $name          Element name
    * @param string|array                $attributes    Element attributes
    * @param array                       $data          Element-specific data
    * 
    * @return HTML_QuickForm2_Node
    * @throws   HTML_QuickForm2_InvalidArgumentException
    * @throws   HTML_QuickForm2_NotFoundException
    */
    public function prependElement(
        $elementOrType, $name = null, $attributes = null, array $data = array()
    ) {
        if ($elementOrType instanceof HTML_QuickForm2_Node) {
            return $this->prependChild($elementOrType);
        } else {
            return $this->prependChild(HTML_QuickForm2_Factory::createElement(
                $elementOrType, $name, $attributes, $data
            ));
        }
    }

    /**
     * Removes the element from this container. Has no
     * effect if the element is not a child of this
     * container, or has already been removed.
     *
     * NOTE: Can trigger the element's "container changed"
     * event, see {@see HTML_QuickForm2_Node::onContainerChanged()}.
     *
     * @param HTML_QuickForm2_Node $element Element to remove
     * @return HTML_QuickForm2_Node Removed object
     *
     * @throws HTML_QuickForm2_InvalidArgumentException
     *
     * @see HTML_QuickForm2_Node::onContainerChanged()
     */
    public function removeChild(HTML_QuickForm2_Node $element) : HTML_QuickForm2_Node
    {
        if($this->_removeChild($element))
        {
            $element->setContainer(null);
        }

        return $element;
    }

    /**
     * Removes the target child element from the internal element
     * collection, without triggering any events.
     *
     * @param HTML_QuickForm2_Node $element
     * @return bool
     */
    private function _removeChild(HTML_QuickForm2_Node $element) : bool
    {
        $found = false;
        foreach ($this as $key => $child)
        {
            if ($child === $element)
            {
                unset($this->elements[$key]);
                $found = true;
                break;
            }
        }

        if ($found)
        {
            $this->elements = array_values($this->elements);
            $this->invalidateLookup();
        }

        return $found;
    }

   /**
    * Returns an element if its id is found
    *
    * @param string $id Element id to search for
    *
    * @return   HTML_QuickForm2_Node|null
    */
    public function getElementById(string $id) : ?HTML_QuickForm2_Node
    {
        // Replaced the recursive iterator implementation
        // with a lookup table that indexes the container's
        // own elements as well as all subelements. It is reset
        // when an element is added to the container, or one of
        // its sub-containers.
        
        $lookup = $this->getLookup();

        return $lookup[$id] ?? null;
    }

    /**
     * @param string $name The element name, or its form name.
     * @return HTML_QuickForm2_Node|null
     */
    public function getElementByName(string $name) : ?HTML_QuickForm2_Node
    {
        $elements = $this->getElements();
        $match = $this->resolveChildName($name, $this);

        foreach($elements as $element)
        {
            if($element->getName() === $match)
            {
                return $element;
            }
        }

        return null;
    }
    
   /**
    * Stores the element lookup table.
    * @var array<string,HTML_QuickForm2_Node>|NULL
    * @see HTML_QuickForm2_Container::getLookup()
    */
    protected ?array $lookup = null;
    
   /**
    * Retrieves the element lookup table, which
    * keeps track of all elements in the container.
    * It is used cache element instances by their
    * ID to be able to access them easily without
    * recursively traversing all children each time.
    * 
    * @see HTML_QuickForm2_Container::getElementById()
    * @see HTML_QuickForm2_Container::invalidateLookup()
    * @return array<string,HTML_QuickForm2_Node>
    */
    public function getLookup() : array
    {
        if(isset($this->lookup))
        {
            return $this->lookup;
        }
        
        $this->lookup = array();
        
        $total = count($this->elements);
        for($i=0; $i < $total; $i++) 
        {
            $element = $this->elements[$i];
            $id = $element->getId();

            if($id === null)
            {
                continue;
            }

            $this->lookup[$id] = $element;
            
            if($element instanceof HTML_QuickForm2_Container)
            {
                $els = $element->getLookup();
                foreach($els as $id => $el) {
                    $this->lookup[$id] = $el;
                }
            }
        }
        
        return $this->lookup;
    }
    
   /**
    * Returns an array of elements which name corresponds to element
    *
    * @param string $name Element name to search for
    *
    * @return   array
    */
    public function getElementsByName($name)
    {
        $found = array();
        foreach ($this->getRecursiveIterator() as $element) {
            if ($element->getName() == $name) {
                $found[] = $element;
            }
        }
        return $found;
    }

   /**
    * Inserts an element in the container
    *
    * If the reference object is not given, the element will be appended.
    *
    * @param HTML_QuickForm2_Node $element   Element to insert
    * @param HTML_QuickForm2_Node $reference Reference to insert before
    *
    * @return   HTML_QuickForm2_Node     Inserted element
    */
    public function insertBefore(HTML_QuickForm2_Node $element, HTML_QuickForm2_Node $reference = null)
    {
        return $this->insertChildAtPosition($element, self::POSITION_INSERT_BEFORE, $reference);
    }

   /**
    * Returns a recursive iterator for the container elements
    *
    * @return    HTML_QuickForm2_ContainerIterator
    */
    public function getIterator()
    {
        return new HTML_QuickForm2_ContainerIterator($this);
    }

   /**
    * Returns a recursive iterator iterator for the container elements
    *
    * @param int $mode mode passed to RecursiveIteratorIterator
    *
    * @return   RecursiveIteratorIterator
    */
    public function getRecursiveIterator($mode = RecursiveIteratorIterator::SELF_FIRST)
    {
        return new RecursiveIteratorIterator(
            new HTML_QuickForm2_ContainerIterator($this), $mode
        );
    }

   /**
    * Returns the number of elements in the container
    *
    * @return    int
    */
    public function count()
    {
        return count($this->elements);
    }

   /**
    * Called when the element needs to update its value from form's data sources
    *
    * The default behaviour is just to call the updateValue() methods of
    * contained elements, since default Container doesn't have any value itself
    *
    * @return $this
    */
    protected function updateValue() : self
    {
        $elements = $this->getElements();

        $this->log('Updating value for [%s] elements.', $this->count($elements));

        foreach ($elements as $child)
        {
            echo get_class($child).PHP_EOL;
            $child->updateValue();
        }

        return $this;
    }

   /**
    * Performs the server-side validation
    *
    * This method also calls validate() on all contained elements.
    *
    * @return   boolean Whether the container and all contained elements are valid
    */
    protected function validate()
    {
        $valid = true;
        foreach ($this as $child) {
            $valid = $child->validate() && $valid;
        }
        $valid = parent::validate() && $valid;
        // additional check is needed as a Rule on Container may set errors
        // on contained elements, see HTML_QuickForm2Test::testFormRule()
        if ($valid) {
            foreach ($this->getRecursiveIterator() as $item) {
                if (0 < strlen($item->getError())) {
                    return false;
                }
            }
        }
        return $valid;
    }

   /**
    * Appends an element to the container, creating it first
    *
    * The element will be created via {@link HTML_QuickForm2_Factory::createElement()}
    * and then added via the {@link appendChild()} method.
    * The element type is deduced from the method name.
    * This is a convenience method to reduce typing.
    *
    * @param string $m Method name
    * @param array  $a Method arguments
    *
    * @return   HTML_QuickForm2_Node     Added element
    * @throws   HTML_QuickForm2_InvalidArgumentException
    * @throws   HTML_QuickForm2_NotFoundException
    */
    public function __call($m, $a)
    {
        $match = array();
        if (preg_match('/^(add)([a-zA-Z0-9_]+)$/', $m, $match)) {
            if ($match[1] == 'add') {
                $type = strtolower($match[2]);
                $name = isset($a[0]) ? $a[0] : null;
                $attr = isset($a[1]) ? $a[1] : null;
                $data = isset($a[2]) ? $a[2] : array();
                return $this->addElement($type, $name, $attr, $data);
            }
        }

        throw new HTML_QuickForm2_NotFoundException(
            "Fatal error: Call to undefined method ".get_class($this)."::".$m."()",
            self::ERROR_UNDEFINED_CLASS_METHOD
        );
    }

   /**
    * Renders the container using the given renderer
    *
    * @param HTML_QuickForm2_Renderer $renderer
    *
    * @return   HTML_QuickForm2_Renderer
    */
    public function render(HTML_QuickForm2_Renderer $renderer)
    {
        $renderer->startContainer($this);
        foreach ($this as $element) {
            $element->render($renderer);
        }
        $this->renderClientRules($renderer->getJavascriptBuilder());
        $renderer->finishContainer($this);
        return $renderer;
    }

    public function __toString()
    {
        $renderer = HTML_QuickForm2_Renderer::factory('default');

        $this->render($renderer);

        return $renderer->__toString()
               . $renderer->getJavascriptBuilder()->getSetupCode(null, true);
    }

   /**
    * Returns Javascript code for getting the element's value
    *
    * @param bool $inContainer Whether it should return a parameter
    *                          for qf.form.getContainerValue()
    *
    * @return   string
    */
    public function getJavascriptValue($inContainer = false)
    {
        $args = array();
        foreach ($this as $child) {
            if ('' != ($value = $child->getJavascriptValue(true))) {
                $args[] = $value;
            }
        }
        return 'qf.$cv(' . implode(', ', $args) . ')';
    }

    public function getJavascriptTriggers()
    {
        $triggers = array();
        foreach ($this as $child) {
            foreach ($child->getJavascriptTriggers() as $trigger) {
                $triggers[$trigger] = true;
            }
        }
        return array_keys($triggers);
    }

   /**
    * Makes the container itself and all its child elements non-required
    * by removing any required rules that may have been added.
    *
    * @see HTML_QuickForm2_Node::makeOptional()
    */
    public function makeOptional()
    {
        parent::makeOptional();
        foreach ($this as $child) {
            $child->makeOptional();
        }
    }
    
   /**
    * Whether the element or any of its children have errors.
    * @see HTML_QuickForm2_Node::hasErrors()
    */
    public function hasErrors()
    {
        if (parent::hasErrors()) {
            return true;
        }
        
        foreach ($this as $child) {
            if ($child->hasErrors()) {
                return true;
            }
        }
        
        return false;
    }

    public function getValues()
    {
        /* @var $element HTML_QuickForm2_Node */
        
        $elements = $this->getElements();

        $values = array();
        foreach ($elements as $element) 
        {
            $values[$element->getName()] = $element->getValue();
        }
        
        return $values;
    }

    /**
     * Executes required initialization before the form
     * is rendered. Since this means the form's configuration
     * is completed, elements like the file upload can do
     * necessary checks now.
     *
     * Goes through all elements and lets them do their
     * initialization as well.
     */
     public function preRender()
     {
         $elements = $this->getElements();
         
         $total = count($elements);
         for($i=0; $i < $total; $i++) {
             $elements[$i]->preRender();
         }
     }

     protected function initNode() : void
     {
         parent::initNode();

         if(empty($this->attributes['name']))
         {
             $this->attributes['name'] = 'container-'.$this->getId();
         }
     }

    private ?ValueWalker $valueWalker = null;

    public function getLastValueWalker() : ?ValueWalker
    {
        return $this->valueWalker;
    }

    public function setValue($value) : self
    {
        if (!is_array($value))
        {
            return $this;
        }

        $this->valueWalker = new ValueWalker($this, $value);
        $this->valueWalker->walk();

        return $this;
    }

    public function getNestingDepth() : int
    {
        $tokens = explode('.', $this->getPath());
        return count($tokens) - 1;
    }

    public function hasChild(HTML_QuickForm2_Node $searchFor) : bool
    {
        $id = $searchFor->getId();

        // If we have an ID, the best way is to use the
        // ID lookup, which requires the least performance.
        if($id !== null)
        {
            return $this->getElementById($id) !== null;
        }

        // Do a full search instead

        $elements = $this->getElements();

        foreach($elements as $element)
        {
            if($element === $searchFor)
            {
                return true;
            }
        }

        return false;
    }
}

