<?php

/**
 * A non-abstract subclass of Element
 *
 * Element class is still abstract, we should "implement" the remaining methods.
 * We need working setValue() / getValue() to test getValue() of Container
 */
class HTML_QuickForm2_ElementImpl2 extends HTML_QuickForm2_Element
{
    protected $value;

    public function getType() { return 'concrete'; }
    public function __toString() { return ''; }

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
