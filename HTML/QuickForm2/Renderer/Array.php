<?php
/**
 * @category HTML
 * @package HTML_QuickForm2
 * @subpackage Renderer
 * @see HTML_QuickForm2_Renderer_Array
 */

declare(strict_types=1);

/**
 * A renderer for HTML_QuickForm2 building an array of form elements
 *
 * Based on Array renderer from HTML_QuickForm 3.x package
 *
 * The form array structure is the following:
 * <pre>
 * array(
 *   'id'               => form's "id" attribute (string),
 *   'frozen'           => whether the form is frozen (bool),
 *   'attributes'       => attributes for &lt;form&gt; tag (string),
 *   // if form contains required elements:
 *   'required_note'    => note about the required elements (string),
 *   // if 'group_hiddens' option is true:
 *   'hidden'           => array with html of hidden elements (array),
 *   // if form has some javascript for setup or validation:
 *   'javascript'       => form javascript (string)
 *   // if 'group_errors' option is true:
 *   'errors' => array(
 *     '1st element id' => 'Error for the 1st element',
 *     ...
 *     'nth element id' => 'Error for the nth element'
 *   ),
 *   'elements' => array(
 *     element_1,
 *     ...
 *     element_N
 *   )
 * );
 * </pre>
 * Where element_i is an array of the form
 * <pre>
 * array(
 *   'id'        => element id (string),
 *   'type'      => type of the element (string),
 *   'frozen'    => whether element is frozen (bool),
 *   // if element has a label:
 *   'label'     => 'label for the element',
 *   // note that if 'static_labels' option is true and element's label is an
 *   // array then there will be several 'label_*' keys corresponding to
 *   // labels' array keys
 *   'required'  => whether element is required (bool),
 *   // if a validation error is present and 'group_errors' option is false:
 *   'error'     => error associated with the element (string),
 *   // if some style was associated with an element:
 *   'style'     => 'some information about element style (e.g. for Smarty)',
 *
 *   // if element is not a Container
 *   'value'     => element value (mixed),
 *   'html'      => HTML for the element (string),
 *
 *   // if element is a Container
 *   'attributes' => container attributes (string)
 *   // if element is a Group
 *   'class'      => element's 'class' attribute
 *   // only for groups, if separator is set:
 *   'separator'  => separator for group elements (array),
 *   'elements'   => array(
 *     element_1,
 *     ...
 *     element_N
 *   )
 * );
 * </pre>
 *
 * While almost everything in this class is defined as public, its properties
 * and those methods that are not published (i.e. not in array returned by
 * exportMethods()) will be available to renderer plugins only.
 *
 * The following methods are published:
 *   - {@see self::toArray()}
 *   - {@see self::setStyleForId()}
 *   - {@see self::setStaticLabels()}
 *   - {@see self::isStaticLabelsEnabled()}
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @subpackage Renderer
 * @author Alexey Borzov <avb@php.net>
 * @author Bertrand Mansion <golgote@mamasam.com>
 * @author Thomas Schulz <ths@4bconsult.de>
 *
 * @see \HTML\QuickForm2\Renderer\Proxy\ArrayRendererProxy
 */
class HTML_QuickForm2_Renderer_Array extends HTML_QuickForm2_Renderer
{
    public const SETTING_STATIC_LABELS = 'static_labels';

    public const RENDERER_ID = 'array';

    /**
    * An array being generated
    * @var array
    */
    public array $array = array();

   /**
    * Array with references to 'elements' fields of currently processed containers
    * @var array
    */
    public array $containers = array();

   /**
    * Whether the form contains required elements
    * @var  bool
    */
    public bool $hasRequired = false;

   /**
    * Additional style information for elements
    * @var array
    */
    public array $styles = array();

   /**
    * Constructor, adds a new 'static_labels' option
    */
    protected function __construct()
    {
        $this->options[self::SETTING_STATIC_LABELS] = false;

        parent::__construct();
    }

    public function getID() : string
    {
        return self::RENDERER_ID;
    }

    protected function exportMethods() : array
    {
        return array(
            array(self::class, 'toArray')[1],
            array(self::class, 'setStyleForId')[1],
            array(self::class, 'setStaticLabels')[1],
            array(self::class, 'isStaticLabelsEnabled')[1]
        );
    }

   /**
    * Resets the accumulated data
    *
    * This method is called automatically by startForm() method, but should
    * be called manually before calling other rendering methods separately.
    *
    * @return $this
    */
    public function reset() : self
    {
        $this->array       = array();
        $this->containers  = array();
        $this->hasRequired = false;

        return $this;
    }

   /**
    * Returns the resultant array
    *
    * @return array{id:string,html:string,value:mixed,type:string,required:bool,frozen:bool}
    */
    public function toArray() : array
    {
        return $this->array;
    }

    /**
     * Creates an array with fields that are common to all elements
     *
     * @param HTML_QuickForm2_Node $element Element being rendered
     *
     * @return array<string,mixed>
     * @throws HTML_QuickForm2_NotFoundException {@see HTML_QuickForm2_Renderer::ERROR_OPTION_UNKNOWN}
     */
    public function buildCommonFields(HTML_QuickForm2_Node $element) : array
    {
        $ary = array(
            'id'     => $element->getId(),
            'frozen' => $element->toggleFrozen()
        );

        if ($labels = $element->getLabel())
        {
            if (!is_array($labels) || !$this->isStaticLabelsEnabled()) {
                $ary['label'] = $labels;
            } else {
                foreach ($labels as $key => $label) {
                    $key = is_int($key)? $key + 1: $key;
                    if (1 === $key) {
                        $ary['label'] = $label;
                    } else {
                        $ary['label_' . $key] = $label;
                    }
                }
            }
        }

        if (($error = $element->getError()) && $this->isGroupErrorsEnabled()) {
            $this->array['errors'][$ary['id']] = $error;
        } elseif ($error) {
            $ary['error'] = $error;
        }

        if (isset($this->styles[$ary['id']])) {
            $ary['style'] = $this->styles[$ary['id']];
        }

        return $ary;
    }

    /**
     * Creates an array with fields that are common to all Containers
     *
     * @param HTML_QuickForm2_Node $container Container being rendered
     *
     * @return array<string,mixed>
     * @throws HTML_QuickForm2_NotFoundException {@see HTML_QuickForm2_Renderer::ERROR_OPTION_UNKNOWN}
     */
    public function buildCommonContainerFields(HTML_QuickForm2_Node $container) : array
    {
        return $this->buildCommonFields($container) + array(
            'elements'   => array(),
            'attributes' => $container->getAttributes(true)
        );
    }

   /**
    * Stores an array representing "scalar" element in the form array
    *
    * @param array $element
    */
    public function pushScalar(array $element): void
    {
        if (!empty($element['required'])) {
            $this->hasRequired = true;
        }
        if (empty($this->containers)) {
            $this->array += $element;
        } else {
            $this->containers[count($this->containers) - 1][] = $element;
        }
    }

   /**
    * Stores an array representing a Container in the form array
    *
    * @param array $container
    */
    public function pushContainer(array $container): void
    {
        if (!empty($container['required'])) {
            $this->hasRequired = true;
        }
        if (empty($this->containers)) {
            $this->array      += $container;
            $this->containers  = array(&$this->array['elements']);
        } else {
            $cntIndex = count($this->containers) - 1;
            $myIndex  = count($this->containers[$cntIndex]);
            $this->containers[$cntIndex][$myIndex] = $container;
            $this->containers[$cntIndex + 1] =& $this->containers[$cntIndex][$myIndex]['elements'];
        }
    }

   /**
    * Sets a style for element rendering
    *
    * "Style" is some information that is opaque to Array Renderer but may be
    * of use to e.g. template engine that receives the resultant array.
    *
    * @param string|array $idOrStyles Element id or array ('element id' => 'style')
    * @param mixed        $style      Element style if $idOrStyles is not an array
    *
    * @return $this
    */
    public function setStyleForId($idOrStyles, $style = null) : self
    {
        if (is_array($idOrStyles)) {
            $this->styles = array_merge($this->styles, $idOrStyles);
        } else {
            $this->styles[$idOrStyles] = $style;
        }
        return $this;
    }

   /**#@+
    * Implementations of abstract methods from {@link HTML_QuickForm2_Renderer}
    */
    public function renderElement(HTML_QuickForm2_Node $element): void
    {
        $ary = $this->buildCommonFields($element) + array(
            'html'     => $element->__toString(),
            'value'    => $element->getValue(),
            'type'     => $element->getType(),
            'required' => $element->isRequired(),
        );
        $this->pushScalar($ary);
    }

    public function renderHidden(HTML_QuickForm2_Node $element): void
    {
        if ($this->isGroupHiddensEnabled()) {
            $this->array['hidden'][] = $element->__toString();
        } else {
            $this->renderElement($element);
        }
    }

    /**
     *
     * @param bool $enabled
     * @return self
     * @throws HTML_QuickForm2_NotFoundException {@see HTML_QuickForm2_Renderer::ERROR_OPTION_UNKNOWN}
     */
    public function setStaticLabels(bool $enabled) : self
    {
        return $this->setOption(self::SETTING_STATIC_LABELS, $enabled);
    }

    /**
     * @return bool
     * @throws HTML_QuickForm2_NotFoundException {@see HTML_QuickForm2_Renderer::ERROR_OPTION_UNKNOWN}
     */
    public function isStaticLabelsEnabled() : bool
    {
        return $this->getOption(self::SETTING_STATIC_LABELS) === true;
    }

    public function startForm(HTML_QuickForm2_Node $form): void
    {
        $this->reset();

        $this->array = $this->buildCommonContainerFields($form);
        if ($this->options[HTML_QuickForm2_Renderer::OPTION_GROUP_ERRORS]) {
            $this->array['errors'] = array();
        }
        if ($this->options[HTML_QuickForm2_Renderer::OPTION_GROUP_HIDDENS]) {
            $this->array['hidden'] = array();
        }
        $this->containers  = array(&$this->array['elements']);
    }

    public function finishForm(HTML_QuickForm2_Node $form): void
    {
        $this->finishContainer($form);
        if ($this->hasRequired) {
            $this->array['required_note'] = $this->getRequiredNote();
        }
        $this->array['javascript'] = $this->getJavascriptBuilder()->getFormJavascript($form->getId());
    }

    public function startContainer(HTML_QuickForm2_Node $container): void
    {
        $ary = $this->buildCommonContainerFields($container) + array(
            'required' => $container->isRequired(),
            'type'     => $container->getType()
        );
        $this->pushContainer($ary);
    }

    public function finishContainer(HTML_QuickForm2_Node $container): void
    {
        array_pop($this->containers);
    }

    public function startGroup(HTML_QuickForm2_Container_Group $group) : void
    {
        $ary = $this->buildCommonContainerFields($group) + array(
            'required' => $group->isRequired(),
            'type'     => $group->getType(),
            'class'    => $group->getAttribute('class')
        );
        if ($separator = $group->getSeparator()) {
            $ary['separator'] = array();
            for ($i = 0, $count = count($group); $i < $count - 1; $i++) {
                if (!is_array($separator)) {
                    $ary['separator'][] = $separator;
                } else {
                    $ary['separator'][] = $separator[$i % count($separator)];
                }
            }
        }
        $this->pushContainer($ary);
    }

    public function finishGroup(HTML_QuickForm2_Container_Group $group) : void
    {
        $this->finishContainer($group);
    }
    /**#@-*/
}
