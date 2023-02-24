<?php

class HTML_QuickForm2_ContainerFilterImpl extends HTML_QuickForm2_Container
{
    public function getType() { return 'concrete'; }
    public function setValue($value) : self { return $this; }
    public function __toString() { return ''; }
    public function validate() { return parent::validate(); }

    public function isNameNullable() : bool
    {
        return true;
    }
}
