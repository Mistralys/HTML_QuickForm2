[![Build Status](https://travis-ci.com/Mistralys/HTML_QuickForm2.svg?branch=trunk)](https://travis-ci.com/Mistralys/HTML_QuickForm2)

# HTML_QuickForm2 - Mistralys fork

This fork focuses on quality of life improvements, as well as performance enhancements 
when working with large forms or many parallel forms. It also aims to make the package
evolve to leverage the new PHP language features.

## Composer compatible

Install via package name `mistralys/html_quickform2`.

See https://packagist.org/packages/mistralys/html_quickform2

## Additions and changes

  * Elements: `setRuntimeProperty()` and `getRuntimeProperty()` methods to store data at runtime
  * Default array datasource: `setValues()` method 
  * Textarea element: `setRows()` and `setColumns()` methods
  * New `GlobalOptions` class to handle options like language, charset, nonce, etc.
  * Replaced `HTML_Common2` with a bundled class upgraded to strict typing.
  * Elements: `makeOptional()` method to remove any required rules
  * Elements: `hasErrors()` method to check if an element has errors after validation
  * Elements: `getRules()` method to retrieve all rules added to the element
  * Elements: `hasRules()` method to check if an element has any rules 
  * Rules: The callback rule now has a method `getCallback()` to retrieve the configured callback
  * Text-based elements: `addFilterTrim()` method 
  * Select element: prependOption() method to insert an element at the top
  * Select element: Option groups: `getLabel()` method
  * Select element: `countOptions()` method with recursive capability
  * Fully reworked test suites for PHP 7.4 and PHPUnit 9

## Performance tweaks

  * Container `getElementById()` method is much faster. 
  * Element IDs are now auto-generated when not explicitly specified.

## Requirements

  * Compatible with PHP >= 7.3 

## Element ID generation

The element ID generation mechanism has been modified, so it is no longer possible
to rely on a specific naming scheme to predict the automatic element IDs. In practice,
this was impractical at best anyway, and the new system has big performance gains. 

# Documentation

See the main branch for details and documentation: https://github.com/pear/HTML_QuickForm2
