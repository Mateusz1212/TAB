<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Porównuje wynik funkcji z oczekiwaną wartością.
 * Porównanie jest wykonywane operatorem ścisłym, dlatego sprawdzana
 * jest zarówno wartość, jak i jej typ.
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
    // Sprawdza, czy przekazanie wartości null powoduje zwrócenie pustego tekstu.
    [
        'name' => 'Wartość null',
        'input' => null,
        'expected' => '',
    ],

    // Sprawdza, czy zwykłe litery, cyfry oraz spacje nie są usuwane.
    [
        'name' => 'Zwykły tekst i cyfry',
        'input' => 'Abc 123',
        'expected' => 'Abc 123',
    ],

    // Sprawdza, czy wszystkie dozwolone znaki specjalne pozostają w tekście.
    [
        'name' => 'Dozwolone znaki specjalne',
        'input' => '!@#$%^&*()[]{},.-+_=?:\"\'<>/',
        'expected' => '!@#$%^&*()[]{},.-+_=?:\"\'<>/',
    ],

    // Sprawdza, czy obsługiwane polskie znaki diakrytyczne nie są usuwane.
    [
        'name' => 'Polskie znaki',
        'input' => 'Zażółć gęślą jaźń',
        'expected' => 'Zażółć gęślą jaźń',
    ],

    // Sprawdza, czy tabulator i znak nowej linii są usuwane.
    [
        'name' => 'Usunięcie znaków nowej linii i tabulatorów',
        'input' => "tekst\nz\tbiałymi znakami",
        'expected' => 'tekstzbiałymi znakami',
    ],

    // Sprawdza, czy emoji oraz symbol euro są usuwane.
    [
        'name' => 'Usunięcie emoji i symbolu euro',
        'input' => 'abc😀def€ghi',
        'expected' => 'abcdefghi',
    ],

    // Sprawdza, czy średnik zostaje usunięty, a pozostałe dozwolone znaki pozostają.
    [
        'name' => 'Usunięcie średnika',
        'input' => "<script>alert('x');</script>",
        'expected' => "<script>alert('x')</script>",
    ],

    // Sprawdza usuwanie kilku różnych niedozwolonych znaków specjalnych.
    [
        'name' => 'Usunięcie niedozwolonych symboli',
        'input' => 'a\\b|c;d`e~f',
        'expected' => 'abcdef',
    ],

    // Sprawdza, czy liczba całkowita zostaje przekonwertowana na tekst.
    [
        'name' => 'Liczba całkowita jako argument',
        'input' => 12345,
        'expected' => '12345',
    ],

    // Sprawdza, czy wartość logiczna true zostaje przekonwertowana na tekst "1".
    [
        'name' => 'Wartość logiczna true',
        'input' => true,
        'expected' => '1',
    ],

    // Sprawdza, czy wartość logiczna false zostaje przekonwertowana na pusty tekst.
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
    // Sprawdza, czy wartość null jest traktowana jako brak oceny.
    [
        'name' => 'Wartość null',
        'input' => null,
        'expected' => '',
    ],

    // Sprawdza, czy pusty ciąg znaków jest traktowany jako brak oceny.
    [
        'name' => 'Pusty tekst',
        'input' => '',
        'expected' => '',
    ],

    // Sprawdza zachowanie empty(), według którego tekst "0" jest traktowany jako pusty.
    [
        'name' => 'Tekst zero',
        'input' => '0',
        'expected' => '',
    ],

    // Sprawdza, czy tekst złożony wyłącznie ze spacji zwraca "0.00".
    [
        'name' => 'Same spacje',
        'input' => '   ',
        'expected' => '0.00',
    ],

    // Sprawdza rozpoznawanie wartości "zw" zapisanej małymi literami.
    [
        'name' => 'Zwolnienie małymi literami',
        'input' => 'zw',
        'expected' => 'zw',
    ],

    // Sprawdza ignorowanie wielkości liter i usuwanie spacji dla wartości "zw".
    [
        'name' => 'Zwolnienie wielkimi literami i spacjami',
        'input' => ' ZW ',
        'expected' => 'zw',
    ],

    // Sprawdza rozpoznawanie wartości "nb" zapisanej małymi literami.
    [
        'name' => 'Nieobecność małymi literami',
        'input' => 'nb',
        'expected' => 'nb',
    ],

    // Sprawdza zamianę wartości "NB" na zapis małymi literami.
    [
        'name' => 'Nieobecność wielkimi literami',
        'input' => 'NB',
        'expected' => 'nb',
    ],

    // Sprawdza formatowanie liczby całkowitej do dwóch miejsc po przecinku.
    [
        'name' => 'Ocena całkowita',
        'input' => '4',
        'expected' => '4.00',
    ],

    // Sprawdza zamianę przecinka dziesiętnego na kropkę.
    [
        'name' => 'Ocena z przecinkiem',
        'input' => '4,5',
        'expected' => '4.50',
    ],

    // Sprawdza przetwarzanie oceny zapisanej z kropką dziesiętną.
    [
        'name' => 'Ocena z kropką',
        'input' => '3.25',
        'expected' => '3.25',
    ],

    // Sprawdza zaokrąglenie wartości do dwóch miejsc po przecinku.
    [
        'name' => 'Zaokrąglenie do dwóch miejsc',
        'input' => '3.14159',
        'expected' => '3.14',
    ],

    // Sprawdza usunięcie spacji z początku i końca wartości.
    [
        'name' => 'Usunięcie spacji wokół oceny',
        'input' => ' 4,25 ',
        'expected' => '4.25',
    ],

    // Sprawdza ograniczenie wartości większej od 5 do maksymalnej oceny 5.00.
    [
        'name' => 'Ograniczenie oceny powyżej pięciu',
        'input' => '6.50',
        'expected' => '5.00',
    ],

    // Sprawdza ograniczenie niewielkiego przekroczenia maksymalnej oceny.
    [
        'name' => 'Ograniczenie niewielkiego przekroczenia pięciu',
        'input' => '5.01',
        'expected' => '5.00',
    ],

    // Sprawdza ograniczenie ujemnej oceny do minimalnej wartości 0.00.
    [
        'name' => 'Ograniczenie wartości ujemnej',
        'input' => '-1.50',
        'expected' => '0.00',
    ],

    // Sprawdza obsługę liczby poprzedzonej znakiem plus.
    [
        'name' => 'Ocena poprzedzona znakiem plus',
        'input' => '+2.5',
        'expected' => '2.50',
    ],

    // Sprawdza, czy nierozpoznany tekst powoduje zwrócenie wartości "0.00".
    [
        'name' => 'Nieprawidłowy tekst',
        'input' => 'bardzo dobry',
        'expected' => '0.00',
    ],

    // Sprawdza odrzucenie liczby zawierającej więcej niż jeden separator dziesiętny.
    [
        'name' => 'Nieprawidłowa liczba z dwoma przecinkami',
        'input' => '2,5,0',
        'expected' => '0.00',
    ],

    // Sprawdza obsługę notacji wykładniczej i ograniczenie wyniku do wartości 5.00.
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
echo "Liczba testów:      {$testsCount}" . PHP_EOL;
echo "Zaliczone testy:    {$passedTests}" . PHP_EOL;
echo "Niezaliczone testy: {$failedTests}" . PHP_EOL;

if ($failedTests === 0) {
    echo "Wszystkie testy zakończyły się powodzeniem." . PHP_EOL;
    exit(0);
}

echo "Niektóre testy zakończyły się niepowodzeniem." . PHP_EOL;
exit(1);