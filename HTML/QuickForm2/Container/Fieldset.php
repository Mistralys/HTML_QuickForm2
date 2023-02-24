<?php
/**
 * Base class for fieldsets
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
 * Concrete implementation of a container for field sets.
 *
 * @category HTML
 * @package HTML_QuickForm2
 * @subpackage Elements
 * @author Alexey Borzov <avb@php.net>
 * @author Bertrand Mansion <golgote@mamasam.com>
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 * @license  https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 */
class HTML_QuickForm2_Container_Fieldset extends HTML_QuickForm2_Container_Group
{
    public function getType()
    {
        return 'fieldset';
    }

    public function prependsName() : bool
    {
        return false;
    }

    public function getName() : ?string
    {
        return null;
    }

    public function setName(?string $name) : self
    {
        return $this;
    }

    protected function handle_nameAttributeChanged(?string $value) : void
    {
        // nothing to do here
    }

    public function isNameNullable() : bool
    {
        return true;
    }
}
