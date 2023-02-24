<?php

declare(strict_types=1);

namespace HTML\QuickForm2\Interfaces\Events;

use HTML_QuickForm2;

interface FormArgInterface
{
    public const KEY_FORM = 'form';
    public const ERROR_FORM_NOT_SPECIFIED = 103501;

    public function getForm() : HTML_QuickForm2;
}
