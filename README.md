TYPO3 CMS Data Processors
=========================

Additional TypoScript data processors for your TYPO3 installation.

You already know how to use Composer packages. This one is called `namelesscoder/typo3-cms-data-processors`.


Structured Variables Data Processor
-----------------------------------

Intended to fill the gap between FLUIDTEMPLATE data processors and variables, which allow only a single
level of variables, to facilitate creation and overriding of structured variables.

Allows you to do two important things you normally cannot:

1. Other data processors which support the `as` argument gain support for using dotted paths as
   target variable name, for example, the menu data processor can put the menu items array into a
   nested array in Fluid.
2. TypoScript objects can also be rendered and assigned as template variables using dotted path
   variable names.

In addition the data processor allows overwriting variables in already assigned arrays such as the
settings array - even overriding model object instance properties is possible if you have previously
assigned a domain model instance as a template variable.

#### Examples:

```
# First, a standard data processor - except with a dotted-name variable in "as"
page.10.dataProcessing.150 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
page.10.dataProcessing.150 {
     levels = 4
     as = foo.bar.menu
     expandAll = 1
     titleField = nav_title // title
}

# The StructuredVariablesProcessor must always come last...
page.10.dataProcessing.5000 = NamelessCoder\DataProcessors\StructuredVariablesProcessor

# ...and it can define any number of TS objects as first-level children
page.10.dataProcessing.5000.searchText = TEXT
page.10.dataProcessing.5000.searchText.value = Test...
# ...which now also supports (actually, requires) an "as" attribute
page.10.dataProcessing.5000.searchText.as = foo.bar.text
# ...that then creates that variable and has it re-mapped by the structured variables processor.
```

Resulting array structure that becomes a Fluid variable:

```php
[
     "foo" => [
             "bar" => [
                     "menu" => [...array of menu items...],
                     "text" => "Test..."
             ]
     ]
]
```

Which means you can reference the two new variables as:

     {foo.bar.menu}
     {foo.bar.text}


Credits
-------

This package contains work sponsored by [SYZYGY GmbH](https://www.syzygy.net/global/en). If you work with 
TYPO3 then [SYZYGY may be looking for someone just like you!](https://www.syzygy.net/global/en/careers)!
