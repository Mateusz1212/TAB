<?php

$pdo    = db();
$action = $_REQUEST['action'] ?? '';

if (!$action) {
    return;
}

function redirect_with_context(string $fallback = 'login.php', string $msg = '', string $err = ''): void {
    $base = $_SERVER['HTTP_REFERER'] ?? $fallback;
    $base = preg_replace('/([?&])(msg|err)=[^&]*/', '', $base);
    $base = rtrim($base, '?&');
    $sep  = (str_contains($base, '?')) ? '&' : '?';
    if ($msg !== '')      $base .= $sep . 'msg=' . urlencode($msg);
    elseif ($err !== '')  $base .= $sep . 'err=' . urlencode($err);
    header('Location: ' . $base);
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($action === 'add_subject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitizeString(trim($_POST['name'] ?? ''));
    $rok     = sanitizeString(trim($_POST['rok']  ?? ''));
    $type    = sanitizeString(trim($_POST['type'] ?? 'laboratorium'));
    $id_typu = ($type === 'egzamin') ? 2 : 1;

    if ($name === '') {
        redirect_with_context('login.php?view_action=add_subject', '', 'Nazwa przedmiotu jest wymagana.');
    }
    $pdo->prepare(
        "INSERT INTO Przedmioty (nazwa,rocznik,id_uzytkownika,id_typu,czy_archiwum) VALUES (?,?,?,?,0)"
    )->execute([$name, $rok ?: null, (int)$me['id'], $id_typu]);
    redirect_with_context('login.php?view_action=add_subject', 'Dodano przedmiot.');
}

if ($action === 'edit_subject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid     = (int)($_POST['subject_id'] ?? 0);
    $name    = sanitizeString(trim($_POST['name'] ?? ''));
    $rok     = sanitizeString(trim($_POST['rok']  ?? ''));
    $type    = sanitizeString(trim($_POST['type'] ?? 'laboratorium'));
    $id_typu = ($type === 'egzamin') ? 2 : 1;

    if (!$sid || !is_subject_owner($sid, (int)$me['id'])) {
        redirect_with_context('login.php?view_action=add_subject', '', 'Brak uprawnień.');
    }
    $pdo->prepare("UPDATE Przedmioty SET nazwa=?,rocznik=?,id_typu=? WHERE id_przedmiotu=?")
        ->execute([$name, $rok ?: null, $id_typu, $sid]);
    redirect_with_context('login.php?view_action=add_subject', 'Zaktualizowano przedmiot.');
}

if ($action === 'archive_subject') {
    $sid = (int)($_GET['sid'] ?? 0);
    if ($sid && is_subject_owner($sid, (int)$me['id'])) {
        $pdo->prepare("UPDATE Przedmioty SET czy_archiwum=1 WHERE id_przedmiotu=?")->execute([$sid]);
    }
    redirect_with_context('login.php?view_action=add_subject', 'Przedmiot zarchiwizowany.');
}

if ($action === 'unarchive_subject') {
    $sid = (int)($_GET['sid'] ?? 0);
    if ($sid && is_subject_owner($sid, (int)$me['id'])) {
        $pdo->prepare("UPDATE Przedmioty SET czy_archiwum=0 WHERE id_przedmiotu=?")->execute([$sid]);
    }
    redirect_with_context('login.php?view_action=add_subject', 'Przedmiot przywrócony.');
}

if ($action === 'delete_subject') {
    $sid = (int)($_GET['sid'] ?? 0);
    if ($sid && is_subject_owner($sid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Zapisy_Przedmiotow WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Oceny WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Oceny_Koncowe WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Obecnosci WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Ogloszenia WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Wspolprowadzacy WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Uprawnienia WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Harmonogramy WHERE id_cwiczenia IN (SELECT id_cwiczenia FROM Cwiczenia WHERE id_przedmiotu=?)")->execute([$sid]);
        $rids = $pdo->prepare("SELECT id_sprawozdania FROM Sprawozdania WHERE id_przedmiotu=?");
        $rids->execute([$sid]);
        foreach ($rids->fetchAll(PDO::FETCH_COLUMN) as $rid) {
            $pdo->prepare("DELETE FROM Historia_Sprawozdan WHERE id_sprawozdania=?")->execute([$rid]);
        }
        $pdo->prepare("DELETE FROM Sprawozdania WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Cwiczenia WHERE id_przedmiotu=?")->execute([$sid]);
        $sec_ids_q = $pdo->prepare("SELECT id_sekcji FROM Sekcje_Studentow WHERE id_przedmiotu=?");
        $sec_ids_q->execute([$sid]);
        foreach ($sec_ids_q->fetchAll(PDO::FETCH_COLUMN) as $sec_id) {
            $pdo->prepare("DELETE FROM Zapisy WHERE id_sekcji=?")->execute([$sec_id]);
        }
        $pdo->prepare("DELETE FROM Sekcje_Studentow WHERE id_przedmiotu=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM Przedmioty WHERE id_przedmiotu=?")->execute([$sid]);
    }
    redirect_with_context('login.php?view_action=add_subject', 'Przedmiot usunięty.');
}

if ($action === 'rollover_subject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = (int)($_POST['sid'] ?? 0);
    if (!$sid || !is_subject_owner($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $old = $pdo->prepare(
        "SELECT p.nazwa, p.id_typu FROM Przedmioty p WHERE p.id_przedmiotu=?"
    );
    $old->execute([$sid]);
    $old_data = $old->fetch();
    if (!$old_data) redirect_with_context('login.php', '', 'Nie znaleziono przedmiotu.');

    $pdo->prepare("UPDATE Przedmioty SET czy_archiwum=1 WHERE id_przedmiotu=?")->execute([$sid]);
    $pdo->prepare(
        "INSERT INTO Przedmioty (nazwa,rocznik,id_uzytkownika,id_typu,czy_archiwum) VALUES (?,NULL,?,?,0)"
    )->execute([$old_data['nazwa'], (int)$me['id'], $old_data['id_typu']]);
    $new_sid = (int)$pdo->lastInsertId();

    $exs = $pdo->prepare(
        "SELECT nazwa,opis,waga,id_uzytkownika FROM Cwiczenia WHERE id_przedmiotu=? ORDER BY kolejnosc,id_cwiczenia"
    );
    $exs->execute([$sid]);
    $kolejnosc = 0;
    foreach ($exs->fetchAll() as $ex) {
        $pdo->prepare(
            "INSERT INTO Cwiczenia (id_przedmiotu,nazwa,opis,waga,id_uzytkownika,kolejnosc) VALUES (?,?,?,?,?,?)"
        )->execute([$new_sid, $ex['nazwa'], $ex['opis'] ?? '', $ex['waga'], $ex['id_uzytkownika'], $kolejnosc++]);
    }
    redirect_with_context('login.php?view_action=add_subject', 'Nowy rocznik przedmiotu utworzony.');
}

if ($action === 'add_student' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie     = sanitizeString(trim($_POST['imie']      ?? ''));
    $nazwisko = sanitizeString(trim($_POST['nazwisko']  ?? ''));
    $pass     = sanitizeString(trim($_POST['password']  ?? ''));
    $nr_alb   = sanitizeString(trim($_POST['nr_albumu'] ?? ''));
    $uczelnia = (int)($me['id_uczelni'] ?? 1) ?: 1;

    if (!$imie || !$nazwisko || !$pass) {
        redirect_with_context('login.php?view_action=add_student', '', 'Wypełnij imię, nazwisko i hasło.');
    }
    $full = "$imie $nazwisko";
    $chk  = $pdo->prepare("SELECT COUNT(*) FROM Uzytkownicy WHERE rola='student' AND imie_i_nazwisko=?");
    $chk->execute([$full]);
    if ((int)$chk->fetchColumn() > 0) {
        redirect_with_context('login.php?view_action=add_student', '', 'Student o takim imieniu i nazwisku już istnieje.');
    }
    $pdo->prepare(
        "INSERT INTO Uzytkownicy (id_uczelni,imie_i_nazwisko,haslo,rola,nr_albumu) VALUES (?,?,?,'student',?)"
    )->execute([$uczelnia, $full, $pass, $nr_alb ?: null]);
    redirect_with_context('login.php?view_action=add_student', 'Dodano studenta.');
}

if ($action === 'edit_student' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int)($_POST['student_id'] ?? 0);
    $imie      = sanitizeString(trim($_POST['imie']      ?? ''));
    $nazwisko  = sanitizeString(trim($_POST['nazwisko']  ?? ''));
    $pass      = sanitizeString(trim($_POST['password']  ?? ''));
    $nr_alb    = sanitizeString(trim($_POST['nr_albumu'] ?? ''));

    if (!$target_id || !$imie || !$nazwisko) {
        redirect_with_context('login.php?view_action=add_student', '', 'Brak wymaganych danych.');
    }
    if (!student_belongs_to_uczelnia($target_id, $me['id_uczelni'] ?? '')) {
        redirect_with_context('login.php?view_action=add_student', '', 'Brak uprawnień (inna uczelnia).');
    }
    $full = "$imie $nazwisko";
    if ($pass !== '') {
        $pdo->prepare(
            "UPDATE Uzytkownicy SET imie_i_nazwisko=?,haslo=?,nr_albumu=? WHERE id_uzytkownika=? AND rola='student'"
        )->execute([$full, $pass, $nr_alb ?: null, $target_id]);
    } else {
        $pdo->prepare(
            "UPDATE Uzytkownicy SET imie_i_nazwisko=?,nr_albumu=? WHERE id_uzytkownika=? AND rola='student'"
        )->execute([$full, $nr_alb ?: null, $target_id]);
    }
    redirect_with_context('login.php?view_action=add_student', 'Zaktualizowano dane studenta.');
}

if ($action === 'delete_student') {
    $target_id = (int)($_GET['student_id'] ?? 0);
    if ($target_id && student_belongs_to_uczelnia($target_id, $me['id_uczelni'] ?? '')) {
        $pdo->prepare("DELETE FROM Zapisy_Przedmiotow WHERE id_studenta=?")->execute([$target_id]);
        $pdo->prepare("DELETE FROM Zapisy WHERE id_uzytkownika=?")->execute([$target_id]);
        $pdo->prepare("DELETE FROM Uzytkownicy WHERE id_uzytkownika=? AND rola='student'")->execute([$target_id]);
    }
    redirect_with_context('login.php?view_action=add_student', 'Usunięto studenta.');
}

if ($action === 'enroll' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid        = (int)($_POST['subject_id'] ?? 0);
    $student_id = (int)($_POST['student_id'] ?? 0);
    if (!$sid || !$student_id || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    if (!student_belongs_to_uczelnia($student_id, $me['id_uczelni'] ?? '')) {
        redirect_with_context('login.php', '', 'Student z innej uczelni.');
    }
    $pdo->prepare("INSERT IGNORE INTO Zapisy_Przedmiotow (id_studenta,id_przedmiotu) VALUES (?,?)")
        ->execute([$student_id, $sid]);
    redirect_with_context('login.php', 'Zapisano studenta na przedmiot.');
}

if ($action === 'unenroll') {
    $sid        = (int)($_GET['sid']        ?? 0);
    $student_id = (int)($_GET['student_id'] ?? 0);
    if ($sid && $student_id && has_subject_access($sid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Zapisy_Przedmiotow WHERE id_studenta=? AND id_przedmiotu=?")
            ->execute([$student_id, $sid]);
        $pdo->prepare(
            "DELETE z FROM Zapisy z
             JOIN Sekcje_Studentow ss ON z.id_sekcji=ss.id_sekcji
             WHERE ss.id_przedmiotu=? AND z.id_uzytkownika=?"
        )->execute([$sid, $student_id]);
        $pdo->prepare("DELETE FROM Oceny WHERE id_studenta=? AND id_przedmiotu=?")->execute([$student_id, $sid]);
        $pdo->prepare("DELETE FROM Obecnosci WHERE id_studenta=? AND id_przedmiotu=?")->execute([$student_id, $sid]);
    }
    redirect_with_context('login.php', 'Wypisano studenta z przedmiotu.');
}

if ($action === 'add_exercise' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid  = (int)($_POST['subject_id'] ?? 0);
    $name = sanitizeString(trim($_POST['name'] ?? ''));
    $opis = sanitizeString(trim($_POST['opis'] ?? ''));
    $waga = max(1, (int)($_POST['waga'] ?? 1));

    if (!$sid || !$name || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    $max_q = $pdo->prepare("SELECT COALESCE(MAX(kolejnosc),0)+1 FROM Cwiczenia WHERE id_przedmiotu=?");
    $max_q->execute([$sid]);
    $next_ord = (int)$max_q->fetchColumn();

    $pdo->prepare(
        "INSERT INTO Cwiczenia (id_przedmiotu,nazwa,opis,waga,id_uzytkownika,kolejnosc) VALUES (?,?,?,?,?,?)"
    )->execute([$sid, $name, $opis, $waga, (int)$me['id'], $next_ord]);
    redirect_with_context('login.php?view_action=manage_exercises&view=list&sid=' . $sid, 'Dodano ćwiczenie.');
}

if ($action === 'edit_exercise' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid  = (int)($_POST['exercise_id'] ?? 0);
    $sid  = (int)($_POST['subject_id']  ?? 0);
    $name = sanitizeString(trim($_POST['name'] ?? ''));
    $opis = sanitizeString(trim($_POST['opis'] ?? ''));
    $waga = max(1, (int)($_POST['waga'] ?? 1));

    if (!$eid || !$sid || !has_subject_access($sid, (int)$me['id']) || !exercise_belongs_to_subject($eid, $sid)) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $pdo->prepare("UPDATE Cwiczenia SET nazwa=?,opis=?,waga=? WHERE id_cwiczenia=? AND id_przedmiotu=?")
        ->execute([$name, $opis, $waga, $eid, $sid]);
    redirect_with_context('login.php?view_action=manage_exercises&view=list&sid=' . $sid, 'Zaktualizowano ćwiczenie.');
}

if ($action === 'delete_exercise') {
    $eid = (int)($_GET['eid'] ?? 0);
    $sid = (int)($_GET['sid'] ?? 0);
    if ($eid && $sid && has_subject_access($sid, (int)$me['id']) && exercise_belongs_to_subject($eid, $sid)) {
        $pdo->prepare("DELETE FROM Cwiczenia WHERE id_cwiczenia=? AND id_przedmiotu=?")->execute([$eid, $sid]);
        $pdo->prepare("DELETE FROM Oceny WHERE id_cwiczenia=? AND id_przedmiotu=?")->execute([$eid, $sid]);
        $pdo->prepare("DELETE FROM Obecnosci WHERE id_cwiczenia=? AND id_przedmiotu=?")->execute([$eid, $sid]);
        $rids = $pdo->prepare("SELECT id_sprawozdania FROM Sprawozdania WHERE id_cwiczenia=? AND id_przedmiotu=?");
        $rids->execute([$eid, $sid]);
        foreach ($rids->fetchAll(PDO::FETCH_COLUMN) as $rid) {
            $pdo->prepare("DELETE FROM Historia_Sprawozdan WHERE id_sprawozdania=?")->execute([$rid]);
        }
        $pdo->prepare("DELETE FROM Sprawozdania WHERE id_cwiczenia=? AND id_przedmiotu=?")->execute([$eid, $sid]);
        $pdo->prepare("DELETE FROM Harmonogramy WHERE id_cwiczenia=?")->execute([$eid]);
    }
    redirect_with_context('login.php?view_action=manage_exercises&view=list&sid=' . $sid, 'Usunięto ćwiczenie.');
}

if ($action === 'reorder_exercises' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid   = (int)($_POST['subject_id'] ?? 0);
    $order = $_POST['order'] ?? [];
    if ($sid && is_array($order) && has_subject_access($sid, (int)$me['id'])) {
        $stmt = $pdo->prepare("UPDATE Cwiczenia SET kolejnosc=? WHERE id_cwiczenia=? AND id_przedmiotu=?");
        foreach (array_values($order) as $i => $eid) {
            $stmt->execute([$i, (int)$eid, $sid]);
        }
    }
    redirect_with_context('login.php?view_action=manage_exercises&view=list&sid=' . $sid, 'Zapisano kolejność ćwiczeń.');
}

if ($action === 'add_grade' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid     = (int)($_POST['subject_id']  ?? 0);
    $st_id   = (int)($_POST['student_id']  ?? 0);
    $eid     = (int)($_POST['exercise_id'] ?? 0);
    $term    = max(1, min(4, (int)($_POST['term'] ?? 1)));
    $val     = validateAndFormatGrade(sanitizeString(trim($_POST['grade'] ?? '')));
    $note    = sanitizeString(trim($_POST['note'] ?? ''));
    $date    = date('Y-m-d H:i:s');

    if (!$sid || !$st_id || $val === '' || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    $is_txt    = in_array($val, ['zw','nb']);
    $ocena_num = (!$is_txt && is_numeric($val)) ? (float)$val : null;
    $ocena_txt = $is_txt ? $val : null;
    $eid_ins   = ($eid > 0) ? $eid : null;

    $pdo->prepare(
        "INSERT INTO Oceny (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,
                            ocena,ocena_tekstowa,komentarz,data_wstawienia,terminy)
         VALUES (?,?,?,?,?,?,?,?,?)"
    )->execute([$st_id, $sid, $eid_ins, (int)$me['id'], $ocena_num, $ocena_txt, $note, $date, (string)$term]);
    redirect_with_context('login.php', 'Dodano ocenę.');
}

if ($action === 'delete_grade') {
    $gid = (int)($_GET['gid'] ?? 0);
    if ($gid && grade_belongs_to_accessible_subject($gid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Oceny WHERE id_oceny=?")->execute([$gid]);
    }
    redirect_with_context('login.php', 'Usunięto ocenę.');
}

if ($action === 'edit_grade' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gid  = (int)($_POST['grade_id'] ?? 0);
    $val  = validateAndFormatGrade(sanitizeString(trim($_POST['grade'] ?? '')));
    $note = sanitizeString(trim($_POST['note'] ?? ''));

    if (!$gid || $val === '' || !grade_belongs_to_accessible_subject($gid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $is_txt    = in_array($val, ['zw','nb']);
    $ocena_num = (!$is_txt && is_numeric($val)) ? (float)$val : null;
    $ocena_txt = $is_txt ? $val : null;
    $pdo->prepare("UPDATE Oceny SET ocena=?,ocena_tekstowa=?,komentarz=? WHERE id_oceny=?")
        ->execute([$ocena_num, $ocena_txt, $note, $gid]);
    redirect_with_context('login.php', 'Ocena zaktualizowana.');
}

if ($action === 'batch_grading' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid    = (int)($_POST['subject_id']  ?? 0);
    $eid    = (int)($_POST['exercise_id'] ?? 0);
    $term   = max(1, min(4, (int)($_POST['term'] ?? 1)));
    $grades = $_POST['grades'] ?? [];
    $notes  = $_POST['notes']  ?? [];
    $date   = date('Y-m-d H:i:s');

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $eid_ins = ($eid > 0) ? $eid : null;
    foreach ($grades as $st_id => $val_raw) {
        $st_id = (int)$st_id;
        $val   = validateAndFormatGrade(sanitizeString(trim((string)$val_raw)));
        if ($val === '') continue;
        $note      = sanitizeString(trim($notes[$st_id] ?? ''));
        $is_txt    = in_array($val, ['zw','nb']);
        $ocena_num = (!$is_txt && is_numeric($val)) ? (float)$val : null;
        $ocena_txt = $is_txt ? $val : null;
        $pdo->prepare(
            "INSERT INTO Oceny (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,
                                ocena,ocena_tekstowa,komentarz,data_wstawienia,terminy)
             VALUES (?,?,?,?,?,?,?,?,?)"
        )->execute([$st_id, $sid, $eid_ins, (int)$me['id'], $ocena_num, $ocena_txt, $note, $date, (string)$term]);
    }
    redirect_with_context('login.php?view_action=batch_grading&sid=' . $sid, 'Wstawiono oceny seryjne.');
}

if ($action === 'apply_penalty' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid      = (int)($_POST['subject_id']  ?? 0);
    $eid      = (int)($_POST['exercise_id'] ?? 0);
    $students = $_POST['students'] ?? [];
    $date     = date('Y-m-d H:i:s');

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $eid_ins = ($eid > 0) ? $eid : null;
    foreach ($students as $st_id) {
        $st_id = (int)$st_id;
        if ($st_id <= 0) continue;
        $pdo->prepare(
            "INSERT INTO Oceny (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,
                                ocena,ocena_tekstowa,komentarz,data_wstawienia,terminy)
             VALUES (?,?,?,?,2.00,NULL,'auto-kara',?,?)"
        )->execute([$st_id, $sid, $eid_ins, (int)$me['id'], $date, '1']);
    }
    redirect_with_context('login.php?view_action=enforce_tasks&sid=' . $sid, 'Wstawiono oceny 2.00.');
}

if ($action === 'save_exercise_att' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid  = (int)($_POST['subject_id']  ?? 0);
    $eid  = (int)($_POST['exercise_id'] ?? 0);
    $atts = $_POST['attendance'] ?? [];
    $date = date('Y-m-d H:i:s');

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $eid_ins = ($eid > 0) ? $eid : null;
    if ($eid > 0) {
        $pdo->prepare("DELETE FROM Obecnosci WHERE id_przedmiotu=? AND id_cwiczenia=?")->execute([$sid, $eid]);
    } else {
        $pdo->prepare("DELETE FROM Obecnosci WHERE id_przedmiotu=? AND id_cwiczenia IS NULL")->execute([$sid]);
    }
    foreach ($atts as $st_id => $status) {
        $st_id  = (int)$st_id;
        $status = sanitizeString(trim((string)$status));
        if ($st_id <= 0 || $status === '') continue;
        $pdo->prepare(
            "INSERT INTO Obecnosci (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,typ,data_i_czas,data_wstawienia)
             VALUES (?,?,?,?,?,?,?)"
        )->execute([$st_id, $sid, $eid_ins, (int)$me['id'], $status, $date, $date]);
    }
    redirect_with_context('login.php?view_action=manage_exercise_att&sid=' . $sid, 'Zapisano obecności.');
}

if ($action === 'delete_exercise_att') {
    $aid = (int)($_GET['aid'] ?? 0);
    if ($aid && exercise_att_belongs_to_accessible_subject($aid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Obecnosci WHERE id_obecnosci=?")->execute([$aid]);
    }
    redirect_with_context('login.php', 'Usunięto wpis obecności.');
}

if ($action === 'add_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid   = (int)($_POST['subject_id']  ?? 0);
    $st_id = (int)($_POST['student_id']  ?? 0);
    $eid   = (int)($_POST['exercise_id'] ?? 0);
    $note  = sanitizeString(trim($_POST['note'] ?? ''));
    $date  = date('Y-m-d H:i:s');

    if (!$sid || !$st_id || !$eid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    $file_path = '';
    if (!empty($_FILES['report_file']['name']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['report_file']['name']));
        $dest = $upload_dir . time() . '_' . $safe;
        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $dest)) {
            $file_path = 'uploads/' . time() . '_' . $safe;
        }
    }
    $pdo->prepare(
        "INSERT INTO Sprawozdania (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,data_dodania)
         VALUES (?,?,?,?,?)"
    )->execute([$st_id, $sid, $eid, (int)$me['id'], $date]);
    $rid = (int)$pdo->lastInsertId();
    $pdo->prepare(
        "INSERT INTO Historia_Sprawozdan (id_sprawozdania,plik_sciezka,status,data,komentarz,id_uzytkownika)
         VALUES (?,'oczekuje',?,?,?,?)"
    )->execute([$rid, $date, $note, (int)$me['id']]);

    redirect_with_context('login.php?view_action=manage_reports&sid=' . $sid, 'Dodano sprawozdanie.');
}

if ($action === 'review_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid     = (int)($_POST['report_id'] ?? 0);
    $status  = sanitizeString(trim($_POST['status']  ?? ''));
    $comment = sanitizeString(trim($_POST['comment'] ?? ''));
    $date    = date('Y-m-d H:i:s');

    if (!$rid || !$status || !report_belongs_to_accessible_subject($rid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $pdo->prepare(
        "INSERT INTO Historia_Sprawozdan (id_sprawozdania,status,data,komentarz,id_uzytkownika)
         VALUES (?,?,?,?,?)"
    )->execute([$rid, $status, $date, $comment, (int)$me['id']]);
    redirect_with_context('login.php?view_action=manage_reports', 'Zaktualizowano status sprawozdania.');
}

if ($action === 'delete_report') {
    $rid = (int)($_GET['rid'] ?? 0);
    if ($rid && report_belongs_to_accessible_subject($rid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Historia_Sprawozdan WHERE id_sprawozdania=?")->execute([$rid]);
        $pdo->prepare("DELETE FROM Sprawozdania WHERE id_sprawozdania=?")->execute([$rid]);
    }
    redirect_with_context('login.php?view_action=manage_reports', 'Usunięto sprawozdanie.');
}

if ($action === 'delete_report_history') {
    $hid = (int)($_GET['hid'] ?? 0);
    if ($hid && report_history_belongs_to_accessible_subject($hid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Historia_Sprawozdan WHERE id_historii=?")->execute([$hid]);
    }
    redirect_with_context('login.php?view_action=edit_reports', 'Usunięto wpis historii.');
}

if ($action === 'save_deadlines' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid  = (int)($_POST['subject_id'] ?? 0);
    $eids = $_POST['exercises'] ?? [];

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    foreach ($eids as $eid => $data) {
        $eid = (int)$eid;
        if (!exercise_belongs_to_subject($eid, $sid)) continue;
        $w = json_encode([
            'req_grade'      => (int)!empty($data['req_grade']),
            'req_report'     => (int)!empty($data['req_report']),
            'req_attendance' => (int)!empty($data['req_attendance']),
            't1' => sanitizeString(trim($data['t1'] ?? '')),
            't2' => sanitizeString(trim($data['t2'] ?? '')),
            't3' => sanitizeString(trim($data['t3'] ?? '')),
            't4' => sanitizeString(trim($data['t4'] ?? '')),
        ], JSON_UNESCAPED_UNICODE);
        $pdo->prepare("UPDATE Cwiczenia SET wymagania=? WHERE id_cwiczenia=? AND id_przedmiotu=?")
            ->execute([$w, $eid, $sid]);
    }
    redirect_with_context('login.php?view_action=manage_deadlines&sid=' . $sid, 'Zapisano wymagania zaliczenia.');
}

if ($action === 'add_section' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid  = (int)($_POST['subject_id']    ?? 0);
    $name = sanitizeString(trim($_POST['section_name'] ?? ''));
    if (!$sid || !$name || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    $pdo->prepare("INSERT INTO Sekcje_Studentow (id_przedmiotu,nazwa) VALUES (?,?)")->execute([$sid, $name]);
    redirect_with_context('login.php?view_action=manage_sections&sid=' . $sid, 'Dodano sekcję.');
}

if ($action === 'rename_section' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sec_id   = (int)($_POST['sec_id']     ?? 0);
    $sid      = (int)($_POST['subject_id'] ?? 0);
    $new_name = sanitizeString(trim($_POST['new_name'] ?? ''));
    if ($sec_id && $sid && $new_name && has_subject_access($sid, (int)$me['id']) && defined_section_belongs_to_subject($sec_id, $sid)) {
        $pdo->prepare("UPDATE Sekcje_Studentow SET nazwa=? WHERE id_sekcji=?")->execute([$new_name, $sec_id]);
    }
    redirect_with_context('login.php?view_action=manage_sections&sid=' . $sid, 'Zmieniono nazwę sekcji.');
}

if ($action === 'delete_section') {
    $sec_id = (int)($_GET['sec_id'] ?? 0);
    $sid    = (int)($_GET['sid']    ?? 0);
    if ($sec_id && $sid && has_subject_access($sid, (int)$me['id']) && defined_section_belongs_to_subject($sec_id, $sid)) {
        $pdo->prepare("DELETE FROM Zapisy WHERE id_sekcji=?")->execute([$sec_id]);
        $pdo->prepare("DELETE FROM Sekcje_Studentow WHERE id_sekcji=?")->execute([$sec_id]);
    }
    redirect_with_context('login.php?view_action=manage_sections&sid=' . $sid, 'Usunięto sekcję.');
}

if ($action === 'save_sections' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid                = (int)($_POST['subject_id'] ?? 0);
    $submitted_sections = $_POST['sections'] ?? [];

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }

    $pdo->prepare(
        "DELETE z FROM Zapisy z
         JOIN Sekcje_Studentow ss ON z.id_sekcji=ss.id_sekcji
         WHERE ss.id_przedmiotu=?"
    )->execute([$sid]);

    foreach ($submitted_sections as $st_id => $sec_id) {
        $st_id  = (int)$st_id;
        $sec_id = (int)$sec_id;
        if ($st_id <= 0 || $sec_id <= 0) continue;
        if (!defined_section_belongs_to_subject($sec_id, $sid)) continue;
        $pdo->prepare("INSERT IGNORE INTO Zapisy (id_sekcji,id_uzytkownika) VALUES (?,?)")->execute([$sec_id, $st_id]);
    }
    redirect_with_context('login.php?view_action=manage_sections&sid=' . $sid, 'Zapisano przypisania do sekcji.');
}

if ($action === 'move_students_section' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid         = (int)($_POST['subject_id']  ?? 0);
    $from_sec_id = (int)($_POST['from_sec_id'] ?? 0);
    $to_sec_id   = (int)($_POST['to_sec_id']   ?? 0);
    $student_ids = $_POST['student_ids'] ?? [];

    if (!$sid || !$from_sec_id || !$to_sec_id || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    if (!defined_section_belongs_to_subject($from_sec_id, $sid) || !defined_section_belongs_to_subject($to_sec_id, $sid)) {
        redirect_with_context('login.php', '', 'Nieprawidłowe sekcje.');
    }
    foreach ($student_ids as $st_id) {
        $st_id = (int)$st_id;
        if ($st_id <= 0) continue;
        $pdo->prepare("DELETE FROM Zapisy WHERE id_sekcji=? AND id_uzytkownika=?")->execute([$from_sec_id, $st_id]);
        $pdo->prepare("INSERT IGNORE INTO Zapisy (id_sekcji,id_uzytkownika) VALUES (?,?)")->execute([$to_sec_id, $st_id]);
    }
    redirect_with_context('login.php?view_action=manage_sections&sid=' . $sid, 'Przeniesiono studentów do sekcji.');
}

if ($action === 'batch_add_students_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid      = (int)($_POST['subject_id']  ?? 0);
    $sec_id   = (int)($_POST['section_id']  ?? 0);
    $raw      = $_POST['students_text'] ?? '';
    $uczelnia = (int)($me['id_uczelni'] ?? 1) ?: 1;

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    $lines   = explode("\n", str_replace("\r", "", $raw));
    $added   = 0;
    $skipped = 0;

    foreach ($lines as $line) {
        $line  = trim($line);
        if ($line === '') continue;
        $parts    = preg_split('/[\t;,]/', $line);
        $imie     = sanitizeString(trim($parts[0] ?? ''));
        $nazwisko = sanitizeString(trim($parts[1] ?? ''));
        $nr_alb   = sanitizeString(trim($parts[2] ?? ''));
        $pass_raw = sanitizeString(trim($parts[3] ?? ''));
        if (!$imie || !$nazwisko) { $skipped++; continue; }

        $full = "$imie $nazwisko";
        $chk  = $pdo->prepare("SELECT id_uzytkownika FROM Uzytkownicy WHERE rola='student' AND imie_i_nazwisko=?");
        $chk->execute([$full]);
        $existing = $chk->fetchColumn();

        if ($existing) {
            $uid = (int)$existing;
        } else {
            $pass = $pass_raw ?: ('student' . rand(1000, 9999));
            $pdo->prepare(
                "INSERT INTO Uzytkownicy (id_uczelni,imie_i_nazwisko,haslo,rola,nr_albumu) VALUES (?,?,?,'student',?)"
            )->execute([$uczelnia, $full, $pass, $nr_alb ?: null]);
            $uid = (int)$pdo->lastInsertId();
        }
        $pdo->prepare("INSERT IGNORE INTO Zapisy_Przedmiotow (id_studenta,id_przedmiotu) VALUES (?,?)")
            ->execute([$uid, $sid]);
        if ($sec_id > 0 && defined_section_belongs_to_subject($sec_id, $sid)) {
            $pdo->prepare("INSERT IGNORE INTO Zapisy (id_sekcji,id_uzytkownika) VALUES (?,?)")->execute([$sec_id, $uid]);
        }
        $added++;
    }
    redirect_with_context(
        'login.php?view_action=batch_add_students_view&sid=' . $sid,
        "Przetworzono: {$added} studentów, pominięto: {$skipped}."
    );
}

if ($action === 'grant_access' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = (int)($_POST['subject_id']  ?? 0);
    $tid = (int)($_POST['teacher_id']  ?? 0);
    if (!$sid || !$tid || !is_subject_owner($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    if (!teacher_belongs_to_uczelnia($tid, $me['id_uczelni'] ?? '')) {
        redirect_with_context('login.php', '', 'Prowadzący z innej uczelni.');
    }
    $perms = [
        'manage_exercises_scope' => sanitizeString(trim($_POST['manage_exercises_scope'] ?? 'none')),
        'manage_sections'        => (int)!empty($_POST['manage_sections']),
        'grading_own'            => (int)!empty($_POST['grading_own']),
        'grading_all'            => (int)!empty($_POST['grading_all']),
        'final_grades'           => (int)!empty($_POST['final_grades']),
        'announcements'          => (int)!empty($_POST['announcements']),
    ];
    $pdo->prepare("INSERT IGNORE INTO Wspolprowadzacy (id_przedmiotu,id_nauczyciela) VALUES (?,?)")->execute([$sid, $tid]);
    $pdo->prepare("DELETE FROM Uprawnienia WHERE id_przedmiotu=? AND id_nauczyciela=?")->execute([$sid, $tid]);
    $stmt = $pdo->prepare("INSERT INTO Uprawnienia (id_nauczyciela,id_przedmiotu,typ_uprawnienia) VALUES (?,?,?)");
    foreach ($perms as $k => $v) {
        $stmt->execute([$tid, $sid, "{$k}:{$v}"]);
    }
    redirect_with_context('login.php?view_action=manage_access&view=details&sid=' . $sid, 'Przyznano dostęp współprowadzącemu.');
}

if ($action === 'revoke_access') {
    $sid = (int)($_GET['sid'] ?? 0);
    $tid = (int)($_GET['tid'] ?? 0);
    if ($sid && $tid && is_subject_owner($sid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Wspolprowadzacy WHERE id_przedmiotu=? AND id_nauczyciela=?")->execute([$sid, $tid]);
        $pdo->prepare("DELETE FROM Uprawnienia WHERE id_przedmiotu=? AND id_nauczyciela=?")->execute([$sid, $tid]);
    }
    redirect_with_context('login.php?view_action=manage_access&view=details&sid=' . $sid, 'Odwołano dostęp.');
}

if ($action === 'add_announcement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = sanitizeString(trim($_POST['title']   ?? ''));
    $content = str_replace(["\r","\n"], ' ', sanitizeString(trim($_POST['content'] ?? '')));
    $target  = sanitizeString(trim($_POST['target']  ?? 'global'));
    $date    = date('Y-m-d H:i:s');

    if (!$title || !$content) {
        redirect_with_context('login.php?view_action=manage_announcements', '', 'Tytuł i treść są wymagane.');
    }
    $sid_ann    = is_numeric($target) ? (int)$target : null;
    $widocznosc = is_numeric($target) ? 'przedmiot' : $target;
    if ($sid_ann && !has_subject_access($sid_ann, (int)$me['id'])) {
        redirect_with_context('login.php?view_action=manage_announcements', '', 'Brak dostępu do przedmiotu.');
    }
    $pdo->prepare(
        "INSERT INTO Ogloszenia (id_przedmiotu,widocznosc,tytul,opis,id_uzytkownika,data_dodania)
         VALUES (?,?,?,?,?,?)"
    )->execute([$sid_ann, $widocznosc, $title, $content, (int)$me['id'], $date]);
    redirect_with_context('login.php?view_action=manage_announcements', 'Dodano ogłoszenie.');
}

if ($action === 'delete_announcement') {
    $aid = (int)($_GET['aid'] ?? 0);
    if ($aid && announcement_belongs_to_teacher($aid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Ogloszenia WHERE id_ogloszenia=?")->execute([$aid]);
    }
    redirect_with_context('login.php?view_action=manage_announcements', 'Usunięto ogłoszenie.');
}

if ($action === 'save_final_grade' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid    = (int)($_POST['subject_id']  ?? 0);
    $st_id  = (int)($_POST['student_id']  ?? 0);
    $val_r  = sanitizeString(trim($_POST['grade']   ?? ''));
    $note   = sanitizeString(trim($_POST['comment'] ?? ''));
    $date   = date('Y-m-d H:i:s');

    if (!$sid || !$st_id || $val_r === '' || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    $is_txt    = in_array(strtolower($val_r), ['zw','nb']);
    $ocena_num = (!$is_txt && is_numeric(str_replace(',','.',$val_r)))
        ? number_format((float)str_replace(',','.',$val_r), 2, '.', '') : null;
    $ocena_txt = $is_txt ? strtolower($val_r) : null;

    $pdo->prepare("DELETE FROM Oceny_Koncowe WHERE id_studenta=? AND id_przedmiotu=?")->execute([$st_id, $sid]);
    $pdo->prepare(
        "INSERT INTO Oceny_Koncowe (id_studenta,id_przedmiotu,id_nauczyciela,ocena,ocena_tekstowa,komentarz,data)
         VALUES (?,?,?,?,?,?,?)"
    )->execute([$st_id, $sid, (int)$me['id'], $ocena_num, $ocena_txt, $note, $date]);
    redirect_with_context('login.php?view_action=final_grades_view&sid=' . $sid, 'Zapisano ocenę końcową.');
}

if ($action === 'delete_final_grade') {
    $fgid = (int)($_GET['fgid'] ?? 0);
    if ($fgid) {
        $row = $pdo->prepare("SELECT id_przedmiotu FROM Oceny_Koncowe WHERE id_oceny_koncowej=?");
        $row->execute([$fgid]);
        $sid_fg = $row->fetchColumn();
        if ($sid_fg && has_subject_access((int)$sid_fg, (int)$me['id'])) {
            $pdo->prepare("DELETE FROM Oceny_Koncowe WHERE id_oceny_koncowej=?")->execute([$fgid]);
        }
    }
    redirect_with_context('login.php?view_action=final_grades_view', 'Usunięto ocenę końcową.');
}

if ($action === 'add_exemption' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid   = (int)($_POST['subject_id']  ?? 0);
    $st_id = (int)($_POST['student_id']  ?? 0);
    $eid   = (int)($_POST['exercise_id'] ?? 0);
    $date  = date('Y-m-d H:i:s');

    if (!$sid || !$st_id || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    $eid_ins = ($eid > 0) ? $eid : null;
    $chk = $pdo->prepare(
        "SELECT COUNT(*) FROM Oceny WHERE id_studenta=? AND id_przedmiotu=?
         AND (id_cwiczenia=? OR (id_cwiczenia IS NULL AND ? IS NULL)) AND ocena_tekstowa='zw'"
    );
    $chk->execute([$st_id, $sid, $eid_ins, $eid_ins]);
    if (!(int)$chk->fetchColumn()) {
        $pdo->prepare(
            "INSERT INTO Oceny (id_studenta,id_przedmiotu,id_cwiczenia,id_nauczyciela,
                                ocena,ocena_tekstowa,komentarz,data_wstawienia,terminy)
             VALUES (?,?,?,?,NULL,'zw','zwolnienie',?,?)"
        )->execute([$st_id, $sid, $eid_ins, (int)$me['id'], $date, '1']);
    }
    redirect_with_context('login.php?view_action=manage_exemptions&sid=' . $sid, 'Dodano zwolnienie.');
}

if ($action === 'remove_exemption') {
    $gid = (int)($_GET['gid'] ?? 0);
    if ($gid && grade_belongs_to_accessible_subject($gid, (int)$me['id'])) {
        $pdo->prepare("DELETE FROM Oceny WHERE id_oceny=? AND ocena_tekstowa='zw'")->execute([$gid]);
    }
    redirect_with_context('login.php?view_action=manage_exemptions', 'Usunięto zwolnienie.');
}

if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = sanitizeString(trim($_POST['old_password'] ?? ''));
    $new = sanitizeString(trim($_POST['new_password'] ?? ''));
    $u   = find_user($me['nr_albumu'], '', $old);
    if (!$u) {
        redirect_with_context('login.php?view_action=change_password_view', '', 'Nieprawidłowe stare hasło.');
    }
    if (strlen($new) < 4) {
        redirect_with_context('login.php?view_action=change_password_view', '', 'Nowe hasło za krótkie (min. 4 znaki).');
    }
    $pdo->prepare("UPDATE Uzytkownicy SET haslo=? WHERE id_uzytkownika=?")->execute([$new, (int)$me['id']]);
    $_SESSION['user']['password'] = $new;
    redirect_with_context('login.php?view_action=change_password_view', 'Hasło zostało zmienione.');
}

if ($action === 'add_teacher' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie     = sanitizeString(trim($_POST['imie']      ?? ''));
    $nazwisko = sanitizeString(trim($_POST['nazwisko']  ?? ''));
    $pass     = sanitizeString(trim($_POST['password']  ?? ''));
    $inicjaly = sanitizeString(trim($_POST['inicjaly']  ?? ''));
    $uczelnia = (int)($me['id_uczelni'] ?? 1) ?: 1;

    if (!$imie || !$nazwisko || !$pass || !$inicjaly) {
        redirect_with_context('login.php?view_action=add_teacher_view', '', 'Wypełnij wszystkie pola.');
    }
    $chk = $pdo->prepare("SELECT COUNT(*) FROM Uzytkownicy WHERE rola='teacher' AND inicjaly=?");
    $chk->execute([$inicjaly]);
    if ((int)$chk->fetchColumn() > 0) {
        redirect_with_context('login.php?view_action=add_teacher_view', '', 'Prowadzący z tymi inicjałami już istnieje.');
    }
    $full = "$imie $nazwisko";
    $pdo->prepare(
        "INSERT INTO Uzytkownicy (id_uczelni,inicjaly,haslo,imie_i_nazwisko,rola,nr_albumu) VALUES (?,?,?,?,'teacher',NULL)"
    )->execute([$uczelnia, $inicjaly, $pass, $full]);
    redirect_with_context('login.php?view_action=add_teacher_view', 'Dodano prowadzącego.');
}

if ($action === 'edit_teacher' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid      = (int)($_POST['teacher_id'] ?? 0);
    $imie     = sanitizeString(trim($_POST['imie']     ?? ''));
    $nazwisko = sanitizeString(trim($_POST['nazwisko'] ?? ''));
    $pass     = sanitizeString(trim($_POST['password'] ?? ''));
    $inicjaly = sanitizeString(trim($_POST['inicjaly'] ?? ''));

    if (!$tid || $tid === (int)$me['id'] || !teacher_belongs_to_uczelnia($tid, $me['id_uczelni'] ?? '')) {
        redirect_with_context('login.php?view_action=add_teacher_view', '', 'Brak uprawnień.');
    }
    $full = "$imie $nazwisko";
    if ($pass !== '') {
        $pdo->prepare("UPDATE Uzytkownicy SET imie_i_nazwisko=?,inicjaly=?,haslo=? WHERE id_uzytkownika=? AND rola='teacher'")
            ->execute([$full, $inicjaly, $pass, $tid]);
    } else {
        $pdo->prepare("UPDATE Uzytkownicy SET imie_i_nazwisko=?,inicjaly=? WHERE id_uzytkownika=? AND rola='teacher'")
            ->execute([$full, $inicjaly, $tid]);
    }
    redirect_with_context('login.php?view_action=add_teacher_view', 'Zaktualizowano dane prowadzącego.');
}

if ($action === 'add_application' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid      = (int)($_POST['subject_id']  ?? 0);
    $st_id    = (int)($_POST['student_id']  ?? 0);
    $eid      = (int)($_POST['exercise_id'] ?? 0);
    $grade_id = (int)($_POST['grade_id']    ?? 0);
    $reason   = sanitizeString(trim($_POST['reason'] ?? ''));
    $date     = date('Y-m-d H:i:s');

    if (!$sid || !$st_id || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak danych lub uprawnień.');
    }
    if (!_table_exists('Podania')) redirect_with_context('login.php', '', 'Tabela Podania nie istnieje.');
    $pdo->prepare(
        "INSERT INTO Podania (id_studenta,id_przedmiotu,id_cwiczenia,id_oceny,uzasadnienie,data,status)
         VALUES (?,?,?,?,?,?,'oczekuje')"
    )->execute([$st_id, $sid, $eid ?: null, $grade_id ?: null, $reason, $date]);
    redirect_with_context('login.php?view_action=manage_applications&sid=' . $sid, 'Dodano podanie.');
}

if ($action === 'resolve_application' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = (int)($_POST['application_id'] ?? 0);
    $status = sanitizeString(trim($_POST['status'] ?? ''));
    if ($app_id && in_array($status, ['rozpatrzone','odrzucone']) && _table_exists('Podania')) {
        $pdo->prepare("UPDATE Podania SET status=? WHERE id_podania=?")->execute([$status, $app_id]);
    }
    redirect_with_context('login.php?view_action=manage_applications', 'Podanie zaktualizowane.');
}

if ($action === 'delete_application') {
    $app_id = (int)($_GET['app_id'] ?? 0);
    if ($app_id && _table_exists('Podania')) {
        $pdo->prepare("DELETE FROM Podania WHERE id_podania=?")->execute([$app_id]);
    }
    redirect_with_context('login.php?view_action=manage_applications', 'Usunięto podanie.');
}

if ($action === 'save_harmonogram' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid     = (int)($_POST['subject_id']  ?? 0);
    $sec_id  = (int)($_POST['section_id']  ?? 0);
    $eid     = (int)($_POST['exercise_id'] ?? 0);
    $terminy = sanitizeString(trim($_POST['terminy'] ?? ''));

    if (!$sid || !has_subject_access($sid, (int)$me['id'])) {
        redirect_with_context('login.php', '', 'Brak uprawnień.');
    }
    if (!_table_exists('Harmonogramy')) redirect_with_context('login.php', '', 'Tabela Harmonogramy nie istnieje.');
    $pdo->prepare(
        "INSERT INTO Harmonogramy (id_cwiczenia,id_sekcji,terminy) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE terminy=VALUES(terminy)"
    )->execute([$eid, $sec_id, $terminy]);
    redirect_with_context('login.php?view_action=harmonogram&sid=' . $sid, 'Zapisano harmonogram.');
}

if ($action === 'delete_harmonogram') {
    $hid = (int)($_GET['hid'] ?? 0);
    if ($hid && _table_exists('Harmonogramy')) {
        $pdo->prepare("DELETE FROM Harmonogramy WHERE id_harmonogramu=?")->execute([$hid]);
    }
    redirect_with_context('login.php?view_action=harmonogram', 'Usunięto wpis harmonogramu.');
}

if ($action === 'add_uczelnia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeString(trim($_POST['name'] ?? ''));
    if ($name) {
        $pdo->prepare("INSERT INTO Uczelnia (nazwa) VALUES (?)")->execute([$name]);
    }
    redirect_with_context('login.php', 'Dodano uczelnię.');
}

