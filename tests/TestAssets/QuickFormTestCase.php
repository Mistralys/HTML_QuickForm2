<?php

declare(strict_types=1);

namespace assets;

use HTML_QuickForm2;
use PHPUnit\Framework\TestCase;

class QuickFormTestCase extends TestCase
{
    private static int $formCounter = 0;

    protected function createSubmittedForm(string $id='') : HTML_QuickForm2
    {
        $id = $this->generateFormID($id);

        $_REQUEST = array(HTML_QuickForm2::generateTrackingVarName($id) => 'yes');
        $_POST = array(HTML_QuickForm2::generateTrackingVarName($id) => 'yes');
        $_GET = array();
        $_FILES = array();

        $form  = new HTML_QuickForm2($id);

        $this->assertTrue($form->isSubmitted());

        return $form;
    }

    protected function createForm(string $id='') : HTML_QuickForm2
    {
        return new HTML_QuickForm2($this->generateFormID($id));
    }

    protected function generateFormID(string $id='') : string
    {
        if(!empty($id))
        {
            return $id;
        }

        self::$formCounter++;
        return 'testform'.self::$formCounter;
    }
}
