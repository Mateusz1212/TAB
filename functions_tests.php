<?php

declare(strict_types=1);

function sanitizeString($inputString): string
{
    if ($inputString === null) return '';

    $pattern = '/[^0-9a-zA-Z!@#$%^&*()\[\]{},.\-+_ ĄąŻżÓóŁłĆćŃńŹźŚśĘę=?:\"\'<>\/]/u';

    return preg_replace($pattern, '', (string)$inputString) ?? '';
}

function validateAndFormatGrade(?string $gradeString): string
{
    if (empty($gradeString)) return '';

    $n = strtolower(trim($gradeString));

    if ($n === 'zw') return 'zw';
    if ($n === 'nb') return 'nb';

    $f = str_replace(',', '.', $n);

    if (is_numeric($f)) {
        $v = max(0.0, min(5.0, (float)$f));

        return number_format($v, 2, '.', '');
    }

    return '0.00';
}

/**
 * Sprawdza, czy wynik funkcji jest identyczny z oczekiwanym wynikiem.
 */
function assertSameValue(
    string $testName,
    mixed $expected,
    mixed $actual
): bool {
    if ($expected === $actual) {
        echo "[OK]   {$testName}" . PHP_EOL;

        return true;
    }

    echo "[BŁĄD] {$testName}" . PHP_EOL;
    echo "       Oczekiwano: " . var_export($expected, true) . PHP_EOL;
    echo "       Otrzymano:  " . var_export($actual, true) . PHP_EOL;

    return false;
}

$testsCount = 0;
$passedTests = 0;

/*
 * Testy funkcji sanitizeString().
 */

$sanitizeTests = [
    [
        'name' => 'Wartość null',
        'input' => null,
        'expected' => '',
    ],
    [
        'name' => 'Zwykły tekst i cyfry',
        'input' => 'Abc 123',
        'expected' => 'Abc 123',
    ],
    [
        'name' => 'Dozwolone znaki specjalne',
        'input' => '!@#$%^&*()[]{},.-+_=?:\"\'<>/',
        'expected' => '!@#$%^&*()[]{},.-+_=?:\"\'<>/',
    ],
    [
        'name' => 'Polskie znaki',
        'input' => 'Zażółć gęślą jaźń',
        'expected' => 'Zażółć gęślą jaźń',
    ],
    [
        'name' => 'Usunięcie znaków nowej linii i tabulatorów',
        'input' => "tekst\nz\tbiałymi znakami",
        'expected' => 'tekstzbiałymi znakami',
    ],
    [
        'name' => 'Usunięcie emoji i symbolu euro',
        'input' => 'abc😀def€ghi',
        'expected' => 'abcdefghi',
    ],
    [
        'name' => 'Usunięcie średnika',
        'input' => "<script>alert('x');</script>",
        'expected' => "<script>alert('x')</script>",
    ],
    [
        'name' => 'Usunięcie niedozwolonych symboli',
        'input' => 'a\\b|c;d`e~f',
        'expected' => 'abcdef',
    ],
    [
        'name' => 'Liczba całkowita jako argument',
        'input' => 12345,
        'expected' => '12345',
    ],
    [
        'name' => 'Wartość logiczna true',
        'input' => true,
        'expected' => '1',
    ],
    [
        'name' => 'Wartość logiczna false',
        'input' => false,
        'expected' => '',
    ],
];

echo "TESTY sanitizeString()" . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

foreach ($sanitizeTests as $test) {
    $testsCount++;

    $result = sanitizeString($test['input']);

    if (assertSameValue(
        $test['name'],
        $test['expected'],
        $result
    )) {
        $passedTests++;
    }
}

echo PHP_EOL;

/*
 * Testy funkcji validateAndFormatGrade().
 */

$gradeTests = [
    [
        'name' => 'Wartość null',
        'input' => null,
        'expected' => '',
    ],
    [
        'name' => 'Pusty tekst',
        'input' => '',
        'expected' => '',
    ],
    [
        'name' => 'Tekst zero',
        'input' => '0',
        'expected' => '',
    ],
    [
        'name' => 'Same spacje',
        'input' => '   ',
        'expected' => '0.00',
    ],
    [
        'name' => 'Zwolnienie małymi literami',
        'input' => 'zw',
        'expected' => 'zw',
    ],
    [
        'name' => 'Zwolnienie wielkimi literami i spacjami',
        'input' => ' ZW ',
        'expected' => 'zw',
    ],
    [
        'name' => 'Nieobecność małymi literami',
        'input' => 'nb',
        'expected' => 'nb',
    ],
    [
        'name' => 'Nieobecność wielkimi literami',
        'input' => 'NB',
        'expected' => 'nb',
    ],
    [
        'name' => 'Ocena całkowita',
        'input' => '4',
        'expected' => '4.00',
    ],
    [
        'name' => 'Ocena z przecinkiem',
        'input' => '4,5',
        'expected' => '4.50',
    ],
    [
        'name' => 'Ocena z kropką',
        'input' => '3.25',
        'expected' => '3.25',
    ],
    [
        'name' => 'Zaokrąglenie do dwóch miejsc',
        'input' => '3.14159',
        'expected' => '3.14',
    ],
    [
        'name' => 'Usunięcie spacji wokół oceny',
        'input' => ' 4,25 ',
        'expected' => '4.25',
    ],
    [
        'name' => 'Ograniczenie oceny powyżej pięciu',
        'input' => '6.50',
        'expected' => '5.00',
    ],
    [
        'name' => 'Ograniczenie niewielkiego przekroczenia pięciu',
        'input' => '5.01',
        'expected' => '5.00',
    ],
    [
        'name' => 'Ograniczenie wartości ujemnej',
        'input' => '-1.50',
        'expected' => '0.00',
    ],
    [
        'name' => 'Ocena poprzedzona znakiem plus',
        'input' => '+2.5',
        'expected' => '2.50',
    ],
    [
        'name' => 'Nieprawidłowy tekst',
        'input' => 'bardzo dobry',
        'expected' => '0.00',
    ],
    [
        'name' => 'Nieprawidłowa liczba z dwoma przecinkami',
        'input' => '2,5,0',
        'expected' => '0.00',
    ],
    [
        'name' => 'Notacja wykładnicza',
        'input' => '1e2',
        'expected' => '5.00',
    ],
];

echo "TESTY validateAndFormatGrade()" . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

foreach ($gradeTests as $test) {
    $testsCount++;

    $result = validateAndFormatGrade($test['input']);

    if (assertSameValue(
        $test['name'],
        $test['expected'],
        $result
    )) {
        $passedTests++;
    }
}

$failedTests = $testsCount - $passedTests;

echo PHP_EOL;
echo str_repeat('=', 50) . PHP_EOL;
echo "Liczba testów:     {$testsCount}" . PHP_EOL;
echo "Zaliczone testy:   {$passedTests}" . PHP_EOL;
echo "Niezaliczone testy: {$failedTests}" . PHP_EOL;

if ($failedTests === 0) {
    echo "Wszystkie testy zakończyły się powodzeniem." . PHP_EOL;
    exit(0);
}

echo "Niektóre testy zakończyły się niepowodzeniem." . PHP_EOL;
exit(1);