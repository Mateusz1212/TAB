<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('read_lines')) {
    function read_lines($file)
    {
        if (!is_file($file)) {
            return [];
        }

        return file($file, FILE_IGNORE_NEW_LINES);
    }
}

require_once __DIR__ . '/login.php';

class ModelTests extends TestCase
{
    private string $tmpFile;
    private $oldSubjectAccessFile = null;

    protected function setUp(): void
    {
        global $subjectAccessFile;

        $this->oldSubjectAccessFile = $subjectAccessFile ?? null;
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'subject_access_');
        $subjectAccessFile = $this->tmpFile;
    }

    protected function tearDown(): void
    {
        global $subjectAccessFile;

        if (is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }

        $subjectAccessFile = $this->oldSubjectAccessFile;
    }

    private function writeLines(array $lines): void
    {
        file_put_contents(
            $this->tmpFile,
            implode(PHP_EOL, $lines)
        );
    }

    /**
    * Test zgodnoœci typów parametrów.
    * Identyfikator w³aœciciela przekazany jako string lub int powinien zostaæ poprawnie rozpoznany i zwróciæ pe³ne uprawnienia.
    */
    public function testOwnerGetsFullPermissionsWithDifferentTypes(): void
    {
        $expected = [
            'manage_exercises'       => true,
            'manage_exercises_scope' => 'all',
            'manage_sections'        => true,
            'grading_scope'          => 'all',
            'grading_own'            => true,
            'grading_all'            => true,
            'exemptions'             => true,
            'final_grades'           => true,
            'announcements'          => true,
        ];

        $this->assertEquals(
            $expected,
            get_subject_permissions(10, '5', 5)
        );

        $this->assertEquals(
            $expected,
            get_subject_permissions(10, 5, '5')
        );
    }

    /**
    * Je¿eli wpis istnieje, ale nie zawiera czêœci z JSON-em,
    * funkcja powinna zwróciæ domyœlny zestaw odmowy uprawnieñ.
    */
    public function testMissingJsonReturnsDefaultDeniedPermissions(): void
    {
        $this->writeLines([
            '10;20;ignored'
        ]);

        $expected = [
            'manage_exercises'       => false,
            'manage_exercises_scope' => 'none',
            'manage_sections'        => false,
            'grading_scope'          => 'own',
            'grading_own'            => false,
            'grading_all'            => false,
            'exemptions'             => false,
            'final_grades'           => false,
            'announcements'          => false,
        ];

        $this->assertEquals(
            $expected,
            get_subject_permissions(10, 20, 999)
        );
    }

    /**
    * Niepoprawny JSON powinien byæ traktowany tak samo jak jego brak,
    * czyli zwrócony powinien zostaæ domyœlny zestaw odmowy uprawnieñ.
    */
    public function testInvalidJsonReturnsDefaultDeniedPermissions(): void
    {
        $this->writeLines([
            '10;20;x;{invalid-json'
        ]);

        $expected = [
            'manage_exercises'       => false,
            'manage_exercises_scope' => 'none',
            'manage_sections'        => false,
            'grading_scope'          => 'own',
            'grading_own'            => false,
            'grading_all'            => false,
            'exemptions'             => false,
            'final_grades'           => false,
            'announcements'          => false,
        ];

        $this->assertEquals(
            $expected,
            get_subject_permissions(10, 20, 999)
        );
    }

    /**
    * Sprawdza poprawn¹ obs³ugê manage_exercises_scope='all'.
    * Oczekiwane jest w³¹czenie manage_exercises oraz exemptions.
    */
    public function testManageExercisesScopeAll(): void
    {
        $json = json_encode([
            'manage_exercises_scope' => 'all'
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $expected = [
            'manage_exercises'       => true,
            'manage_exercises_scope' => 'all',
            'manage_sections'        => false,
            'grading_scope'          => 'own',
            'grading_own'            => true,
            'grading_all'            => false,
            'exemptions'             => true,
            'final_grades'           => false,
            'announcements'          => false,
        ];

        $this->assertEquals(
            $expected,
            get_subject_permissions(10, 20, 999)
        );
    }
      
    /**
    * Sprawdza poprawn¹ obs³ugê manage_exercises_scope='none'.
    * Oczekiwane jest wy³¹czenie manage_exercises oraz exemptions.
    */
    public function testManageExercisesScopeNone(): void
    {
        $json = json_encode([
            'manage_exercises_scope' => 'none'
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $expected = [
            'manage_exercises'       => false,
            'manage_exercises_scope' => 'none',
            'manage_sections'        => false,
            'grading_scope'          => 'own',
            'grading_own'            => true,
            'grading_all'            => false,
            'exemptions'             => false,
            'final_grades'           => false,
            'announcements'          => false,
        ];

        $this->assertEquals(
            $expected,
            get_subject_permissions(10, 20, 999)
        );
    }

    /**
    * Test zgodnoœci wstecznej.
    * Stare pole manage_exercises=true powinno zostaæ zinterpretowane
    * jako manage_exercises_scope='all'.
    */
    public function testLegacyManageExercisesIsConvertedToScopeAll(): void
    {
        $json = json_encode([
            'manage_exercises' => true
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $result = get_subject_permissions(10, 20, 999);

        $this->assertEquals('all', $result['manage_exercises_scope']);
        $this->assertTrue($result['manage_exercises']);
        $this->assertTrue($result['exemptions']);
    }

    /**
    * Sprawdza interpretacjê nowych pól grading_own oraz grading_all
    * i wyliczenie odpowiadaj¹cego im grading_scope.
    */
    public function testNewGradingFields(): void
    {
        $json = json_encode([
            'grading_own' => true,
            'grading_all' => false
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $result = get_subject_permissions(10, 20, 999);

        $this->assertEquals('own', $result['grading_scope']);
        $this->assertTrue($result['grading_own']);
        $this->assertFalse($result['grading_all']);

        $json = json_encode([
            'grading_own' => true,
            'grading_all' => true
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $result = get_subject_permissions(10, 20, 999);

        $this->assertEquals('all', $result['grading_scope']);
        $this->assertTrue($result['grading_own']);
        $this->assertTrue($result['grading_all']);
    }

    /**
    * Sprawdza zgodnoœæ wsteczn¹ dla grading_scope='own'.
    * Powinno zostaæ ustawione grading_own=true i grading_all=false.
    */
    public function testLegacyGradingScopeOwn(): void
    {
        $json = json_encode([
            'grading_scope' => 'own'
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $expected = [
            'grading_scope' => 'own',
            'grading_own'   => true,
            'grading_all'   => false,
        ];

        $result = get_subject_permissions(10, 20, 999);

        $this->assertEquals($expected['grading_scope'], $result['grading_scope']);
        $this->assertEquals($expected['grading_own'], $result['grading_own']);
        $this->assertEquals($expected['grading_all'], $result['grading_all']);
    }

    /**
    * Sprawdza zgodnoœæ wsteczn¹ dla grading_scope='all'.
    * Powinny zostaæ ustawione grading_own=true oraz grading_all=true.
     */
    public function testLegacyGradingScopeAll(): void
    {
        $json = json_encode([
            'grading_scope' => 'all'
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $result = get_subject_permissions(10, 20, 999);

        $this->assertEquals('all', $result['grading_scope']);
        $this->assertTrue($result['grading_own']);
        $this->assertTrue($result['grading_all']);
    }

    /**
    * Sprawdza zgodnoœæ wsteczn¹ dla grading_scope='none'.
    * Oba pola grading_own i grading_all powinny byæ wy³¹czone.
    */
    public function testLegacyGradingScopeNone(): void
    {
        $json = json_encode([
            'grading_scope' => 'none'
        ]);

        $this->writeLines([
            "10;20;x;$json"
        ]);

        $result = get_subject_permissions(10, 20, 999);

        $this->assertEquals('none', $result['grading_scope']);
        $this->assertFalse($result['grading_own']);
        $this->assertFalse($result['grading_all']);
    }
      
    /**
    * Je¿eli w pliku nie ma wpisu pasuj¹cego do sid oraz me_id,
    * funkcja powinna zwróciæ null.
    */
    public function testReturnsNullWhenNoMatchingEntryExists(): void
    {
        $json = json_encode([
            'manage_exercises_scope' => 'all'
        ]);

        $this->writeLines([
            "999;888;x;$json"
        ]);

        $this->assertNull(
            get_subject_permissions(10, 20, 999)
        );
    }
}