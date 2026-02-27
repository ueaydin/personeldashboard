<?php
/**
 * Unit Tests for Utility Functions in config.php
 */

// Define constants to avoid session errors in config.php if possible
// Or just let it happen but suppress warnings for the test
error_reporting(E_ALL & ~E_WARNING);

require_once __DIR__ . '/../config.php';

function testSanitizeInput() {
    echo "Testing sanitizeInput...\n";

    // Test basic string
    $input = "  Hello 'World' <b>Test</b>  ";
    // trim: "Hello 'World' <b>Test</b>"
    // strip_tags: "Hello 'World' Test"
    // htmlspecialchars(ENT_QUOTES): "Hello &#039;World&#039; Test"
    $expected = "Hello &#039;World&#039; Test";
    $result = sanitizeInput($input);

    if ($result === $expected) {
        echo "PASS: sanitizeInput handles basic string and tags\n";
    } else {
        echo "FAIL: sanitizeInput basic string. Expected: $expected, Got: $result\n";
    }

    // Test array
    $arrayInput = ["<b>text</b>", ["'quote'"]];
    $expectedArray = ["text", ["&#039;quote&#039;"]];
    $resultArray = sanitizeInput($arrayInput);
    if ($resultArray === $expectedArray) {
        echo "PASS: sanitizeInput handles nested arrays\n";
    } else {
        echo "FAIL: sanitizeInput handles nested arrays\n";
    }
}

function testFormatDate() {
    echo "\nTesting formatDate...\n";
    $date = "2024-05-20";
    $expected = "20.05.2024";
    $result = formatDate($date);

    if ($result === $expected) {
        echo "PASS: formatDate default format\n";
    } else {
        echo "FAIL: formatDate default format. Expected: $expected, Got: $result\n";
    }

    $expectedCustom = "20/05/2024";
    $resultCustom = formatDate($date, "d/m/Y");
    if ($resultCustom === $expectedCustom) {
        echo "PASS: formatDate custom format\n";
    } else {
        echo "FAIL: formatDate custom format\n";
    }
}

function testFormatMoney() {
    echo "\nTesting formatMoney...\n";
    $amount = 1234.56;

    // TRY
    $resultTry = formatMoney($amount, 'TRY');
    // Note: number_format with ',' and '.' depends on locale but here it's hardcoded in config.php
    // symbol . number_format($amount, 2, ',', '.')
    $expectedTry = "₺1.234,56";
    if ($resultTry === $expectedTry) {
        echo "PASS: formatMoney TRY\n";
    } else {
        echo "FAIL: formatMoney TRY. Expected: $expectedTry, Got: $resultTry\n";
    }

    // USD
    $resultUsd = formatMoney($amount, 'USD');
    $expectedUsd = "$1.234,56";
    if ($resultUsd === $expectedUsd) {
        echo "PASS: formatMoney USD\n";
    } else {
        echo "FAIL: formatMoney USD. Expected: $expectedUsd, Got: $resultUsd\n";
    }
}

// Run tests
testSanitizeInput();
testFormatDate();
testFormatMoney();
