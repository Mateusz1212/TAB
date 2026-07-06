<?php
// ============================================================
//  functions.php – WERSJA MYSQL
//  Zastępuje całkowicie wersję plikową.
//  Wszystkie odczyty/zapisy trafiają do bazy MySQL.
// ============================================================
require_once __DIR__ . '/db.php';

// ============================================================
//  IDENTYFIKATORY „PLIKÓW" – teraz to tylko stałe symboliczne
//  używane przez read_lines() i append_line() do routowania
//  do odpowiedniej tabeli.  Login.php i compact_view.php
//  nadal deklarują global $usersFile itp. i to działa bez zmian.
// ============================================================
$dataDir              = __DIR__ . '/';
$usersFile            = '__DB_USERS__';
$subjectsFile         = '__DB_SUBJECTS__';
$enrollFile           = '__DB_ENROLL__';
$gradesFile           = '__DB_GRADES__';
$attFile              = '__DB_ATT_GENERAL__';
$exercisesFile        = '__DB_EXERCISES__';
$subjectExerciseFile  = '__DB_SUBJ_EX__';
$reportsFile          = '__DB_REPORTS__';
$reportHistoryFile    = '__DB_REP_HIST__';
$exerciseAttendanceFile = '__DB_ATT_EX__';
$deadlinesFile        = '__DB_DEADLINES__';
$subjectAccessFile    = '__DB_SUBJ_ACCESS__';
$sectionsFile         = '__DB_SECTIONS__';
$announcementsFile    = '__DB_ANNOUNCEMENTS__';
$logsFile             = '__DB_LOGS__';
$definedSectionsFile  = '__DB_DEF_SECTIONS__';
$finalGradesFile      = '__DB_FINAL_GRADES__';
$uczelnieFile         = '__DB_UCZELNIE__';
$applicationsFile     = '__DB_APPLICATIONS__';
$harmonogramFile      = '__DB_HARMONOGRAM__';

// ============================================================
//  INICJALIZACJA BAZY – dodatkowe kolumny i tabele
//  Uruchamiane raz przy każdym requeście (szybkie dzięki IF NOT EXISTS).
// ============================================================
function init_db_extras(): void {
    $pdo = db();
    // Wyciszamy błędy ALTER – kolumna może już istnieć
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    // Umożliwiamy NULL w id_przedmiotu dla ogłoszeń globalnych
    @$pdo->exec("ALTER TABLE `Ogloszenia` MODIFY COLUMN `id_przedmiotu` INT UNSIGNED NULL DEFAULT NULL");

    // Kolumna dla ocen tekstowych (zw, nb) w tabeli Oceny
    @$pdo->exec("ALTER TABLE `Oceny` ADD COLUMN `ocena_tekstowa` VARCHAR(10) NULL DEFAULT NULL");

    // Kolumna dla ocen tekstowych w tabeli Oceny_Koncowe
    @$pdo->exec("ALTER TABLE `Oceny_Koncowe` ADD COLUMN `ocena_tekstowa` VARCHAR(10) NULL DEFAULT NULL");

    // Kolejność ćwiczeń wewnątrz przedmiotu
    @$pdo->exec("ALTER TABLE `Cwiczenia` ADD COLUMN `kolejnosc` INT NOT NULL DEFAULT 0");

    // rocznik jako tekst (stary format: '2024/25')
    @$pdo->exec("ALTER TABLE `Przedmioty` MODIFY COLUMN `rocznik` VARCHAR(20) NULL DEFAULT NULL");

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabela Zapisy_Przedmiotow – zapis studenta na przedmiot (bez sekcji)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Zapisy_Przedmiotow` (
        `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_studenta`   INT UNSIGNED NOT NULL,
        `id_przedmiotu` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_zp` (`id_studenta`, `id_przedmiotu`),
        KEY `idx_student` (`id_studenta`),
        KEY `idx_przedmiot` (`id_przedmiotu`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabela Historia_Logowania (student logins) – jeśli nie istnieje
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Historia_Logowania` (
        `id`                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_uzytkownika`           INT UNSIGNED NOT NULL,
        `data_logowania`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `adres_ip`                 VARCHAR(45) NULL,
        `informacje_o_urzadzeniu`  TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_uzytkownik` (`id_uzytkownika`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabela Ogloszenia_Ukryte (przeczytane ogłoszenia)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Ogloszenia_Ukryte` (
        `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_studenta`    INT UNSIGNED NOT NULL,
        `id_ogloszenia`  INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_ou` (`id_studenta`, `id_ogloszenia`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Domyślne typy przedmiotów
    $pdo->exec("INSERT IGNORE INTO `Typy_Przedmiotow` (`id_typu`, `nazwa`) VALUES (1,'laboratorium'),(2,'egzamin')");

    // Domyślna uczelnia
    $pdo->exec("INSERT IGNORE INTO `Uczelnia` (`id_uczelni`, `nazwa`) VALUES (1,'Domyślna')");

    // Domyślny nauczyciel (MG) – jeśli brak w bazie
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `Uzytkownicy` WHERE `rola`='teacher'")->fetchColumn();
    if ($cnt === 0) {
        $pdo->exec("INSERT INTO `Uzytkownicy`
            (`id_uczelni`,`inicjaly`,`haslo`,`imie_i_nazwisko`,`rola`,`nr_albumu`)
            VALUES (1,'MG','ust2.51','Mateusz Grzanka','teacher',NULL)");
    }
}

init_db_extras();

// ============================================================
//  STUB – zachowuje zgodność z kodem, który sprawdza plik
// ============================================================
function ensure_file($file, $initial = ''): void { /* nie potrzebne */ }

// ============================================================
//  PRYWATNE FUNKCJE RAW – zwracają dane w starym formacie
//  (tablice stringów rozdzielonych średnikami), żeby cały
//  stary kod PHP działał bez żadnych zmian.
// ============================================================

function _raw_users(): array {
    $rows = db()->query(
        "SELECT id_uzytkownika, rola, imie_i_nazwisko, haslo,
                inicjaly, nr_albumu, id_uczelni
         FROM Uzytkownicy ORDER BY id_uzytkownika"
    )->fetchAll();
    $result = [];
    foreach ($rows as $r) {
        $parts = explode(' ', $r['imie_i_nazwisko'], 2);
        $imie    = $parts[0] ?? '';
        $nazwisko= $parts[1] ?? '';
        $extra   = ($r['rola'] === 'teacher') ? ($r['inicjaly'] ?? '') : ($r['nr_albumu'] ?? '');
        $uczelnia= $r['id_uczelni'] ?? '';
        $result[] = "{$r['rola']};{$imie};{$nazwisko};{$r['haslo']};{$r['id_uzytkownika']};{$extra};{$uczelnia}";
    }
    return $result;
}

function _raw_subjects(): array {
    $rows = db()->query(
        "SELECT p.id_przedmiotu, p.nazwa, COALESCE(p.rocznik,'') AS rocznik,
                p.id_uzytkownika,
                IF(p.czy_archiwum,'archived','active') AS status,
                COALESCE(tp.nazwa,'laboratorium') AS typ
         FROM Przedmioty p
         LEFT JOIN Typy_Przedmiotow tp ON p.id_typu = tp.id_typu
         ORDER BY p.id_przedmiotu"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_przedmiotu']};{$r['nazwa']};{$r['rocznik']};{$r['id_uzytkownika']};{$r['status']};{$r['typ']}",
        $rows
    );
}

function _raw_enroll(): array {
    $rows = db()->query(
        "SELECT id_studenta, id_przedmiotu FROM Zapisy_Przedmiotow ORDER BY id"
    )->fetchAll();
    return array_map(fn($r) => "{$r['id_studenta']};{$r['id_przedmiotu']}", $rows);
}

function _raw_grades(): array {
    $rows = db()->query(
        "SELECT o.id_oceny, o.id_studenta, o.id_przedmiotu,
                COALESCE(o.ocena_tekstowa, CAST(o.ocena AS CHAR)) AS val,
                COALESCE(o.komentarz,'') AS komentarz,
                o.data_wstawienia, COALESCE(o.id_cwiczenia,0) AS id_cwiczenia,
                COALESCE(o.terminy,'1') AS terminy,
                o.id_nauczyciela
         FROM Oceny o ORDER BY o.id_oceny"
    )->fetchAll();
    $result = [];
    foreach ($rows as $r) {
        $val = (!empty($r['val'])) ? $r['val'] : '0.00';
        $result[] = "{$r['id_oceny']};{$r['id_studenta']};{$r['id_przedmiotu']};ocena;{$val};"
                  . "{$r['komentarz']};{$r['data_wstawienia']};{$r['id_cwiczenia']};"
                  . "{$r['terminy']};{$r['id_nauczyciela']}";
    }
    return $result;
}

function _raw_exercises(): array {
    $rows = db()->query(
        "SELECT id_cwiczenia, nazwa, COALESCE(opis,'') AS opis, waga, id_uzytkownika
         FROM Cwiczenia ORDER BY kolejnosc, id_cwiczenia"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_cwiczenia']};{$r['nazwa']};{$r['opis']};{$r['waga']};{$r['id_uzytkownika']}",
        $rows
    );
}

function _raw_subject_exercises(): array {
    $rows = db()->query(
        "SELECT id_przedmiotu, id_cwiczenia FROM Cwiczenia ORDER BY id_przedmiotu, kolejnosc, id_cwiczenia"
    )->fetchAll();
    return array_map(fn($r) => "{$r['id_przedmiotu']};{$r['id_cwiczenia']}", $rows);
}

function _raw_exercise_att(): array {
    $rows = db()->query(
        "SELECT o.id_obecnosci, o.id_studenta, o.id_przedmiotu,
                COALESCE(o.id_cwiczenia,0) AS id_cwiczenia,
                o.typ, COALESCE(o.data_i_czas, o.data_wstawienia) AS data
         FROM Obecnosci o
         WHERE o.id_cwiczenia IS NOT NULL
         ORDER BY o.id_obecnosci"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_obecnosci']};{$r['id_studenta']};{$r['id_przedmiotu']};{$r['id_cwiczenia']};{$r['typ']};{$r['data']}",
        $rows
    );
}

function _raw_general_att(): array {
    $rows = db()->query(
        "SELECT o.id_obecnosci, o.id_studenta, o.id_przedmiotu, o.typ,
                COALESCE(o.data_i_czas, o.data_wstawienia) AS data
         FROM Obecnosci o
         WHERE o.id_cwiczenia IS NULL
         ORDER BY o.id_obecnosci"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_obecnosci']};{$r['id_studenta']};{$r['id_przedmiotu']};0;{$r['typ']};{$r['data']}",
        $rows
    );
}

function _raw_reports(): array {
    $rows = db()->query(
        "SELECT s.id_sprawozdania, s.id_studenta, s.id_przedmiotu, s.id_cwiczenia,
                COALESCE(h.plik_sciezka,'') AS path,
                COALESCE(h.data, s.data_dodania) AS data,
                COALESCE(h.komentarz,'') AS note,
                COALESCE(s.id_nauczyciela,0) AS id_nauczyciela
         FROM Sprawozdania s
         LEFT JOIN Historia_Sprawozdan h ON h.id_historii = (
             SELECT MAX(hh.id_historii) FROM Historia_Sprawozdan hh WHERE hh.id_sprawozdania = s.id_sprawozdania
         )
         ORDER BY s.id_sprawozdania"
    )->fetchAll();
    return array_map(fn($r) => implode(';', [
        $r['id_sprawozdania'], $r['id_studenta'], $r['id_przedmiotu'], $r['id_cwiczenia'],
        $r['path'], $r['path'], $r['data'], $r['note'], $r['id_nauczyciela']
    ]), $rows);
}

function _raw_report_history(): array {
    $rows = db()->query(
        "SELECT id_historii, id_sprawozdania, COALESCE(data,'') AS data,
                COALESCE(status,'oczekuje') AS status, COALESCE(komentarz,'') AS komentarz
         FROM Historia_Sprawozdan ORDER BY id_historii"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_historii']};{$r['id_sprawozdania']};{$r['data']};{$r['status']};{$r['komentarz']}",
        $rows
    );
}

function _raw_deadlines(): array {
    $rows = db()->query(
        "SELECT id_cwiczenia, id_przedmiotu, wymagania FROM Cwiczenia WHERE wymagania IS NOT NULL"
    )->fetchAll();
    $result = [];
    foreach ($rows as $r) {
        $w = @json_decode($r['wymagania'], true);
        if (!is_array($w)) continue;
        $sid = $r['id_przedmiotu'];
        $eid = $r['id_cwiczenia'];
        $rg  = isset($w['req_grade'])      ? (int)$w['req_grade']      : 0;
        $rr  = isset($w['req_report'])     ? (int)$w['req_report']     : 0;
        $ra  = isset($w['req_attendance']) ? (int)$w['req_attendance'] : 0;
        $t1  = $w['t1'] ?? '';
        $t2  = $w['t2'] ?? '';
        $t3  = $w['t3'] ?? '';
        $t4  = $w['t4'] ?? '';
        if ($rg || $rr || $ra || $t1 || $t2 || $t3 || $t4) {
            $result[] = "{$sid};{$eid};{$rg};{$rr};{$ra};{$t1};{$t2};{$t3};{$t4}";
        }
    }
    return $result;
}

function _raw_subject_access(): array {
    $pdo = db();
    $wspol = $pdo->query(
        "SELECT w.id_przedmiotu, w.id_nauczyciela
         FROM Wspolprowadzacy w ORDER BY w.id_wspolprowadzacego"
    )->fetchAll();
    $result = [];
    foreach ($wspol as $row) {
        $sid = $row['id_przedmiotu'];
        $tid = $row['id_nauczyciela'];
        $stmt = $pdo->prepare(
            "SELECT typ_uprawnienia FROM Uprawnienia WHERE id_przedmiotu=? AND id_nauczyciela=?"
        );
        $stmt->execute([$sid, $tid]);
        $perms_rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $perms = [
            'manage_exercises_scope' => 'none',
            'manage_sections'        => 0,
            'grading_own'            => 0,
            'grading_all'            => 0,
            'final_grades'           => 0,
            'announcements'          => 0,
        ];
        foreach ($perms_rows as $pd) {
            if (strpos($pd, ':') !== false) {
                [$k, $v] = explode(':', $pd, 2);
                $perms[trim($k)] = $v;
            }
        }
        $result[] = "{$sid};{$tid};co_teacher;" . json_encode($perms, JSON_UNESCAPED_UNICODE);
    }
    return $result;
}

function _raw_sections(): array {
    $rows = db()->query(
        "SELECT z.id_zapisu, z.id_uzytkownika, ss.id_przedmiotu, z.id_sekcji
         FROM Zapisy z
         JOIN Sekcje_Studentow ss ON z.id_sekcji = ss.id_sekcji
         ORDER BY z.id_zapisu"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_zapisu']};{$r['id_uzytkownika']};{$r['id_przedmiotu']};{$r['id_sekcji']}",
        $rows
    );
}

function _raw_defined_sections(): array {
    $rows = db()->query(
        "SELECT id_sekcji, id_przedmiotu, nazwa FROM Sekcje_Studentow ORDER BY id_sekcji"
    )->fetchAll();
    return array_map(fn($r) => "{$r['id_sekcji']};{$r['id_przedmiotu']};{$r['nazwa']}", $rows);
}

function _raw_announcements(): array {
    $rows = db()->query(
        "SELECT id_ogloszenia, tytul, COALESCE(opis,'') AS opis,
                COALESCE(data_dodania,'') AS data,
                CASE WHEN id_przedmiotu IS NOT NULL THEN CAST(id_przedmiotu AS CHAR)
                     ELSE COALESCE(widocznosc,'global') END AS target,
                id_uzytkownika
         FROM Ogloszenia ORDER BY id_ogloszenia DESC"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_ogloszenia']};{$r['tytul']};{$r['opis']};{$r['data']};{$r['target']};{$r['id_uzytkownika']}",
        $rows
    );
}

function _raw_final_grades(): array {
    $rows = db()->query(
        "SELECT id_oceny_koncowej, id_studenta, id_przedmiotu,
                COALESCE(ocena_tekstowa, ocena, '') AS val,
                COALESCE(komentarz,'') AS komentarz,
                COALESCE(data,'') AS data,
                COALESCE(id_nauczyciela,0) AS id_nauczyciela
         FROM Oceny_Koncowe ORDER BY id_oceny_koncowej"
    )->fetchAll();
    return array_map(fn($r) =>
        "{$r['id_oceny_koncowej']};{$r['id_studenta']};{$r['id_przedmiotu']};"
        . "{$r['val']};{$r['komentarz']};{$r['data']};{$r['id_nauczyciela']}",
        $rows
    );
}

function _raw_uczelnie(): array {
    $rows = db()->query("SELECT id_uczelni, nazwa FROM Uczelnia ORDER BY id_uczelni")->fetchAll();
    return array_map(fn($r) => "{$r['id_uczelni']};{$r['nazwa']}", $rows);
}

function _raw_applications(): array {
    if (!_table_exists('Podania')) return [];
    try {
        $rows = db()->query(
            "SELECT id_podania,
                    COALESCE(id_studenta,0)   AS id_studenta,
                    COALESCE(id_przedmiotu,0) AS id_przedmiotu,
                    COALESCE(id_cwiczenia,0)  AS id_cwiczenia,
                    COALESCE(id_oceny,0)      AS id_oceny,
                    COALESCE(uzasadnienie,'') AS uzasadnienie,
                    COALESCE(data,'')         AS data,
                    COALESCE(status,'oczekuje') AS status
             FROM Podania ORDER BY id_podania"
        )->fetchAll();
    } catch (PDOException $e) { return []; }
    return array_map(fn($r) =>
        "{$r['id_podania']};{$r['id_studenta']};{$r['id_przedmiotu']};{$r['id_cwiczenia']};"
        . "{$r['id_oceny']};{$r['uzasadnienie']};{$r['data']};{$r['status']}",
        $rows
    );
}

function _raw_harmonogram(): array {
    if (!_table_exists('Harmonogramy')) return [];
    try {
        $rows = db()->query(
            "SELECT h.id_harmonogramu, c.id_przedmiotu, h.id_sekcji, h.id_cwiczenia,
                    COALESCE(h.terminy,'') AS terminy
             FROM Harmonogramy h
             LEFT JOIN Cwiczenia c ON h.id_cwiczenia = c.id_cwiczenia
             ORDER BY h.id_harmonogramu"
        )->fetchAll();
    } catch (PDOException $e) { return []; }
    return array_map(fn($r) =>
        "{$r['id_harmonogramu']};{$r['id_przedmiotu']};{$r['id_sekcji']};"
        . "{$r['id_cwiczenia']};{$r['terminy']}",
        $rows
    );
}

function _raw_logs(): array {
    if (!_table_exists('Historia_Logowania')) return [];
    try {
        $rows = db()->query(
            "SELECT data_logowania, id_uzytkownika,
                    COALESCE(adres_ip,'') AS adres_ip,
                    COALESCE(informacje_o_urzadzeniu,'') AS device
             FROM Historia_Logowania ORDER BY id DESC"
        )->fetchAll();
    } catch (PDOException $e) { return []; }
    return array_map(fn($r) =>
        "{$r['data_logowania']};{$r['id_uzytkownika']};{$r['adres_ip']};{$r['device']}",
        $rows
    );
}

function _table_exists(string $table): bool {
    try {
        db()->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}


function read_lines(string $file): array {
    global $usersFile, $subjectsFile, $enrollFile, $gradesFile, $attFile,
           $exercisesFile, $subjectExerciseFile, $reportsFile, $reportHistoryFile,
           $exerciseAttendanceFile, $deadlinesFile, $subjectAccessFile, $sectionsFile,
           $announcementsFile, $logsFile, $definedSectionsFile, $finalGradesFile,
           $uczelnieFile, $applicationsFile, $harmonogramFile;

    switch ($file) {
        case $usersFile:              return _raw_users();
        case $subjectsFile:           return _raw_subjects();
        case $enrollFile:             return _raw_enroll();
        case $gradesFile:             return _raw_grades();
        case $attFile:                return _raw_general_att();
        case $exercisesFile:          return _raw_exercises();
        case $subjectExerciseFile:    return _raw_subject_exercises();
        case $reportsFile:            return _raw_reports();
        case $reportHistoryFile:      return _raw_report_history();
        case $exerciseAttendanceFile: return _raw_exercise_att();
        case $deadlinesFile:          return _raw_deadlines();
        case $subjectAccessFile:      return _raw_subject_access();
        case $sectionsFile:           return _raw_sections();
        case $announcementsFile:      return _raw_announcements();
        case $logsFile:               return _raw_logs();
        case $definedSectionsFile:    return _raw_defined_sections();
        case $finalGradesFile:        return _raw_final_grades();
        case $uczelnieFile:           return _raw_uczelnie();
        case $applicationsFile:       return _raw_applications();
        case $harmonogramFile:        return _raw_harmonogram();
        case '__DB_ANN_HIDDEN__':        return _raw_hidden_announcements();
        default:                      return [];
    }
}

function _raw_hidden_announcements(): array {
    if (!_table_exists('Ogloszenia_Ukryte')) return [];
    try {
        $rows = db()->query(
            "SELECT id_studenta, id_ogloszenia FROM Ogloszenia_Ukryte ORDER BY id"
        )->fetchAll();
    } catch (PDOException $e) { return []; }
    return array_map(fn($r) => "{$r['id_studenta']};{$r['id_ogloszenia']}", $rows);
}

function append_line(string $file, string $line): void {
    global $usersFile, $subjectsFile, $enrollFile, $gradesFile,
           $exercisesFile, $subjectExerciseFile, $reportsFile, $reportHistoryFile,
           $exerciseAttendanceFile, $deadlinesFile, $subjectAccessFile, $sectionsFile,
           $announcementsFile, $logsFile, $definedSectionsFile, $finalGradesFile,
           $uczelnieFile, $applicationsFile, $harmonogramFile, $attFile;

    $pdo = db();
    $p   = explode(';', $line);

    try {
        switch ($file) {
            case $usersFile:
                $role     = $p[0];
                $imie     = $p[1] ?? '';
                $nazwisko = $p[2] ?? '';
                $pass     = $p[3] ?? '';
                $extra    = $p[5] ?? '';
                $uczelnia = (int)($p[6] ?? 1) ?: 1;
                $full     = trim("$imie $nazwisko");
                if ($role === 'teacher') {
                    $pdo->prepare(
                        "INSERT INTO Uzytkownicy (id_uczelni,inicjaly,haslo,imie_i_nazwisko,rola,nr_albumu)
                         VALUES (?,?,?,?,'teacher',NULL)"
                    )->execute([$uczelnia, $extra, $pass, $full]);
                } else {
                    $pdo->prepare(
                        "INSERT INTO Uzytkownicy (id_uczelni,inicjaly,haslo,imie_i_nazwisko,rola,nr_albumu)
                         VALUES (?,NULL,?,?,'student',?)"
                    )->execute([$uczelnia, $pass, $full, $extra]);
                }
                break;

            case $subjectsFile:
                $name     = $p[1] ?? '';
                $rok      = $p[2] ?? null;
                $owner    = (int)($p[3] ?? 0);
                $archived = (($p[4] ?? 'active') === 'archived') ? 1 : 0;
                $id_typu  = (($p[5] ?? 'laboratorium') === 'egzamin') ? 2 : 1;
                $pdo->prepare(
                    "INSERT INTO Przedmioty (nazwa,rocznik,id_uzytkownika,id_typu,czy_archiwum) VALUES (?,?,?,?,?)"
                )->execute([$name, $rok ?: null, $owner, $id_typu, $archived]);
                break;

            case $enrollFile:
                $pdo->prepare(
                    "INSERT IGNORE INTO Zapisy_Przedmiotow (id_studenta,id_przedmiotu) VALUES (?,?)"
                )->execute([(int)$p[0], (int)$p[1]]);
                break;

            case $gradesFile:
                $val_raw = trim($p[4] ?? '0.00');
                $is_txt  = in_array(strtolower($val_raw), ['zw','nb']);
                $ocena_num = (!$is_txt && is_numeric(str_replace(',','.',$val_raw)))
                    ? (float)str_replace(',','.',$val_raw) : null;
                $ocena_txt = $is_txt ? strtolower($val_raw) : null;
                $eid = (int)($p[7] ?? 0) ?: null;
                $pdo->prepare(
                    "INSERT INTO Oceny
                     (id_studenta,id_przedmiotu,ocena,ocena_tekstowa,komentarz,data_wstawienia,id_cwiczenia,terminy,id_nauczyciela)
                     VALUES (?,?,?,?,?,?,?,?,?)"
                )->execute([
                    (int)$p[1], (int)$p[2], $ocena_num, $ocena_txt,
                    $p[5]??'', $p[6]??date('Y-m-d H:i:s'), $eid,
                    $p[8]??'1', (int)($p[9]??0)
                ]);
                break;

            case $exerciseAttendanceFile:
                $eid = (int)($p[3] ?? 0) ?: null;
                $pdo->prepare(
                    "INSERT INTO Obecnosci (id_studenta,id_przedmiotu,id_cwiczenia,typ,data_i_czas,data_wstawienia)
                     VALUES (?,?,?,?,?,?)"
                )->execute([
                    (int)$p[1], (int)$p[2], $eid, $p[4]??'nieobecny',
                    $p[5]??date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
                ]);
                break;

            case $attFile:
                $pdo->prepare(
                    "INSERT INTO Obecnosci (id_studenta,id_przedmiotu,id_cwiczenia,typ,data_i_czas,data_wstawienia)
                     VALUES (?,?,NULL,?,?,?)"
                )->execute([
                    (int)$p[1], (int)$p[2], $p[4]??'nieobecny',
                    $p[5]??date('Y-m-d H:i:s'), date('Y-m-d H:i:s')
                ]);
                break;

            case $exercisesFile:
                break;

            case $subjectExerciseFile:
                break;

            case $reportsFile:
                $eid = (int)($p[3] ?? 0);
                $pdo->prepare(
                    "INSERT INTO Sprawozdania (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,data_dodania)
                     VALUES (?,?,?,?,?)"
                )->execute([
                    (int)$p[1], (int)$p[2], $eid, (int)($p[8]??0),
                    $p[6]??date('Y-m-d H:i:s')
                ]);
                $last_id = (int)$pdo->lastInsertId();
                if (!empty($p[4]) || !empty($p[7])) {
                    $pdo->prepare(
                        "INSERT INTO Historia_Sprawozdan (id_sprawozdania,plik_sciezka,status,data,komentarz,id_uzytkownika)
                         VALUES (?,'oczekuje',?,?,?,?)"
                    )->execute([
                        $last_id, $p[6]??date('Y-m-d H:i:s'),
                        $p[7]??'', (int)($p[8]??0)
                    ]);
                }
                break;

            case $reportHistoryFile:
                $pdo->prepare(
                    "INSERT INTO Historia_Sprawozdan (id_sprawozdania,status,data,komentarz)
                     VALUES (?,?,?,?)"
                )->execute([(int)$p[1], $p[3]??'oczekuje', $p[2]??date('Y-m-d H:i:s'), $p[4]??'']);
                break;

            case $sectionsFile:
                $sec_id = (int)($p[3] ?? 0);
                if ($sec_id > 0) {
                    $pdo->prepare(
                        "INSERT IGNORE INTO Zapisy (id_sekcji,id_uzytkownika) VALUES (?,?)"
                    )->execute([$sec_id, (int)$p[1]]);
                }
                break;

            case $definedSectionsFile:
                $pdo->prepare(
                    "INSERT INTO Sekcje_Studentow (id_przedmiotu,nazwa) VALUES (?,?)"
                )->execute([(int)$p[1], $p[2]??'']);
                break;

            case $announcementsFile:
                $target = $p[4] ?? 'global';
                $sid_ann = is_numeric($target) ? (int)$target : null;
                $widocznosc = is_numeric($target) ? 'przedmiot' : $target;
                $pdo->prepare(
                    "INSERT INTO Ogloszenia (id_przedmiotu,widocznosc,tytul,opis,id_uzytkownika,data_dodania)
                     VALUES (?,?,?,?,?,?)"
                )->execute([
                    $sid_ann, $widocznosc, $p[1]??'', $p[2]??'',
                    (int)($p[5]??0), $p[3]??date('Y-m-d H:i:s')
                ]);
                break;

            case $logsFile:
                $pdo->prepare(
                    "INSERT INTO Historia_Logowania (id_uzytkownika,data_logowania,adres_ip,informacje_o_urzadzeniu)
                     VALUES (?,?,?,?)"
                )->execute([
                    (int)($p[1]??0), $p[0]??date('Y-m-d H:i:s'), $p[2]??'', $p[3]??''
                ]);
                break;

            case $finalGradesFile:
                $fval_raw = $p[3] ?? '';
                $is_txt_f = in_array(strtolower($fval_raw), ['zw','nb']);
                $pdo->prepare(
                    "INSERT INTO Oceny_Koncowe
                     (id_studenta,id_przedmiotu,ocena,ocena_tekstowa,komentarz,data,id_nauczyciela)
                     VALUES (?,?,?,?,?,?,?)"
                )->execute([
                    (int)$p[1], (int)$p[2],
                    $is_txt_f ? null : ($fval_raw ?: null),
                    $is_txt_f ? strtolower($fval_raw) : null,
                    $p[4]??'', $p[5]??date('Y-m-d H:i:s'), (int)($p[6]??0)
                ]);
                break;

            case $uczelnieFile:
                $pdo->prepare("INSERT IGNORE INTO Uczelnia (nazwa) VALUES (?)")->execute([$p[1]??'']);
                break;

            case $applicationsFile:
                if (!_table_exists('Podania')) break;
                $pdo->prepare(
                    "INSERT INTO Podania (id_studenta,id_przedmiotu,id_cwiczenia,id_oceny,uzasadnienie,data,status)
                     VALUES (?,?,?,?,?,?,?)"
                )->execute([
                    (int)($p[1]??0), (int)($p[2]??0),
                    (int)($p[3]??0) ?: null, (int)($p[4]??0) ?: null,
                    $p[5]??'', $p[6]??date('Y-m-d H:i:s'), $p[7]??'oczekuje'
                ]);
                break;

            case $harmonogramFile:
                if (!_table_exists('Harmonogramy')) break;
                $pdo->prepare(
                    "INSERT INTO Harmonogramy (id_cwiczenia,id_sekcji,terminy) VALUES (?,?,?)"
                )->execute([(int)($p[3]??0), (int)($p[2]??0), $p[4]??'']);
                break;
        }
    } catch (PDOException $e) {
        error_log("append_line DB error [{$file}]: " . $e->getMessage());
    }
}

function overwrite_file(string $file, array $lines): void {}

function get_new_id(string $file): int {
    return 0; // nieużywane – MySQL AUTO_INCREMENT
}

function sanitizeString($inputString): string {
    if ($inputString === null) return '';
    $pattern = '/[^0-9a-zA-Z!@#$%^&*()\[\]{},.\-+_ ĄąŻżÓóŁłĆćŃńŹźŚśĘę=?:\"\'<>\/]/u';
    return preg_replace($pattern, '', (string)$inputString) ?? '';
}

function validateAndFormatGrade(?string $gradeString): string {
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

function find_user(string $imie, string $nazwisko, ?string $password = null): ?array {
    $pdo = db();
    $sql    = "SELECT * FROM Uzytkownicy WHERE rola='teacher' AND inicjaly=?";
    $params = [trim($imie)];
    if ($password !== null) { $sql .= " AND haslo=?"; $params[] = $password; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    if ($row) {
        $parts = explode(' ', $row['imie_i_nazwisko'], 2);
        return [
            'role'       => 'teacher',
            'imie'       => $parts[0] ?? '',
            'nazwisko'   => $parts[1] ?? '',
            'password'   => $row['haslo'],
            'id'         => $row['id_uzytkownika'],
            'nr_albumu'  => $row['inicjaly'] ?? '',
            'id_uczelni' => (string)($row['id_uczelni'] ?? ''),
        ];
    }

    $full   = trim($imie) . ' ' . trim($nazwisko);
    $sql    = "SELECT * FROM Uzytkownicy WHERE rola='student' AND imie_i_nazwisko=?";
    $params = [$full];
    if ($password !== null) { $sql .= " AND haslo=?"; $params[] = $password; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    if ($row) {
        $parts = explode(' ', $row['imie_i_nazwisko'], 2);
        return [
            'role'       => 'student',
            'imie'       => $parts[0] ?? '',
            'nazwisko'   => $parts[1] ?? '',
            'password'   => $row['haslo'],
            'id'         => $row['id_uzytkownika'],
            'nr_albumu'  => $row['nr_albumu'] ?? '',
            'id_uczelni' => (string)($row['id_uczelni'] ?? ''),
        ];
    }
    return null;
}

function get_uczelnia_name(int $id_uczelni): string {
    if ($id_uczelni <= 0) return 'Nieznana uczelnia';
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT nazwa FROM Uczelnia WHERE id_uczelni=?");
    $stmt->execute([$id_uczelni]);
    return $stmt->fetchColumn() ?: 'Nieznana uczelnia';
}

// ============================================================
//  FUNKCJE BEZPIECZEŃSTWA – identyczne sygnatury jak dawniej
// ============================================================
function get_subject_owner_id(int $sid) {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_uzytkownika FROM Przedmioty WHERE id_przedmiotu=?");
    $stmt->execute([$sid]);
    $val  = $stmt->fetchColumn();
    return ($val !== false) ? (int)$val : false;
}

function is_subject_owner(int $sid, int $me_id): bool {
    $owner = get_subject_owner_id($sid);
    return ($owner !== false && $owner === $me_id);
}

function has_subject_access(int $sid, int $me_id): bool {
    if (is_subject_owner($sid, $me_id)) return true;
    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Wspolprowadzacy WHERE id_przedmiotu=? AND id_nauczyciela=?"
    );
    $stmt->execute([$sid, $me_id]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function student_belongs_to_uczelnia(int $st_id, $me_uczelni): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_uczelni FROM Uzytkownicy WHERE id_uzytkownika=? AND rola='student'");
    $stmt->execute([$st_id]);
    $row  = $stmt->fetch();
    if (!$row) return false;
    $st_u = (string)$row['id_uczelni'];
    $me_u = trim((string)$me_uczelni);
    if ($st_u === '' && $me_u === '') return true;
    return $st_u === $me_u;
}

function grade_belongs_to_accessible_subject(int $gid, int $me_id): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_przedmiotu FROM Oceny WHERE id_oceny=?");
    $stmt->execute([$gid]);
    $sid  = $stmt->fetchColumn();
    return ($sid !== false && has_subject_access((int)$sid, $me_id));
}

function exercise_att_belongs_to_accessible_subject(int $aid, int $me_id): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_przedmiotu FROM Obecnosci WHERE id_obecnosci=?");
    $stmt->execute([$aid]);
    $sid  = $stmt->fetchColumn();
    return ($sid !== false && has_subject_access((int)$sid, $me_id));
}

function report_belongs_to_accessible_subject(int $rid, int $me_id): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_przedmiotu FROM Sprawozdania WHERE id_sprawozdania=?");
    $stmt->execute([$rid]);
    $sid  = $stmt->fetchColumn();
    return ($sid !== false && has_subject_access((int)$sid, $me_id));
}

function report_history_belongs_to_accessible_subject(int $hid, int $me_id): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_sprawozdania FROM Historia_Sprawozdan WHERE id_historii=?");
    $stmt->execute([$hid]);
    $rid  = $stmt->fetchColumn();
    return ($rid !== false && report_belongs_to_accessible_subject((int)$rid, $me_id));
}

function announcement_belongs_to_teacher(int $aid, int $me_id): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_uzytkownika FROM Ogloszenia WHERE id_ogloszenia=?");
    $stmt->execute([$aid]);
    $author = $stmt->fetchColumn();
    return ($author !== false && (int)$author === $me_id);
}

function teacher_belongs_to_uczelnia(int $tid, $me_uczelni): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id_uczelni FROM Uzytkownicy WHERE id_uzytkownika=? AND rola='teacher'");
    $stmt->execute([$tid]);
    $row  = $stmt->fetch();
    if (!$row) return false;
    $t_u  = (string)$row['id_uczelni'];
    $me_u = trim((string)$me_uczelni);
    if ($t_u === '' && $me_u === '') return true;
    return $t_u === $me_u;
}

function exercise_belongs_to_subject(int $eid, int $sid): bool {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Cwiczenia WHERE id_cwiczenia=? AND id_przedmiotu=?");
    $stmt->execute([$eid, $sid]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function defined_section_belongs_to_subject(int $sec_id, int $sid): bool {
    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Sekcje_Studentow WHERE id_sekcji=? AND id_przedmiotu=?"
    );
    $stmt->execute([$sec_id, $sid]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function student_enrolled_in_subject(int $st_id, int $sid): bool {
    $pdo  = db();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Zapisy_Przedmiotow WHERE id_studenta=? AND id_przedmiotu=?"
    );
    $stmt->execute([$st_id, $sid]);
    return ((int)$stmt->fetchColumn()) > 0;
}

// Helper: zwraca wartość oceny do wyświetlenia (string)
function ocena_display($numeric_val, $text_val): string {
    if (!empty($text_val)) return $text_val;
    if ($numeric_val !== null) return number_format((float)$numeric_val, 2, '.', '');
    return '';
}

// ============================================================
//  IDENTYFIKATOR hidden announcements – ogłoszenia ukryte
//  (dodany tutaj dla obsługi login.php)
// ============================================================
$hiddenAnnouncementsFile = '__DB_ANN_HIDDEN__';