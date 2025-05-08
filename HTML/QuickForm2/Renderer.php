<?php
/**
 * @category HTML
 * @package HTML_QuickForm2
 * @subpackage Renderer
 * @see HTML_QuickForm2_Renderer
 */

declare(strict_types=1);

use HTML\QuickForm2\Renderer\Proxy\ArrayRendererProxy;
use HTML\QuickForm2\Renderer\Proxy\Bootstrap5RendererProxy;
use HTML\QuickForm2\Renderer\Proxy\CallbackRendererProxy;
use HTML\QuickForm2\Renderer\Proxy\DefaultRendererProxy;
use HTML\QuickForm2\Renderer\Proxy\StubRendererProxy;

/**
 * Abstract base class for QuickForm2 renderers
 *
 * This class serves two main purposes:
 * <ul>
 *   <li>Defines the API all renderers should implement (render*() methods);</li>
 *   <li>Provides static methods for registering renderers and their plugins
 *       and {@link factory()} method for creating renderer instances.</li>
 * </ul>
 *
 * Note that renderers should always be instantiated through factory(), in the
 * other case it will not be possible to add plugins.
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @author   Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
abstract class HTML_QuickForm2_Renderer
{
    public const ERROR_RENDERER_TYPE_UNKNOWN = 141801;
    public const ERROR_RENDERER_TYPE_ALREADY_REGISTERED = 141802;
    public const ERROR_OPTION_UNKNOWN = 141803;
    public const ERROR_RENDERER_PLUGIN_ALREADY_REGISTERED = 141804;

    public const OPTION_GROUP_HIDDENS = 'group_hiddens';
    public const OPTION_REQUIRED_NOTE = 'required_note';
    public const OPTION_GROUP_ERRORS = 'group_errors';
    public const OPTION_ERRORS_PREFIX = 'errors_prefix';
    public const OPTION_ERRORS_SUFFIX = 'errors_suffix';

    /**
     * List of registered renderer types
     *
     * @var array<string, array{class:class-string,proxy:class-string}>
     */
    private static array $_types = array(
        HTML_QuickForm2_Renderer_Callback::RENDERER_ID => array(
            'class' => HTML_QuickForm2_Renderer_Callback::class,
            'proxy' => CallbackRendererProxy::class
        ),
        HTML_QuickForm2_Renderer_Default::RENDERER_ID => array(
            'class' => HTML_QuickForm2_Renderer_Default::class,
            'proxy' => DefaultRendererProxy::class
        ),
        HTML_QuickForm2_Renderer_Array::RENDERER_ID => array(
            'class' => HTML_QuickForm2_Renderer_Array::class,
            'proxy' => ArrayRendererProxy::class
        ),
        HTML_QuickForm2_Renderer_Stub::RENDERER_ID => array(
            'class' => HTML_QuickForm2_Renderer_Stub::class,
            'proxy' => StubRendererProxy::class
        ),
        HTML_QuickForm2_Renderer_Bootstrap5::RENDERER_ID => array(
            'class' => HTML_QuickForm2_Renderer_Bootstrap5::class,
            'proxy' => Bootstrap5RendererProxy::class
        )
    );

   /**
    * List of registered renderer plugins
    * @var array<string, array<string, class-string>>
    */
    private static array $_pluginClasses = array(
        HTML_QuickForm2_Renderer_Callback::RENDERER_ID => array(),
        HTML_QuickForm2_Renderer_Default::RENDERER_ID => array(),
        HTML_QuickForm2_Renderer_Array::RENDERER_ID => array(),
        HTML_QuickForm2_Renderer_Stub::RENDERER_ID => array(),
        HTML_QuickForm2_Renderer_Bootstrap5::RENDERER_ID => array()
    );

   /**
    * Renderer options
    * @var  array
    * @see  setOption()
    */
    protected array $options = array(
        self::OPTION_GROUP_HIDDENS => true,
        self::OPTION_REQUIRED_NOTE => '<em>*</em> denotes required fields.',
        self::OPTION_ERRORS_PREFIX => 'Invalid information entered:',
        self::OPTION_ERRORS_SUFFIX => 'Please correct these fields.',
        self::OPTION_GROUP_ERRORS => false
    );

   /**
    * Javascript builder object
    * @var HTML_QuickForm2_JavascriptBuilder|NULL
    */
    protected ?HTML_QuickForm2_JavascriptBuilder $jsBuilder = null;

   /**
    * Creates a new renderer instance of the given type
    *
    * A renderer is always wrapped by a Proxy, which handles calling its
    * "published" methods and methods of its plugins. Registered plugins are
    * added automagically to the existing renderer instances so that
    * <code>
    * $foo = HTML_QuickForm2_Renderer::factory('foo');
    * // Plugin implementing bar() method
    * HTML_QuickForm2_Renderer::registerPlugin('foo', 'Plugin_Foo_Bar');
    * $foo->bar();
    * </code>
    * will work.
    *
    * @param string $type Type name (treated case-insensitively)
    *
    * @return HTML_QuickForm2_Renderer_Proxy  A renderer instance of the given
    *                                         type wrapped by a Proxy
    * @throws HTML_QuickForm2_InvalidArgumentException {@see self::ERROR_RENDERER_TYPE_UNKNOWN} if type name is unknown
    * @throws HTML_QuickForm2_NotFoundException {@see HTML_QuickForm2_Loader::ERROR_CLASS_DOES_NOT_EXIST} if class for the renderer can
    *                                    not be found and/or loaded from file
    */
    final public static function factory(string $type) : HTML_QuickForm2_Renderer_Proxy
    {
        $type = strtolower($type);

        if (!isset(self::$_types[$type])) {
            throw new HTML_QuickForm2_InvalidArgumentException(
                "Renderer type '$type' is not known",
                self::ERROR_RENDERER_TYPE_UNKNOWN
            );
        }

        $rendererClass = self::$_types[$type]['class'];
        $proxyClass    = self::$_types[$type]['proxy'];

        return HTML_QuickForm2_Loader::requireObjectInstanceOf(
            HTML_QuickForm2_Renderer_Proxy::class,
            new $proxyClass(
                HTML_QuickForm2_Loader::requireObjectInstanceOf(
                    HTML_QuickForm2_Renderer::class,
                    new $rendererClass
                ),
                self::$_pluginClasses[$type]
            )
        );
    }

    public static function createArray() : ArrayRendererProxy
    {
        return HTML_QuickForm2_Loader::requireObjectInstanceOf(
            ArrayRendererProxy::class,
            self::factory(HTML_QuickForm2_Renderer_Array::RENDERER_ID)
        );
    }

    public static function createCallback() : CallbackRendererProxy
    {
        return HTML_QuickForm2_Loader::requireObjectInstanceOf(
            CallbackRendererProxy::class,
            self::factory(HTML_QuickForm2_Renderer_Callback::RENDERER_ID)
        );
    }

    public static function createDefault() : DefaultRendererProxy
    {
        return HTML_QuickForm2_Loader::requireObjectInstanceOf(
            DefaultRendererProxy::class,
            self::factory(HTML_QuickForm2_Renderer_Default::RENDERER_ID)
        );
    }

    public static function createStub() : StubRendererProxy
    {
        return HTML_QuickForm2_Loader::requireObjectInstanceOf(
            StubRendererProxy::class,
            self::factory(HTML_QuickForm2_Renderer_Stub::RENDERER_ID)
        );
    }

    public static function createBootstrap5() : Bootstrap5RendererProxy
    {
        return HTML_QuickForm2_Loader::requireObjectInstanceOf(
            Bootstrap5RendererProxy::class,
            self::factory(HTML_QuickForm2_Renderer_Bootstrap5::RENDERER_ID)
        );
    }

   /**
    * Registers a new renderer type.
    *
    * @param string $type Type name (treated case-insensitively).
    * @param class-string $className Renderer class name, must extend {@see HTML_QuickForm2_Renderer}.
    * @param class-string|NULL $proxyClass Custom proxy class name, defaults to {@see HTML_QuickForm2_Renderer_Proxy}.
    *                              The proxy class is useful to give visibility to the renderer's methods.
    *                              See the {@see ArrayRendererProxy} class for an example: It implements methods
    *                              like {@see ArrayRendererProxy::toArray()} which are not present in the proxy class.
    *                              With a custom proxy class, you can call these methods directly after a simple
    *                              <code>instanceof</code> check.
    *
    * @throws HTML_QuickForm2_InvalidArgumentException if type already registered
    */
    final public static function register(string $type, string $className, ?string $proxyClass=null): void
    {
        if(is_null($proxyClass) || !class_exists($proxyClass)) {
            $proxyClass = HTML_QuickForm2_Renderer_Proxy::class;
        }

        $type = strtolower($type);

        if (isset(self::$_types[$type])) {
            throw new HTML_QuickForm2_InvalidArgumentException(
                "Renderer type '$type' is already registered",
                self::ERROR_RENDERER_TYPE_ALREADY_REGISTERED
            );
        }

        self::$_types[$type] = array(
            'class' => $className,
            'proxy' => $proxyClass
        );

        if (empty(self::$_pluginClasses[$type])) {
            self::$_pluginClasses[$type] = array();
        }
    }

   /**
    * Registers a plugin for a renderer type
    *
    * @param string $type        Renderer type name (treated case-insensitively)
    * @param class-string $className   Plugin class name
    *
    * @throws   HTML_QuickForm2_InvalidArgumentException if plugin is already registered
    */
    final public static function registerPlugin(string $type, string $className) : void
    {
        $type = strtolower($type);

        if(!isset(self::$_pluginClasses[$type])) {
            self::$_pluginClasses[$type] = array();
        }

        foreach (self::$_pluginClasses[$type] as $pluginClass)
        {
            if (0 === strcasecmp($pluginClass, $className)) {
                throw new HTML_QuickForm2_InvalidArgumentException(
                    sprintf(
                        "Plugin [%s] for renderer type [%s] is already registered",
                        $className,
                        $type
                    ),
                    self::ERROR_RENDERER_PLUGIN_ALREADY_REGISTERED
                );
            }
        }

        self::$_pluginClasses[$type][] = $className;
    }

   /**
    * Constructor
    *
    * Renderer instances should not be created directly, use {@link factory()}
    */
    protected function __construct()
    {
        $this->init();
    }

    protected function init() : void
    {

    }

   /**
    * Checks whether a method is available in this object
    *
    * @param string $name Method name
    *
    * @return bool
    */
    public function methodExists(string $name) : bool
    {
        try {
            return (new ReflectionMethod($this, $name))->isPublic();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    // region: Handling of options

   /**
    * Sets the option(s) affecting renderer behaviour
    *
    * The following options are available:
    * <ul>
    *   <li>'group_hiddens' - whether to group hidden elements together or
    *                         render them where they were added (boolean)</li>
    *   <li>'group_errors'  - whether to group error messages or render them
    *                         alongside elements they apply to (boolean)</li>
    *   <li>'errors_prefix' - leading message for grouped errors (string)</li>
    *   <li>'errors_suffix' - trailing message for grouped errors (string)</li>
    *   <li>'required_note' - note displayed if the form contains required
    *                         elements (string)</li>
    * </ul>
    *
    * @param string|array $nameOrOptions option name or array ('option name' => 'option value')
    * @param mixed        $value         parameter value if $nameOrConfig is not an array
    *
    * @return   $this
    * @throws   HTML_QuickForm2_NotFoundException in case of unknown option
    */
    public function setOption($nameOrOptions, $value = null) : self
    {
        if (is_array($nameOrOptions))
        {
            foreach ($nameOrOptions as $name => $optionValue) {
                $this->setOption($name, $optionValue);
            }

            return $this;
        }

        if (!array_key_exists($nameOrOptions, $this->options)) {
            throw new HTML_QuickForm2_NotFoundException(
                sprintf('Unknown option [%s].', $nameOrOptions),
                self::ERROR_OPTION_UNKNOWN
            );
        }

        $this->options[$nameOrOptions] = $value;

        return $this;
    }

    public function setErrorsPrefix(string $prefix) : self
    {
        return $this->setOption(self::OPTION_ERRORS_PREFIX, $prefix);
    }

    public function getErrorsPrefix() : string
    {
        return (string)$this->getOption(self::OPTION_ERRORS_PREFIX);
    }

    public function setErrorsSuffix(string $suffix) : self
    {
        return $this->setOption(self::OPTION_ERRORS_SUFFIX, $suffix);
    }

    public function getErrorsSuffix() : string
    {
        return (string)$this->getOption(self::OPTION_ERRORS_SUFFIX);
    }

    public function setGroupErrors(bool $enabled) : self
    {
        return $this->setOption(self::OPTION_GROUP_ERRORS, $enabled);
    }

    public function isGroupErrorsEnabled() : bool
    {
        return $this->getOption(self::OPTION_GROUP_ERRORS) === true;
    }

    public function setGroupHiddens(bool $enabled) : self
    {
        return $this->setOption(self::OPTION_GROUP_HIDDENS, $enabled);
    }

    public function isGroupHiddensEnabled() : bool
    {
        return $this->getOption(self::OPTION_GROUP_HIDDENS) === true;
    }

    public function setRequiredNote(string $note) : self
    {
        return $this->setOption(self::OPTION_REQUIRED_NOTE, $note);
    }

    public function getRequiredNote() : string
    {
        return (string)$this->getOption(self::OPTION_REQUIRED_NOTE);
    }

   /**
    * Returns the value(s) of the renderer option(s)
    *
    * @param string|NULL $name parameter name
    * @return   mixed   value of $name parameter, array of all configuration
    *                   parameters if $name is not given
    * @throws HTML_QuickForm2_NotFoundException {@see self::ERROR_OPTION_UNKNOWN} in case of unknown option.
    */
    public function getOption(?string $name = null)
    {
        if (null === $name)
        {
            return $this->options;
        }

        if (array_key_exists($name, $this->options))
        {
            return $this->options[$name];
        }

        throw new HTML_QuickForm2_NotFoundException(
            sprintf('Unknown option [%s] for renderer [%s].', $name, $this->getID()),
            self::ERROR_OPTION_UNKNOWN
        );
    }

    // endregion

   /**
    * Returns the javascript builder object
    *
    * @return   HTML_QuickForm2_JavascriptBuilder
    */
    public function getJavascriptBuilder() : HTML_QuickForm2_JavascriptBuilder
    {
        if (!isset($this->jsBuilder)) {
            $this->jsBuilder = new HTML_QuickForm2_JavascriptBuilder();
        }
        return $this->jsBuilder;
    }

   /**
    * Sets the JavaScript builder object
    *
    * You may want to reuse the same builder object if outputting several
    * forms on one page.
    *
    * @param HTML_QuickForm2_JavascriptBuilder|NULL $builder
    * @return $this
    */
    public function setJavascriptBuilder(?HTML_QuickForm2_JavascriptBuilder $builder = null) : self
    {
        $this->jsBuilder = $builder;
        return $this;
    }

    // region: Abstract & extensible methods

    /**
     * Returns an array of "published" method names that should
     * be callable through the renderer's proxy class, i.e.
     * public methods relevant for using the renderer, like
     * setting render options.
     *
     * Example: {@see HTML_QuickForm2_Renderer_Array::toArray()}.
     *
     * NOTE: Only renderer-specific methods should be returned.
     *
     * @return string[]
     */
    protected function exportMethods() : array
    {
        return array();
    }

    /**
     * Returns the renderer ID, as used in the {@see self::factory()}
     * method to create the renderer.
     *
     * NOTE: It is recommended to use a class constant for this,
     * to make referencing the ID easier.
     *
     * @return string
     */
    abstract public function getID() : string;

   /**
    * Resets the accumulated data
    *
    * This method is called automatically by startForm() method, but should
    * be called manually before calling other rendering methods separately.
    *
    * @return $this
    */
    abstract public function reset() : self;

   /**
    * Renders a generic element
    *
    * @param HTML_QuickForm2_Node $element Element being rendered
    */
    abstract public function renderElement(HTML_QuickForm2_Node $element);

   /**
    * Renders a hidden element
    *
    * @param HTML_QuickForm2_Node $element Hidden element being rendered
    */
    abstract public function renderHidden(HTML_QuickForm2_Node $element);

   /**
    * Starts rendering a form, called before processing contained elements
    *
    * @param HTML_QuickForm2_Node $form Form being rendered
    */
    abstract public function startForm(HTML_QuickForm2_Node $form);

   /**
    * Finishes rendering a form, called after processing contained elements
    *
    * @param HTML_QuickForm2_Node $form Form being rendered
    */
    abstract public function finishForm(HTML_QuickForm2_Node $form);

   /**
    * Starts rendering a generic container, called before processing contained elements
    *
    * @param HTML_QuickForm2_Node $container Container being rendered
    */
    abstract public function startContainer(HTML_QuickForm2_Node $container);

   /**
    * Finishes rendering a generic container, called after processing contained elements
    *
    * @param HTML_QuickForm2_Node $container Container being rendered
    */
    abstract public function finishContainer(HTML_QuickForm2_Node $container);

   /**
    * Starts rendering a group, called before processing grouped elements
    *
    * @param HTML_QuickForm2_Container_Group $group Group being rendered
    */
    abstract public function startGroup(HTML_QuickForm2_Container_Group $group) : void;

   /**
    * Finishes rendering a group, called after processing grouped elements
    *
    * @param HTML_QuickForm2_Container_Group $group Group being rendered
    */
    abstract public function finishGroup(HTML_QuickForm2_Container_Group $group) : void;

    // endregion

    public static function renderElementsWithSeparator($separator, array $elements) : string
    {
        if (!is_array($separator))
        {
            return implode((string)$separator, $elements);
        }

        $content = '';
        $cSeparator = count($separator);
        foreach ($elements as $i => $element)
        {
            $content .= (0 === $i ? '' : $separator[($i - 1) % $cSeparator]) .
                $element;
        }

        return $content;
    }
}
