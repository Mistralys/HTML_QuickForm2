<?php

/**
 * A non-abstract subclass of Element
 *
 * Element class is still abstract, we should "implement" the remaining methods.
 * Note the default implementation of setValue() / getValue(), needed to test
 * setting the value from Data Source
 */
class HTML_QuickForm2_ElementImpl extends HTML_QuickForm2_Element
{
    protected $value = null;

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
