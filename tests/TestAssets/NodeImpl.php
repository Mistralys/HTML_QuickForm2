<?php

/**
 * A non-abstract subclass of Node
 *
 * We can't instantiate the class directly and thus need to "implement" its
 * abstract methods. And also make validate() public to be able to test.
 */
class HTML_QuickForm2_NodeImpl extends HTML_QuickForm2_Node
{
    public function getType() { return 'concrete'; }
    public function getRawValue() { return ''; }
    public function setValue($value) : self { return $this; }
    public function __toString() { return ''; }

    public function getName() : ?string { return ''; }
    public function setName(?string $name) : self { return $this; }

    protected function updateValue() : self { return $this; }

    public function validate() { return parent::validate(); }

    public function getJavascriptValue($inContainer = false) { return ''; }
    public function getJavascriptTriggers() { return array(); }

    public function render(HTML_QuickForm2_Renderer $renderer) { }
}
