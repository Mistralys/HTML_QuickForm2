<?php
/**
 * Usage example for HTML_QuickForm2 package: builtin rules
 *
 * The example uses all Rule classes provided with HTML_QuickForm2 and also
 * showcases rule chaining.
 */

require_once __DIR__.'/_prepend.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <style type="text/css">
/* Set up custom font and form width */
body {
    margin-left: 10px;
    font-family: Arial,sans-serif;
    font-size: small;
}

.quickform {
    min-width: 500px;
    max-width: 600px;
    width: 560px;
}

/* Use default styles included with the package */

<?php
if ('@data_dir@' !== '@' . 'data_dir@') {
    $filename = '@data_dir@/HTML_QuickForm2/quickform.css';
} else {
    $filename = dirname(dirname(__DIR__)) . '/data/quickform.css';
}
readfile($filename);
?>

.quickform .valid input { background: #F0FFF0; }
.quickform .error input { background: #FFF0F0; }

    </style>
    <script type="text/javascript">
// <![CDATA[
var validatorBackup;

//in real application the password check will a bit be different, of course
function check_password(password)
{
    return (password === 'qwerty');
}

function enableValidation(box)
{
    if (box.checked) {
        box.form.validator = validatorBackup;
    } else {
        validatorBackup = box.form.validator;
        box.form.validator = null;
    }
}

// ]]>
    </script>
    <title>HTML_QuickForm2 built-in rules example</title>
  </head>
  <body>
<?php

// in real application the password check will a bit be different, of course
function check_password($password): bool
{
    return ($password === 'qwerty');
}

//
// Form setup
//

$form = new HTML_QuickForm2('basicRules');
// for file upload to work
$form->setAttribute('enctype', 'multipart/form-data');

// data source with default values:
$form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
    'testUsername'      => 'luser',
    'friends'           => array('luser123', 'gangsta1998')
)));

// override whatever value was submitted
$form->addHidden('MAX_FILE_SIZE')->setValue('102400');

//
// Simple fields validation, rule chaining
//

$fsAuth = $form->addFieldset()->setLabel('Auth credentials');

$username = $fsAuth->addText('testUsername')
    ->setStyle('width: 200px;')
    ->setLabel('Username (letters only):');

$email = $fsAuth->addText('testEmail')
    ->setStyle('width: 200px')
    ->setLabel('Email:');

$fsPasswords = $fsAuth->addFieldset()->setLabel('Supply password only if you want to change it');

$oldPassword = $fsPasswords->addPassword('oldPassword')
    ->setStyle('width: 200px')
    ->setLabel('Old password (<i>qwerty</i>):');

$newPassword = $fsPasswords->addPassword('newPassword')
    ->setStyle('width: 200px;')
    ->setLabel('New password (min 6 chars):');

$repPassword = $fsPasswords->addPassword('newPasswordRepeat')
    ->setStyle('width: 200px')
    ->setLabel('Repeat new password:');

$username->addRule('required', 'Username is required', null,
                   HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

$username->addRule('regex', 'Username should contain only letters', '/^[a-zA-Z]+$/',
                   HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

$email->addRule('email', 'Email address is invalid', null,
                HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

// old password should be either left blank or be equal to 'qwerty'
$oldPassword->addRule('empty', '', null, HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER)
            ->or_($oldPassword->createRule('callback', 'Wrong password', 'check_password'));

// this behaves exactly as it reads: either "password" and "password repeat"
// are both empty or they should be equal
$newPassword->addRule('empty', '', null, HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER)
            ->and_($repPassword->createRule('empty'))
            ->or_($repPassword->createRule('eq', 'The passwords do not match', $newPassword));

// Either new password is not given, or old password is required
$newPassword->addRule('empty', '', null, HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER)
            ->or_($oldPassword->createRule('nonempty', 'Supply old password if you want to change it'));

$newPassword->addRule('minlength', 'The password is too short', 6, HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

// No sense changing the password to the same value
$newPassword->addRule('nonempty', '', null, HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER)
            ->and_($newPassword->createRule('neq', 'New password is the same as the old one', $oldPassword));

//
// Grouped elements validation
//

$fsGrouped = $form->addFieldset()->setLabel('Validating grouped elements');
$boxGroup = $fsGrouped->addGroup('boxes')->setLabel('Check at least two:');

$boxGroup->addCheckbox()
    ->setValue('red')
    ->setContent('<span style="color: #f00;">Red</span>');

$boxGroup->addCheckbox()
    ->setValue('green')
    ->setContent('<span style="color: #0f0;">Green</span>');

$boxGroup->addCheckbox()
    ->setValue('blue')
    ->setContent('<span style="color: #00f;">Blue</span>');

$boxGroup->addRule('required', 'Check at least two boxes', 2,
                   HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

$friends = $fsGrouped->addGroup( 'friends')
    ->setLabel('Friends usernames (letters only):')
    ->setSeparator('<br />');

$friends->addText('0')
    ->setStyle('width: 200px')
    ->setId('friend-1');

$friends->addText('1')
    ->setStyle('width: 200px')
    ->setId('friend-2');

$friends->addText('2')
    ->setStyle('width: 200px')
    ->setId('friend-3');

$friends->addRule('each', 'Friends\' usernames should contain only letters',
                  $friends->createRule('regex', '', '/^[a-zA-Z]+$/'),
                  HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

//
// File uploads validation
//

$fsUpload = $form->addFieldset()->setLabel('Upload picture (try one &gt; 100 kB for fun)');

$upload = $fsUpload->addFile('testUpload', array('style' => ''))
    ->setStyle('width: 200px')
    ->setLabel('Picture (gif, jpg, png, &lt;=20kB):');

// no longer using special 'uploadedfile' rule for uploads, allow client-side validation
$upload->addRule('required', 'Please upload picture', null,
                 HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);
// no longer using 'filename' rule for uploads, allow client-side validation
$upload->addRule('regex', 'Allowed extensions: .gif, .jp(e)g, .png', '/\\.(gif|jpe?g|png)$/i',
                 HTML_QuickForm2_Rule::ONBLUR_CLIENT_SERVER);

// these don't work client-side, for obvious reasons
$upload->addRule('mimetype', 'Your browser doesn\'t think that\'s an image',
                 array('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg'));
$upload->addRule('maxfilesize', 'File is too big, allowed size 20kB', 20480);

$submitGrp = $form->addGroup()->setSeparator('&nbsp;&nbsp;&nbsp;');

$submitGrp->addSubmit('testSubmit')->setLabel('Send');
$submitGrp->addCheckbox('clientSide')
    ->setAttribute('onclick', 'enableValidation(this);')
    ->setContent('perform client-side validation')
    ->setChecked(); // override submit value

if ($form->validate()) {
    echo "<pre>\n";
    var_dump($form->getValue());
    echo "</pre>\n<hr />";
    $form->toggleFrozen(true);
}

$renderer = HTML_QuickForm2_Renderer::createDefault();
$form->render($renderer);
// Output javascript libraries, needed for client-side validation
echo $renderer->getJavascriptBuilder()->getLibraries(true, true);
echo $renderer;
?>
  </body>
</html>