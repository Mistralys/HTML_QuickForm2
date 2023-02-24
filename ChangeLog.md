# Changes in HTML_QuickForm2

## 3.0.0 - 2022
 * Removed the dependency to `HTML_Common2`, replaced by an evolved version
   of the class upgraded to strict typing and PHP 7.4 type hinting, which
   is now bundled with the package. With this added control over the code,
   we were able to tailor it to better suit HTML QuickForm's needs.
 * Removed the tracking option of forms: The submit tracking variable
   is now always present.
 * Code quality improvements overall, thanks to PHPStan static analysis
   and modernizing some key classes.
 * PEAR compatibility removed: Installation is now Composer-only.
 * Added the `GlobalOptions` class to set global options instead of
   using the `HTML_Common2` option methods. This made it possible to 
   add canned methods like `GlobalOptions::getCharset()`, 
   `GlobalOptions::setNonce()` and more.
 * Updated unit tests to work on PHP 7.4 with PHPUnit 9.
 * Added checkbox element methods: `setChecked()` and `isChecked()`.
 * Added button element methods: `isSubmit()` and `makeSubmit()`.
 * Added disabled-related methods to elements: `isDisabled()` and `setDisabled()`.
 * Containers: Added the `getElementByName()` method.
 * Containers: Added the `onNodeAdded()` event listening method.

**Breaking changes**

- Type hinting changes require every custom form element in your projects
  to be upgraded. We recommend using PHPStan, Psalm or the like to check
  your code base.
- Forms always require the tracking variable for submits now: they do not 
  load any data from GET or POST without it being present.
- The form's `onNodeAdded()` has been renamed to `onFormNodeAdded()`, 
  including the event class, renamed to `HTML_QuickForm2_Event_FormNodeAdded`.
- Event handling methods now get the listener ID as second parameter before
  any custom listener parameters.

## 2.1.2 - 2021-01-28
 * This release integrates all essential change-sets from the main branch.
 * Removed obsolete magic_quotes_gpc() calls.
 * Date elements now accept DateTimeInterface values.
 * Minor code quality changes and meta data updates.

## 2.1.0 - 2019-04-10

 * Older changelog entries can be found in the original package.
   The two projects started diverging from v2.1.0.
   https://github.com/pear/HTML_QuickForm2/blob/trunk/ChangeLog.md#210---2019-04-10
