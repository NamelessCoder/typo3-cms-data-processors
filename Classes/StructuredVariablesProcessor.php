<?php
declare(strict_types=1);
namespace NamelessCoder\DataProcessors;

use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Structured Variables Processor
 *
 * Does two things which normal data processors cannot do:
 *
 * 1. Rewrites first-level variables with dots in their name to
 *    create a nested structure compatible with Fluid.
 * 2. Allows rendering TS objects as variables with dots in their
 *    name, which you cannot do by using `variables.xyz` in TS.
 *
 * The result is a significantly friendlier data structure in
 * particular for Fluid templates which do not support first-level
 * variables with dots in their names.
 *
 * Not only that, it also allows moving TS objects from `variables.`
 * into this data processor and providing each object with an `as`
 * setting which supports a dotted path.
 *
 * Examples:
 *
 * # First, a standard data processor - except with a dotted-name variable in "as"
 * page.10.dataProcessing.150 = TYPO3\CMS\Frontend\DataProcessing\MenuProcessor
 * page.10.dataProcessing.150 {
 *      levels = 4
 *      as = foo.bar.menu
 *      expandAll = 1
 *      titleField = nav_title // title
 * }
 *
 * # The StructuredVariablesProcessor must always come last...
 * page.10.dataProcessing.5000 = NamelessCoder\DataProcessors\StructuredVariablesProcessor
 *
 * # ...and it can define any number of TS objects as first-level children
 * page.10.dataProcessing.5000.searchText = TEXT
 * page.10.dataProcessing.5000.searchText.value = Test...
 * # ...which now also supports (actually, requires) an "as" attribute
 * page.10.dataProcessing.5000.searchText.as = foo.bar.text
 * # ...that then creates that variable and has it re-mapped by the structured variables processor.
 *
 * Resulting array structure that becomes a Fluid variable:
 *
 * [
 *      "foo" => [
 *              "bar" => [
 *                      "menu" => [...array of menu items...],
 *                      "text" => "Test..."
 *              ]
 *      ]
 * ]
 *
 * Which means you can reference the two new variables as:
 *
 *      {foo.bar.menu}
 *      {foo.bar.text}
 */
class StructuredVariablesProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $mapped = [];
        foreach ($processorConfiguration as $key => $subObject) {
            if (!isset($subObject['as']) || !is_array($subObject)) {
                continue;
            }
            $as = $subObject['as'];
            unset($subObject['as']);
            $processedData[$as] = $cObj->getContentObject($processorConfiguration[trim($key, '.')])->render($subObject);
        }
        foreach ($processedData as $key => $value) {
            if (strpos($key, '.') === false) {
                $mapped[$key] = $value;
            } else {
                $segments = explode('.', $key);
                $target = &$mapped;
                $propertyName = array_pop($segments);
                foreach ($segments as $segment) {
                    try {
                        if (!isset($target[$segment])) {
                            $target[$segment] = [];
                            $target = &$target[$segment];
                        } elseif (is_array($target[$segment])) {
                            $target = &$target[$segment];
                        } else {
                            // We assume that the returned value will be an object. Unfortunately, if the property is
                            // an array this will de-reference it and the actual array will not be changed. This is the
                            // limitation of path-based setters where it is not reasonable to re-write an entire array
                            // by setter method call.
                            $target = ObjectAccess::getProperty($target, $segment);
                        }
                    } catch (PropertyNotAccessibleException $error) {
                        $target[$segment] = [];
                        $target = &$target[$segment];
                    }
                }
                try {
                    ObjectAccess::setProperty($target, $propertyName, $value);
                } catch (PropertyNotAccessibleException $error) {
                    // Suppressed. Value would be NULL anyway.
                }
            }
        }
        return $mapped;
    }
}
