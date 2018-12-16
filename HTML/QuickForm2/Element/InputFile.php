<?php
/**
 * Class for <input type="file" /> elements
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006-2014, Alexey Borzov <avb@php.net>,
 *                          Bertrand Mansion <golgote@mamasam.com>
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
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://pear.php.net/package/HTML_QuickForm2
 */

/**
 * Base class for <input> elements
 */
require_once 'HTML/QuickForm2/Element/Input.php';

/**
 * Class for <input type="file" /> elements
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_InputFile extends HTML_QuickForm2_Element_Input
{
   /**
    * Language to display error messages in
    * @var  string
    */
    protected $language = null;

   /**
    * Information on uploaded file, from submit data source
    * @var array
    */
    protected $value = null;

    protected $attributes = array('type' => 'file');

   /**
    * Message provider for upload error messages
    * @var  callback|HTML_QuickForm2_MessageProvider
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
            HTML_QuickForm2_Loader::loadClass('HTML_QuickForm2_MessageProvider_Default');
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
    * @param bool $freeze Whether element should be frozen or editable. This
    *                     parameter is ignored in case of file uploads
    *
    * @return   bool    Always returns false
    */
    public function toggleFrozen($freeze = null)
    {
        return false;
    }

   /**
    * Returns the information on uploaded file
    *
    * @return   array|null
    */
    public function getRawValue()
    {
        return $this->value;
    }

   /**
    * Alias of getRawValue(), InputFile elements do not allow filters
    *
    * @return   array|null
    */
    public function getValue()
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
    public function setValue($value)
    {
        return $this;
    }
    
    public function preRender()
    {
        // request #16807: file uploads should not be added to forms with
        // method="get", enctype should be set to multipart/form-data.
        
        $form = $this->getForm();
        if ('get' == strtolower($form->getAttribute('method'))) {
            throw new HTML_QuickForm2_InvalidArgumentException(
                'File upload elements can only be added to forms with post submit method'
            );
        }
        
        if ($form->getAttribute('enctype') != 'multipart/form-data') {
            $form->setAttribute('enctype', 'multipart/form-data');
        }
    }

    protected function updateValue()
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
    * @return   boolean     Whether the element is valid
    */
    protected function validate()
    {
        if (strlen($this->error)) {
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

    public function addFilter($callback, array $options = array())
    {
        throw new HTML_QuickForm2_Exception(
            'InputFile elements do not support filters'
        );
    }

    public function addRecursiveFilter($callback, array $options = array())
    {
        throw new HTML_QuickForm2_Exception(
            'InputFile elements do not support filters'
        );
    }
    
   /**
    * Adds a mime type to the accept attribute.
    * @param string $mime A mime type, e.g "image/png"
    * @return HTML_QuickForm2_Element_InputFile
    */
    public function addAccept($mime)
    {
        $accepts = $this->getAttribute('accept');
        $result = array();
        if(!empty($accepts)) {
            $result = explode(',', $accepts);
        }
        
        if(!in_array($mime, $result)) {
            $result[] = $mime;
        }
        
        $this->setAttribute('accept', implode(',', $result));
        return $this;
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
    * @return HTML_QuickForm2_Element_InputFile
    */
    public function addAccepts()
    {
        $args = func_get_args();
        if(is_array($args[0])) {
            $args = args[0];
        }
        
        foreach($args as $accept) {
            $this->addAccept($accept);
        }
        
        return $this;
    }

   /**
    * Retrieves the upload instance for the uploaded file, if any.
    * Make sure to check if there is a valid uploaded file using
    * the upload's {@link HTML_QuickForm2_Element_InputFile_Upload::isValid()} 
    * method.
    *
    * If the form has not been submitted, the upload will, of course,
    * not be valid. This is meant to be used after the form has been
    * submitted and validated to make working with the result easier.
    * 
    * @return HTML_QuickForm2_Element_InputFile_Upload
    */
    public function getUpload()
    {
        require_once __DIR__.'/InputFile/Upload.php';
        
        return new HTML_QuickForm2_Element_InputFile_Upload($this, $this->getValue());
    }
}

