<?php

/**
 * This script transforms a PHPUnit JUnit XML report by:
 * 1. Moving attributes from the first all-wrapping <testsuite> to the <testsuites> element.
 * 2. Flattening the third level of <testsuite> elements by moving their <testcase> elements up one level.
 * 3. Renaming the "class" attribute to "classname" on all <testcase> elements.
 *
 * The script accepts two mandatory parameters:
 * - input file path (relative or absolute)
 * - output file path (relative or absolute)
 */

if ($argc < 3) {
    echo "Usage: php {$argv[0]} <input-file-path> <output-file-path>\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

if (!file_exists($inputFile)) {
    echo "Error: Input file '$inputFile' not found.\n";
    exit(1);
}

$dom = new DOMDocument;
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;

if (!$dom->load($inputFile)) {
    echo "Error: Failed to load XML file.\n";
    exit(1);
}

// Get the root <testsuites> element.
$testsuitesElem = $dom->documentElement;

// ===== STEP 1: Remove the outer wrapping <testsuite> =====
// Assumes that <testsuites> has one child <testsuite> that wraps the rest.
$outerSuite = null;
foreach ($testsuitesElem->childNodes as $child) {
    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'testsuite') {
        $outerSuite = $child;
        break;
    }
}

if ($outerSuite) {
    // Copy attributes from outerSuite to testsuites element.
    foreach ($outerSuite->attributes as $attr) {
        $testsuitesElem->setAttribute($attr->nodeName, $attr->nodeValue);
    }
    // Move all children (the inner testsuites) up to <testsuites>.
    while ($outerSuite->hasChildNodes()) {
        $testsuitesElem->appendChild($outerSuite->firstChild);
    }
    // Remove the outerSuite element.
    $testsuitesElem->removeChild($outerSuite);
}

// ===== STEP 2: Flatten nested <testsuite> wrappers =====
/**
 * Recursively flattens nested <testsuite> elements.
 *
 * For each <testsuite> element, if it has child <testsuite> elements,
 * move their <testcase> children into the current element and remove the wrapper.
 */
function flattenTestSuite(DOMElement $suite)
{
    // Collect direct child <testsuite> elements.
    $childSuites = [];
    foreach ($suite->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'testsuite') {
            $childSuites[] = $child;
        }
    }
    // Process each nested testsuite.
    foreach ($childSuites as $childSuite) {
        // Collect all <testcase> children from the nested testsuite.
        $testcases = [];
        foreach ($childSuite->childNodes as $grandChild) {
            if ($grandChild->nodeType === XML_ELEMENT_NODE && $grandChild->nodeName === 'testcase') {
                $testcases[] = $grandChild;
            }
        }
        // Move each <testcase> to the current suite.
        foreach ($testcases as $testcase) {
            $suite->appendChild($testcase);
        }
        // Remove the now-empty nested testsuite.
        $suite->removeChild($childSuite);
    }
    // Recursively process remaining <testsuite> elements.
    foreach ($suite->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'testsuite') {
            flattenTestSuite($child);
        }
    }
}

// Apply flattening to each direct <testsuite> child of <testsuites>.
foreach ($testsuitesElem->childNodes as $child) {
    if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'testsuite') {
        flattenTestSuite($child);
    }
}

// ===== STEP 3: Rename "class" attribute to "classname" in all <testcase> elements =====
$xpath = new DOMXPath($dom);
$testcaseNodes = $xpath->query('//testcase');

foreach ($testcaseNodes as $testcase) {
    if ($testcase->hasAttribute('class')) {
        $testcase->setAttribute('classname', $testcase->getAttribute('class'));
        $testcase->removeAttribute('class');
    }
}

// Save the transformed XML.
if ($dom->save($outputFile)) {
    echo "Transformed XML saved to: $outputFile\n";
} else {
    echo "Error: Failed to save XML to '$outputFile'\n";
}