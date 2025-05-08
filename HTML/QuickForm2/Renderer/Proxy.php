<?php
/**
 * Proxy class for HTML_QuickForm2 renderers and their plugins
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.githubusercontent.com/pear/HTML_QuickForm2/trunk/docs/LICENSE
 *
 * @package   HTML_QuickForm2
 * @author    Alexey Borzov <avb@php.net>
 * @author    Bertrand Mansion <golgote@mamasam.com>
 * @category  HTML
 * @copyright 2006-2020 Alexey Borzov <avb@php.net>, Bertrand Mansion <golgote@mamasam.com>
 * @license   https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      https://pear.php.net/package/HTML_QuickForm2
 */

/**
 * Proxy class for HTML_QuickForm2 renderers and their plugins
 *
 * This class serves two purposes:
 * <ol>
 *   <li>Aggregates renderer and its plugins. From user's point of view
 *       renderer plugins simply add new methods to renderer instances.</li>
 *   <li>Restricts access to renderer properties and methods. Those are defined
 *       as 'public' to allow easy access from plugins, but only methods
 *       with names explicitly returned by {@see HTML_QuickForm2_Renderer::exportMethods()}
 *       are available to the outside world.</li>
 * </ol>
 *
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @category HTML
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Renderer_Proxy extends HTML_QuickForm2_Renderer
{
    public const ERROR_DUPLICATE_METHOD = 142101;
    public const ERROR_UNKNOWN_METHOD = 142102;

    /**
     * Renderer instance
     */
    protected HTML_QuickForm2_Renderer $_renderer;

    /**
     * Additional renderer methods to proxy via {@see self::__call()}, as returned by {@see self::exportMethods()}.
     *
     * @var array<string,true>
     */
    private array $_rendererMethods = array();

    /**
     * Reference to a list of registered renderer plugins for that renderer type
     * @var array
     */
    private array $_pluginClasses;

    /**
     * Plugins for this renderer
     */
    private array $_plugins = array();

    /**
     * Plugin methods to call via __call() magic method
     *
     * Array has the form ('lowercase method name' => 'index in _plugins array')
     */
    private array $_pluginMethods = array();

    /**
     * Constructor, sets proxied renderer and its plugins
     *
     * @param HTML_QuickForm2_Renderer $renderer Renderer instance to proxy
     * @param array<string, class-string> &$pluginClasses Plugins registered for that renderer type. See {@see HTML_QuickForm2_Renderer::$_pluginClasses}.
     *
     * @see HTML_QuickForm2_Renderer::factory() Proxy classes are instantiated here.
     */
    protected function __construct(HTML_QuickForm2_Renderer $renderer, array &$pluginClasses)
    {
        parent::__construct();

        foreach ($renderer->exportMethods() as $method)
        {
            $this->_rendererMethods[strtolower($method)] = true;
        }

        $this->_renderer = $renderer;
        $this->_pluginClasses = &$pluginClasses;
    }

    public function getID() : string
    {
        return $this->_renderer->getID();
    }

    /**
     * Magic function; call an imported method of a renderer or its plugin
     *
     * @param string $name method name
     * @param array $arguments method arguments
     *
     * @return   mixed
     */
    public function __call(string $name, $arguments)
    {
        $lower = strtolower($name);

        if (isset($this->_rendererMethods[$lower]))
        {
            // support fluent interfaces
            $ret = call_user_func_array(array($this->_renderer, $name), $arguments);
            return $ret === $this->_renderer ? $this : $ret;
        }

        $this->updatePlugins();

        if (isset($this->_pluginMethods[$lower]))
        {
            return call_user_func_array(
                array($this->_plugins[$this->_pluginMethods[$lower]], $name),
                $arguments
            );
        }

        throw new HTML_QuickForm2_InvalidArgumentException(
            sprintf(
                'Method [%s] does not exist.',
                get_class($this->_renderer) . '::' . $name . '()'
            ),
            self::ERROR_UNKNOWN_METHOD
        );
    }

    /**
     * Checks whether a method is available in this object
     *
     * A method is considered available if this class has such a public method,
     * if a proxied renderer publishes such a method, if some plugin has such
     * a public method.
     *
     * @param string $name Method name
     *
     * @return bool
     */
    public function methodExists(string $name) : bool
    {
        $lower = strtolower($name);
        $exists = parent::methodExists($name) || isset($this->_rendererMethods[$lower]);
        if (!$exists)
        {
            $this->updatePlugins();
            $exists = isset($this->_pluginMethods[$lower]);
        }
        return $exists;
    }

    /**
     * Updates the list of plugins for the current renderer instance
     *
     * This method checks whether any new plugin classes were registered
     * since its previous invocation and adds instances of these classes to
     * the list. Plugins' methods are imported and can be later called as
     * this object's own.
     *
     * @throws   HTML_QuickForm2_InvalidArgumentException if a plugin has already
     *                   imported name
     */
    protected function updatePlugins() : void
    {
        $total = count($this->_pluginClasses);

        for ($i = count($this->_plugins); $i < $total; $i++)
        {
            $className = $this->_pluginClasses[$i];

            $methods = array();
            $plugin = new $className;
            $reflection = new ReflectionObject($plugin);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
            {
                $lower = strtolower($method->getName());
                if (HTML_QuickForm2_Renderer_Plugin::class === $method->getDeclaringClass()->getName())
                {
                    continue;
                }

                if (
                    !isset($this->_rendererMethods[$lower])
                    &&
                    !isset($this->_pluginMethods[$lower])
                )
                {
                    $methods[$lower] = $i;
                }
                else
                {
                    throw new HTML_QuickForm2_InvalidArgumentException(
                        sprintf(
                            'Duplicate method name: name [%s] in plugin [%s] already taken by [%s]',
                            $method->getName(),
                            get_class($plugin),
                            (
                                isset($this->_rendererMethods[$lower]) ?
                                get_class($this->_renderer) :
                                get_class($this->_plugins[$this->_pluginMethods[$lower]])
                            )
                        ),
                        self::ERROR_DUPLICATE_METHOD
                    );
                }
            }

            $plugin->setRenderer($this->_renderer);
            $this->_plugins[$i] = $plugin;
            $this->_pluginMethods += $methods;
        }
    }

    /**#@+
     * Proxies for methods defined in {@link HTML_QuickForm2_Renderer}
     */
    public function setOption($nameOrOptions, $value = null) : self
    {
        $this->_renderer->setOption($nameOrOptions, $value);
        return $this;
    }

    public function getOption($name = null)
    {
        return $this->_renderer->getOption($name);
    }

    public function getJavascriptBuilder() : HTML_QuickForm2_JavascriptBuilder
    {
        return $this->_renderer->getJavascriptBuilder();
    }

    public function setJavascriptBuilder(?HTML_QuickForm2_JavascriptBuilder $builder = null) : self
    {
        $this->_renderer->setJavascriptBuilder($builder);
        return $this;
    }

    public function reset() : self
    {
        $this->_renderer->reset();
        return $this;
    }

    public function renderElement(HTML_QuickForm2_Node $element) : void
    {
        $this->_renderer->renderElement($element);
    }

    public function renderHidden(HTML_QuickForm2_Node $element) : void
    {
        $this->_renderer->renderHidden($element);
    }

    public function startForm(HTML_QuickForm2_Node $form) : void
    {
        $this->_renderer->startForm($form);
    }

    public function finishForm(HTML_QuickForm2_Node $form) : void
    {
        $this->_renderer->finishForm($form);
    }

    public function startContainer(HTML_QuickForm2_Node $container) : void
    {
        $this->_renderer->startContainer($container);
    }

    public function finishContainer(HTML_QuickForm2_Node $container) : void
    {
        $this->_renderer->finishContainer($container);
    }

    public function startGroup(HTML_QuickForm2_Container_Group $group) : void
    {
        $this->_renderer->startGroup($group);
    }

    public function finishGroup(HTML_QuickForm2_Container_Group $group) : void
    {
        $this->_renderer->finishGroup($group);
    }

    /**#@-*/

    public function __toString() : string
    {
        return (string)$this->_renderer;
    }
}
