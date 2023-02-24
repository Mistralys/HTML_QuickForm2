<?php

class FormRule extends HTML_QuickForm2_Rule
{
    protected function validateOwner()
    {
        return false;
    }

    protected function setOwnerError()
    {
        $this->owner->getElementById('foo')->setError('an error message');
    }
}
