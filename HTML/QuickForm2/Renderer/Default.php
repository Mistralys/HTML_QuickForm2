<?php
/**
 * @package HTML_QuickForm2
 * @subpackage Renderer
 * @see HTML_QuickForm2_Renderer_Default
 * @category HTML
 */

declare(strict_types=1);

/**
 * Default renderer for QuickForm2
 *
 * Mostly a direct port of Default renderer from QuickForm 3.x package.
 *
 * While almost everything in this class is defined as public, its properties
 * and those methods that are not published (i.e. not in array returned by
 * exportMethods()) will be available to renderer plugins only.
 *
 * The following methods are published:
 *   - {@link setTemplateForClass()}
 *   - {@link setTemplateForId()}
 *   - {@link setErrorTemplate()}
 *   - {@link setElementTemplateForGroupClass()}
 *   - {@link setElementTemplateForGroupId()}
 *
 * @package HTML_QuickForm2
 * @subpackage Renderer
 * @author Alexey Borzov <avb@php.net>
 * @author Bertrand Mansion <golgote@mamasam.com>
 * @category HTML
 */
class HTML_QuickForm2_Renderer_Default extends HTML_QuickForm2_Renderer
{
    public const RENDERER_ID = 'default';

    /**
     * Whether the form contains required elements
     * @var  bool
     */
    public bool $hasRequired = false;

    /**
     * HTML generated for the form
     * @var  array
     */
    public array $html = array(array());

    /**
     * HTML for hidden elements if 'group_hiddens' option is on
     * @var  string
     */
    public string $hiddenHtml = '';

    /**
     * Array of validation errors if 'group_errors' option is on
     * @var  array
     */
    public array $errors = array();

    protected const TEMPLATE_QUICKFORM = <<<'HTML'
<div class="quickform">
    {errors}
    <form{attributes}>
        <div class="hiddens">
            {hidden}
        </div>
        {content}
    </form>
    <qf:reqnote>
        <div class="reqnote">{reqnote}</div>
    </qf:reqnote>
</div>
HTML;

    protected const TEMPLATE_ELEMENT_DEFAULT = <<<'HTML'
<div class="row">
    <p class="label">
        <qf:required><span class="required">*</span></qf:required>
        <qf:label><label for="{id}">{label}</label></qf:label>
    </p>
    <div class="element<qf:error> error</qf:error>">
        <qf:error><span class="error">{error}<br /></span></qf:error>
        {element}
    </div>
</div>
HTML;

    /**
     * Default templates for elements of the given class
     * @var array<string,string|NULL>
     */
    public array $templatesForClass = array(
        'html_quickform2_element_inputhidden' => '<div style="display: none;">{element}</div>',
        'html_quickform2' => self::TEMPLATE_QUICKFORM,
        'html_quickform2_container_fieldset' => '<fieldset{attributes}><qf:label><legend id="{id}-legend">{label}</legend></qf:label>{content}</fieldset>',
        'error:prefix' => '<div class="errors"><qf:message><p>{message}</p></qf:message><ul><li>',
        'error:separator' => '</li><li>',
        'error:suffix' => '</li></ul><qf:message><p>{message}</p></qf:message></div>',
        'html_quickform2_element' => self::TEMPLATE_ELEMENT_DEFAULT,
        'html_quickform2_container_group' => '<div class="row {class}"><p class="label"><qf:required><span class="required">*</span></qf:required><qf:label><label>{label}</label></qf:label></p><div class="element group<qf:error> error</qf:error>" id="{id}"><qf:error><span class="error">{error}<br /></span></qf:error>{content}</div></div>',
        'html_quickform2_container_repeat' => '<div class="row repeat" id="{id}"><qf:label><p>{label}</p></qf:label>{content}</div>'
    );

    /**
     * Custom templates for elements with the given IDs
     * @var array<string,string|NULL>
     */
    public array $templatesForId = array();

    /**
     * Default templates for elements in groups of the given classes
     *
     * Array has the form ('group class' => ('element class' => 'template', ...), ...)
     *
     * @var array<string,array<string,string|NULL>>
     */
    public array $elementTemplatesForGroupClass = array(
        'html_quickform2_container' => array(
            'html_quickform2_element' => '{element}',
            'html_quickform2_container_fieldset' => '<fieldset{attributes}><qf:label><legend id="{id}-legend">{label}</legend></qf:label>{content}</fieldset>'
        )
    );

    /**
     * Custom templates for grouped elements in the given group IDs
     *
     * Array has the form ('group id' => ('element class' => 'template', ...), ...)
     *
     * @var array<string,array<string,string|NULL>>
     */
    public array $elementTemplatesForGroupId = array();

    /**
     * Array containing IDs of the groups being rendered
     * @var array<int,string|false|NULL>
     */
    public array $groupId = array();

    public function getID() : string
    {
        return self::RENDERER_ID;
    }

    protected function exportMethods() : array
    {
        return array(
            array(self::class, 'setTemplateForClass')[1],
            array(self::class, 'setTemplateForId')[1],
            array(self::class, 'setErrorTemplate')[1],
            array(self::class, 'setElementTemplateForGroupClass')[1],
            array(self::class, 'setElementTemplateForGroupId')[1]
        );
    }

    /**
     * Sets template for form elements that are instances of the given class
     *
     * When searching for a template to use, renderer will check for templates
     * set for element's class and its parent classes, until found. Thus, a more
     * specific template will override a more generic one.
     *
     * @param string $className Class name
     * @param string|NULL $template Template to use for elements of that class
     *
     * @return $this
     */
    public function setTemplateForClass(string $className, ?string $template) : self
    {
        $this->templatesForClass[strtolower($className)] = $template;
        return $this;
    }

    /**
     * Sets template for form element with the given id
     *
     * If a template is set for an element via this method, it will be used.
     * In the other case a generic template set by {@link setTemplateForClass()}
     * or {@link setElementTemplateForGroupClass()} will be used.
     *
     * @param string $id Element's id
     * @param string|NULL $template Template to use for rendering of that element
     *
     * @return $this
     */
    public function setTemplateForId(string $id, ?string $template) : self
    {
        $this->templatesForId[$id] = $template;
        return $this;
    }

    /**
     * Sets templates for rendering validation errors.
     *
     * This template will be used if 'group_errors' option is set to true.
     *
     * @param string|NULL $prefix Prefix
     * @param string|NULL $separator Separator
     * @param string|NULL $suffix Suffix
     * @return $this
     */
    public function setErrorTemplate(?string $prefix, ?string $separator, ?string $suffix) : self
    {
        $this->setTemplateForClass('error:prefix', $prefix);
        $this->setTemplateForClass('error:separator', $separator);
        $this->setTemplateForClass('error:suffix', $suffix);

        return $this;
    }

    /**
     * Sets grouped elements templates using group class
     *
     * Templates set via {@link setTemplateForClass()} will not be used for
     * grouped form elements. When searching for a template to use, the renderer
     * will first consider template set for a specific group id, then the
     * group templates set by group class.
     *
     * @param string $groupClass Group class name
     * @param string $elementClass Element class name
     * @param string|NULL $template Template
     *
     * @return $this
     */
    public function setElementTemplateForGroupClass(string $groupClass, string $elementClass, ?string $template) : self
    {
        $this->elementTemplatesForGroupClass[strtolower($groupClass)][strtolower($elementClass)] = $template;
        return $this;
    }

    /**
     * Sets grouped elements templates using group id
     *
     * Templates set via {@link setTemplateForClass()} will not be used for
     * grouped form elements. When searching for a template to use, the renderer
     * will first consider template set for a specific group id, then the
     * group templates set by group class.
     *
     * @param string $groupId Group id
     * @param string $elementClass Element class name
     * @param string|NULL $template Template
     *
     * @return $this
     */
    public function setElementTemplateForGroupId(string $groupId, string $elementClass, ?string $template) : self
    {
        $this->elementTemplatesForGroupId[$groupId][strtolower($elementClass)] = $template;
        return $this;
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
        $this->html = array(array());
        $this->hiddenHtml = '';
        $this->errors = array();
        $this->hasRequired = false;
        $this->groupId = array();

        return $this;
    }

    /**
     * Returns generated HTML
     *
     * @return string
     */
    public function __toString()
    {
        return ($this->html[0][0] ?? '') .
            $this->hiddenHtml;
    }

    /**
     * Renders a generic element
     *
     * @param HTML_QuickForm2_Node $element Element being rendered
     */
    public function renderElement(HTML_QuickForm2_Node $element) : void
    {
        $elTpl = $this->prepareTemplate($this->findTemplate($element), $element);
        $placeholders = $this->resolvePlaceholders($element);

        $this->html[count($this->html) - 1][] = str_replace(
            array_keys($placeholders), array_values($placeholders), $elTpl
        );
    }

    protected function resolvePlaceholders(HTML_QuickForm2_Node $element) : array
    {
        return array(
            '{element}' => (string)$element,
            '{id}' => $element->getId(),
            '{comment}' => $element->getComment()
        );
    }

    /**
     * Renders a hidden element
     *
     * @param HTML_QuickForm2_Node $element Hidden element being rendered
     */
    public function renderHidden(HTML_QuickForm2_Node $element) : void
    {
        if ($this->isGroupHiddensEnabled())
        {
            $this->hiddenHtml .= $element;
        }
        else
        {
            $this->html[count($this->html) - 1][] = str_replace(
                '{element}', (string)$element, $this->findTemplate($element)
            );
        }
    }

    /**
     * Starts rendering a generic container, called before processing contained elements
     *
     * @param HTML_QuickForm2_Node $container Container being rendered
     */
    public function startContainer(HTML_QuickForm2_Node $container) : void
    {
        $this->html[] = array();
        $this->groupId[] = false;
    }

    /**
     * Finishes rendering a generic container, called after processing contained elements
     *
     * @param HTML_QuickForm2_Node $container Container being rendered
     */
    public function finishContainer(HTML_QuickForm2_Node $container) : void
    {
        array_pop($this->groupId);

        $cTpl = $this->replacePlaceholders(
            $container,
            $this->resolveContainerPlaceholders($container),
            true
        );

        $cHtml = array_pop($this->html);
        $break = BaseHTMLElement::getOption('linebreak');
        $indent = str_repeat(BaseHTMLElement::getOption('indent'), count($this->html));
        $this->html[count($this->html) - 1][] = str_replace(
            '{content}', $break . $indent . implode($break . $indent, $cHtml), $cTpl
        );
    }

    protected function resolveContainerPlaceholders(HTML_QuickForm2_Node $container) : array
    {
        return  array(
            '{attributes}' => $container->getAttributes(true),
            '{id}' => $container->getId()
        );
    }

    /**
     * Starts rendering a group, called before processing grouped elements
     *
     * @param HTML_QuickForm2_Container_Group $group Group being rendered
     */
    public function startGroup(HTML_QuickForm2_Container_Group $group) : void
    {
        $this->html[] = array();
        $this->groupId[] = $group->getId();
    }

    /**
     * Finishes rendering a group, called after processing grouped elements
     *
     * @param HTML_QuickForm2_Container_Group $group Group being rendered
     */
    public function finishGroup(HTML_QuickForm2_Container_Group $group) : void
    {
        $gTpl = str_replace(
            array('{attributes}', '{id}', '{class}', '{comment}'),
            array($group->getAttributes(true), array_pop($this->groupId),
                $group->getAttribute('class')),
            $this->prepareTemplate($this->findTemplate($group, '{content}'), $group)
        );

        $content = self::renderElementsWithSeparator($group->getSeparator(), array_pop($this->html));

        $this->html[count($this->html) - 1][] = str_replace('{content}', $content, $gTpl);
    }

    protected function replacePlaceholders(HTML_QuickForm2_Node $element, array $placeholders, bool $prepare, string $default='{content}') : string
    {
        $tpl = $this->findTemplate($element, $default);

        if($prepare) {
            $tpl = $this->prepareTemplate($tpl, $element);
        }

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $tpl
        );
    }

    protected function resolveGroupPlaceholders(HTML_QuickForm2_Container_Group $group) : array
    {
        return array(
            '{attributes}' => $group->getAttributes(true),
            '{id}' => array_pop($this->groupId),
            '{class}' => $group->getAttribute('class'),
            '{comment}' => $group->getComment()
        );
    }

    /**
     * Starts rendering a form, called before processing contained elements
     *
     * @param HTML_QuickForm2_Node $form Form being rendered
     */
    public function startForm(HTML_QuickForm2_Node $form) : void
    {
        $this->reset();
    }

    /**
     * @param HTML_QuickForm2_Node $form
     * @return array<string,string>
     */
    protected function resolveFormPlaceholders(HTML_QuickForm2_Node $form) : array
    {
        return array(
            '{attributes}' => (string)$form->getAttributes(true),
            '{hidden}' => $this->hiddenHtml,
            '{errors}' => $this->outputGroupedErrors()
        );
    }

    /**
     * Finishes rendering a form, called after processing contained elements
     *
     * @param HTML_QuickForm2_Node $form Form being rendered
     */
    public function finishForm(HTML_QuickForm2_Node $form) : void
    {
        $placeholders = $this->resolveFormPlaceholders($form);

        $formTpl = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $this->findTemplate($form, '{content}')
        );

        $this->hiddenHtml = '';

        $note = $this->getRequiredNote();

        // required note
        if (!$this->hasRequired || empty($note) || $form->toggleFrozen())
        {
            $formTpl = preg_replace('!<qf:reqnote>.*</qf:reqnote>!isU', '', $formTpl);
        }
        else
        {
            $formTpl = str_replace(
                array('<qf:reqnote>', '</qf:reqnote>', '{reqnote}'),
                array('', '', $note),
                $formTpl
            );
        }

        $break = BaseHTMLElement::getOption('linebreak');
        $script = $this->getJavascriptBuilder()->getFormJavascript($form->getId());
        $this->html[0] = array(
            str_replace('{content}', $break . implode($break, $this->html[0]), $formTpl) .
            (empty($script) ? '' : $break . $script)
        );
    }

    /**
     * Creates an error list if 'group_errors' option is true
     *
     * @return   string  HTML with a list of all validation errors
     */
    public function outputGroupedErrors()
    {
        if (empty($this->errors))
        {
            return '';
        }

        $prefix = $this->getErrorsPrefix();
        if (!empty($prefix))
        {
            $errorHtml = str_replace(
                array('<qf:message>', '</qf:message>', '{message}'),
                array('', '', $prefix),
                $this->templatesForClass['error:prefix']
            );
        }
        else
        {
            $errorHtml = preg_replace(
                '!<qf:message>.*</qf:message>!isU', '',
                $this->templatesForClass['error:prefix']
            );
        }
        $errorHtml .= implode(
            $this->templatesForClass['error:separator'],
            $this->errors
        );

        $suffix = $this->getErrorsSuffix();
        if (!empty($suffix))
        {
            $errorHtml .= str_replace(
                array('<qf:message>', '</qf:message>', '{message}'),
                array('', '', $suffix),
                $this->templatesForClass['error:suffix']
            );
        }
        else
        {
            $errorHtml .= preg_replace(
                '!<qf:message>.*</qf:message>!isU', '',
                $this->templatesForClass['error:suffix']
            );
        }
        return $errorHtml;
    }

    /**
     * Finds a proper template for the element
     *
     * Templates are scanned in a predefined order. First, if a template was
     * set for a specific element by id, it is returned, no matter if the
     * element belongs to a group. If the element does not belong to a group,
     * we try to match a template using the element class.
     * But, if the element belongs to a group, templates are first looked up
     * using the containing group id, then using the containing group class.
     * When no template is found, the provided default template is returned.
     *
     * @param HTML_QuickForm2_Node $element Element being rendered
     * @param string $default Default template to use if not found
     *
     * @return   string  Template
     */
    public function findTemplate(HTML_QuickForm2_Node $element, string $default = '{element}') : string
    {
        if (!empty($this->templatesForId[$element->getId()]))
        {
            return (string)$this->templatesForId[$element->getId()];
        }

        $class = strtolower(get_class($element));
        $groupId = end($this->groupId);
        $elementClasses = array();
        do
        {
            if (empty($groupId) && !empty($this->templatesForClass[$class]))
            {
                return $this->templatesForClass[$class];
            }
            $elementClasses[$class] = true;
        }
        while ($class = strtolower((string)get_parent_class($class)));

        if (!empty($groupId))
        {
            if (!empty($this->elementTemplatesForGroupId[$groupId]))
            {
                foreach (array_keys($elementClasses) as $elClass)
                {
                    if (!empty($this->elementTemplatesForGroupId[$groupId][$elClass]))
                    {
                        return $this->elementTemplatesForGroupId[$groupId][$elClass];
                    }
                }
            }

            $group = $element->getContainer();

            if($group !== null)
            {
                $grClass = strtolower(get_class($group));
                do
                {
                    if (!empty($this->elementTemplatesForGroupClass[$grClass]))
                    {
                        foreach (array_keys($elementClasses) as $elClass)
                        {
                            if (!empty($this->elementTemplatesForGroupClass[$grClass][$elClass]))
                            {
                                return $this->elementTemplatesForGroupClass[$grClass][$elClass];
                            }
                        }
                    }
                }
                while ($grClass = strtolower(get_parent_class($grClass)));
            }
        }

        return $default;
    }

    /**
     * Processes the element's template, adding label(s), required note and error message
     *
     * @param string $elTpl Element template
     * @param HTML_QuickForm2_Node $element Element being rendered
     *
     * @return   string  Template with some substitutions done
     */
    public function prepareTemplate($elTpl, HTML_QuickForm2_Node $element)
    {
        // if element is required
        $elTpl = $this->markRequired($elTpl, $element->isRequired());
        $elTpl = $this->outputError($elTpl, $element->getError());
        return $this->outputLabel($elTpl, $element->getLabel());
    }

    /**
     * Marks element required or removes "required" block
     *
     * @param string $elTpl Element template
     * @param bool $required Whether element is required
     *
     * @return   string  Template with processed "required" block
     */
    public function markRequired($elTpl, $required)
    {
        if ($required)
        {
            $this->hasRequired = true;
            $elTpl = str_replace(
                array('<qf:required>', '</qf:required>'), array('', ''), $elTpl
            );
        }
        else
        {
            $elTpl = preg_replace('!<qf:required>.*</qf:required>!isU', '', $elTpl);
        }
        return $elTpl;
    }

    /**
     * Outputs element error, removes empty error blocks
     *
     * @param string $elTpl Element template
     * @param string $error Validation error for the element
     *
     * @return   string  Template with error substitutions done
     */
    public function outputError($elTpl, $error)
    {
        if ($error && !$this->isGroupErrorsEnabled())
        {
            $elTpl = str_replace(
                array('<qf:error>', '</qf:error>', '{error}'),
                array('', '', $error), $elTpl
            );
        }
        else
        {
            if ($error && $this->isGroupErrorsEnabled())
            {
                $this->errors[] = $error;
            }
            $elTpl = preg_replace('!<qf:error>.*</qf:error>!isU', '', $elTpl);
        }
        return $elTpl;
    }

    /**
     * Outputs element's label(s), removes empty label blocks
     *
     * @param string $elTpl Element template
     * @param string|array $label Element label(s)
     *
     * @return   string  Template with label substitutions done
     */
    public function outputLabel($elTpl, $label) : string
    {
        $mainLabel = (string)(is_array($label) ? array_shift($label) : $label);
        $elTpl = str_replace('{label}', $mainLabel, $elTpl);
        if (false !== strpos($elTpl, '<qf:label>'))
        {
            if ($mainLabel)
            {
                $elTpl = str_replace(array('<qf:label>', '</qf:label>'), array('', ''), $elTpl);
            }
            else
            {
                $elTpl = preg_replace('!<qf:label>.*</qf:label>!isU', '', $elTpl);
            }
        }
        if (is_array($label))
        {
            foreach ($label as $key => $text)
            {
                $key = is_int($key) ? $key + 2 : $key;
                $elTpl = str_replace(
                    array('<qf:label_' . $key . '>', '</qf:label_' . $key . '>', '{label_' . $key . '}'),
                    array('', '', $text), $elTpl
                );
            }
        }
        if (strpos($elTpl, '{label_'))
        {
            $elTpl = preg_replace('!<qf:label_([^>]+)>.*</qf:label_\1>!isU', '', $elTpl);
        }
        return $elTpl;
    }
}
