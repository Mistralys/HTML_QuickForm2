<?php
/**
 * Class for <input type="file" /> elements
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

declare(strict_types=1);

/**
 * Class for <input type="file" /> elements
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_InputFile extends HTML_QuickForm2_Element_Input
{
    public const ERROR_FILES_CANNOT_USE_FILTERS = 140101;
    public const ERROR_ELEMENT_HAS_NO_FORM = 140102;

    /**
    * Language to display error messages in
    * @var  string
    */
    protected $language = null;

   /**
    * Information on uploaded file, from submit data source
    * @var array{name:string,type:string,size:int,tmp_name:string,error:int}|NULL
    */
    protected ?array $value = null;

    protected array $attributes = array('type' => 'file');

   /**
    * Message provider for upload error messages
    * @var  callable|HTML_QuickForm2_MessageProvider
    */
    protected $messageProvider;

   /**
    * Class constructor
    *
    * Possible keys in $data array are:
    *  - 'messageProvider': an instance of a class implementing
    *    HTML_QuickForm2_MessageProvider interface, this will be used to get
    *    localized error messages. Default will be used if not given.
    *  - 'language': language to display error messages in, will be passed to
    *    message provider.
    *
    * @param string       $name       Element name
    * @param string|array $attributes Attributes (either a string or an array)
    * @param array        $data       Data used to set up error messages for PHP's
    *                                 file upload errors.
    */
    public function __construct($name = null, $attributes = null, array $data = array())
    {
        if (isset($data['messageProvider'])) {
            if (!is_callable($data['messageProvider'])
                && !$data['messageProvider'] instanceof HTML_QuickForm2_MessageProvider
            ) {
                throw new HTML_QuickForm2_InvalidArgumentException(
                    "messageProvider: expecting a callback or an implementation"
                    . " of HTML_QuickForm2_MessageProvider"
                );
            }
            $this->messageProvider = $data['messageProvider'];

        } else {
            $this->messageProvider = HTML_QuickForm2_MessageProvider_Default::getInstance();
        }
        if (isset($data['language'])) {
            $this->language = $data['language'];
        }
        unset($data['messageProvider'], $data['language']);
        parent::__construct($name, $attributes, $data);
    }


   /**
    * File upload elements cannot be frozen
    *
    * To properly "freeze" a file upload element one has to store the uploaded
    * file somewhere and store the file info in session. This is way outside
    * the scope of this class.
    *
    * @return bool
    */
    public function isFreezable(): bool
    {
        return false;
    }

   /**
    * Returns the information on uploaded file
    *
    * @return array{name:string,type:string,size:int,tmp_name:string,error:int}|null
    */
    public function getRawValue() : ?array
    {
        $this->checkPrerequisites();
        
        return $this->value;
    }

   /**
    * Alias of getRawValue(), InputFile elements do not allow filters
    *
    * @return array{name:string,type:string,size:int,tmp_name:string,error:int}|null
    */
    public function getValue() : ?array
    {
        return $this->getRawValue();
    }

   /**
    * File upload's value cannot be set here
    *
    * @param mixed $value Value for file element, this parameter is ignored
    *
    * @return $this
    */
    public function setValue($value) : self
    {
        return $this;
    }
    
    public function preRender() : void
    {
        $this->checkPrerequisites();
    }

   /**
    * request #16807: file uploads should not be added to forms with
    * method="get", enctype should be set to multipart/form-data.
    * 
    * @throws HTML_QuickForm2_Exception
    * @throws HTML_QuickForm2_InvalidArgumentException
    */
    protected function checkPrerequisites() : void
    {
        $form = $this->getForm();

        if($form === null) {
            throw new HTML_QuickForm2_Exception(
                sprintf(
                    'Cannot pre-render element [%s]: it has no form.',
                    $this->getName()
                ),
                self::ERROR_ELEMENT_HAS_NO_FORM
            );
        }

        $form->makeMultiPart();
    }

    protected function updateValue() : void
    {
        $sources = $this->getDataSources();
        
        foreach ($sources as $ds) {
            if ($ds instanceof HTML_QuickForm2_DataSource_Submit) {
                $value = $ds->getUpload($this->getName());
                if (null !== $value) {
                    $this->value = $value;
                    return;
                }
            }
        }
        
        $this->value = null;
    }

   /**
    * Performs the server-side validation
    *
    * Before the Rules added to the element kick in, the element checks the
    * error code added to the $_FILES array by PHP. If the code isn't
    * UPLOAD_ERR_OK or UPLOAD_ERR_NO_FILE then a built-in error message will be
    * displayed and no further validation will take place.
    *
    * @return boolean Whether the element is valid
    */
    protected function validate() : bool
    {
        $this->checkPrerequisites();
        
        if ($this->hasErrors()) {
            return false;
        }
        if (isset($this->value['error'])
            && !in_array($this->value['error'], array(UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE))
        ) {
            $errorMessage = $this->messageProvider instanceof HTML_QuickForm2_MessageProvider
                            ? $this->messageProvider->get(array('file', $this->value['error']), $this->language)
                            : call_user_func($this->messageProvider, array('file', $this->value['error']), $this->language);
            if (UPLOAD_ERR_INI_SIZE == $this->value['error']) {
                $iniSize = ini_get('upload_max_filesize');
                $size    = intval($iniSize);
                switch (strtoupper(substr($iniSize, -1))) {
                case 'G': $size *= 1024;
                case 'M': $size *= 1024;
                case 'K': $size *= 1024;
                }

            } elseif (UPLOAD_ERR_FORM_SIZE == $this->value['error']) {
                foreach ($this->getDataSources() as $ds) {
                    if ($ds instanceof HTML_QuickForm2_DataSource_Submit) {
                        $size = intval($ds->getValue('MAX_FILE_SIZE'));
                        break;
                    }
                }
            }
            $this->error = isset($size)? sprintf($errorMessage, $size): $errorMessage;
            return false;
        }
        return parent::validate();
    }

    /**
     * NOTE: File input elements cannot use filters. This will
     * throw an exception.
     *
     * @param callable $callback
     * @param array<mixed> $options
     * @return $this
     * @throws HTML_QuickForm2_Exception {@see self::ERROR_FILES_CANNOT_USE_FILTERS}
     */
    public function addFilter(callable $callback, array $options = array()) : self
    {
        throw new HTML_QuickForm2_Exception(
            'InputFile elements do not support filters',
            self::ERROR_FILES_CANNOT_USE_FILTERS
        );
    }

    /**
     * NOTE: File inputs cannot use filters. This will throw
     * an exception.
     *
     * @param callable $callback
     * @param array<mixed> $options
     * @return $this
     * @throws HTML_QuickForm2_Exception {@see self::ERROR_FILES_CANNOT_USE_FILTERS}
     */
    public function addRecursiveFilter(callable $callback, array $options = array()) : self
    {
        throw new HTML_QuickForm2_Exception(
            'InputFile elements do not support filters',
            self::ERROR_FILES_CANNOT_USE_FILTERS
        );
    }
    
   /**
    * Adds a mime type to the accept attribute.
    * @param string $mime A mime type, e.g "image/png"
    * @return $this
    */
    public function addAccept(string $mime) : self
    {
        $accepts = $this->getAttribute('accept');
        $result = array();
        if(!empty($accepts)) {
            $result = explode(',', $accepts);
        }
        
        if(!in_array($mime, $result, true)) {
            $result[] = $mime;
        }

        sort($result);
        
        $this->setAttribute('accept', implode(',', $result));
        return $this;
    }

    /**
     * Gets the value of the <code>accept</code> attribute.
     *
     * @return string Comma-separated list of mime types.
     * @see self::getAcceptMimes()
     */
    public function getAccept() : string
    {
        return $this->getAttribute('accept');
    }

    /**
     * Like {@see self::getAccept()}, but returns the list of
     * mime types in an array.
     *
     * @return string[]
     */
    public function getAcceptMimes() : array
    {
        $accept = $this->getAccept();
        if(!empty($accept)) {
            return explode(',', $accept);
        }

        return array();
    }
    
   /**
    * Like {@link HTML_QuickForm2_Element_InputFile::addAccept()},
    * but allows setting several mime types at once. The first 
    * parameter may be an array with mime types, or you can set
    * them as separate parameters.
    * 
    * The following statements are equivalent:
    * 
    * <pre>
    * $el->addAccepts(array('image/jpeg', 'image/png'));
    * $el->addAccepts('image/jpeg', 'image/png');
    * </pre>
    * 
    * @return $this
    */
    public function addAccepts(...$accepts) : self
    {
        if(is_array($accepts[0])) {
            $accepts = $accepts[0];
        }
        
        foreach($accepts as $accept) {
            $this->addAccept($accept);
        }
        
        return $this;
    }

   /**
    * Retrieves the upload instance for the uploaded file, if any.
    * Make sure to check if there is a valid upload using the
    * upload's {@link HTML_QuickForm2_Element_InputFile_Upload::isValid()}
    * method.
    *
    * If the form has not been submitted, the upload will, of course,
    * not be valid. This is meant to be used after the form has been
    * submitted and validated to make working with the result easier.
    * 
    * @return HTML_QuickForm2_Element_InputFile_Upload
    */
    public function getUpload() : HTML_QuickForm2_Element_InputFile_Upload
    {
        return new HTML_QuickForm2_Element_InputFile_Upload($this, $this->getValue());
    }
}
