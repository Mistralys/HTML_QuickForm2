<?php

declare(strict_types=1);

namespace QuickFormTests\CustomClasses;

use HTML_QuickForm2_Element_Select;

class TestSelectWithCustomGroups extends HTML_QuickForm2_Element_Select
{
    protected function initSelect(): void
    {
        $this->optionContainer->setOptGroupClass(TestCustomOptGroup::class);
    }
}
