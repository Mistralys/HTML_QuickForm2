<?php
/**
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */

declare(strict_types=1);

namespace TestAssets;

use HTML_QuickForm2_Element;

/**
 * A non-abstract subclass of Element
 *
 * Element class is still abstract, we should "implement" the remaining methods.
 * Note the default implementation of setValue() / getValue(), needed to test
 * setting the value from Data Source
 *
 * @package HTML_QuickForm2
 * @subpackage UnitTests
 */
class MockElement extends HTML_QuickForm2_Element
{
    /**
     * @var mixed|null
     */
    protected $value = null;

    public function getType()
    {
        return 'concrete';
    }

    public function __toString()
    {
        return '';
    }

    public function getRawValue()
    {
        return $this->value;
    }

    public function setValue($value) : self
    {
        $this->value = $value;
        return $this;
    }
}
