# Changes in HTML_QuickForm2

## v2.3.6 PHP8.4 compatibility
- Core: Fixed deprecation notices for implicit nullable parameters.

## v2.3.5 Typing fix
- HTML Elements: Fixed a PHP typing error in `BaseHTMLElement`.
- HTML Elements: Improved `BaseHTMLElement::getAttributesString()`.
- HTML Elements: Now sanitizing attribute names, and ignoring non-scalar values.

## v2.3.4 PHP8 compatibility
- DataSources: Fixed a PHP8 notice in the `Array` data source.

## v2.3.3 Data Source update
- DataSources: Fixed a PHP8 notice in the `Array` data source.

## v2.3.2 Data Source update
- DataSources: Added `clearDataSources()`.
- Form: Added `submitManually()` to formalize manual submission.
- Select: Fixed ignoring submitted empty values when any data source has a value.

## v2.3.1 Data Source update
- DataSources: Added methods `getValues()`, `setValue()` and `setValues()`.
- DataSources: Added `getInstanceID()` for debugging purposes.
- Form: Added `resolveDataSourceByName()` to replace the foreach loops.

## v2.3.0 Renderers update (breaking)
- Renderers: Added specialized proxy classes for better method visibility.
- Renderers: The `register()` method now accepts a proxy class name.
- Renderers: Added setters and getters for options.
- Renderers: Added constants for option names.
- Renderers: Added factory methods for the proxy classes, e.g. `createArray()`.
- Renderers: Added a first basic Bootstrap5 renderer.
- Elements: Added specialized render methods, e.g. `renderToArray()`.
- Unit Tests: Updated to use renderer setters and getters.
- Rules: Fixed an issue with the required rule considering a string `0` as empty.
- Form: Added the static `resolveTrackVarName()`.
- Form: Added `getTrackVarName()`.

### Breaking changes

- The renderer method signatures have changed. If you have any custom
  renderers, they will have to be adjusted. It is mostly a matter of
  adding method type hints.

## v2.2.1
- Factory: Added the `ElementFactory` class to ease element creation.
- Containers: Element methods like `addText()` now have concrete implementations.
- Containers: Added `getElementByName()` to fetch a unique element.
- Containers: Added `ElementContainerInterface`.
- Base HTML Element: Added `setStyle()` and `getStyle()`.
- Button Element: Added `makeSubmit()` and `makeButton()`.
- Input Button Element: Added `setLabel()`.
- Input Reset Element: Added `setLabel()`.
- Input Submit Element: Added `setLabel()`.
- Image Element: Added `setURL()`.
- Select Element: Added `setInstrinsicValidation()`.
- Checkbox Element: Added `setChecked()`.
- Date Element: Added option setters and getters.
- Date Element: Added constants for options.
- Date Element: Better "empty" select option handling with methods.
- Date Element: Now using strict typing.
- Base Node: Added `setDataKey()` and `getDataKey()` with type flavors.
- Examples: Updated code to make use of chained methods.
- Examples: Using typed methods instead of the generic `addElement()`.

## v2.2.0 PHP8 support release (Breaking)
- Core: Dropped the `PEAR_Exception` package.
- Core: Now fully PHP8 compatible, without deprecated warnings.
- Containers: Added `requireElementById()` for a guaranteed return type.
- Exceptions: Removed the PEAR exception dependency.
- Elements: Added `addRuleRequired()` helper method.
- Elements: Added `renderToArray()` utility method.
- InputFile: Added `getAccept()` and `getAcceptMimes()` methods.
- InputFile: Added exception constants.
- Form: Added `getMethod()`.
- Form: Added `makeMultiPart()` and `isMultiPart()`.
- Rules: Added static `setDefaultMessage()` to the required rule.
- Rules: `setMessage()` now accepts more message variable types.
- Exceptions: Added error codes to some exceptions.
- Unit Tests: Ongoing modernisation.
- Unit Tests: Added the base test case class `QuickFormCase`.
- Unit Tests: Moved mock classes to individual files.
- Unit Tests: Added tests to increase code coverage for unused methods.
- Factory: Deprecated the old class loading mechanisms.
- Factory: Removed the file parameter from `registerElements()`.
- Factory: Parameter `$includeFile` in `registerRule()` is deprecated.
- Loader: Added `requireObjectInstanceOf()`.
- Loader: Added `requireClassExists()`.
- Examples: They can now be opened in the browser (via a webserver).

### Breaking changes

- The `HTML_QuickForm2_Exception` no longer extends `PEAR_Exception`, but
  simply the vanilla `Exception` class with a slightly more lenient
  constructor. Please check if you use `PEAR_Exception` instead of the
  QuickForm exception.
- Some error exception error messages have changed. If you relied on
  matching the text to detect the exact exception, please switch to
  the new exception codes.
- If you have any custom elements, they will have to be adjusted because
  method return types and property types have been added in the base
  node class, `HTML_QuickForm2_Node`.

### Deprecated methods

- `HTML_QuickForm2_Loader::autoload()`.
- `HTML_QuickForm2_Loader::loadClass()`.

## v2.1.8
- Select Element: Added `isMultiple()`.
- Select Element: Added `setSize()` and `getSize()`.
- Code Quality: Continued type hinting improvements.

## v2.1.7
- Select Element: Added `getAttribute()` to options.

## v2.1.6
- Select Element: Added `getSelectedOption()`.
- Select Element: Added `getOptionByValue()`.
- Select Element: Options are now `SelectOption` objects with array access.
- Select Element: Adding options now returns a `SelectOption` instance.
- Select Element: Added `makeMultiple()` ([#2](https://github.com/Mistralys/HTML_QuickForm2/issues/2)).
- Code Quality: PHPStan analysis now clean up to level 5.

## v2.1.5
- Elements: Added `isFrozen()`.
- Elements: Added `isFreezable()`.
- Elements: Logic of `toggleFrozen()` now handled using `isFreezable()` without overrides.
- Elements: Added `appendComment()`.
- Elements: `setComment()` now accepts a wider range of values, including `Stringable`.
- Select Element: `addOption()` now accepts numeric and `null` values.
- Composer: Removed the `HTML_Common2` dependency (code integrated for future changes).

## v2.1.4
- Fixed autoloading; Switched to a classmap instead of PSR-0.

## v2.1.3
- Select Element: Added support for selects with a custom OptGroup class.
- Select Element: Added runtime properties.
- Select Element: Added `initSelect()` to avoid overriding the constructor
  when extending the class.
- Traits: Added the `RuntimePropertiesTrait` and matching interface.
- Unit Tests: Tests now run correctly in PHPUnit versions up to 9.6.

## v2.1.2
- This release integrates all essential changesets from the main branch.
- Removed obsolete `magic_quotes_gpc()` calls.
- Date elements now accept `DateTimeInterface` values.
- Minor code quality changes and meta data updates.

## v2.1.1
- Added Travis CI build status to the readme.

## v2.1.0
- HTML_QuickForm2 runs under PHP 7.2+ without warnings / deprecated messages
- Tests run correctly on PHPUnit versions up to 5
- It is possible to automatically add `nonce` attributes to inline
   `<script>` tags, just call
   ```HTML_Common2::setOption('nonce', $someNonceValue);```
  before outputting the form. This allows running HTML_QuickForm2 with
  reasonable Content-Security-Policy
- Bundled a separate `LICENSE` file instead of having it in each .php file
  header. Updated phrasing and links to mention 3-Clause BSD license
  the package actually uses.
- When installing with composer, files no longer contain `require_once` calls
  and `'include-path'` option is not used. The package is now 100% autoloader
  compatible, all classes reside in separate files.


## v2.0.2
- [Bug #20295] was incorrectly fixed for Static elements, this led to removing
  their contents when DataSources did not contain values for them.

## v2.0.1

This is the first release installable with composer, changelogs for older versions
are available in `package.xml` file or [on PEAR website]

### Bug fixes

- When using `HTML_QuickForm2_DataSource_Array` and its descendants elements'
   `updateValue()` implementations now differentiate between "no value available
   for an element" and "explicit null value provided for an element"
   (see [bug #20295]). Custom DataSources may implement the new
   `HTML_QuickForm2_DataSource_NullAware` interface to achieve the same.
- Contents of Static elements added to Repeat are no longer cleared ([bug #19802])
- Client-side rules for containers within Repeat are correctly removed when
   removing a repeated item ([bug #19803])
- Client-side validator is always generated for a form with a Repeat having
   some client-side rules on its child elements, even if Repeat is empty
- Unit tests updated to work with newer PHPUnit, prevent running tests twice
   under some circumstances (see [bug #19038])

### Other features and changes

- Calling `HTML_QuickForm2_Container_Group::setValue()` will clear values of
   those grouped elements that do not have a corresponding key in the passed
   array. Passing a null or an empty array to `setValue()` will clear the values
   of all grouped elements. Previous behaviour was counter-intuitive.
- Added `HTML_QuickForm2_Element_Select::getOptionContainer()` ([request #19955])
- `HTML_QuickForm2_Container_Group::setValue()` properly handles a group of radio
   elements ([request #20103])
- `HTML_QuickForm2_Element_Date::setValue()` can accept an instance of DateTime
- Extracted `removeErrorMessage()` from `removeRelatedErrors()` of `qf.Validator`
   for easier customizing of client-side errors output


[bug #19038]: https://pear.php.net/bugs/bug.php?id=19038
[bug #19802]: https://pear.php.net/bugs/bug.php?id=19802
[bug #19803]: https://pear.php.net/bugs/bug.php?id=19803
[request #19955]: https://pear.php.net/bugs/bug.php?id=19955
[request #20103]: https://pear.php.net/bugs/bug.php?id=20103
[bug #20295]: https://pear.php.net/bugs/bug.php?id=20295
[on PEAR website]: https://pear.php.net/package/HTML_QuickForm2/download/All
