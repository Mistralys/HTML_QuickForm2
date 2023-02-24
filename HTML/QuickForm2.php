<?php
/**
 * File containing the class {@see HTML_QuickForm2}.
 *
 * @category HTML
 * @package HTML_QuickForm2
 * @see HTML_QuickForm2
 */

use HTML\QuickForm2\AbstractHTMLElement\WatchedAttributes;
use HTML\QuickForm2\Interfaces\Events\ContainerArgInterface;
use HTML\QuickForm2\Interfaces\Events\FormArgInterface;
use HTML\QuickForm2\Interfaces\Events\NodeArgInterface;

/**
 * Class representing a HTML form
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2 extends HTML_QuickForm2_Container
{
    public const ERROR_CANNOT_ADD_FORM_TO_CONTAINER = 103601;

    /**
    * Data sources providing values for form elements
    * @var array
    */
    protected array $datasources = array();

    protected array $attributes = array(
        'method' => 'post'
    );

    protected $dataReason;

   /**
    * NOTE: The form's "id" and "method" attributes can only
    * be set here. Changing them afterwards will throw an exception.
    *
    * @param string|NULL  $id "id" attribute of <form> tag.
    * @param string $method HTTP method used to submit the form - "post" or "get".
    * @param string|array $attributes Additional HTML attributes (either a string or an array)
    */
    public function __construct(?string $id, string $method = 'post', $attributes = null)
    {
        parent::__construct();

        $this->log('Creating a new form with ID [%s]', $id);

        $this->initAttributes($id, $method, self::prepareAttributes($attributes));
        $this->initDatasources();
        $this->initTracking();

        $this->addFilter(array($this, 'skipInternalFields'));
    }

    // region: Event handling

    public const EVENT_FORM_NODE_ADDED = 'FormNodeAdded';

    /**
     * Called automatically whenever a container in the
     * form adds a new node. This in turn triggers the
     * form's node added event.
     *
     * @param HTML_QuickForm2_Container $container
     * @param HTML_QuickForm2_Node $element
     * @return void
     *
     * @throws HTML_QuickForm2_InvalidEventException
     *
     * @see HTML_QuickForm2_Container::onNodeAdded()
     */
    public function handle_nodeAdded(HTML_QuickForm2_Container $container, HTML_QuickForm2_Node $element) : void
    {
        $this->eventHandler->triggerEvent(
            self::EVENT_FORM_NODE_ADDED,
            array(
                FormArgInterface::KEY_FORM => $this,
                ContainerArgInterface::KEY_CONTAINER => $container,
                NodeArgInterface::KEY_NODE => $element
            )
        );
    }

    /**
     * Adds an event listener for whenever a new node is
     * added to the form, or any of its child containers.
     *
     * The callback method gets the following parameters:
     *
     * - Event instance, {@see HTML_QuickForm2_Event_FormNodeAdded}
     * - [Optional] List of event listener function arguments.
     *
     * Example listener function:
     *
     * <pre>
     * function(HTML_QuickForm2_Event_FormNodeAdded $event, ...$args) : void {}
     * </pre>
     *
     * @param callable $callback
     * @param array<int,mixed> $args
     * @return int
     *
     * @throws HTML_QuickForm2_InvalidEventException
     */
    public function onFormNodeAdded(callable $callback, array $args=array()) : int
    {
        return $this->eventHandler->addHandler(
            self::EVENT_FORM_NODE_ADDED,
            $callback,
            $args
        );
    }

    // endregion

    protected function initWatchedAttributes(WatchedAttributes $attributes) : void
    {
        parent::initWatchedAttributes($attributes);

        $attributes->setReadonly('id');
        $attributes->setReadonly('method');
    }

    public function isAttributeReadonly(string $name) : bool
    {
        return $this->watchedAttributes->isReadonly($name);
    }

    private function initTracking() : void
    {
        $this->log('Init | TrackingVar | Adding the tracking variable.');

        $this->appendChild(HTML_QuickForm2_Factory::createElement(
            'hidden',
            $this->getTrackingVarName(),
            array(
                'id' => 'qf:' . $this->getId()
            )
        ));
    }

    private function initAttributes(?string $id, string $method, array $attributes) : void
    {
        $this->log('Init | Populating default attributes.');

        if($id !== null)
        {
            $this->attributes['id'] = $id;
        }

        if(strtolower($method) === 'get')
        {
            $this->attributes['method'] = 'get';
        }

        $this->attributes = array_merge(
            self::prepareAttributes($attributes),
            $this->attributes
        );

        if(!isset($this->attributes['action']))
        {
            $this->attributes['action'] = $_SERVER['PHP_SELF'];
        }
    }

    private function initDataSources() : void
    {
        $this->log('Init | DataSources | Resolving data sources.');

        $method = $this->getMethod();
        $getNotEmpty = 'get' === $method && !empty($_GET);
        $postNotEmpty = 'post' === $method && (!empty($_POST) || !empty($_FILES));
        $trackVar = $this->getTrackingVarName();
        $trackVarFound = isset($_REQUEST[$trackVar]);

        // automatically add the super globals datasource to access
        // submitted form values, if data is present.
        if($trackVarFound)
        {
            $this->log('Init | DataSources | Adding super global source.');

            $this->addDataSource(new HTML_QuickForm2_DataSource_SuperGlobal($method));
        }

        $this->dataReason = array(
            'trackVarFound' => $trackVarFound,
            'getNotEmpty' => $getNotEmpty,
            'postNotEmpty' => $postNotEmpty
        );
    }

    public function getTrackingVarName() : string
    {
        return self::generateTrackingVarName($this->getId());
    }

    public static function generateTrackingVarName(string $id) : string
    {
        return '_qf__' . $id;
    }

    public function getMethod() : string
    {
        return $this->getAttribute('method');
    }
    
    public function getDataReason()
    {
        return $this->dataReason;
    }

    public function setContainer(HTML_QuickForm2_Container $container = null) : self
    {
        throw new HTML_QuickForm2_Exception(
            'The form itself cannot be added to a container.',
            self::ERROR_CANNOT_ADD_FORM_TO_CONTAINER
        );
    }

   /**
    * Adds a new data source to the form
    *
    * @param HTML_QuickForm2_DataSource $datasource Data source
    * @return $this
    */
    public function addDataSource(HTML_QuickForm2_DataSource $datasource) : self
    {
        $this->datasources[] = $datasource;
        $this->updateValue();
        return $this;
    }

   /**
    * Replaces the list of form's data sources with a completely new one
    *
    * @param array $datasources A new data source list
    *
    * @throws   HTML_QuickForm2_InvalidArgumentException    if given array
    *               contains something that is not a valid data source
    */
    public function setDataSources(array $datasources)
    {
        foreach ($datasources as $ds) {
            if (!$ds instanceof HTML_QuickForm2_DataSource) {
                throw new HTML_QuickForm2_InvalidArgumentException(
                    'Array should contain only DataSource instances'
                );
            }
        }
        $this->datasources = $datasources;
        $this->updateValue();
    }

   /**
    * Returns the list of data sources attached to the form
    *
    * @return HTML_QuickForm2_DataSource[]
    */
    public function getDataSources() : array
    {
        return $this->datasources;
    }

    public function getType()
    {
        return 'form';
    }

    public function setValue($value) : self
    {
        throw new HTML_QuickForm2_Exception('Not implemented');
    }

   /**
    * Tells whether the form was already submitted
    *
    * This is a shortcut for checking whether there is an instance of Submit
    * data source in the list of form data sources
    *
    * @return bool
    */
    public function isSubmitted()
    {
        foreach ($this->datasources as $ds) {
            if ($ds instanceof HTML_QuickForm2_DataSource_Submit) {
                return true;
            }
        }
        return false;
    }

   /**
    * Performs the server-side validation
    *
    * @return   boolean Whether all form's elements are valid
    */
    public function validate()
    {
        return $this->isSubmitted() && parent::validate();
    }

   /**
    * Renders the form using the given renderer
    *
    * @param HTML_QuickForm2_Renderer $renderer
    *
    * @return   HTML_QuickForm2_Renderer
    */
    public function render(HTML_QuickForm2_Renderer $renderer)
    {
        $this->preRender();
        
        $renderer->startForm($this);
        $renderer->getJavascriptBuilder()->setFormId($this->getId());
        foreach ($this as $element) {
            $element->render($renderer);
        }
        $this->renderClientRules($renderer->getJavascriptBuilder());
        $renderer->finishForm($this);
        return $renderer;
    }
    
    /**
     * Filter for form's getValue() removing internal fields' values from the array
     *
     * @param array $value
     *
     * @return array
     * @link http://pear.php.net/bugs/bug.php?id=19403
     */
    protected function skipInternalFields($value)
    {
        foreach (array_keys($value) as $key) {
            if ('_qf' === substr($key, 0, 3)) {
                unset($value[$key]);
            }
        }
        return $value;
    }
   
   /**
    * Retrieves the event handler instance of the form,
    * which is used to manage form events.
    * 
    * @return HTML_QuickForm2_EventHandler
    */
    public function getEventHandler()
    {
        return $this->eventHandler;
    }

    public function getLogIdentifier() : string
    {
        $label = 'Form'.$this->instanceID;

        $id = $this->getId();
        if(!empty($id))
        {
            $label .= ' [ID '.$id.']';
        }

        return $label;
    }

    public function isNameNullable() : bool
    {
        return false;
    }
}
