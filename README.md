[![Build Status](https://travis-ci.com/Mistralys/HTML_QuickForm2.svg?branch=trunk)](https://travis-ci.com/Mistralys/HTML_QuickForm2)

# HTML_QuickForm2 - Mistralys fork

This fork focuses on quality of life improvements, as well as performance enhancements when working 
with large forms, or many parallel forms. 

## Composer compatible

Install via package name `mistralys/html_quickform2`.

See https://packagist.org/packages/mistralys/html_quickform2

## Additions

  * Elements: set/getRuntimeProperty() method to store data at runtime
  * Default array datasource: setValues() method 
  * Textarea element: setRows() / setColumns() methods
  * Elements: makeOptional() method to remove any required rules
  * Elements: hasErrors() method to check if an element has errors after validation
  * Elements: getRules() method to retrieve all rules added to the element
  * Elements: hasRules() method to check if an element has any rules 
  * Rules: The callback rule now has a method getCallback() to retrieve the configured callback
  * Text-based elements: addFilterTrim() method 
  * Select element: prependOption() method to insert an element at the top
  * Select optgroups: getLabel() method
  * Select element and optgroups: countOptions() method with recursive capability
  * Fully reworked the testsuites so they all run correctly

## Performance tweaks

  * Container getElementById() method 
  * Auto-generated element IDs

## Requirements

  * Compatible with PHP >= 7.3 

## Element ID generation

The element ID generation mechanism has been modified, so it is no longer possible
to rely on a specific naming scheme to predict the automatic element IDs. In practice,
this was impractical at best anyway, and the new system has big performance gains. 

# Documentation

See the main branch for details and documentation: https://github.com/pear/HTML_QuickForm2
