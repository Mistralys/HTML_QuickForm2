<?php
/**
 * Class representing a page of a multipage form
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

/**
 * Class representing a page of a multipage form
 *
 * Unlike old HTML_QuickForm_Controller, this does not extend HTML_QuickForm2
 * but accepts an instance of that in the constructor. You need to create a
 * subclass of this class and implement its populateForm() method.
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
abstract class HTML_QuickForm2_Controller_Page
{
   /**
    * Button name template (needs form ID and action name substituted by sprintf())
    */
    public const KEY_NAME = '_qf_%s_%s';

   /**
     * Whether populateForm() was already called
     */
    private bool $_formPopulated = false;

   /**
    * The form wrapped by this page
    * @var  HTML_QuickForm2
    */
    protected HTML_QuickForm2 $form;

   /**
    * Controller this page belongs to
    * @var  HTML_QuickForm2_Controller|NULL
    */
    protected ?HTML_QuickForm2_Controller $controller = null;

   /**
    * Contains the mapping of action names to handlers (objects implementing HTML_QuickForm2_Controller_Action)
    * @var  array
    */
    protected $handlers = array();

   /**
    * Class constructor, accepts the form to wrap around
    *
    * @param HTML_QuickForm2 $form
    */
    public function __construct(HTML_QuickForm2 $form)
    {
        $this->form = $form;
    }

   /**
    * Returns the form this page wraps around
    *
    * @return   HTML_QuickForm2
    */
    public function getForm() : HTML_QuickForm2
    {
        return $this->form;
    }

   /**
    * Sets the controller owning the page
    *
    * @param HTML_QuickForm2_Controller $controller controller the page belongs to
    */
    public function setController(HTML_QuickForm2_Controller $controller): void
    {
        $this->controller = $controller;
    }

   /**
    * Returns the controller owning this page
    *
    * @return   HTML_QuickForm2_Controller|NULL
    */
    public function getController() : ?HTML_QuickForm2_Controller
    {
        return $this->controller;
    }

   /**
    * Adds a handler for a specific action
    *
    * @param string                            $actionName action name
    * @param HTML_QuickForm2_Controller_Action $action     the handler for the action
    */
    public function addHandler($actionName, HTML_QuickForm2_Controller_Action $action): void
    {
        $this->handlers[$actionName] = $action;
    }

   /**
    * Handles an action
    *
    * If the page does not contain a handler for this action, controller's
    * handle() method will be called.
    *
    * @param string $actionName Name of the action
    *
    * @return   mixed Return value of action handler
    * @throws   HTML_QuickForm2_NotFoundException   if handler for an action is missing
    */
    public function handle($actionName)
    {
        if (isset($this->handlers[$actionName])) {
            return $this->handlers[$actionName]->perform($this, $actionName);
        } else {
            return $this->getController()->handle($this, $actionName);
        }
    }

   /**
    * Returns a name for a submit button that will invoke a specific action
    *
    * @param string $actionName Name of the action
    *
    * @return string  "name" attribute for a submit button
    */
    public function getButtonName($actionName)
    {
        return sprintf(self::KEY_NAME, $this->getForm()->getId(), $actionName);
    }

   /**
    * Sets the default action invoked on page-form submit
    *
    * This is necessary as the user may just press Enter instead of
    * clicking one of the named submit buttons and then no action name will
    * be passed to the script.
    *
    * @param string $actionName Default action name
    * @param string $imageSrc   Path to a 1x1 transparent GIF image
    *
    * @return HTML_QuickForm2_Controller_DefaultAction Returns the image input used for default action
    */
    public function setDefaultAction(
        string $actionName,
        string $imageSrc = 'data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='
    ) : HTML_QuickForm2_Controller_DefaultAction
    {
        $image = $this->form->getElementById('qf:default-action');

        if ($image instanceof HTML_QuickForm2_Controller_DefaultAction)
        {
            $image->setName($this->getButtonName($actionName))
                ->setAttribute('src', $imageSrc);
        }
        // Inject the element to the first position to improve chances that
        // it ends up on top in the output
        elseif (count($this->form) === 0)
        {
            $image = new HTML_QuickForm2_Controller_DefaultAction(
                $this->getButtonName($actionName), array('src' => $imageSrc)
            );

            $this->form->appendChild($image);
        }
        // replace the existing DefaultAction
        else
        {
            $it = $this->form->getIterator();
            $it->rewind();

            $image = new HTML_QuickForm2_Controller_DefaultAction(
                $this->getButtonName($actionName), array('src' => $imageSrc)
            );

            $this->form->insertBefore($image, $it->current());
        }

        return $image;
    }

   /**
    * Wrapper around populateForm() ensuring that it is only called once
    */
    final public function populateFormOnce(): void
    {
        if (!$this->_formPopulated) {
            if (!empty($this->controller) && $this->controller->propagateId()) {
                $this->form->addElement(
                    'hidden', HTML_QuickForm2_Controller::KEY_ID,
                    array('id' => 'qf:controller-id')
                )->setValue($this->controller->getId());
            }
            $this->populateForm();
            $this->_formPopulated = true;
        }
    }

   /**
    * Populates the form with the elements
    *
    * The implementation of this method in your subclass of
    * HTML_QuickForm2_Controller_Page should contain all the necessary
    * addElement(), addRule() etc. calls. The method will only be called if
    * needed to prevent wasting resources on the forms that aren't going to
    * be seen by the user.
    */
    abstract protected function populateForm();

   /**
    * Stores the form values (and validation status) is session container
    *
    * @param bool $validate Whether to store validation status
    *
    * @return   bool    Validation status for the page
    */
    public function storeValues($validate = true)
    {
        $this->populateFormOnce();
        $container = $this->getController()->getSessionContainer();
        $id        = $this->form->getId();

        $container->storeValues($id, (array)$this->form->getValue());
        if ($validate) {
            $container->storeValidationStatus($id, $this->form->validate());
        }
        return $container->getValidationStatus($id);
    }
}
?>