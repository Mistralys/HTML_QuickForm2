<?php
/**
 * Class for <input type="image" /> elements
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
 * Class for <input type="image" /> elements
 *
 * @category HTML
 * @package  HTML_QuickForm2
 * @author   Alexey Borzov <avb@php.net>
 * @author   Bertrand Mansion <golgote@mamasam.com>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/HTML_QuickForm2
 */
class HTML_QuickForm2_Element_InputImage extends HTML_QuickForm2_Element_Input
{
    protected array $attributes = array('type' => 'image');

   /**
    * Coordinates of user click within the image, array contains keys 'x' and 'y'
    * @var  array|NULL
    */
    protected ?array $coordinates = null;

    public function isFreezable(): bool
    {
        return false;
    }

   /**
    * Image button's value cannot be set via this method
    *
    * @param mixed $value Element's value, this parameter is ignored
    *
    * @return $this
    */
    public function setValue($value) : self
    {
        return $this;
    }

   /**
    * Returns the element's value
    *
    * The value is only returned if the form was actually submitted and this
    * image button was clicked. Returns null in all other cases.
    *
    * @return   array|null  An array with keys 'x' and 'y' containing the
    *                       coordinates of user click if the image was clicked,
    *                       null otherwise
    */
    public function getRawValue()
    {
        return $this->getAttribute('disabled')? null: $this->coordinates;
    }

   /**
    * Returns the HTML representation of the element
    *
    * The method changes the element's name to foo[bar][] if it was foo[bar]
    * originally. If it is not done, then one of the click coordinates will be
    * lost, see {@link http://bugs.php.net/bug.php?id=745}
    *
    * @return   string
    */
    public function __toString()
    {
        if (false === strpos($this->attributes['name'], '[')
            || '[]' === substr($this->attributes['name'], -2)
        ) {
            return parent::__toString();
        }

        $this->attributes['name'] .= '[]';
        $html = parent::__toString();
        $this->attributes['name']  = substr($this->attributes['name'], 0, -2);

        return $html;
    }

    protected function updateValue() : void
    {
        foreach ($this->getDataSources() as $ds) {
            if ($ds instanceof HTML_QuickForm2_DataSource_Submit) {
                $name = $this->getName();
                if (false === strpos($name, '[')
                    && null !== ($value = $ds->getValue($name . '_x'))
                ) {
                    $this->coordinates = array(
                        'x' => $value,
                        'y' => $ds->getValue($name . '_y')
                    );
                    return;

                } elseif (false !== strpos($name, '[')) {
                    if ('[]' === substr($name, -2)) {
                        $name = substr($name, 0, -2);
                    }
                    if (null !== ($value = $ds->getValue($name))) {
                        $this->coordinates = array(
                            'x' => $value[0],
                            'y' => $value[1]
                        );
                        return;
                    }
                }
            }
        }
        $this->coordinates = null;
    }

    public function setURL(string $url) : self
    {
        return $this->setAttribute('src', $url);
    }
}
