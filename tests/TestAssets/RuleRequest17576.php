<?php

/**
 * A Rule to check that Container Rules are called after those of contained elements
 *
 * @see https://pear.php.net/bugs/17576
 */
class RuleRequest17576 extends HTML_QuickForm2_Rule
{
    protected function validateOwner()
    {
        foreach ($this->owner as $child) {
            if ($child->getError()) {
                return false;
            }
        }
        return true;
    }
}
