<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Panel Prowadzącego (Widok Kompaktowy)</title>
    <link rel="stylesheet" href="compact_style.css">
    <style>
        .th-tooltip { position: relative; cursor: help; }
        .th-tooltip .tooltip-content {
            display: none; position: absolute; bottom: 100%; left: 50%;
            transform: translateX(-50%); background: #333; color: #fff;
            padding: 8px; border-radius: 4px; white-space: nowrap;
            z-index: 100; font-size: 12px; font-weight: normal; min-width: 200px; white-space: normal;
        }
        .th-tooltip:hover .tooltip-content { display: block; }
        .draggable-row.over { border: 2px dashed #000; background-color: #f0f0f0; }
        .draggable-row.drag-active { opacity: 0.4; }
        .grade-cell { cursor: pointer; min-width: 40px; transition: background 0.2s; }
        .grade-cell:hover { background-color: #e0e0e0; }
        #gradeEditOverlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.35); z-index:9000; }
        #gradeEditModal { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border:2px solid #555; padding:16px; z-index:9001; min-width:340px; max-width:95vw; max-height:90vh; overflow-y:auto; box-shadow:0 4px 24px rgba(0,0,0,0.25); }
        .grades-table-wrap { overflow-x:auto; }
        .grades-table { border-collapse:collapse; }
        .grades-table .col-sticky-student { position:sticky; left:0; z-index:2; background:#fff; }
        .grades-table .col-sticky-add { position:sticky; left:0; z-index:2; background:#fff; }
        .grades-table thead .col-sticky-student,
        .grades-table thead .col-sticky-add { z-index:3; background:#ddd; }
        .grades-table tr.n0 .col-sticky-student,
        .grades-table tr.n0 .col-sticky-add { background:#f9f9f9; }
        .grades-table tr.n1 .col-sticky-student,
        .grades-table tr.n1 .col-sticky-add { background:#fff; }
        .btn-sm { padding: 2px 8px; font-size: 13px; cursor: pointer; background: #eee; border: 1px solid #ccc; }
        .progress-bar-outer { background:#ddd; width:100%; height:16px; border:1px solid #ccc; }
        .progress-bar-inner { height:100%; text-align:center; color:#fff; font-size:11px; line-height:16px; text-shadow:1px 1px 1px #000; }
        .pagination { margin: 10px 0; }
        .pagination a { display:inline-block; margin:1px; padding:3px 7px; border:1px solid #999; text-decoration:none; color:#333; }
        .pagination a.active { background:#ABABAB; font-weight:bold; }
        .pagination a.disabled { color:#ccc; pointer-events:none; }
    </style>
    <script>
        function toggleSection(id) {
            var el = document.getElementById(id);
            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }
        function toggleDesc(id) {
            var shortSpan = document.getElementById('desc_short_' + id);
            var fullSpan = document.getElementById('desc_full_' + id);
            if (shortSpan.style.display === 'none') {
                shortSpan.style.display = 'inline';
                fullSpan.style.display = 'none';
            } else {
                shortSpan.style.display = 'none';
                fullSpan.style.display = 'inline';
            }
        }
        function toggleAllStudents(source) {
            var checkboxes = document.getElementsByClassName('st-chk');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function toggleAll(source, className) {
            var checkboxes = document.getElementsByClassName(className);
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function closeSection(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        }
        function openGradeEditModal(cellId) {
            var src = document.getElementById(cellId);
            if (!src) return;
            var modal = document.getElementById('gradeEditModal');
            modal.innerHTML = src.innerHTML;
            document.getElementById('gradeEditOverlay').style.display = 'block';
            modal.style.display = 'block';
            var closeBtn = modal.querySelector('.grade-modal-close');
            if (closeBtn) closeBtn.onclick = closeGradeEditModal;
        }
        // Sticky columns – ustaw left dla kolumny "Dodaj" na podstawie szerokości kolumny "Student"
        document.addEventListener('DOMContentLoaded', function() {
            var table = document.querySelector('.grades-table');
            if (!table) return;
            var studentTh = table.querySelector('thead .col-sticky-student');
            if (!studentTh) return;
            var w = studentTh.offsetWidth;
            var style = document.createElement('style');
            style.textContent = '.grades-table .col-sticky-add { left: ' + w + 'px; }';
            document.head.appendChild(style);

            // Upewnij się że modal jest ukryty przy starcie strony
            var o = document.getElementById('gradeEditOverlay');
            var m = document.getElementById('gradeEditModal');
            if (o) o.style.display = 'none';
            if (m) { m.style.display = 'none'; m.innerHTML = ''; }
        });
        function closeGradeEditModal() {
            document.getElementById('gradeEditOverlay').style.display = 'none';
            document.getElementById('gradeEditModal').style.display = 'none';
        }
        function openRolloverForm(sid) {
            var el = document.getElementById('rolloverForm_' + sid);
            if (el) {
                el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
            }
        }

        /* Obsługa okna dodawania/edycji ocen */
        function openAddGradeInline(stId) {
            toggleSection('addGradeInline_' + stId);
        }
        function openEditGradeInline(stId, exId) {
            toggleSection('editGradeInline_' + stId + '_' + exId);
        }
    </script>
</head>
<body>

<?php if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher'): ?>
    <div style="margin: 50px auto; width: 300px; border: 1px solid black; padding: 20px;">
        <h3>Logowanie (Widok Kompaktowy)</h3>
        <form action="login.php" method="POST">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
                <tr><td align="right">Inicjały:</td><td><input type="text" name="imie" size="16" maxlength="16"></td></tr>
                <tr><td align="right">Hasło:</td><td><input type="password" name="password" size="8" maxlength="8"></td></tr>
            </table>
            <div style="text-align:center; margin-top:10px;">
                <input type="submit" value="Zaloguj się">
            </div>
        </form>
        <?php if (!empty($login_err)) echo "<div class='error-msg'>$login_err</div>"; ?>
    </div>
<?php else: ?>

<div class="compact-layout">

    <!-- ===================== SIDEBAR ===================== -->
    <div class="compact-sidebar">
        <h3>Panel Sterowania</h3>
        <div>
            Zalogowany: <b><?php echo htmlspecialchars("{$me['imie']} {$me['nazwisko']}"); ?></b><br>
            Uczelnia: <?php echo htmlspecialchars($viewData['user_uczelnia'] ?? '-'); ?>
        </div>
        <hr>
        <div>
            <a href="login.php?action=toggle_view<?php echo (!empty($_SERVER['QUERY_STRING']) ? '&' . str_replace('action=toggle_view', '', $_SERVER['QUERY_STRING']) : ''); ?>">
                [Przełącz na Widok Nowoczesny]
            </a>
            <br><br>
            <a href="login.php">[Menu Główne]</a>
            &nbsp;|&nbsp;
            <a href="login.php?action=logout">[Wyloguj]</a>
        </div>
        <hr>
        <h4>Menu Główne</h4>
        <ul style="padding-left: 20px;">
            <?php
            $menu_items = [
                'add_subject'              => '1. Przedmioty',
                'manage_sections'          => '2. Sekcje studentów',
                'batch_add_students_view'  => '3. Dodawanie studentów',
                'add_student'              => '4. Baza studentów',
                'manage_exercises'         => '5. Ćwiczenia',
                'add_grade'                => '6. Oceny',
                'manage_reports'           => '7. Sprawozdania',
                'change_password_view'     => '8. Zmiana hasła',
                'add_teacher_view'         => '9. Nowy prowadzący',
                'manage_exercise_att'      => '10. Obecności',
                'statistics'               => '11. Statystyki',
                'manage_deadlines'         => '12. Wymagania zaliczenia ćwiczeń',
                'manage_access'            => '13. Współprowadzący',
                'batch_grading'            => '14. Oceny seryjne',
                'export_view'              => '15. Eksport (CSV/PDF)',
                'manage_announcements'     => '16. Ogłoszenia',
                'progress_view'            => '17. Ranking postępów',
                'manage_exemptions'        => '18. Zwolnienia',
                'student_grades_view'      => '19. Przegląd ocen studenta',
                'enforce_tasks'            => '20. Egzekwowanie zadań',
                'student_logs_view'        => '21. Logi studentów',
                'final_grades_view'        => '22. Oceny końcowe',
                'manage_applications'      => '23. Podania',
                'edit_reports'             => '24. Edycja sprawozdań',
				'szukaj_studenta'          => '25. Szukaj studenta',
                'harmonogram'              => '26. Harmonogram',
                'wydruk_sprawozdan'        => '27. Wydruk sprawozdań',
            ];
            foreach ($menu_items as $action_key => $description) {
                $active = ($view_action === $action_key) ? ' style="font-weight:bold;"' : '';
                echo "<li{$active}><a href='login.php?view_action={$action_key}'>{$description}</a></li>";
            }
            ?>
        </ul>
    </div>

    <!-- ===================== CONTENT ===================== -->
    <div class="compact-content">
        <?php
        if (!empty($msg)) echo "<div class='success-msg'>" . htmlspecialchars($msg) . "</div>";
        if (!empty($err)) echo "<div class='error-msg'>" . htmlspecialchars($err) . "</div>";
        ?>
        <script>
        (function() {
            var redirectPending = sessionStorage.getItem('after_report_redirect');
            var hasMsgInUrl = (window.location.search.indexOf('msg=') !== -1);
            var hasViewAction = (window.location.search.indexOf('view_action=') !== -1);
            if (redirectPending === 'manage_reports' && hasMsgInUrl && !hasViewAction) {
                sessionStorage.removeItem('after_report_redirect');
                window.location.replace('login.php?view_action=manage_reports');
            }
        })();
        </script>

        <!-- ============================================================ -->
        <!--  1. PRZEDMIOTY                                               -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'add_subject'): ?>
        <h3>Przedmioty Aktywne</h3>
        <button onclick="toggleSection('addSubjectForm')" style="margin-bottom:8px;">[+] Dodaj Przedmiot</button>
        <div id="addSubjectForm" class="hidden-section">
            <form method="post" action="login.php?action=add_subject&view_action=add_subject">
                Nazwa: <input type="text" name="name" required><br>
                Rok: <input type="text" name="rok" size="7" required placeholder="2024/25"><br>
                Rodzaj: <select name="type">
                    <option value="laboratorium">Laboratorium</option>
                    <option value="egzamin">Egzamin</option>
                </select><br>
                <input type="submit" value="Dodaj">
            </form>
        </div>
        <br>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Nazwa</th><th>Rok</th><th>Rodzaj</th><th>Rola</th><th>Sekcje/Stud.</th><th>Akcje</th></tr>
            <?php
            $i = 0;
            $stats_map = $viewData['subject_stats'] ?? [];
            foreach ($subs as $s):
                $p = explode(';', $s, 6);
                $sid = intval($p[0]);
                $owner_id = intval($p[3]);
                $is_owner = ($owner_id === intval($me['id']));
                $sec_c = $stats_map[$sid]['sec_count'] ?? 0;
                $stud_c = $stats_map[$sid]['stud_count'] ?? 0;
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $type_label = (($p[5] ?? '') === 'egzamin') ? 'Egzamin' : 'Laboratorium';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($p[1])?></td>
                <td><?=htmlspecialchars($p[2])?></td>
                <td><?=htmlspecialchars($type_label)?></td>
                <td><?=($is_owner ? 'Właściciel' : 'Współ.')?></td>
                <td align="center"><?=$sec_c?>/<?=$stud_c?></td>
                <td>
                    <a href="login.php?view=subject&sid=<?=$sid?>&view_action=add_grade">Oceny</a>
                    <?php if ($is_owner): ?>
                     | <a href="login.php?view_action=edit_subject_form&sid=<?=$sid?>">Edytuj</a>
                     | <a href="login.php?view_action=manage_exercises&view=list&sid=<?=$sid?>">Ćw.</a>
                     | <a href="login.php?view_action=manage_sections&sid=<?=$sid?>">Sekcje</a>
                     | <a href="login.php?view_action=manage_access&view=details&sid=<?=$sid?>">Dostęp</a>
                     | <a href="#" onclick="toggleSection('rolloverForm_<?=$sid?>'); return false;">Nowy rocznik</a>
                     | <a href="login.php?action=archive_subject&sid=<?=$sid?>" onclick="return confirm('Archiwizować?')">Archiwizuj</a>
                     | <a href="login.php?action=delete_subject&sid=<?=$sid?>" onclick="return confirm('Usunąć trwale?')" style="color:red;">Usuń</a>
                    <?php else: ?>
                     | <span style="color:#aaa;">brak upr.</span>
                    <?php endif; ?>
                    <?php if ($is_owner): ?>
                    <div id="rolloverForm_<?=$sid?>" class="hidden-section">
                        <p><b>Utwórz nowy rocznik</b><br>
                        Przeniesienie do archiwum z kopiowaniem ćwiczeń (bez studentów).<br>Czy potwierdzasz?</p>
                        <form method="post" action="login.php?action=rollover_subject">
                            <input type="hidden" name="sid" value="<?=$sid?>">
                            <input type="submit" value="Potwierdzam">
                            <button type="button" onclick="toggleSection('rolloverForm_<?=$sid?>')">Anuluj</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php
        if (isset($archived_subs) && count($archived_subs) > 0):
        ?>
        <hr>
        <h4>Archiwum Przedmiotów</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4" style="background-color:#f4f4f4; color:#666;">
            <tr><th>Nazwa</th><th>Rok</th><th>Akcje</th></tr>
            <?php $j = 0; foreach ($archived_subs as $as):
                $p = explode(';', $as, 5); $sid = intval($p[0]); $cls = ($j++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($p[1])?></td>
                <td><?=htmlspecialchars($p[2])?></td>
                <td>
                    <a href="login.php?action=restore_subject&sid=<?=$sid?>">Przywróć</a> |
                    <a href="login.php?view_action=edit_subject_form&sid=<?=$sid?>">Edytuj</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; // add_subject ?>


        <!-- ============================================================ -->
        <!--  EDYCJA PRZEDMIOTU                                           -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'edit_subject_form' && isset($_GET['sid'])):
            $s_data = $viewData['subject_to_edit'] ?? null;
            if ($s_data):
        ?>
        <A HREF="login.php?view_action=add_subject"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Edycja: <?=htmlspecialchars($s_data[1])?></h3>
        <form method="post" action="login.php?action=edit_subject">
            <input type="hidden" name="sid" value="<?=intval($s_data[0])?>">
            Nazwa: <input type="text" name="name" value="<?=htmlspecialchars($s_data[1])?>"><br>
            Rok: <input type="text" name="rok" size="7" value="<?=htmlspecialchars($s_data[2])?>"><br>
            Rodzaj: <select name="type">
                <option value="laboratorium" <?=(($s_data[5] ?? '')==='laboratorium'?'selected':'')?>>Laboratorium</option>
                <option value="egzamin" <?=(($s_data[5] ?? '')==='egzamin'?'selected':'')?>>Egzamin</option>
            </select><br>
            <input type="submit" value="Zapisz zmiany">
        </form>
        <?php else: echo "<p style='color:red;'>Nie znaleziono przedmiotu lub brak uprawnień.</p>"; endif; ?>
        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  4. BAZA STUDENTÓW                                           -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'add_student'):
            $q_student = trim($_GET['q_student'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 20;
            // Użyj listy studentów przefiltrowanej do uczelni zalogowanego prowadzącego
            $students_base = $viewData['students_filtered'] ?? $students;
            $filtered = [];
            if ($q_student !== '') {
                foreach ($students_base as $s) {
                    if (stripos($s[1] . ' ' . $s[2] . ' ' . $s[5], $q_student) !== false) $filtered[] = $s;
                }
            } else {
                $filtered = $students_base;
            }
            $total = count($filtered);
            $total_pages = max(1, ceil($total / $perPage));
            $page = min($page, $total_pages);
            $show = array_slice($filtered, ($page - 1) * $perPage, $perPage);
        ?>
        <h3>Baza Studentów (<?=htmlspecialchars($viewData['user_uczelnia'] ?? 'Uczelnia')?>)</h3>
        <form method="get" action="login.php" style="margin-bottom:8px;">
            <input type="hidden" name="view_action" value="add_student">
            Szukaj: <input type="text" name="q_student" value="<?=htmlspecialchars($q_student)?>" placeholder="nazwisko, imię lub album">
            <input type="submit" value="Szukaj">
            <?php if ($q_student !== ''): ?>
                <a href="login.php?view_action=add_student">[Wyczyść]</a>
            <?php endif; ?>
        </form>
        <button onclick="toggleSection('addStudentForm')" style="margin-bottom:8px;">[+] Dodaj Studenta</button>
        <div id="addStudentForm" class="hidden-section">
            <form method="post" action="login.php?action=add_student&view_action=add_student">
                Imię: <input type="text" name="imie" required><br>
                Nazwisko: <input type="text" name="nazwisko" required><br>
                Hasło: <input type="text" name="password" required><br>
                Nr albumu: <input type="text" name="nr_albumu" required><br>
                <input type="submit" value="Dodaj studenta">
            </form>
        </div>
        <br>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>ID</th><th>Imię</th><th>Nazwisko</th><th>Nr albumu</th><th>Akcje</th></tr>
            <?php if (count($show) === 0) echo "<tr><td colspan='5'>Brak studentów spełniających kryteria.</td></tr>"; ?>
            <?php $i = 0; foreach ($show as $s): $cls = ($i++ % 2 == 0) ? 'n0' : 'n1'; ?>
            <tr class="<?=$cls?>">
                <td><?=$s[4]?></td>
                <td><?=htmlspecialchars($s[1])?></td>
                <td><?=htmlspecialchars($s[2])?></td>
                <td><?=htmlspecialchars($s[5])?></td>
                <td>
                    <a href="login.php?view_action=edit_student_form&sid=<?=$s[4]?>">Edytuj</a> |
                    <a href="login.php?action=delete_student&sid=<?=$s[4]?>" onclick="return confirm('Usunąć studenta?')" style="color:red;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <a href="login.php?view_action=add_student&page=<?=max(1,$page-1)?>&q_student=<?=urlencode($q_student)?>" class="<?=($page<=1?'disabled':'')?>">&laquo;</a>
            <?php for ($pg = 1; $pg <= $total_pages; $pg++): ?>
                <a href="login.php?view_action=add_student&page=<?=$pg?>&q_student=<?=urlencode($q_student)?>" class="<?=($pg===$page?'active':'')?>"><?=$pg?></a>
            <?php endfor; ?>
            <a href="login.php?view_action=add_student&page=<?=min($total_pages,$page+1)?>&q_student=<?=urlencode($q_student)?>" class="<?=($page>=$total_pages?'disabled':'')?>">&raquo;</a>
        </div>
        <small>Pokazano <?=($total>0?($page-1)*$perPage+1:0)?>-<?=min($page*$perPage,$total)?> z <?=$total?></small>
        <?php endif; ?>
        <?php endif; // add_student ?>


        <!-- ============================================================ -->
        <!--  EDYCJA STUDENTA                                             -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'edit_student_form' && isset($_GET['sid'])):
            $st_data = $viewData['st_data'] ?? null;
            if ($st_data):
        ?>
        <A HREF="login.php?view_action=add_student"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Edycja studenta: <?=htmlspecialchars($st_data[1] . ' ' . $st_data[2])?></h3>
        <form method="post" action="login.php?action=edit_student">
            <input type="hidden" name="sid" value="<?=intval($_GET['sid'])?>">
            Imię: <input type="text" name="imie" value="<?=htmlspecialchars($st_data[1])?>"><br>
            Nazwisko: <input type="text" name="nazwisko" value="<?=htmlspecialchars($st_data[2])?>"><br>
            Hasło (puste = bez zmian): <input type="text" name="password" placeholder="nowe hasło..."><br>
            Nr albumu: <input type="text" name="nr_albumu" value="<?=htmlspecialchars($st_data[5]??'')?>"><br>
            <input type="submit" value="Zapisz zmiany">
        </form>
        <?php else: echo "<p>Nie znaleziono studenta.</p>"; endif; ?>
        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  9. NOWY PROWADZĄCY                                          -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'add_teacher_view'): ?>
        <h3>Dodaj Nowego Prowadzącego</h3>
        <form method="post" action="login.php?action=add_teacher">
            Imię: <input type="text" name="imie"><br>
            Nazwisko: <input type="text" name="nazwisko"><br>
            Hasło: <input type="text" name="password"><br>
            <input type="submit" value="Dodaj prowadzącego">
        </form>
        <hr>
        <h4>Lista prowadzących</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Imię i Nazwisko</th><th>Inicjały</th></tr>
            <?php $t_idx = 0; foreach ($teachers_list as $teacher):
                $t_class = ($t_idx++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$t_class?>">
                <td><?=htmlspecialchars($teacher['name'])?></td>
                <td align="center"><?=htmlspecialchars($teacher['inicjaly'])?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($teachers_list) === 0) echo "<tr><td colspan='2'>Brak prowadzących.</td></tr>"; ?>
        </table>
        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  8. ZMIANA HASŁA                                             -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'change_password_view'): ?>
        <h3>Zmień hasło (prowadzący)</h3>
        <form method="post" action="login.php?action=change_password&view_action=change_password_view">
            Stare: <input type="password" name="old"><br>
            Nowe: <input type="password" name="new"><br>
            <input type="submit" value="Zmień hasło">
        </form>
        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  5. ĆWICZENIA                                                -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_exercises' && empty($view)):
            if (isset($viewData['error_perm'])) { echo $viewData['error_perm']; }
            else{
        ?>
        <h3>Ćwiczenia – Wybierz przedmiot</h3>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>ID</th><th>Nazwa</th><th>Rok</th><th>Akcje</th></tr>
            <?php $i = 0; foreach ($subs as $s): $p = explode(';', $s, 4); $cls = ($i++ % 2 == 0) ? 'n0' : 'n1'; ?>
            <tr class="<?=$cls?>">
                <td><?=$p[0]?></td><td><?=htmlspecialchars($p[1])?></td><td><?=htmlspecialchars($p[2])?></td>
                <td><a href="login.php?view_action=manage_exercises&view=list&sid=<?=$p[0]?>">Wejdź</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($i === 0) echo "<tr><td colspan='4'>Brak dostępnych przedmiotów.</td></tr>"; ?>
        </table>
			<?php } ?>
        <?php endif; ?>

        <?php if ($view_action === 'manage_exercises' && $view === 'list' && isset($_GET['sid'])):
            $subLine = $viewData['subLine'] ?? null;
            $my_exercises = $viewData['my_exercises'] ?? [];
            $ex_scope_view = $viewData['manage_exercises_scope'] ?? 'all';
            $sid = intval($_GET['sid']);
            if ($subLine):
        ?>
        <A HREF="login.php?view_action=manage_exercises"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Ćwiczenia: <?=htmlspecialchars($subLine[1])?><?=($ex_scope_view === 'own' ? ' <small style="color:#888;">(widok: tylko własne ćwiczenia)</small>' : '')?></h3>
        <?php if ($ex_scope_view === 'all'): ?>
        <button onclick="toggleSection('addExForm')" style="margin-bottom:8px;">[+] Dodaj Ćwiczenie</button>
        <div id="addExForm" class="hidden-section">
            <form method="post" action="login.php?action=add_exercise">
                <input type="hidden" name="subject_id" value="<?=$sid?>">
                Nazwa: <input type="text" name="cw_name" required><br>
                Waga: <select name="waga" required>
                    <?php for ($w = 1; $w <= 10; $w++) echo "<option value='$w'>$w</option>"; ?>
                </select><br>
                Prowadzący: <select name="teacher_id">
                    <option value="0">-- Brak --</option>
                    <?php foreach ($teachers_list as $t) echo "<option value='{$t['id']}'" . ($t['id'] == $me['id'] ? ' selected' : '') . ">" . htmlspecialchars($t['name']) . "</option>"; ?>
                </select><br>
                Opis: <textarea name="cw_opis" rows="3" style="width:95%;"></textarea><br>
                <input type="submit" value="Dodaj ćwiczenie">
            </form>
        </div>
        <?php endif; ?>
        <br>
        <?php if (count($my_exercises) === 0): ?>
            <p>Brak ćwiczeń<?=($ex_scope_view === 'own' ? ' przypisanych do Ciebie' : '')?> w tym przedmiocie.</p>
        <?php else: ?>
        <form method="post" action="login.php?action=reorder_exercises" id="reorderForm">
            <input type="hidden" name="sid" value="<?=$sid?>">
            <input type="hidden" name="exercise_order" id="exercise_order_input">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4" id="exercisesTable">
                <thead>
                <tr>
                    <th style="width:25px;">#</th>
                    <th>Nazwa</th>
                    <th>Opis</th>
                    <th>Waga</th>
                    <th>Prowadzący</th>
                    <th>Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 0; foreach ($my_exercises as $ex):
                    $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                    $eid = intval($ex[0]);
                    $fullDesc = htmlspecialchars($ex[2]);
                    $weight = htmlspecialchars($ex[3] ?? '-');
                    $tid = intval($ex[4] ?? 0);
                    $t_name = htmlspecialchars($viewData['teachers_surnames'][$tid] ?? '-');
                    $dotPos = strpos($fullDesc, '.');
                    $descHtml = $fullDesc;
                    if ($dotPos !== false && strlen($fullDesc) > $dotPos + 1) {
                        $short = substr($fullDesc, 0, $dotPos + 1);
                        $descHtml = "<span id='desc_short_{$eid}'>{$short} <a href='#' onclick='toggleDesc({$eid}); return false;'>[więcej]</a></span>"
                                  . "<span id='desc_full_{$eid}' style='display:none;'>{$fullDesc} <a href='#' onclick='toggleDesc({$eid}); return false;'>[mniej]</a></span>";
                    }
                ?>
                <tr class="<?=$cls?> draggable-row" draggable="true" data-eid="<?=$eid?>" style="cursor:grab;">
                    <td align="center" style="cursor:move; color:#888;">&#9776;</td>
                    <td><?=htmlspecialchars($ex[1])?></td>
                    <td style="max-width:300px;"><?=$descHtml?></td>
                    <td align="center"><?=$weight?></td>
                    <td align="center"><?=$t_name?></td>
                    <td>
                        <a href="login.php?view_action=manage_exercises&view=edit_form&sid=<?=$sid?>&eid=<?=$eid?>">Edytuj</a> |
                        <a href="login.php?action=delete_exercise&eid=<?=$eid?>&sid=<?=$sid?>" onclick="return confirm('Usunąć ćwiczenie?')" style="color:red;">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($my_exercises) > 1 && $ex_scope_view === 'all'): ?>
            <div style="text-align:right; margin-top:5px;">
                <input type="submit" value="Zapisz kolejność">
            </div>
            <?php endif; ?>
        </form>
        <?php endif; ?>
        <?php else: echo "<p style='color:red;'>Brak uprawnień lub przedmiot nie istnieje.</p>"; endif; ?>
        <?php endif; // manage_exercises list ?>

        <?php if ($view_action === 'manage_exercises' && $view === 'edit_form' && isset($_GET['eid']) && isset($_GET['sid'])):
            $exData = $viewData['exercise_to_edit'] ?? null;
            $sid = intval($_GET['sid']);
            if ($exData):
        ?>
        <A HREF="login.php?view_action=manage_exercises&view=list&sid=<?=$sid?>"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Edycja ćwiczenia: <?=htmlspecialchars($exData[1])?></h3>
        <form method="post" action="login.php?action=edit_exercise">
            <input type="hidden" name="eid" value="<?=intval($exData[0])?>">
            <input type="hidden" name="sid" value="<?=$sid?>">
            Nazwa: <input type="text" name="cw_name" value="<?=htmlspecialchars($exData[1])?>"><br>
            Waga: <select name="waga" required>
                <?php for ($w = 1; $w <= 10; $w++) echo "<option value='$w'" . ($w == $exData[3] ? ' selected' : '') . ">$w</option>"; ?>
            </select><br>
            Prowadzący: <select name="teacher_id">
                <option value="0">-- Brak --</option>
                <?php $ct = isset($exData[4]) ? intval($exData[4]) : 0;
                foreach ($teachers_list as $t) echo "<option value='{$t['id']}'" . ($t['id'] == $ct ? ' selected' : '') . ">" . htmlspecialchars($t['name']) . "</option>"; ?>
            </select><br>
            Opis: <textarea name="cw_opis" rows="3" style="width:95%;"><?=htmlspecialchars($exData[2])?></textarea><br>
            <input type="submit" value="Zapisz zmiany">
        </form>
        <?php else: echo "<p>Nie znaleziono ćwiczenia.</p>"; endif; ?>
        <?php endif; ?>

        <!-- Drag & Drop script dla ćwiczeń -->
        <?php if ($view_action === 'manage_exercises' && $view === 'list'): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('exercisesTable');
            if (!table) return;
            let dragSrcEl = null;
            function handleDragStart(e) { dragSrcEl = this; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/html', this.innerHTML); this.classList.add('drag-active'); }
            function handleDragOver(e) { if (e.preventDefault) e.preventDefault(); e.dataTransfer.dropEffect = 'move'; return false; }
            function handleDragEnter(e) { this.classList.add('over'); }
            function handleDragLeave(e) { this.classList.remove('over'); }
            function handleDrop(e) {
                if (e.stopPropagation) e.stopPropagation();
                if (dragSrcEl !== this) {
                    let tbody = table.querySelector('tbody');
                    let rows = Array.from(tbody.querySelectorAll('tr.draggable-row'));
                    let srcIndex = rows.indexOf(dragSrcEl), targetIndex = rows.indexOf(this);
                    if (srcIndex < targetIndex) tbody.insertBefore(dragSrcEl, this.nextSibling);
                    else tbody.insertBefore(dragSrcEl, this);
                    updateHiddenInput();
                }
                return false;
            }
            function handleDragEnd(e) { this.classList.remove('drag-active'); table.querySelectorAll('.draggable-row').forEach(r => r.classList.remove('over')); }
            function updateHiddenInput() {
                let ids = [];
                table.querySelectorAll('tr.draggable-row').forEach(r => ids.push(r.getAttribute('data-eid')));
                document.getElementById('exercise_order_input').value = ids.join(',');
            }
            table.querySelectorAll('.draggable-row').forEach(function(row) {
                row.addEventListener('dragstart', handleDragStart, false);
                row.addEventListener('dragenter', handleDragEnter, false);
                row.addEventListener('dragover', handleDragOver, false);
                row.addEventListener('dragleave', handleDragLeave, false);
                row.addEventListener('drop', handleDrop, false);
                row.addEventListener('dragend', handleDragEnd, false);
            });
            updateHiddenInput();
        });
        </script>
        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  6. OCENY                                                     -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'add_grade' && empty($view)): ?>
        <h3>Oceny – Wybierz Przedmiot</h3>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>ID</th><th>Nazwa</th><th>Rok</th><th>Akcje</th></tr>
            <?php $i = 0; foreach ($subs as $s): $p = explode(';', $s, 4); $cls = ($i++ % 2 == 0) ? 'n0' : 'n1'; ?>
            <tr class="<?=$cls?>">
                <td><?=$p[0]?></td><td><?=htmlspecialchars($p[1])?></td><td><?=htmlspecialchars($p[2])?></td>
                <td><a href="login.php?view=subject&sid=<?=$p[0]?>&view_action=add_grade">Wejdź</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if ($view_action === 'add_grade' && $view === 'subject' && isset($_GET['sid'])):
            $sid = intval($_GET['sid']);
            $subLine = null;
            foreach ($subs as $s_check) { $p = explode(';', $s_check, 4); if (intval($p[0]) === $sid) { $subLine = $p; break; } }

            if ($subLine):
                $subject_name = $subLine[1];
                $sub_owner_id = intval($subLine[3]);
                $my_id = intval($me['id']);

                $enrolled_ids = [];
                foreach (read_lines($enrollFile) as $l) { $p = explode(';', $l); if (intval($p[1]) === $sid) $enrolled_ids[] = intval($p[0]); }

                // Budujemy mapę definicji ćwiczeń (eid => dane)
                $exercises_defs_map = [];
                foreach (read_lines($exercisesFile) as $l) {
                    $p = explode(';', $l);
                    $exercises_defs_map[intval($p[0])] = $p;
                }

                // Iterujemy po subjectExerciseFile zachowując kolejność jak na liście ćwiczeń
                $subject_exercises = [];
                foreach (read_lines($subjectExerciseFile) as $l) {
                    $p = explode(';', $l);
                    if (intval($p[0]) !== $sid) continue;
                    $eid = intval($p[1]);
                    if (!isset($exercises_defs_map[$eid])) continue;
                    $ex = $exercises_defs_map[$eid];
                    $ex_teacher_id = isset($ex[4]) ? intval($ex[4]) : 0;
                    if ($sub_owner_id === $my_id || $ex_teacher_id === $my_id) $subject_exercises[] = $ex;
                }

                $grades_map = [];
                foreach (read_lines($gradesFile) as $l) {
                    $p = explode(';', $l);
                    if (count($p) >= 9 && intval($p[2]) === $sid) {
                        $st_id = intval($p[1]); $eid_curr = intval($p[7]); $term = intval($p[8]);
                        $grades_map[$st_id][$eid_curr][$term] = ['val' => $p[4], 'note' => $p[5] ?? ''];
                    }
                }

                $defined_sections = $viewData['defined_sections'] ?? [];
                $sel_sec_id = $viewData['selected_sec_id'] ?? 0;
                $student_section_map = $viewData['student_section_map'] ?? [];

                // Wyznacz eid-y, dla których przynajmniej jeden student ma ocenę (nie licząc zwolnień)
                $eids_with_grades = [];
                foreach ($subject_exercises as $ex_chk) {
                    $eid_chk = intval($ex_chk[0]);
                    foreach ($grades_map as $stId_chk => $st_ex_map) {
                        if (!in_array($stId_chk, $enrolled_ids)) continue;
                        if (isset($st_ex_map[$eid_chk])) {
                            foreach ($st_ex_map[$eid_chk] as $t_chk => $td_chk) {
                                $v_chk = strtolower(trim($td_chk['val']));
                                if ($v_chk !== '' && $v_chk !== 'zw') {
                                    $eids_with_grades[$eid_chk] = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
        ?>
        <A HREF="login.php?view_action=add_grade"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Oceny: <?=htmlspecialchars($subject_name)?></h3>
        <div style="margin-bottom:8px;">
            Widok sekcji:
            <form method="get" action="login.php" style="display:inline;">
                <input type="hidden" name="view_action" value="add_grade">
                <input type="hidden" name="view" value="subject">
                <input type="hidden" name="sid" value="<?=$sid?>">
                <select name="sec_id" onchange="this.form.submit()">
                    <option value="0" <?=($sel_sec_id===0?'selected':'')?>>Wszyscy studenci</option>
                    <?php foreach ($defined_sections as $ds): ?>
                        <option value="<?=$ds['id']?>" <?=($sel_sec_id===$ds['id']?'selected':'')?>><?=htmlspecialchars($ds['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (empty($enrolled_ids)): ?>
            <p>Brak zapisanych studentów.</p>
        <?php else: ?>
        <div class="grades-table-wrap">
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border grades-table" cellpadding="3" style="font-size:16px;">
            <thead>
            <tr>
                <th class="col-sticky-student">Student</th>
                <th class="col-sticky-add">Dodaj</th>
                <?php foreach ($subject_exercises as $ex) { if (!isset($eids_with_grades[intval($ex[0])])) continue; echo "<th title='" . htmlspecialchars($ex[1]) . "'>" . htmlspecialchars($ex[1]) . "</th>"; } ?>
            </tr>
            </thead>
            <tbody>
            <?php $i = 0; foreach ($students as $st):
                $stId = intval($st[4]);
                if (!in_array($stId, $enrolled_ids)) continue;
                if ($sel_sec_id > 0 && ($student_section_map[$stId] ?? 0) !== $sel_sec_id) continue;
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td nowrap class="col-sticky-student"><b><?=htmlspecialchars($st[1].' '.$st[2])?></b> <small>(<?=htmlspecialchars($st[5])?>)</small></td>
                <td align="center" class="col-sticky-add">
                    <button class="btn-sm" onclick="toggleSection('ag_<?=$stId?>')">+</button>
                    <div id="ag_<?=$stId?>" class="hidden-section" onclick="event.stopPropagation()" style="position:absolute; background:#fff; border:1px solid #000; padding:8px; z-index:20; min-width:220px;">
                        <form method="post" action="login.php?action=add_grade">
                            <input type="hidden" name="subject_id" value="<?=$sid?>">
                            <input type="hidden" name="student_id" value="<?=$stId?>">
                            <input type="hidden" name="view_action" value="add_grade">
                            <input type="hidden" name="view" value="subject">
                            <input type="hidden" name="sid" value="<?=$sid?>">
                            Ćw: <select name="exercise_id">
                                <?php foreach ($subject_exercises as $ex) echo "<option value='{$ex[0]}'>" . htmlspecialchars($ex[1]) . "</option>"; ?>
                            </select><br>
                            Termin: <select name="term">
                                <option value="1">1</option><option value="2">2</option>
                                <option value="3">3</option><option value="4">4</option>
                            </select><br>
                            Ocena: <input type="text" name="grade_val" size="5" placeholder="np. 3.5"><br>
                            Komentarz: <textarea name="note" rows="2" style="width:95%; box-sizing:border-box;" placeholder="opcjonalnie"></textarea><br>
                            <input type="submit" value="Dodaj">
                            <button type="button" onclick="closeSection('ag_<?=$stId?>')">Zamknij</button>
                        </form>
                    </div>
                </td>
                <?php foreach ($subject_exercises as $ex):
                    $eid = intval($ex[0]);
                    if (!isset($eids_with_grades[$eid])) continue;
                    $st_grades = $grades_map[$stId][$eid] ?? [];
                    $is_exempt = false; $sum = 0; $count = 0;
                    foreach ($st_grades as $t_data) {
                        $v = strtolower(trim($t_data['val']));
                        if ($v === 'zw') $is_exempt = true;
                        $vn = str_replace(',', '.', $v);
                        if (is_numeric($vn) && floatval($vn) > 0) { $sum += floatval($vn); $count++; }
                    }
                    if ($is_exempt) {
                        $display_text = "zw"; $style = "color:#888; background:#f0f0f0;";
                    } elseif ($count > 0) {
                        $avg = $sum / $count;
                        $display_text = number_format($avg, 2);
                        $style = ($avg >= 2.51) ? "color:green; font-weight:bold;" : "color:red; font-weight:bold;";
                    } else {
                        $display_text = "-"; $style = "color:#ccc;";
                    }
                    $jsonData = htmlspecialchars(json_encode($st_grades), ENT_QUOTES, 'UTF-8');
                    $cellId = "eg_{$stId}_{$eid}";
                ?>
                <td align="center" class="grade-cell" style="<?=$style?>"
                    onclick="openGradeEditModal('<?=$cellId?>')"
                    title="Kliknij by edytować">
                    <?=$display_text?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <!-- Szablony danych dla modalnego okna edycji ocen (poza tabelą) -->
        <div id="grade-templates" style="display:none;">
        <?php foreach ($students as $st2):
            $stId2 = intval($st2[4]);
            if (!in_array($stId2, $enrolled_ids)) continue;
            if ($sel_sec_id > 0 && ($student_section_map[$stId2] ?? 0) !== $sel_sec_id) continue;
            foreach ($subject_exercises as $ex2):
                $eid2 = intval($ex2[0]);
                if (!isset($eids_with_grades[$eid2])) continue;
                $st_grades2 = $grades_map[$stId2][$eid2] ?? [];
                $cellId2 = "eg_{$stId2}_{$eid2}";
        ?>
        <div id="<?=$cellId2?>">
            <b>Edycja ocen – <?=htmlspecialchars($ex2[1])?> (<?=htmlspecialchars($st2[1].' '.$st2[2])?>)</b><br>
            <form method="post" action="login.php?action=edit_all_terms">
                <input type="hidden" name="subject_id" value="<?=$sid?>">
                <input type="hidden" name="exercise_id" value="<?=$eid2?>">
                <input type="hidden" name="student_id" value="<?=$stId2?>">
                <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" style="width:100%; margin:5px 0;">
                    <tr><th>Termin</th><th>Ocena</th><th>Komentarz</th></tr>
                    <?php for ($t2 = 1; $t2 <= 4; $t2++):
                        $gval2 = isset($st_grades2[$t2]) ? htmlspecialchars($st_grades2[$t2]['val']) : '';
                        $gnote2 = isset($st_grades2[$t2]) ? htmlspecialchars($st_grades2[$t2]['note']) : '';
                    ?>
                    <tr>
                        <td align="center">T<?=$t2?></td>
                        <td><input type="text" name="grades[<?=$t2?>]" value="<?=$gval2?>" size="5"></td>
                        <td><input type="text" name="notes[<?=$t2?>]" value="<?=$gnote2?>" size="12"></td>
                    </tr>
                    <?php endfor; ?>
                </table>
                <input type="submit" value="Zapisz wszystkie">
                <button type="button" class="grade-modal-close">Zamknij</button>
            </form>
        </div>
        <?php endforeach; endforeach; ?>
        </div>
        <?php endif; ?>
        <?php else: echo "<p>Przedmiot nie znaleziony lub brak uprawnień.</p>"; endif; ?>
        <?php endif; // add_grade subject ?>


        <!-- ============================================================ -->
        <!--  7. SPRAWOZDANIA                                              -->
        <!-- ============================================================ -->
        <?php
        // ============================================================
        //  7. SPRAWOZDANIA - NOWY WIDOK
        // ============================================================

        // Helper statusu
        function rpt_status_label_c($s) {
            if ($s === 'zal') return '<span style="color:#27ae60;font-weight:bold;">ZAL</span>';
            if ($s === 'zwr') return '<span style="color:#c0392b;font-weight:bold;">ZWR</span>';
            return '<span style="color:#e67e22;font-weight:bold;">DO SPR.</span>';
        }

        // ---- WIDOK SZCZEGÓŁOWY SPRAWOZDANIA ----
        if ($view_action === 'manage_reports' && $view === 'report_detail'):
            $detail_rid = intval($_GET['rid'] ?? 0);
            $detail_sid = intval($_GET['sid'] ?? 0);

            $detail_report = null;
            foreach (read_lines($reportsFile) as $rl) {
                $rp = explode(';', $rl);
                if (intval($rp[0]) === $detail_rid) { $detail_report = $rp; break; }
            }

            if ($detail_report):
                $detail_stId  = intval($detail_report[1]);
                $detail_subId = intval($detail_report[2]);
                $detail_exId  = intval($detail_report[3]);
                $detail_path  = $detail_report[4] ?? '';
                $detail_date  = $detail_report[6] ?? '';

                $detail_stName = '???'; $detail_stAlbum = '???';
                foreach ($students as $ss) {
                    if (intval($ss[4]) === $detail_stId) {
                        $detail_stName  = htmlspecialchars("{$ss[1]} {$ss[2]}");
                        $detail_stAlbum = $ss[5];
                        break;
                    }
                }

                $detail_subName = '???';
                foreach ($subs as $sline) {
                    $sp = explode(';', $sline);
                    if (intval($sp[0]) === $detail_subId) { $detail_subName = htmlspecialchars($sp[1]); break; }
                }

                $detail_exName = '???';
                foreach (read_lines($exercisesFile) as $el) {
                    $ep = explode(';', $el);
                    if (intval($ep[0]) === $detail_exId) { $detail_exName = htmlspecialchars($ep[1]); break; }
                }

                // Zbierz wszystkie rid dla tego studenta+przedmiot+ćwiczenie
                $detail_all_rids = [];
                foreach (read_lines($reportsFile) as $rl2) {
                    $rp2 = explode(';', $rl2);
                    if (count($rp2) >= 4 && intval($rp2[1]) === $detail_stId && intval($rp2[2]) === $detail_subId && intval($rp2[3]) === $detail_exId) {
                        $detail_all_rids[] = intval($rp2[0]);
                    }
                }
                $detail_history = [];
                foreach (read_lines($reportHistoryFile) as $hl) {
                    $hp = explode(';', $hl, 5);
                    if (count($hp) >= 4 && in_array(intval($hp[1]), $detail_all_rids)) {
                        $detail_history[] = [
                            'hid'     => intval($hp[0]),
                            'date'    => $hp[2],
                            'status'  => trim($hp[3]),
                            'comment' => htmlspecialchars(trim($hp[4] ?? ''))
                        ];
                    }
                }
                usort($detail_history, function($a, $b) { return strcmp($a['date'], $b['date']); });

                $detail_current_status = 'do_sprawdzenia';
                $detail_current_comment = '';
                if (!empty($detail_history)) {
                    $last = end($detail_history);
                    $detail_current_status  = $last['status'];
                    $detail_current_comment = $last['comment'];
                }
                $detail_is_zal = ($detail_current_status === 'zal');

                $detail_hasGrade = false;
                foreach (read_lines($gradesFile) as $gl) {
                    $gp = explode(';', $gl);
                    if (count($gp) >= 8 && intval($gp[1]) === $detail_stId && intval($gp[2]) === $detail_subId && intval($gp[7]) === $detail_exId) {
                        $detail_hasGrade = true; break;
                    }
                }
                $detail_hasAtt = false;
                foreach (read_lines($exerciseAttendanceFile) as $al) {
                    $ap = explode(';', $al);
                    if (count($ap) >= 5 && intval($ap[1]) === $detail_stId && intval($ap[2]) === $detail_subId && intval($ap[3]) === $detail_exId) {
                        $detail_hasAtt = true; break;
                    }
                }

                function c_status_txt($s) {
                    if ($s === 'zal') return 'ZALICZONE';
                    if ($s === 'zwr') return 'zwrot';
                    return 'do sprawdzenia';
                }
        ?>
        <A HREF="login.php?view_action=manage_reports"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Szczegóły sprawozdania</h3>
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%; vertical-align:top;">
        <tr>
        <td style="width:55%; padding-right:20px; vertical-align:top;">
            <table border="0" cellpadding="3">
                <tr><td><b>Przedmiot:</b></td><td><?=htmlspecialchars($detail_subName)?></td></tr>
                <tr><td><b>Ćwiczenie:</b></td><td><?=$detail_exName?></td></tr>
                <tr><td><b>Student:</b></td><td><?=$detail_stName?> (<?=htmlspecialchars($detail_stAlbum)?>)</td></tr>
                <tr><td><b>Data przesłania:</b></td><td><?=htmlspecialchars(preg_replace('/^(\d{4}-\d{2}-\d{2})_(\d{2})-(\d{2})-(\d{2})$/', '$1 $2:$3:$4', $detail_date))?></td></tr>
                <tr><td><b>Aktualny status:</b></td><td><?=c_status_txt($detail_current_status)?></td></tr>
            </table>
            <?php if ($detail_path !== '' && $detail_path !== 'brak_pliku'): ?>
                <p><a href="<?=htmlspecialchars($detail_path)?>" target="_blank">Pobierz aktualny plik</a></p>
            <?php elseif ($detail_path === 'brak_pliku'): ?>
                <p><i>Sprawozdanie wpisane ręcznie – brak pliku od studenta.</i></p>
            <?php endif; ?>
            <br>
            <b>Historia sprawozdania:</b><br><br>
            <?php if (empty($detail_history)): ?>
                <p>Brak wpisów w historii. Czeka na pierwsze sprawdzenie.</p>
            <?php else: ?>
                <table CELLPADDING="3" CELLSPACING="0" BORDER="1" class="border" style="width:100%;">
                    <tr><th>Data</th><th>Status</th><th>Komentarz</th></tr>
                    <?php $hi_i = 0; foreach ($detail_history as $hi):
                        $hi_cls = ($hi_i++ % 2 == 0) ? 'n0' : 'n1';
                    ?>
                    <tr class="<?=$hi_cls?>">
                        <td><?=htmlspecialchars($hi['date'])?></td>
                        <td><b><?=c_status_txt($hi['status'])?></b></td>
                        <td><?=($hi['comment'] !== '' ? $hi['comment'] : '<i>brak</i>')?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </td>
        <td style="vertical-align:top; padding-left:10px;">
            <b>Ocena sprawozdania:</b><br><br>
            <?php if ($detail_is_zal): ?>
                Sprawozdanie jest już <b>ZALICZONE</b>. Nie można zmienić statusu.
            <?php else: ?>
            <form method="post" action="login.php?action=evaluate_report" onsubmit="sessionStorage.setItem('after_report_redirect','manage_reports');">
                <input type="hidden" name="report_id"   value="<?=$detail_rid?>">
                <input type="hidden" name="subject_id"  value="<?=$detail_subId?>">
                <input type="hidden" name="student_id"  value="<?=$detail_stId?>">
                <input type="hidden" name="exercise_id" value="<?=$detail_exId?>">
                <input type="hidden" name="return_to"   value="manage_reports">
                <table border="0" cellpadding="4" cellspacing="0">
                    <tr>
                        <td><b>Decyzja:</b></td>
                        <td>
                            <select name="status">
                                <option value="zal">Zalicz</option>
                                <option value="zwr">Zwróć</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><b>Komentarz:</b></td>
                        <td><textarea name="comment" rows="4" cols="30"></textarea></td>
                    </tr>
                    <tr>
                        <td><b>Ocena za ćwiczenie:</b></td>
                        <td>
                            <?php if (!$detail_hasGrade): ?>
                                <input type="text" name="grade_val" size="8">
                            <?php else: ?>
                                <i>Student ma już wpisaną ocenę.</i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Obecność:</b></td>
                        <td>
                            <?php if (!$detail_hasAtt): ?>
                                <select name="att_status">
                                    <option value="" selected>-- pomiń --</option>
                                    <option value="obecny">obecny</option>
                                    <option value="nieobecny">nieobecny</option>
                                    <option value="nieobecność usprawiedliwiona">nieobecność usprawiedliwiona</option>
                                    <option value="spóźniony">spóźniony</option>
                                    <option value="odrobione">odrobione</option>
                                    <option value="niewykonane">niewykonane</option>
                                </select>
                            <?php else: ?>
                                <i>Student ma już wpisaną obecność.</i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" value="Zatwierdź"></td>
                    </tr>
                </table>
            </form>
            <?php endif; ?>
        </td>
        </tr>
        </table>
        <?php
            else:
                echo "<p>Sprawozdanie ID {$detail_rid} nie znalezione.</p>";
            endif;
        endif; // report_detail

        // ---- WIDOK STARTOWY: Lista oczekujących sprawozdań (tylko status do_sprawdzenia) ----
        if ($view_action === 'manage_reports' && empty($view)):
            $me_id_r = intval($me['id']);
            $sub_nm = []; foreach ($subs as $sl) { $sp = explode(';', $sl); $sub_nm[intval($sp[0])] = $sp[1]; }
            $ex_nm = []; foreach (read_lines($exercisesFile) as $el) { $ep = explode(';', $el); $ex_nm[intval($ep[0])] = $ep[1]; }
            $st_nm = []; $st_al = [];
            foreach ($students as $ss) { $sid_s=intval($ss[4]); $st_nm[$sid_s]=htmlspecialchars("{$ss[1]} {$ss[2]}"); $st_al[$sid_s]=$ss[5]; }

            $hist_latest = [];
            foreach (read_lines($reportHistoryFile) as $hl) {
                $hp = explode(';', $hl, 5);
                if (count($hp) < 4) continue;
                $rid_h = intval($hp[1]);
                if (!isset($hist_latest[$rid_h]) || strcmp($hp[2], $hist_latest[$rid_h]['date']) > 0)
                    $hist_latest[$rid_h] = ['status' => $hp[3], 'date' => $hp[2]];
            }

            $pending = [];
            foreach (read_lines($reportsFile) as $rl) {
                $rp = explode(';', $rl);
                if (count($rp) < 7) continue;
                $rid = intval($rp[0]); $stId = intval($rp[1]); $subId = intval($rp[2]); $exId = intval($rp[3]);
                $tt = isset($rp[8]) ? intval($rp[8]) : 0;
                if ($tt !== 0 && $tt !== $me_id_r) continue;
                if (!isset($sub_nm[$subId])) continue;
                $status = $hist_latest[$rid]['status'] ?? 'do_sprawdzenia';
                // Pokazuj TYLKO sprawozdania oddane przez studenta (do_sprawdzenia), pomijaj zwrócone (zwr) i zaliczone (zal)
                if ($status === 'do_sprawdzenia') {
                    $pending[] = ['rid'=>$rid,'stId'=>$stId,'subId'=>$subId,'exId'=>$exId,'date'=>$rp[6]??''];
                }
            }
            usort($pending, function($a,$b){ return strcmp($a['date'],$b['date']); });
        ?>
        <h3>Sprawozdania – Do sprawdzenia (<?=count($pending)?>)</h3>
        <?php if (empty($pending)): ?>
            <p>Brak oczekujacych sprawozdan do sprawdzenia.</p>
        <?php else: ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border">
            <tr>
                <th>Przedmiot</th>
                <th>Ćwiczenie</th>
                <th>Student</th>
                <th>Akcja</th>
            </tr>
            <?php $i=0; foreach ($pending as $pr):
                $cls=($i++%2==0)?'n0':'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($sub_nm[$pr['subId']]??'???')?></td>
                <td><?=htmlspecialchars($ex_nm[$pr['exId']]??'???')?></td>
                <td><?=$st_nm[$pr['stId']]??'???'?></td>
                <td><a href="login.php?view_action=manage_reports&view=report_detail&rid=<?=$pr['rid']?>&sid=<?=$pr['subId']?>">Wejdź</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; // empty view (pending list) ?>




        <!-- ============================================================ -->
        <!--  10. OBECNOŚCI                                               -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_exercise_att' && empty($view)): ?>
        <h3>Obecności – Wybierz Przedmiot</h3>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>ID</th><th>Nazwa</th><th>Rok</th><th>Akcje</th></tr>
            <?php $i = 0; foreach ($subs as $s): $p = explode(';', $s, 4); $cls = ($i++ % 2 == 0) ? 'n0' : 'n1'; ?>
            <tr class="<?=$cls?>">
                <td><?=$p[0]?></td><td><?=htmlspecialchars($p[1])?></td><td><?=htmlspecialchars($p[2])?></td>
                <td><a href="login.php?view=subject&sid=<?=$p[0]?>&view_action=manage_exercise_att">Wybierz ćwiczenie</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if ($view_action === 'manage_exercise_att' && $view === 'subject' && isset($_GET['sid'])):
            $sid = intval($_GET['sid']);
            $subLine = $viewData['subLine'] ?? null;
            $assigned_ex = $viewData['assigned_ex'] ?? [];
            if ($subLine):
                $ex_names = []; $ex_descriptions = [];
                if (isset($viewData['all_exercises'])) {
                    foreach ($viewData['all_exercises'] as $c) { $cp = explode(';', $c, 4); $ex_names[intval($cp[0])] = htmlspecialchars($cp[1]); $ex_descriptions[intval($cp[0])] = $cp[2]; }
                }
        ?>
        <A HREF="login.php?view_action=manage_exercise_att"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Ćwiczenia dla: <?=htmlspecialchars($subLine[1])?></h3>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Nazwa ćwiczenia</th><th>Akcje</th></tr>
            <?php $i = 0; foreach ($assigned_ex as $ax):
                $pp = explode(';', $ax); $eid = intval($pp[1]); $cname = $ex_names[$eid] ?? '???';
                $eDesc = $ex_descriptions[$eid] ?? '';
                $dpos = strpos($eDesc, '. ');
                $shortDesc = ($dpos !== false) ? substr($eDesc, 0, $dpos + 1) : $eDesc;
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($cname)?> <small style="font-size:0.85em; color:#555;">(<?=htmlspecialchars($shortDesc)?>)</small></td>
                <td><a href="login.php?view=subject_exercise&sid=<?=$sid?>&eid=<?=$eid?>&view_action=manage_exercise_att">Zarządzaj</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: echo "<p>Przedmiot nie znaleziony lub brak uprawnień.</p>"; endif; ?>
        <?php endif; ?>

        <?php if ($view_action === 'manage_exercise_att' && $view === 'subject_exercise' && isset($_GET['sid']) && isset($_GET['eid'])):
            $sid = intval($_GET['sid']); $eid = intval($_GET['eid']);
            $subLine = $viewData['subLine'] ?? null; $exLine = $viewData['exLine'] ?? null;
            if ($subLine && $exLine):
                $att_options = ['nie sprawdzono','obecny','nieobecny','nieobecność usprawiedliwiona','spóźniony','odrobione','niewykonane'];
                $enrolled = $viewData['enrolled'] ?? [];
                $student_info = $viewData['student_info'] ?? [];
                $current_att = $viewData['current_att'] ?? [];
                $exemptions_map = $viewData['exemptions_map'] ?? [];
        ?>
        <A HREF="login.php?view=subject&sid=<?=$sid?>&view_action=manage_exercise_att"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Obecność: <?=htmlspecialchars($exLine[1])?> (Przedmiot: <?=htmlspecialchars($subLine[1])?>)</h3>
        <h4>Masowe wpisywanie</h4>
        <form method="post" action="login.php?action=add_exercise_att_batch">
            <input type="hidden" name="subject_id" value="<?=$sid?>">
            <input type="hidden" name="exercise_id" value="<?=$eid?>">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
                <tr><th>Student (Album)</th><th>Status obecności</th></tr>
                <?php $i = 0; foreach ($enrolled as $e):
                    $parts = explode(';', $e); $stId = intval($parts[0]);
                    $stData = $student_info[$stId] ?? null; if (!$stData) continue;
                    $current_status = $current_att[$stId]['status'] ?? 'nie sprawdzono';
                    $is_exempt = isset($exemptions_map[$stId]);
                    $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                ?>
                <tr class="<?=$cls?>">
                    <td><?=$stData['name']?> (<?=$stData['album']?>)</td>
                    <td>
                        <?php if ($is_exempt): ?>
                            <span style="color:#999; font-style:italic;">Zwolniony (zw)</span>
                        <?php else: ?>
                            <select name="att_status[<?=$stId?>]">
                                <?php foreach ($att_options as $opt) echo "<option value='" . htmlspecialchars($opt) . "'" . ($opt===$current_status?' selected':'') . ">" . htmlspecialchars($opt) . "</option>"; ?>
                            </select>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div style="margin-top:10px;">
            <input type="submit" value="Zapisz wszystkie (Nadpisz)">
            </div>
        </form>
        <hr>
        <h4>Aktualne wpisy (edycja pojedyncza)</h4>
        <?php if (count($current_att) === 0): ?>
            <p>Brak zapisanych obecności.</p>
        <?php else: ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Student (Album)</th><th>Status</th><th>Data</th><th>Akcje</th></tr>
            <?php $j = 0; foreach ($enrolled as $e):
                $parts = explode(';', $e); $stId = intval($parts[0]);
                if (!isset($current_att[$stId])) continue;
                $attData = $current_att[$stId];
                $stData = $student_info[$stId] ?? ['name'=>'???','album'=>'???'];
                $cls = ($j++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=$stData['name']?> (<?=$stData['album']?>)</td>
                <td><b><?=htmlspecialchars($attData['status'])?></b></td>
                <td><?=htmlspecialchars($attData['date'])?></td>
                <td>
                    <form style="display:inline; margin:0;" method="post" action="login.php?action=edit_exercise_att">
                        <input type="hidden" name="att_id" value="<?=$attData['id']?>">
                        <input type="hidden" name="subject_id" value="<?=$sid?>">
                        <input type="hidden" name="exercise_id" value="<?=$eid?>">
                        <select name="new_status" style="width:auto;">
                            <?php foreach (array_filter($att_options, fn($o) => $o !== 'nie sprawdzono') as $opt)
                                echo "<option value='" . htmlspecialchars($opt) . "'" . ($opt===$attData['status']?' selected':'') . ">" . htmlspecialchars($opt) . "</option>"; ?>
                        </select>
                        <input type="submit" value="Edytuj">
                    </form>
                    <a href="login.php?action=delete_exercise_att&aid=<?=$attData['id']?>&sid=<?=$sid?>&eid=<?=$eid?>&view_action=manage_exercise_att" onclick="return confirm('Usunąć wpis?')">[usuń]</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php else: echo "<p>Przedmiot lub ćwiczenie nie znalezione albo brak uprawnień.</p>"; endif; ?>
        <?php endif; // manage_exercise_att subject_exercise ?>


        <!-- ============================================================ -->
        <!--  11. STATYSTYKI                                               -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'statistics'):
            $selected_sid = $viewData['selected_sid'] ?? 0;
            $stats_type = $viewData['stats_type'] ?? 'avg';
            $sort_desc = $viewData['sort_desc'] ?? false;
        ?>
        <h3>Statystyki</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="statistics">
            Przedmiot:
            <select name="subject_id" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($selected_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?> (id <?=$p[0]?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selected_sid > 0): ?>
            Typ:
            <select name="stats_type" onchange="this.form.submit()">
                <option value="avg" <?=($stats_type==='avg'?'selected':'')?>>Średnie ocen</option>
                <option value="distribution" <?=($stats_type==='distribution'?'selected':'')?>>Rozkład ocen</option>
                <option value="attendance" <?=($stats_type==='attendance'?'selected':'')?>>Frekwencja</option>
                <option value="attendance_detail" <?=($stats_type==='attendance_detail'?'selected':'')?>>Frekwencja – szczegółowa</option>
                <option value="pass_rate" <?=($stats_type==='pass_rate'?'selected':'')?>>Zawalność</option>
                <option value="difficulty" <?=($stats_type==='difficulty'?'selected':'')?>>Trudność ćwiczeń</option>
            </select>
            <?php if ($stats_type === 'avg'): ?>
                <label><input type="checkbox" name="sort_desc" <?=($sort_desc?'checked':'')?> onchange="this.form.submit()"> Sortuj malejąco</label>
            <?php endif; ?>
            <input type="submit" value="Odśwież">
            <?php endif; ?>
        </form>
        <hr>
        <?php if ($selected_sid > 0):
            $subject_name = $viewData['subject_name'] ?? '';
            echo "<h4>Statystyki: " . htmlspecialchars($subject_name) . "</h4>";
            if ($stats_type === 'avg' && isset($viewData['stats_data'])):
                $stats_data = $viewData['stats_data'];
        ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr>
                <th>Student (Album)</th>
                <th>ŚREDNIA KOŃCOWA</th>
            </tr>
            <?php $i = 0; foreach ($stats_data as $row):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $fs = ($row['final_avg'] >= 3.0) ? "color:green;font-weight:bold;" : (($row['final_avg']>0)?"color:red;":'');
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($row['name'])?> (<?=htmlspecialchars($row['album'])?>)</td>
                <td align="center" style="<?=$fs?>"><?=number_format($row['final_avg'],2)?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php elseif ($stats_type === 'distribution' && isset($viewData['stats_distribution'])):
            $dist = $viewData['stats_distribution'];
            $max_v = 0; foreach ($dist as $v) if ($v > $max_v) $max_v = $v;
        ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Ocena</th><th>Liczba</th><th>Wykres</th></tr>
            <?php $i = 0; foreach ($dist as $grade => $count):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $bw = ($max_v > 0) ? ($count / $max_v) * 200 : 0;
            ?>
            <tr class="<?=$cls?>">
                <td align="center"><b><?=$grade?></b></td>
                <td align="center"><?=$count?></td>
                <td><div style="background:#3498db; height:14px; width:<?=$bw?>px;"></div></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php elseif ($stats_type === 'attendance' && isset($viewData['stats_attendance'])):
            $att = $viewData['stats_attendance'];
        ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Student</th><th>Obecności</th><th>Wszystkich</th><th>Procent</th></tr>
            <?php $i = 0; foreach ($att as $row):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $pval = number_format($row['percent'],1);
                $color = ($row['percent'] < 50) ? 'red' : 'green';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($row['name'])?> (<?=htmlspecialchars($row['album'])?>)</td>
                <td align="center"><?=$row['present']?></td>
                <td align="center"><?=$row['total']?></td>
                <td align="center" style="font-weight:bold; color:<?=$color?>;"><?=$pval?>%</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php elseif ($stats_type === 'attendance_detail' && isset($viewData['stats_attendance_detail'])):
            $att_d        = $viewData['stats_attendance_detail'];
            $att_statuses = $viewData['att_detail_statuses'];
            $status_labels = [
                'obecny'                       => 'Obecny',
                'nieobecny'                    => 'Nieobecny',
                'nieobecność usprawiedliwiona' => 'Nieob. uspr.',
                'spóźniony'                    => 'Spóźniony',
                'odrobione'                    => 'Odrobione',
                'niewykonane'                  => 'Niewykonane',
                'nie sprawdzono'               => 'Nie sprawdz.',
            ];
            $col_totals    = array_fill_keys($att_statuses, 0);
            $grand_total   = 0;
            $grand_present = 0;
            foreach ($att_d as $row) {
                foreach ($att_statuses as $st) { $col_totals[$st] += $row['counts'][$st]; }
                $grand_total   += $row['total'];
                $grand_present += $row['present'];
            }
        ?>
        <div style="overflow-x:auto;">
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <thead>
            <tr style="background:#ddd;">
                <th>Student (Album)</th>
                <?php foreach ($att_statuses as $st): ?>
                    <th title="<?=htmlspecialchars($st)?>"><?=htmlspecialchars($status_labels[$st] ?? $st)?></th>
                <?php endforeach; ?>
                <th>Razem</th>
                <th>Frekwencja</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 0; foreach ($att_d as $row):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $pval = number_format($row['percent'], 1);
                $color = ($row['percent'] < 50) ? 'red' : 'green';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($row['name'])?> (<?=htmlspecialchars($row['album'])?>)</td>
                <?php foreach ($att_statuses as $st): ?>
                    <td align="center"><?=$row['counts'][$st]?></td>
                <?php endforeach; ?>
                <td align="center"><b><?=$row['total']?></b></td>
                <td align="center" style="font-weight:bold; color:<?=$color?>;"><?=$pval?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr style="background:#eee; font-weight:bold;">
                <td><b>RAZEM</b></td>
                <?php foreach ($att_statuses as $st): ?>
                    <td align="center"><b><?=$col_totals[$st]?></b></td>
                <?php endforeach; ?>
                <td align="center"><b><?=$grand_total?></b></td>
                <td align="center" style="font-weight:bold; color:<?=($grand_total > 0 && ($grand_present / $grand_total * 100) < 50) ? 'red' : 'green'?>;">
                    <?=($grand_total > 0) ? number_format($grand_present / $grand_total * 100, 1) : '0.0'?>%
                </td>
            </tr>
            </tfoot>
        </table>
        </div>
        <?php elseif ($stats_type === 'pass_rate' && isset($viewData['pass_stats'])):
            $ps = $viewData['pass_stats'];
            $total_ps = $ps['pass'] + $ps['fail'];
            $pp = ($total_ps > 0) ? number_format(($ps['pass']/$total_ps)*100,1) : 0;
            $fp = ($total_ps > 0) ? number_format(($ps['fail']/$total_ps)*100,1) : 0;
        ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="8">
            <tr><th>ZALICZONE</th><th>NIEZALICZONE</th></tr>
            <tr>
                <td align="center" style="background:#27ae60; color:white; font-size:1.5em;"><b><?=$ps['pass']?></b><br><?=$pp?>%</td>
                <td align="center" style="background:#c0392b; color:white; font-size:1.5em;"><b><?=$ps['fail']?></b><br><?=$fp?>%</td>
            </tr>
        </table>
        <?php elseif ($stats_type === 'difficulty' && isset($viewData['diff_stats'])): ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Ćwiczenie</th><th>Średnia</th><th>Liczba ocen</th></tr>
            <?php $i = 0; foreach ($viewData['diff_stats'] as $row):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $sty = ($row['avg'] < 3.0 && $row['avg'] > 0) ? "color:red;font-weight:bold;" : "";
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($row['name'])?></td>
                <td align="center" style="<?=$sty?>"><?=number_format($row['avg'],2)?></td>
                <td align="center"><?=$row['count']?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php else: if ($selected_sid > 0) echo "<p style='color:red;'>Brak uprawnień do tego przedmiotu.</p>"; endif; ?>
        <?php endif; // statistics ?>


        <!-- ============================================================ -->
        <!--  12. TERMINY                                                  -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_deadlines'):
            $selected_sid = $viewData['selected_sid'] ?? 0;
        ?>
        <h3>Terminy i wymagania zaliczenia ćwiczeń</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="manage_deadlines">
            Przedmiot:
            <select name="subject_id" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($selected_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($selected_sid > 0):
            if (isset($viewData['deadlines_perm_error'])): ?>
                <p style='color:red;'><?=htmlspecialchars($viewData['deadlines_perm_error'])?></p>
            <?php elseif (isset($viewData['deadlines_data'])):
                $subName            = $viewData['subName'];
                $deadlines_data     = $viewData['deadlines_data'];
                $assigned_exercises = $viewData['assigned_exercises'];
                $ex_names_d = []; $ex_descriptions_d = [];
                if (isset($viewData['all_exercises'])) {
                    foreach ($viewData['all_exercises'] as $c) {
                        $cp = explode(';', $c, 4);
                        $ex_names_d[intval($cp[0])]        = $cp[1];
                        $ex_descriptions_d[intval($cp[0])] = $cp[2];
                    }
                }
                echo "<h4>Przedmiot: " . htmlspecialchars($subName) . "</h4>";
                if (count($assigned_exercises) === 0) {
                    echo "<p>Brak ćwiczeń" . (($viewData['manage_exercises_scope'] ?? '') === 'own' ? " przypisanych do Ciebie" : "") . ".</p>";
                } else {
        ?>
        <form method="post" action="login.php?action=save_deadlines_bulk">
    <input type="hidden" name="subject_id" value="<?=$selected_sid?>">
    <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4" style="width:100%;">
        <tr style="background:#dde;">
            <th style="padding:3px 5px; text-align:left; min-width:120px;">Ćwiczenie</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;" title="Przynajmniej jedna ocena ≥ 2,51">Ocena<br>≥&nbsp;2,51</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;" title="Zaliczone sprawozdanie">Spra-<br>wozd.</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;" title="Status: obecny / odrobione / spóźniony">Obec-<br>ność</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;">Termin 1</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;">Termin 2</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;">Termin 3</th>
            <th style="padding:3px 5px; text-align:center; white-space:nowrap;">Termin 4</th>
        </tr>
        <?php
        $row_i = 0;
        foreach ($assigned_exercises as $idx => $ax):
            $pp  = explode(';', $ax);
            $eid = intval($pp[1]);
            $eName     = $ex_names_d[$eid]        ?? 'Nieznane';
            $eDescFull = $ex_descriptions_d[$eid] ?? '';
            $dpos      = strpos($eDescFull, '. ');
            $shortDesc = ($dpos !== false) ? substr($eDescFull, 0, $dpos + 1) : $eDescFull;
            $d = $deadlines_data[$eid] ?? [
                'req_grade' => 0, 'req_report' => 0, 'req_attendance' => 0,
                't1' => '', 't2' => '', 't3' => '', 't4' => '', 'req' => 0,
            ];
            $chkGrade = ($d['req_grade']      ?? 0)              ? 'checked' : '';
            $chkRep   = ($d['req_report']     ?? $d['req'] ?? 0) ? 'checked' : '';
            $chkAtt   = ($d['req_attendance'] ?? 0)              ? 'checked' : '';
            $rowBg    = ($row_i++ % 2 === 0) ? '#f5f5f5' : '#ffffff';
        ?>
            <tr style="background:<?=$rowBg?>; vertical-align:middle;">
                <td style="padding:2px 5px; max-width:150px; word-break:break-word; white-space:normal;">
                    <input type="hidden" name="exercise_ids[<?=$idx?>]" value="<?=$eid?>">
                    <?=htmlspecialchars($eName)?>
                </td>
                <td style="padding:2px; text-align:center;">
                    <input type="checkbox" name="req_grade[<?=$idx?>]" value="1" <?=$chkGrade?> title="Wymagana ocena ≥ 2,51" style="margin:0;">
                </td>
                <td style="padding:2px; text-align:center;">
                    <input type="checkbox" name="req_report[<?=$idx?>]" value="1" <?=$chkRep?> title="Wymagane zaliczone sprawozdanie" style="margin:0;">
                </td>
                <td style="padding:2px; text-align:center;">
                    <input type="checkbox" name="req_attendance[<?=$idx?>]" value="1" <?=$chkAtt?> title="Wymagana obecność" style="margin:0;">
                </td>
                <td style="padding:2px; text-align:center; white-space:nowrap;"><input type="datetime-local" name="term1[<?=$idx?>]" value="<?=htmlspecialchars($d['t1'])?>"></td>
                <td style="padding:2px; text-align:center; white-space:nowrap;"><input type="datetime-local" name="term2[<?=$idx?>]" value="<?=htmlspecialchars($d['t2'])?>"></td>
                <td style="padding:2px; text-align:center; white-space:nowrap;"><input type="datetime-local" name="term3[<?=$idx?>]" value="<?=htmlspecialchars($d['t3'])?>"></td>
                <td style="padding:2px; text-align:center; white-space:nowrap;"><input type="datetime-local" name="term4[<?=$idx?>]" value="<?=htmlspecialchars($d['t4'])?>"></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div style="margin-top:8px; text-align:center;">
        <input type="submit" value="Zapisz wszystkie ćwiczenia">
    </div>
</form>
        </table>
        <p style="color:#000;">
            Kolumny wymagań: <b>Ocena ≥ 2,51</b> – przynajmniej jedna pozytywna ocena &nbsp;|&nbsp;
            <b>Sprawozd.</b> – zaliczone sprawozdanie &nbsp;|&nbsp;
            <b>Obecność</b> – status <i>obecny</i>, <i>odrobione</i> lub <i>spóźniony</i>.
            Terminy są aktywne gdy wymagane jest sprawozdanie.
        </p>
        <?php
                }
            elseif (!isset($viewData['deadlines_perm_error'])): echo "<p style='color:red;'>Brak uprawnień.</p>";
            endif;
        endif; ?>
        <?php endif; // manage_deadlines ?>


        <!-- ============================================================ -->
        <!--  13. WSPÓŁPROWADZĄCY / DOSTĘP                                -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_access' && empty($view)): ?>
        <h3>Zarządzanie dostępem (własne przedmioty)</h3>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>ID</th><th>Nazwa</th><th>Rok</th><th>Akcje</th></tr>
            <?php $i = 0; foreach ($subs as $s):
                $p = explode(';', $s, 4); $sid = intval($p[0]);
                if (intval($p[3]) !== intval($me['id'])) continue;
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=$p[0]?></td><td><?=htmlspecialchars($p[1])?></td><td><?=htmlspecialchars($p[2])?></td>
                <td><a href="login.php?view_action=manage_access&view=details&sid=<?=$sid?>">Zarządzaj</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($i === 0) echo "<tr><td colspan='4'>Brak własnych przedmiotów.</td></tr>"; ?>
        </table>
        <?php endif; ?>

        <?php if ($view_action === 'manage_access' && $view === 'details' && isset($_GET['sid'])):
            $sid = intval($_GET['sid']);
            $subLine = $viewData['subLine'] ?? null;
            $access_list = $viewData['access_list'] ?? [];
            if ($subLine):
        ?>
        <A HREF="login.php?view_action=manage_access"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Dostęp: <?=htmlspecialchars($subLine[1])?></h3>
        <button onclick="toggleSection('addCoTeacherForm')" style="margin-bottom:8px;">[+] Dodaj Współprowadzącego</button>
        <div id="addCoTeacherForm" class="hidden-section">
            <form method="post" action="login.php?action=grant_access">
                <input type="hidden" name="subject_id" value="<?=$sid?>">
                Prowadzący:
                <select name="teacher_id" required>
                    <option value="">-- wybierz --</option>
                    <?php foreach ($teachers_list as $t):
                        if ($t['id'] === intval($me['id'])) continue;
                        $already = false;
                        foreach ($access_list as $al) { if ($al['id'] === $t['id']) $already = true; }
                        if ($already) continue;
                    ?>
                    <option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?> (<?=htmlspecialchars($t['inicjaly'])?>)</option>
                    <?php endforeach; ?>
                </select><br>
                <div style="margin:8px 0;">
                    <b>Uprawnienia:</b><br>
                    <fieldset style="border:1px solid #ccc; padding:6px 10px; margin:4px 0; display:inline-block; min-width:340px;">
                        <legend style="font-weight:bold;">Zarządzanie ćwiczeniami, konfiguracja ich zaliczeń i zwolnienia</legend>
                        <label style="display:block; margin:2px 0;">
                            <input type="radio" name="perm_manage_exercises_scope" value="none" checked>
                            &nbsp;Brak
                        </label>
                        <label style="display:block; margin:2px 0;">
                            <input type="radio" name="perm_manage_exercises_scope" value="own">
                            &nbsp;własne ćwiczenia
                        </label>
                        <label style="display:block; margin:2px 0;">
                            <input type="radio" name="perm_manage_exercises_scope" value="all">
                            &nbsp;wszystkie ćwiczenia
                        </label>
                    </fieldset>
                    <br>
                    <fieldset style="border:1px solid #ccc; padding:6px 10px; margin:4px 0; display:inline-block; min-width:340px;">
                        <legend style="font-weight:bold;">Zarządzanie sekcjami</legend>
                        <label style="display:block; margin:2px 0;">
                            <input type="checkbox" name="perm_manage_sections" value="1" checked>
                            &nbsp;Zarządzanie sekcjami
                        </label>
                    </fieldset>
                    <br>
                    <fieldset style="border:1px solid #ccc; padding:6px 10px; margin:4px 0; display:inline-block; min-width:340px;">
                        <legend style="font-weight:bold;">Ocenianie</legend>
                        <label style="display:block; margin:2px 0;">
                            <input type="checkbox" name="perm_grading_own" value="1" checked>
                            &nbsp;Ocenianie (własne ćwiczenia)
                        </label>
                        <label style="display:block; margin:2px 0;">
                            <input type="checkbox" name="perm_grading_all" value="1">
                            &nbsp;Ocenianie (wszystkie ćwiczenia)
                        </label>
                    </fieldset>
                    <br>
                    <label style="display:block; margin:4px 0;">
                        <input type="checkbox" name="perm_final_grades" value="1">
                        &nbsp;<b>Oceny końcowe</b>
                    </label>
                    <label style="display:block; margin:4px 0;">
                        <input type="checkbox" name="perm_announcements" value="1" checked>
                        &nbsp;<b>Ogłoszenia</b>
                    </label>
                </div>
                <input type="submit" value="Przyznaj dostęp">
                <button type="button" onclick="toggleSection('addCoTeacherForm')">Zamknij</button>
            </form>
        </div>
        <br>
        <h4>Współprowadzący z dostępem</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Prowadzący</th><th>Uprawnienia</th><th>Akcje</th></tr>
            <?php if (count($access_list) === 0) echo "<tr><td colspan='3'>Brak dodatkowych prowadzących.</td></tr>";
            $k = 0; foreach ($access_list as $acc):
                $cls = ($k++ % 2 == 0) ? 'n0' : 'n1';
                $p_desc = []; $p = $acc['perms'];
                if (empty($p)) { $perms_display = "<span style='color:#999;'>Dostęp standardowy</span>"; }
                else {
                    // Zarządzanie ćwiczeniami (nowy model)
                    $ex_scope_disp = $p['manage_exercises_scope'] ?? null;
                    if ($ex_scope_disp === null) {
                        // Migracja ze starego modelu
                        $ex_scope_disp = !empty($p['manage_exercises']) ? 'all' : 'none';
                    }
                    if ($ex_scope_disp === 'own') {
                        $p_desc[] = "Zarz.ćwicz.(własne)+Wymagania+Zwolnienia";
                    } elseif ($ex_scope_disp === 'all') {
                        $p_desc[] = "Zarz.ćwicz.(wszystkie)+Wymagania+Zwolnienia";
                    }

                    if (!empty($p['manage_sections'])) $p_desc[] = "Zarz.sekcjami";

                    // Ocenianie (nowy model)
                    $g_own = !empty($p['grading_own']);
                    $g_all = !empty($p['grading_all']);
                    if ($g_own === false && $g_all === false) {
                        // Migracja ze starego modelu
                        $old_scope = $p['grading_scope'] ?? '';
                        $g_own = in_array($old_scope, ['own','all']);
                        $g_all = ($old_scope === 'all');
                    }
                    if ($g_all) {
                        $p_desc[] = "Ocenianie(wszystkie)";
                    } elseif ($g_own) {
                        $p_desc[] = "Ocenianie(własne)";
                    }

                    if (!empty($p['final_grades'])) $p_desc[] = "Oceny końc.";
                    if (!empty($p['announcements'])) $p_desc[] = "Ogłoszenia";
                    $perms_display = empty($p_desc) ? "<span style='color:#999;'>Brak uprawnień</span>" : implode(', ', $p_desc);
                }
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($acc['name'])?></td>
                <td style="font-size:0.9em;"><?=$perms_display?></td>
                <td align="center">
                    <a href="login.php?action=revoke_access&sid=<?=$sid?>&tid=<?=$acc['id']?>&view_action=manage_access&view=details" onclick="return confirm('Odebrać dostęp?')" style="color:red;">Odbierz</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: echo "<p style='color:red;'>Brak uprawnień lub przedmiot nie istnieje.</p>"; endif; ?>
        <?php endif; // manage_access details ?>


        <!-- ============================================================ -->
        <!--  14. OCENY SERYJNE                                            -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'batch_grading'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
        ?>
        <h3>Oceny seryjne za ćwiczenie</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="batch_grading">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($sel_sid > 0):
            $subject_exercises = $viewData['subject_exercises'] ?? [];
            $sel_eid = $viewData['sel_eid'] ?? 0;
        ?>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="batch_grading">
            <input type="hidden" name="sid" value="<?=$sel_sid?>">
            Ćwiczenie: <select name="eid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subject_exercises as $se): ?>
                    <option value="<?=$se[0]?>" <?=($sel_eid===intval($se[0])?'selected':'')?>>
                        <?=htmlspecialchars($se[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($sel_eid > 0):
            $enrolled = $viewData['enrolled'] ?? [];
            $current_grades = $viewData['current_grades'] ?? [];
            $exName_b = '???';
            foreach ($subject_exercises as $se) { if (intval($se[0]) === $sel_eid) $exName_b = $se[1]; }
        ?>
        <h4>Oceny dla: <?=htmlspecialchars($exName_b)?></h4>
        <form method="post" action="login.php?action=save_batch_grades&view_action=batch_grading">
            <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
            <input type="hidden" name="exercise_id" value="<?=$sel_eid?>">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
                <tr><th>Student (Album)</th><th>Obecność</th><th>T1</th><th>T2</th><th>T3</th><th>T4</th></tr>
                <?php $i = 0; foreach ($enrolled as $e):
                    $parts = explode(';', $e); $stId = intval($parts[0]);
                    $stName = '???'; $stAlbum = '';
                    foreach ($students as $stud) { if (intval($stud[4]) === $stId) { $stName = "{$stud[1]} {$stud[2]}"; $stAlbum = $stud[5]; break; } }
                    $is_exempt = false;
                    if (isset($current_grades[$stId])) { foreach ($current_grades[$stId] as $gv) { if (strtolower(trim($gv)) === 'zw') { $is_exempt = true; break; } } }
                    $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                    $curr_att_status = $viewData['current_att_map'][$stId] ?? 'nie sprawdzono';
                    $att_opts = ['nie sprawdzono','obecny','nieobecny','nieobecność usprawiedliwiona','spóźniony','odrobione','niewykonane'];
                ?>
                <tr class="<?=$cls?>">
                    <td><?=htmlspecialchars($stName)?> (<?=htmlspecialchars($stAlbum)?>)</td>
                    <?php if ($is_exempt): ?>
                        <td colspan="5" align="center" style="color:#666; font-style:italic;">Zwolniony z ćwiczenia</td>
                    <?php else: ?>
                    <td>
                        <select name="att_status[<?=$stId?>]" style="width:100%;">
                            <?php foreach ($att_opts as $opt) echo "<option value='" . htmlspecialchars($opt) . "'" . ($opt===$curr_att_status?' selected':'') . ">" . htmlspecialchars($opt) . "</option>"; ?>
                        </select>
                    </td>
                    <?php for ($t = 1; $t <= 4; $t++):
                        $val = (isset($current_grades[$stId][$t])) ? $current_grades[$stId][$t] : '';
                    ?>
                    <td align="center"><input type="text" name="grades[<?=$stId?>][<?=$t?>]" value="<?=htmlspecialchars($val)?>" size="4" style="text-align:center;"></td>
                    <?php endfor; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (count($enrolled) === 0) echo "<tr><td colspan='6'>Brak studentów.</td></tr>"; ?>
            </table>
            <?php if (count($enrolled) > 0): ?>
            <div style="margin-top:8px;">
                <input type="submit" value="Zapisz / Edytuj oceny seryjne">
            </div>
            <?php endif; ?>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; // batch_grading ?>


        <!-- ============================================================ -->
        <!--  2. SEKCJE STUDENTÓW                                         -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_sections'){
            $sel_sid = $viewData['sel_sid'] ?? 0;
            if (isset($viewData['error_perm'])) { echo $viewData['error_perm']; }
            else {}
        ?>
        <h3>Zarządzanie Sekcjami</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="manage_sections">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz przedmiot --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($sel_sid > 0){
            $defined_sections = $viewData['defined_sections'] ?? [];
            $current_sec_id = $viewData['sel_sec_id'] ?? 0;
        ?>
        <br>
        <button onclick="toggleSection('addSectionForm')" style="margin-bottom:8px;">[+] Dodaj Sekcję</button>
        <div id="addSectionForm" class="hidden-section">
            <form method="post" action="login.php?action=add_defined_section">
                <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                Nazwa sekcji: <input type="text" name="section_name" required placeholder="np. Lab 1, Grupa A...">
                <input type="submit" value="Dodaj">
            </form>
        </div>
        <br>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>ID</th><th>Nazwa Sekcji</th><th>Akcje</th></tr>
            <?php foreach ($defined_sections as $ds):
                $isActive = ($ds['id'] === $current_sec_id);
                $nameStyle = $isActive ? "font-weight:bold; color:#2980b9;" : "";
            ?>
            <tr <?=($isActive?'class="nsh"':'')?>>
                <td><?=$ds['id']?></td>
                <td>
                    <a href="login.php?view_action=manage_sections&sid=<?=$sel_sid?>&sec_id=<?=$ds['id']?>" style="<?=$nameStyle?>">
                        <?=($isActive?'&raquo; ':'')?><?=htmlspecialchars($ds['name'])?>
                    </a>
                </td>
                <td>
                    <button onclick="toggleSection('editSecForm_<?=$ds['id']?>')" class="btn-sm">Edytuj</button>
                    <div id="editSecForm_<?=$ds['id']?>" class="hidden-section">
                        <form method="post" action="login.php?action=edit_defined_section">
                            <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                            <input type="hidden" name="section_id" value="<?=$ds['id']?>">
                            Nowa nazwa: <input type="text" name="section_name" value="<?=htmlspecialchars($ds['name'])?>" required>
                            <input type="submit" value="Zapisz">
                        </form>
                    </div>
                    <a href="login.php?action=delete_defined_section&sid=<?=$sel_sid?>&sec_id=<?=$ds['id']?>" onclick="return confirm('Usunąć sekcję i wszystkie przypisania?')" style="color:red;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($defined_sections)) echo "<tr><td colspan='3' align='center'>Brak sekcji.</td></tr>"; ?>
        </table>

        <?php if ($current_sec_id > 0){
            $students_in_sec = $viewData['students_in_section'] ?? [];
            $sec_name = $viewData['current_section_name'] ?? '';
        ?>
        <hr>
        <h4>Studenci w sekcji: <b><?=htmlspecialchars($sec_name)?></b></h4>
        <div style="margin-bottom:8px;">
            <a href="login.php?view_action=batch_add_students_view&sid=<?=$sel_sid?>&sec_id=<?=$current_sec_id?>">[+ Dodaj studentów do sekcji]</a>
            &nbsp;|&nbsp;
            <form method="post" action="login.php?action=sort_and_save_section" style="display:inline;">
                <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                <input type="hidden" name="section_id" value="<?=$current_sec_id?>">
                <input type="submit" value="&#8645; Sortuj A-Z">
            </form>
        </div>
        <form method="post" action="login.php?action=move_students_section">
            <input type="hidden" name="sid" value="<?=$sel_sid?>">
            <input type="hidden" name="from_sec_id" value="<?=$current_sec_id?>">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
                <tr>
                    <th><input type="checkbox" onclick="toggleAllStudents(this)"></th>
                    <th>Lp.</th><th>Student</th><th>Album</th><th>Akcja</th>
                </tr>
                <?php $cnt = 1; foreach ($students_in_sec as $st): ?>
                <tr>
                    <td align="center"><input type="checkbox" name="students[]" value="<?=$st[4]?>" class="st-chk"></td>
                    <td align="center" style="color:#999;"><?=$cnt++?></td>
                    <td><?=htmlspecialchars($st[1] . ' ' . $st[2])?></td>
                    <td align="center"><?=htmlspecialchars($st[5])?></td>
                    <td align="center">
                        <a href="login.php?action=remove_student_from_section&st_id=<?=$st[4]?>&sid=<?=$sel_sid?>&sec_id=<?=$current_sec_id?>" onclick="return confirm('Usunąć studenta z sekcji?')" style="color:red; font-weight:bold;">&times;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($students_in_sec)) echo "<tr><td colspan='5' align='center'>Brak studentów w tej sekcji.</td></tr>"; ?>
            </table>
            <?php if (!empty($students_in_sec) && count($defined_sections) > 1){ ?>
            <div style="margin-top:8px;">
                Zaznaczonych przenieś do:
                <select name="target_sec_id" required>
                    <option value="">-- wybierz sekcję --</option>
                    <?php foreach ($defined_sections as $ds): if ($ds['id'] === $current_sec_id) continue; ?>
                        <option value="<?=$ds['id']?>"><?=htmlspecialchars($ds['name'])?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Przenieś" onclick="return confirm('Przenieść wybranych studentów?')">
            </div>
            <?php } ?>
        </form>
			<?php } // current_sec_id ?>
			<?php } // sel_sid ?>
        <?php } // manage_sections ?>


        <!-- ============================================================ -->
        <!--  3. DODAWANIE STUDENTÓW DO SEKCJI                            -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'batch_add_students_view'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
            if (isset($viewData['error_perm'])) { echo $viewData['error_perm']; }
            else{}
        ?>
        <h3>Dodawanie studentów do przedmiotu</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="batch_add_students_view">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz przedmiot --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($sel_sid > 0):
            $defined_sections = $viewData['defined_sections'] ?? [];
            if (empty($defined_sections)):
        ?>
            <div style="background:#fee; padding:10px; border:1px solid #f99; margin-top:10px;">
                <b style="color:#c0392b;">Brak zdefiniowanych sekcji!</b><br>
                <a href="login.php?view_action=manage_sections&sid=<?=$sel_sid?>">Przejdź do zarządzania sekcjami</a>
            </div>
        <?php else:
            $pre_sec_id = $viewData['pre_sec_id'] ?? 0;
        ?>
        <br>
        <form method="post" action="login.php?action=batch_add_students_process">
            <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
            Przypisz do sekcji:
            <select name="section_id" required>
                <option value="">-- wybierz sekcję --</option>
                <?php foreach ($defined_sections as $ds): ?>
                    <option value="<?=$ds['id']?>" <?=($ds['id']===$pre_sec_id?'selected':'')?>>
                        <?=htmlspecialchars($ds['name'])?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" id="studentsTable" cellpadding="4">
                <tr><th>Lp.</th><th>Imię</th><th>Nazwisko</th></tr>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <tr>
                    <td align="center"><?=$i?>.</td>
                    <td><input type="text" name="imie_st[]" style="width:95%;"></td>
                    <td><input type="text" name="nazwisko_st[]" style="width:95%;"></td>
                </tr>
                <?php endfor; ?>
            </table>
            <div style="text-align:right; margin-top:4px;">
                <button type="button" onclick="addStudentRow()">+ Dodaj kolejnego</button>
            </div>
            <br>
            <input type="submit" value="Zapisz wszystkich studentów">
        </form>
        <script>
        function addStudentRow() {
            var table = document.getElementById("studentsTable");
            var rowCount = table.rows.length;
            var row = table.insertRow(rowCount);
            var c1 = row.insertCell(0); var c2 = row.insertCell(1); var c3 = row.insertCell(2);
            c1.align = "center"; c1.innerHTML = rowCount + ".";
            c2.innerHTML = '<input type="text" name="imie_st[]" style="width:95%;">';
            c3.innerHTML = '<input type="text" name="nazwisko_st[]" style="width:95%;">';
        }
        </script>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; // batch_add_students_view ?>


        <!-- ============================================================ -->
        <!--  15. EKSPORT                                                  -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'export_view'): ?>
        <h3>Eksport Danych (CSV/PDF)</h3>
        <div style="margin-bottom:12px;">
            <b>Eksport CSV</b>
            <form method="post" action="login.php?action=export_csv">
                Przedmiot:
                <select name="subject_id" required>
                    <option value="">-- wybierz --</option>
                    <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                        <option value="<?=$p[0]?>"><?=htmlspecialchars($p[1])?></option>
                    <?php endforeach; ?>
                </select><br>
                <label><input type="checkbox" name="include_grades" value="1" checked> Oceny ze wszystkich ćwiczeń</label><br>
                <label><input type="checkbox" name="include_attendance" value="1" checked> Statystyka obecności</label><br>
                <label><input type="checkbox" name="include_sections" value="1"> Sekcje / Grupy</label><br>
                <label><input type="checkbox" name="include_album" value="1" checked> Numer albumu</label><br>
                <input type="submit" value="Pobierz .CSV" style="background:#27ae60; color:white; border:none; padding:5px 15px; cursor:pointer;">
            </form>
        </div>
        <div style="margin-bottom:12px;">
            <b>Raport PDF</b>
            <form method="post" action="login.php?action=export_pdf_report">
                Przedmiot:
                <select name="subject_id" required>
                    <option value="">-- wybierz --</option>
                    <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                        <option value="<?=$p[0]?>"><?=htmlspecialchars($p[1])?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="submit" value="Pobierz Raport PDF" style="background:#c0392b; color:white; border:none; padding:5px 15px; cursor:pointer;">
            </form>
        </div>
        <div style="margin-bottom:12px;">
            <b>Lista ćwiczeń PDF</b>
            <form method="post" action="login.php?action=export_pdf_exercises">
                Przedmiot:
                <select name="subject_id" required>
                    <option value="">-- wybierz --</option>
                    <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                        <option value="<?=$p[0]?>"><?=htmlspecialchars($p[1])?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="submit" value="Pobierz listę ćwiczeń PDF" style="background:#2980b9; color:white; border:none; padding:5px 15px; cursor:pointer;">
            </form>
        </div>
        <div style="margin-bottom:12px;">
            <b>Lista zrealizowanych ćwiczeń PDF</b>
            <form method="post" action="login.php?action=export_pdf_exercises_done">
                Przedmiot:
                <select name="subject_id" required>
                    <option value="">-- wybierz --</option>
                    <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                        <option value="<?=$p[0]?>"><?=htmlspecialchars($p[1])?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="submit" value="Pobierz listę zrealizowanych ćwiczeń PDF" style="background:#8e44ad; color:white; border:none; padding:5px 15px; cursor:pointer;">
            </form>
        </div>
        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  16. OGŁOSZENIA                                               -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_announcements'):
            $announcements = $viewData['announcements'] ?? [];
            $subjects_available = $viewData['subjects_list'] ?? [];
            $sub_map_ann = [];
            foreach ($subjects_available as $s) { $p = explode(';', $s); $sub_map_ann[intval($p[0])] = $p[1]; }
            $ann_readers = $viewData['ann_readers'] ?? [];
            $student_name_map = $viewData['student_name_map'] ?? [];
            $ann_readers_detail_aid = $viewData['ann_readers_detail_aid'] ?? 0;
            $ann_readers_detail_list = $viewData['ann_readers_detail_list'] ?? [];
            // Zbuduj mapę id prowadzącego => imię nazwisko (z allUsers lub teachers_list)
            $teacher_name_map = [];
            foreach ($teachers_list as $t) {
                // teachers_list ma format ['name' => ..., 'inicjaly' => ..., 'id' => ...]
                if (isset($t['id'])) $teacher_name_map[intval($t['id'])] = $t['name'];
            }
        ?>
        <h3>Ogłoszenia</h3>
        <button onclick="toggleSection('addAnnForm')" style="margin-bottom:8px;">[+] Dodaj Ogłoszenie</button>
        <div id="addAnnForm" class="hidden-section">
            <form method="post" action="login.php?action=add_announcement&view_action=manage_announcements">
                Tytuł: <input type="text" name="title" required><br>
                Treść: <textarea name="content" rows="4" style="width:95%; box-sizing:border-box;" required></textarea><br>
                Widoczne dla:
                <select name="target">
                    <option value="global">Wszyscy (Ogólne)</option>
                    <?php foreach ($subjects_available as $s): $p = explode(';', $s); ?>
                        <option value="<?=$p[0]?>"><?=htmlspecialchars($p[1])?></option>
                    <?php endforeach; ?>
                </select><br>
                <input type="submit" value="Dodaj ogłoszenie">
                <button type="button" onclick="toggleSection('addAnnForm')">Zamknij</button>
            </form>
        </div>
        <br>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Data</th><th>Tytuł / Treść</th><th>Dla kogo</th><th>Dodał</th><th>Przeczytało</th><th>Akcje</th></tr>
            <?php
            if (count($announcements) === 0) { echo "<tr><td colspan='6'>Brak ogłoszeń.</td></tr>"; }
            else {
                $announcements = array_reverse($announcements);
                $i = 0;
                foreach ($announcements as $line) {
                    $p = explode(';', $line); if (count($p) < 6) continue;
                    $id = $p[0]; $title = htmlspecialchars($p[1]); $content = htmlspecialchars($p[2]);
                    $date = $p[3]; $target = $p[4]; $author_id = intval($p[5]);
                    $dotPos = strpos($content, '.');
                    $descHtml = $content;
                    if ($dotPos !== false && strlen($content) > $dotPos + 1) {
                        $short = substr($content, 0, $dotPos + 1);
                        $descHtml = "<span id='desc_short_{$id}'>{$short} <a href='#' onclick='toggleDesc({$id}); return false;'>[więcej]</a></span>"
                                  . "<span id='desc_full_{$id}' style='display:none;'>{$content} <a href='#' onclick='toggleDesc({$id}); return false;'>[mniej]</a></span>";
                    }
                    $target_display = ($target === 'global') ? '<b>Wszyscy</b>' : ('Przedmiot: ' . htmlspecialchars($sub_map_ann[intval($target)] ?? "ID:$target"));
                    // Autor
                    $author_display = htmlspecialchars($teacher_name_map[$author_id] ?? ($student_name_map[$author_id] ?? "ID:{$author_id}"));
                    // Przeczytało
                    $readers_count = count($ann_readers[intval($id)] ?? []);
                    $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                    echo "<tr class='{$cls}'>";
                    echo "<td style='white-space:nowrap;'>{$date}</td>";
                    echo "<td><b>{$title}</b><br><span style='font-size:0.9em;'>{$descHtml}</span></td>";
                    echo "<td>{$target_display}</td>";
                    echo "<td>{$author_display}</td>";
                    echo "<td>{$readers_count}</td>";
                    echo "<td><a href='login.php?view_action=manage_announcements&ann_readers={$id}'>Kto przeczytał</a> | <a href='login.php?action=delete_announcement&aid={$id}&view_action=manage_announcements' onclick='return confirm(\"Usunąć ogłoszenie?\")' style='color:red;'>Usuń</a></td>";
                    echo "</tr>";
                }
            }
            ?>
        </table>

        <?php if ($ann_readers_detail_aid > 0): ?>
        <h4>Kto przeczytał ogłoszenie #<?=intval($ann_readers_detail_aid)?></h4>
        <?php if (empty($ann_readers_detail_list)): ?>
            <p>Nikt jeszcze nie przeczytał tego ogłoszenia.</p>
        <?php else: ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Lp.</th><th>Imię i Nazwisko</th></tr>
            <?php $ri = 0; foreach ($ann_readers_detail_list as $rname): $rcls = ($ri % 2 == 0) ? 'n0' : 'n1'; $ri++; ?>
            <tr class="<?=$rcls?>">
                <td><?=$ri?></td>
                <td><?=htmlspecialchars($rname)?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; ?>

        <?php endif; ?>


        <!-- ============================================================ -->
        <!--  17. RANKING POSTĘPÓW                                         -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'progress_view'):
            $selected_sid = $viewData['selected_sid'] ?? 0;
        ?>
        <h3>Postęp ćwiczeń studentów</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="progress_view">
            Przedmiot: <select name="subject_id" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($selected_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($selected_sid > 0):
            $ranking = $viewData['progress_ranking'] ?? [];
            $subName = $viewData['subject_name'] ?? '';
        ?>
        <h4>Ranking postępów: <?=htmlspecialchars($subName)?></h4>
        <?php if (count($ranking) === 0): ?>
            <p>Brak studentów lub brak ćwiczeń.</p>
        <?php else: ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Student (Album)</th><th>Postęp</th><th>Szczegóły</th></tr>
            <?php $i = 0; foreach ($ranking as $r):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $percent = number_format($r['percent'], 1);
                $bar_color = ($r['percent'] == 100) ? '#27ae60' : (($r['percent'] >= 50) ? '#f1c40f' : '#e74c3c');
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars("{$r['name']} ({$r['album']})")?></td>
                <td>
                    <div class="progress-bar-outer">
                        <div class="progress-bar-inner" style="background:<?=$bar_color?>; width:<?=$percent?>%;">
                            <?=$percent?>%
                        </div>
                    </div>
                </td>
                <td align="center">
                    <b><?=$r['passed']?>/<?=$r['denom']?></b>
                    <?php if ($r['exempt'] > 0) echo "<br><small style='color:#666;'>(Zw: {$r['exempt']})</small>"; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p style="font-size:0.85em; color:#555;">* Zaliczenie wymaga: oceny &ge; 2.51, obecności i (jeśli wymagane) zaliczonego sprawozdania. Zw = odejmowane od puli.</p>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; // progress_view ?>


        <!-- ============================================================ -->
        <!--  18. ZWOLNIENIA                                               -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_exemptions'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
        ?>
        <h3>Zwolnienia z ćwiczeń</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="manage_exemptions">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($sel_sid > 0):
            $enrolled = $viewData['enrolled'] ?? [];
            $subject_exercises = $viewData['subject_exercises'] ?? [];
            $current_exemptions = $viewData['current_exemptions'] ?? [];
            $subName_e = 'Przedmiot';
            foreach ($subs as $s) { $p = explode(';', $s); if (intval($p[0]) === $sel_sid) $subName_e = $p[1]; }
        ?>
        <h4>Zwolnienia – <?=htmlspecialchars($subName_e)?></h4>
        <form method="post" action="login.php?action=save_exemptions&view_action=manage_exemptions&sid=<?=$sel_sid?>">
            <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
            <div style="overflow-x:auto;">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
                <tr>
                    <th>Student (Album)</th>
                    <?php foreach ($subject_exercises as $ex): ?>
                    <th>
                        <div class="th-tooltip">
                            <?=htmlspecialchars($ex[1])?>
                            <span class="tooltip-content">
                                <strong><?=htmlspecialchars($ex[1])?></strong><br>
                                Waga: <?=htmlspecialchars($ex[3]??'-')?><br>
                                <?php $desc_e = $ex[2]??''; $dp = strpos($desc_e, '.'); $sd = ($dp!==false)?substr($desc_e,0,$dp+1):$desc_e; echo htmlspecialchars($sd); ?>
                            </span>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
                <?php $i = 0; foreach ($enrolled as $e):
                    $parts = explode(';', $e); $stId = intval($parts[0]);
                    $stName_e = '???'; $stAlbum_e = '';
                    foreach ($students as $stud) { if (intval($stud[4]) === $stId) { $stName_e = "{$stud[1]} {$stud[2]}"; $stAlbum_e = $stud[5]; break; } }
                    $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                ?>
                <tr class="<?=$cls?>">
                    <td><?=htmlspecialchars($stName_e)?> (<?=htmlspecialchars($stAlbum_e)?>)</td>
                    <?php foreach ($subject_exercises as $ex):
                        $eid_e = intval($ex[0]);
                        $isChecked = isset($current_exemptions[$stId][$eid_e]) ? 'checked' : '';
                        $hasGrade = isset($grades_exist_map[$stId][$eid_e]);
                        $disabled = $hasGrade ? 'disabled' : '';
                        $tooltip = $hasGrade ? 'title="Student posiada już ocenę"' : '';
                    ?>
                    <td align="center" <?=$tooltip?>>
                        <input type="checkbox" name="exemptions[<?=$stId?>][<?=$eid_e?>]" value="1" <?=$isChecked?> <?=$disabled?> style="<?=($hasGrade?'cursor:not-allowed;':'cursor:pointer;')?>">
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (count($enrolled) === 0) echo "<tr><td colspan='" . (count($subject_exercises)+1) . "'>Brak studentów.</td></tr>"; ?>
            </table>
            </div>
            <?php if (count($enrolled) > 0 && count($subject_exercises) > 0): ?>
            <br><input type="submit" value="Zapisz zwolnienia">
            <?php endif; ?>
        </form>
        <?php endif; ?>
        <?php endif; // manage_exemptions ?>


        <!-- ============================================================ -->
        <!--  19. PRZEGLĄD OCEN STUDENTA                                   -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'student_grades_view'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
            $sel_stid = $viewData['sel_stid'] ?? 0;
        ?>
        <h3>Przegląd ocen studenta</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="student_grades_view">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($sel_sid > 0):
                $enrolled = $viewData['enrolled'] ?? [];
            ?>
            &nbsp;Student: <select name="stid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($enrolled as $e):
                    $parts = explode(';', $e); $stId = intval($parts[0]);
                    $stName_sg = '???'; $stAlbum_sg = '';
                    foreach ($students as $stud) { if (intval($stud[4]) === $stId) { $stName_sg = "{$stud[1]} {$stud[2]}"; $stAlbum_sg = $stud[5]; break; } }
                ?>
                <option value="<?=$stId?>" <?=($sel_stid===$stId?'selected':'')?>>
                    <?=htmlspecialchars("$stName_sg ($stAlbum_sg)")?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </form>
        <?php if ($sel_sid > 0 && $sel_stid > 0):
            $subject_exercises = $viewData['subject_exercises'] ?? [];
            $grades = $viewData['student_grades'] ?? [];
            $stData = $viewData['selected_student_data'] ?? null;
            $stDisplayName = $stData ? "{$stData[1]} {$stData[2]} ({$stData[5]})" : "Student ID: $sel_stid";
        ?>
        <h4>Oceny: <?=htmlspecialchars($stDisplayName)?></h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Ćwiczenie</th><th>T1</th><th>T2</th><th>T3</th><th>T4</th></tr>
            <?php $i = 0; if (count($subject_exercises) === 0) echo "<tr><td colspan='5'>Brak ćwiczeń.</td></tr>";
            foreach ($subject_exercises as $ex):
                $eid_sg = intval($ex[0]); $exName_sg = htmlspecialchars($ex[1]);
                $is_exempt = false;
                if (isset($grades[$eid_sg])) { foreach ($grades[$eid_sg] as $tv) { if (strtolower(trim($tv)) === 'zw') { $is_exempt = true; break; } } }
                if ($is_exempt) continue;
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><b><?=$exName_sg?></b></td>
                <?php for ($t = 1; $t <= 4; $t++):
                    $val = isset($grades[$eid_sg][$t]) ? htmlspecialchars($grades[$eid_sg][$t]) : '-';
                    $sty = '';
                    if (is_numeric(str_replace(',','.',$val))) {
                        $vn = floatval(str_replace(',','.',$val));
                        if ($vn >= 2.51) $sty = "color:green;font-weight:bold;";
                        elseif ($vn > 0) $sty = "color:red;";
                    }
                ?>
                <td align="center" style="<?=$sty?>"><?=$val?></td>
                <?php endfor; ?>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; // student_grades_view ?>


        <!-- ============================================================ -->
        <!--  20. EGZEKWOWANIE ZADAŃ                                       -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'enforce_tasks'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
        ?>
        <h3>Egzekwowanie zadań (Terminy)</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="enforce_tasks">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($sel_sid > 0):
            $enforcement_list = $viewData['enforcement_list'] ?? [];
            $subName_et = $viewData['subject_name'] ?? '';
        ?>
        <h4>Zbliżające się/minione terminy: <?=htmlspecialchars($subName_et)?></h4>
        <p style="font-size:0.9em; color:#555;">Lista zawiera studentów bez oceny pozytywnej (&ge;2.51) ani zwolnienia z danego ćwiczenia.</p>
        <?php if (count($enforcement_list) === 0): ?>
            <p>Brak zdefiniowanych terminów lub wszyscy studenci mają zaliczone ćwiczenia.</p>
        <?php else: ?>
            <?php foreach ($enforcement_list as $item):
                $eid_et = $item['eid'];
                $is_past = (time() > strtotime($item['deadline']));
                $style_date = $is_past ? "color:red;font-weight:bold;" : "color:green;font-weight:bold;";
                $status_txt = $is_past ? "TERMIN MINĄŁ" : "Termin zbliża się";
            ?>
            <div style="margin:8px 0;">
                <form method="post" action="login.php?action=apply_penalty&view_action=enforce_tasks&sid=<?=$sel_sid?>">
                    <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                    <input type="hidden" name="exercise_id" value="<?=$eid_et?>">
                    <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4" width="100%">
                        <tr class="nsh">
                            <td colspan="2"><b>Ćwiczenie: <?=htmlspecialchars($item['ename'])?></b><br>
                            Termin: <span style="<?=$style_date?>"><?=str_replace('T',' ',$item['deadline'])?></span> (<?=$status_txt?>)</td>
                            <td align="right"><input type="submit" value="Wstaw 2.00 zaznaczonym" onclick="return confirm('Wstawić 2.00?');" style="background:#e74c3c; color:white; border:none; padding:4px 8px; cursor:pointer;"></td>
                        </tr>
                        <tr><th align="left">Student</th><th align="center">Album</th><th align="center"><input type="checkbox" onclick="toggleAll(this, 'chk_<?=$eid_et?>')"></th></tr>
                        <?php foreach ($item['students'] as $st): ?>
                        <tr>
                            <td><?=htmlspecialchars($st['name'])?></td>
                            <td align="center"><?=htmlspecialchars($st['album'])?></td>
                            <td align="center"><input type="checkbox" name="students[]" value="<?=$st['id']?>" class="chk_<?=$eid_et?>"></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; // enforce_tasks ?>


        <!-- ============================================================ -->
        <!--  21. LOGI STUDENTÓW                                           -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'student_logs_view'):
            $logs = $viewData['logs'] ?? [];
            $filters = $viewData['filters'] ?? [];
            $topStats = $viewData['top_logins'] ?? [];
            $ip_list = array_unique(array_filter(array_column($logs, 'ip')));
            sort($ip_list);
            $selected_ip = isset($_GET['f_ip']) ? trim($_GET['f_ip']) : '';
            if ($selected_ip !== '') {
                $logs = array_filter($logs, fn($row) => $row['ip'] === $selected_ip);
            }
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $total_items = count($logs);
            $total_pages = max(1, ceil($total_items / $perPage));
            $page = min($page, $total_pages);
            $offset = ($page - 1) * $perPage;
            $logs_to_show = array_slice($logs, $offset, $perPage);
            $filterParams = "&f_student=" . urlencode($filters['student'] ?? '') .
                            "&f_date_from=" . urlencode($filters['d_from'] ?? '') .
                            "&f_date_to=" . urlencode($filters['d_to'] ?? '') .
                            "&f_device=" . urlencode($filters['dev'] ?? '') .
                            "&f_ip=" . urlencode($selected_ip);
        ?>
        <h3>Historia Logowań Studentów</h3>
        <?php if (!empty($topStats)): ?>
        <h4>Najczęściej logujący się</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Student</th><th>Logowania</th></tr>
            <?php foreach ($topStats as $stat): ?>
            <tr class="n0">
                <td><?=htmlspecialchars($stat['name'])?> (<?=htmlspecialchars($stat['album'])?>)</td>
                <td align="center"><b><?=$stat['count']?></b></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <hr>
        <?php endif; ?>
        <h4>Filtrowanie</h4>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="student_logs_view">
            Student: <input type="text" name="f_student" value="<?=htmlspecialchars($filters['student']??'')?>" placeholder="Imię, nazwisko lub album"><br>
            Data od: <input type="date" name="f_date_from" value="<?=htmlspecialchars($filters['d_from']??'')?>">&nbsp;
            do: <input type="date" name="f_date_to" value="<?=htmlspecialchars($filters['d_to']??'')?>"><br>
            Urządzenie: <select name="f_device">
                <option value="">-- Wszystkie --</option>
                <option value="mobile" <?=($filters['dev']??'')==='mobile'?'selected':''?>>Telefon/Tablet</option>
                <option value="desktop" <?=($filters['dev']??'')==='desktop'?'selected':''?>>Komputer</option>
            </select>&nbsp;
            IP: <select name="f_ip">
                <option value="">-- Wszystkie --</option>
                <?php foreach ($ip_list as $ip_addr) echo "<option value='" . htmlspecialchars($ip_addr) . "'" . ($selected_ip===$ip_addr?' selected':'') . ">" . htmlspecialchars($ip_addr) . "</option>"; ?>
            </select><br>
            <input type="submit" value="Filtruj">
            <a href="login.php?view_action=student_logs_view">[Wyczyść filtry]</a>
        </form>
        <hr>
        <h4>Rejestr logowań (znaleziono: <?=$total_items?>)</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Data i Godzina</th><th>Student (Album)</th><th>Adres IP</th><th>Urządzenie</th></tr>
            <?php $i = 0; foreach ($logs_to_show as $l):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $devStyle = (strpos($l['device'], 'Mobile') !== false) ? 'color:#d35400;' : 'color:#2980b9;';
            ?>
            <tr class="<?=$cls?>">
                <td align="center"><?=htmlspecialchars($l['date'])?></td>
                <td><?=htmlspecialchars("{$l['st_name']} ({$l['st_album']})")?></td>
                <td align="center"><?=htmlspecialchars($l['ip'])?></td>
                <td align="center" style="<?=$devStyle?>"><?=htmlspecialchars($l['device'])?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($logs_to_show) === 0) echo "<tr><td colspan='4'>Brak wpisów.</td></tr>"; ?>
        </table>
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <a href="login.php?view_action=student_logs_view&page=<?=max(1,$page-1)?><?=$filterParams?>" class="<?=($page<=1?'disabled':'')?>">&laquo;</a>
            <?php for ($pg = 1; $pg <= $total_pages; $pg++): ?>
                <a href="login.php?view_action=student_logs_view&page=<?=$pg?><?=$filterParams?>" class="<?=($pg===$page?'active':'')?>"><?=$pg?></a>
            <?php endfor; ?>
            <a href="login.php?view_action=student_logs_view&page=<?=min($total_pages,$page+1)?><?=$filterParams?>" class="<?=($page>=$total_pages?'disabled':'')?>">&raquo;</a>
        </div>
        <small>Pokazano <?=($total_items>0?$offset+1:0)?>-<?=min($offset+$perPage,$total_items)?> z <?=$total_items?></small>
        <?php endif; ?>
        <?php endif; // student_logs_view ?>


        <!-- ============================================================ -->
        <!--  22. OCENY KOŃCOWE                                            -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'final_grades_view'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
        ?>
        <h3>Oceny końcowe z przedmiotu</h3>
        <form method="get" action="login.php">
            <input type="hidden" name="view_action" value="final_grades_view">
            Przedmiot: <select name="sid" onchange="this.form.submit()">
                <option value="">-- wybierz --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($sel_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($sel_sid > 0):
            $student_list = $viewData['student_list'] ?? [];
            $calculated_avgs = $viewData['calculated_avgs'] ?? [];
            $final_grades_saved = $viewData['final_grades_saved'] ?? [];
            $subName_fg = $viewData['subject_name'] ?? '';
        ?>
        <h4>Przedmiot: <?=htmlspecialchars($subName_fg)?></h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr>
                <th>Student (Album)</th>
                <th>Śr. ważona</th>
                <th>Komentarz</th>
                <th>Ocena końcowa</th>
            </tr>
            <?php $i = 0; if (count($student_list) === 0) echo "<tr><td colspan='4'>Brak studentów.</td></tr>";
            foreach ($student_list as $stud):
                $stId = intval($stud[4]);
                $avgData = $calculated_avgs[$stId] ?? ['avg'=>0,'is_complete'=>false];
                $savedData = $final_grades_saved[$stId] ?? ['grade'=>'','comment'=>''];
                $savedVal = $savedData['grade']; $savedComment = $savedData['comment'];
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
                $avgDisplay = number_format($avgData['avg'], 2);
                $avgStyle = $avgData['is_complete'] ? "color:green;font-weight:bold;" : "color:gray;";
                $statusInfo = $avgData['is_complete'] ? "" : "<br><small style='color:red;font-weight:normal;font-size:0.8em;'>W trakcie zaliczania</small>";
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars("{$stud[1]} {$stud[2]} ({$stud[5]})")?></td>
                <td align="center" style="<?=$avgStyle?>"><?=$avgDisplay?><?=$statusInfo?></td>
                <form method="post" action="login.php?action=save_final_grade">
                    <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                    <input type="hidden" name="student_id" value="<?=$stId?>">
                    <td align="center">
                        <input type="text" name="final_comment" value="<?=htmlspecialchars($savedComment)?>" placeholder="Komentarz..." style="width:90%;">
                    </td>
                    <td align="center">
                        <select name="final_grade">
                            <option value="" <?=($savedVal==''?'selected':'')?>>-- wybierz --</option>
                            <option value="2.0" <?=($savedVal=='2.0'?'selected':'')?>>bez zaliczenia</option>
                            <option value="3.00" <?=($savedVal=='3.00'?'selected':'')?>>3.00</option>
                            <option value="3.50" <?=($savedVal=='3.50'?'selected':'')?>>3.50</option>
                            <option value="4.00" <?=($savedVal=='4.00'?'selected':'')?>>4.00</option>
                            <option value="4.50" <?=($savedVal=='4.50'?'selected':'')?>>4.50</option>
                            <option value="5.00" <?=($savedVal=='5.00'?'selected':'')?>>5.00</option>
                        </select>
                        <input type="submit" value="Zapisz" style="background:#27ae60; color:white; border:none; padding:3px 8px; cursor:pointer;">
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; // final_grades_view ?>


        <!-- ============================================================ -->
        <!--  23. PODANIA                                                   -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'manage_applications'):
            $current_tab = $viewData['tab'];
            $appsList = $viewData['applications_list'] ?? [];
            $subMap_app = $viewData['subjects_map'] ?? [];
            $stMap_app = $viewData['students_map'] ?? [];
        ?>
        <h3>Podania studentów</h3>
        <div style="margin-bottom:12px;">
            <a href="login.php?view_action=manage_applications&tab=pending" style="font-weight:<?=($current_tab==='pending'?'bold':'normal');?>;<?=($current_tab==='pending'?'text-decoration:underline;':'')?>">
                [OCZEKUJĄCE]
            </a> &nbsp;|&nbsp;
            <a href="login.php?view_action=manage_applications&tab=accepted" style="font-weight:<?=($current_tab==='accepted'?'bold':'normal');?>; color:<?=($current_tab==='accepted'?'green':'#555');?>">
                [ZAAKCEPTOWANE]
            </a> &nbsp;|&nbsp;
            <a href="login.php?view_action=manage_applications&tab=rejected" style="font-weight:<?=($current_tab==='rejected'?'bold':'normal');?>; color:<?=($current_tab==='rejected'?'red':'#555');?>">
                [ODRZUCONE]
            </a>
        </div>
        <?php if (empty($appsList)): ?>
            <p>Brak podań w tej kategorii.</p>
        <?php else: ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr>
                <th>Data</th>
                <th>Student</th>
                <th>Przedmiot</th>
                <th>Plik / Opis</th>
                <th>Akcja / Komentarz</th>
            </tr>
            <?php $i = 0; foreach ($appsList as $app):
                $appId = $app[0]; $stId = intval($app[1]); $subId = intval($app[3]);
                $filePath = htmlspecialchars($app[4]); $desc = htmlspecialchars($app[5]);
                $date = htmlspecialchars($app[6]); $status = $app[7];
                $existingComment = htmlspecialchars($app[8]); $fileName = htmlspecialchars($app[9]);
                $stName = $stMap_app[$stId] ?? 'Nieznany';
                $subName_app = $subMap_app[$subId] ?? 'Nieznany';
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td align="center" style="white-space:nowrap;"><?=$date?></td>
                <td><?=htmlspecialchars($stName)?></td>
                <td><?=htmlspecialchars($subName_app)?></td>
                <td>
                    <b>Plik:</b> <a href="<?=$filePath?>" target="_blank"><?=$fileName?></a>
                    <?php if (!empty($desc)) echo "<br><b>Opis:</b> <i style='font-size:0.9em;'>{$desc}</i>"; ?>
                </td>
                <td>
                    <?php if ($current_tab === 'pending'): ?>
                    <form method="post" action="login.php?action=evaluate_application" style="margin:0;">
                        <input type="hidden" name="app_id" value="<?=$appId?>">
                        <textarea name="comment" rows="2" style="width:98%; box-sizing:border-box; margin-bottom:5px;" placeholder="Komentarz..."></textarea><br>
                        <button type="submit" name="status" value="accepted" style="background:#27ae60; color:white; border:none; padding:4px 10px; cursor:pointer; font-weight:bold;">&#10004; Akceptuj</button>
                        <button type="submit" name="status" value="rejected" style="background:#c0392b; color:white; border:none; padding:4px 10px; cursor:pointer; font-weight:bold;">&#10006; Odrzuć</button>
                    </form>
                    <?php else: ?>
                    <b>Status:</b> <?=($status==='accepted'?'<span style="color:green;">Zaakceptowane</span>':'<span style="color:red;">Odrzucone</span>')?><br>
                    <b>Komentarz:</b> <?=$existingComment?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; // manage_applications ?>
<!-- ============================================================ -->
        <!--  24. EDYCJA SPRAWOZDAŃ                                        -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'edit_reports'):
            $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        ?>
        <h3>Edycja sprawozdań</h3>

        <?php if ($sel_sid === 0): ?>
        <h4>Wybierz przedmiot</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Id</th><th>Nazwa</th><th>Rok</th><th>Akcja</th></tr>
            <?php
            $i = 0;
            foreach ($subs as $s) {
                $p = explode(';', $s, 5);
                $cls = ($i++ % 2 === 0) ? 'n0' : 'n1';
                echo "<tr class='{$cls}'>";
                echo "<td>{$p[0]}</td><td>".htmlspecialchars($p[1])."</td><td>".htmlspecialchars($p[2])."</td>";
                echo "<td><a href='login.php?view_action=edit_reports&sid={$p[0]}'>Wybierz</a></td>";
                echo "</tr>";
            }
            ?>
        </table>

        <?php elseif ($sel_sid > 0):
            $er_data             = $viewData['edit_reports_data'] ?? [];
            $er_subject_name     = $er_data['subject_name']       ?? '';
            $er_exercises        = $er_data['exercises']           ?? [];
            $er_enrolled_students= $er_data['enrolled_students']   ?? [];
            $er_reports          = $er_data['reports']             ?? [];
            $er_history_all      = $er_data['history_all']         ?? [];
            $er_student_names    = $er_data['student_names']       ?? [];
            $er_exercise_names   = $er_data['exercise_names']      ?? [];

            $er_msg = $_GET['er_msg'] ?? '';
            $er_err = $_GET['er_err'] ?? '';
            if ($er_msg) echo "<div style='color:green; font-weight:bold; margin-bottom:8px;'>".htmlspecialchars($er_msg)."</div>";
            if ($er_err) echo "<div style='color:red;   font-weight:bold; margin-bottom:8px;'>".htmlspecialchars($er_err)."</div>";
        ?>
        <A HREF="login.php?view_action=edit_reports"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h4>Przedmiot: <?php echo htmlspecialchars($er_subject_name); ?></h4>

        <h4>Wpisanie zaliczonego sprawozdania (bez pliku studenta)</h4>
        <form method="post" action="login.php?action=er_add_report&view_action=edit_reports&sid=<?php echo $sel_sid; ?>">
            <input type="hidden" name="subject_id" value="<?php echo $sel_sid; ?>">
            Student:
            <select name="student_id" required>
                <option value="">-- wybierz --</option>
                <?php foreach ($er_enrolled_students as $stId => $stName): ?>
                <option value="<?php echo $stId; ?>"><?php echo htmlspecialchars($stName); ?></option>
                <?php endforeach; ?>
            </select>
            Ćwiczenie:
            <select name="exercise_id" required>
                <option value="">-- wybierz --</option>
                <?php foreach ($er_exercises as $ex): ?>
                <option value="<?php echo intval($ex[0]); ?>"><?php echo htmlspecialchars($ex[1]); ?></option>
                <?php endforeach; ?>
            </select>
            Komentarz:
            <input type="text" name="comment" placeholder="Opcjonalny komentarz" size="40">
            <input type="submit" value="Wpisz jako ZALICZONE">
        </form>

        <h4 style="margin-top:20px;">Historia statusów sprawozdań – edycja / usuwanie wpisów</h4>
        <?php if (empty($er_reports)): ?>
        <p>Brak sprawozdań dla tego przedmiotu.</p>
        <?php else: ?>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="3" style="width:100%; font-size:0.85em; table-layout:fixed;">
            <colgroup>
                <col style="width:150px;">
                <col style="width:140px;">
                <col style="width:auto;">
                <col style="width:115px;">
                <col style="width:120px;">
                <col style="width:50px;">
                <col style="width:38px;">
            </colgroup>
            <thead>
                <tr>
                    <th>Ćwiczenie</th>
                    <th>Data</th>
                    <th>Komentarz</th>
                    <th>Status</th>
                    <th>Nowy komentarz</th>
                    <th>Akcja</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($er_reports as $stId => $exercises_map):
                $stName  = htmlspecialchars($er_student_names[$stId] ?? "Student $stId");
                /* szukamy numeru albumu w globalnej tablicy $students */
                $stAlbum = '';
                foreach ($students as $stud) {
                    if (intval($stud[4]) === (int)$stId) { $stAlbum = htmlspecialchars($stud[5]); break; }
                }
            ?>
            <tr class="nsh">
                <td colspan="7" style="font-weight:bold; padding:4px 6px;">
                    <?= $stName ?>
                    <?php if ($stAlbum): ?>
                        <span style="font-weight:normal; color:#555; font-size:0.9em;">(<?= $stAlbum ?>)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
                foreach ($exercises_map as $exId => $rep_info):
                    $exName  = htmlspecialchars($er_exercise_names[$exId] ?? "Ćwiczenie $exId");
                    $rid     = $rep_info['rid'];
                    $history = $er_history_all[$rid] ?? [];
                    if (empty($history)):
            ?>
            <tr class="n1">
                <td style="font-style:italic;"><?= $exName ?></td>
                <td colspan="6" style="color:#bbb;">brak wpisów</td>
            </tr>
            <?php
                    else:
                        $h_num = 0;
                        foreach ($history as $h):
                            $hid      = $h['hid'];
                            $h_cls    = ($h_num % 2 === 0) ? 'n0' : 'n1';
                            $st_val   = $h['status'];
                            $st_color = ($st_val === 'zal') ? 'green' : (($st_val === 'zwr') ? '#c0392b' : '#888');
            ?>
            <tr class="<?= $h_cls ?>" style="vertical-align:middle;">
                <td style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= $exName ?>"><?= $exName ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($h['date']) ?></td>
                <td style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($h['comment']) ?>"><?= htmlspecialchars($h['comment']) ?></td>
                <td align="center">
                    <form method="post" action="login.php?action=er_edit_history&view_action=edit_reports&sid=<?= $sel_sid ?>" style="margin:0;" id="f_<?= $hid ?>">
                        <input type="hidden" name="history_id" value="<?= $hid ?>">
                        <input type="hidden" name="report_id"  value="<?= $rid ?>">
                        <select name="new_status" style="width:100%; font-size:0.85em;">
                            <?php foreach (['zal', 'zwr', 'do_sprawdzenia'] as $st_opt): ?>
                            <option value="<?= $st_opt ?>" <?= ($st_opt === $st_val) ? 'selected' : '' ?>><?= $st_opt ?></option>
                            <?php endforeach; ?>
                        </select>
                </td>
                <td>
                        <input type="text" name="new_comment" value="<?= htmlspecialchars($h['comment']) ?>" placeholder="komentarz" style="width:100%; font-size:0.85em; box-sizing:border-box;">
                </td>
                <td align="center">
                        <input type="submit" value="Zapisz" class="btn-sm">
                    </form>
                </td>
                <td align="center">
                    <a href="login.php?action=er_delete_history&view_action=edit_reports&sid=<?= $sel_sid ?>&hid=<?= $hid ?>&rid=<?= $rid ?>"
                       onclick="return confirm('Na pewno usunąć ten wpis historii?')"
                       style="color:#c0392b; font-weight:bold;">&times;</a>
                </td>
            </tr>
            <?php
                            $h_num++;
                        endforeach;
                    endif;
                endforeach;
            endforeach;
            ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php endif; ?>
        <?php endif; // edit_reports ?>


        <!-- ============================================================ -->
        <!--  25. SZUKAJ STUDENTA                                          -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'szukaj_studenta'):
            $ss_q        = trim($_GET['ss_q'] ?? '');
            $ss_preview_stid = isset($_GET['ss_stid']) ? intval($_GET['ss_stid']) : 0;
            $ss_preview_sid  = isset($_GET['ss_sid'])  ? intval($_GET['ss_sid'])  : 0;
            $ss_results  = $viewData['szukaj_wyniki']  ?? [];
            $ss_subj_map = $viewData['szukaj_subj_map'] ?? [];
            $ss_owner_map = $viewData['szukaj_owner_map'] ?? [];
        ?>
        <h3>Szukaj studenta</h3>

        <?php if ($ss_preview_stid > 0 && $ss_preview_sid > 0):
            // ============================================================
            // PODGLĄD OCEN STUDENTA – replika tabeli z panelu studenta
            // ============================================================
            $pv_data = $viewData['szukaj_preview'] ?? [];
            $pv_stud = $pv_data['student']        ?? null;
            $pv_subj_name = $pv_data['subject_name'] ?? '';
            $pv_subj_rok  = $pv_data['subject_rok']  ?? '';
            $pv_grades    = $pv_data['grades']        ?? [];
            $pv_subj_ex   = $pv_data['subject_exercises'] ?? [];
            $pv_att       = $pv_data['attendance']    ?? [];
            $pv_lsbe      = $pv_data['latestStatusByExercise'] ?? [];
            $pv_student_reports = $pv_data['studentReports'] ?? [];
            $pv_cwCriteria = $pv_data['cwCriteria']  ?? [];
            $pv_weights   = $pv_data['weights']      ?? [];
            $pv_stName    = $pv_stud ? htmlspecialchars("{$pv_stud[1]} {$pv_stud[2]}") : "Student ID: $ss_preview_stid";
            $pv_stAlbum   = $pv_stud[5] ?? '';
        ?>
        <A HREF="login.php?view_action=szukaj_studenta&ss_q=<?=urlencode($ss_q)?>&ss_stid=<?=$ss_preview_stid?>"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h4>Oceny studenta: <?=$pv_stName?> <?= $pv_stAlbum ? "({$pv_stAlbum})" : '' ?></h4>
        <h4>Przedmiot: <?=htmlspecialchars($pv_subj_name)?> <?=htmlspecialchars($pv_subj_rok)?></h4>

        <?php
        // ---- Replika logiki tabeli ocen z st_login.php ----
        $pv_myGrades_all = $pv_grades; // już przefiltrowane wg studenta i przedmiotu

        // Ustal terminy
        $pv_terminy = [];
        foreach ($pv_myGrades_all as $g) {
            $p = explode(';', $g, 9);
            $pv_terminy[] = intval($p[8] ?? 1);
        }
        $pv_terminy = array_unique($pv_terminy);
        if (count($pv_terminy) == 0) $pv_terminy = [1];
        sort($pv_terminy);

        $pv_ile_column_zlaczen = 3 + count($pv_terminy) - 1;

        // Zbierz oceny wg ćwiczenia i terminu
        $pv_oceny_z_cw = [];
        foreach ($pv_subj_ex as $se) {
            $pv_oceny_z_cw[intval($se[0])] = [];
        }
        $pv_cw_id_zwolniony = [];
        foreach ($pv_myGrades_all as $g) {
            $p = explode(';', $g, 9);
            $id_cw = intval($p[7] ?? 0);
            $val   = $p[4] ?? '';
            $term  = intval($p[8] ?? 1);
            $idOceny = $p[0] ?? 0;
            $note  = $p[5] ?? '';
            $date  = $p[6] ?? '';
            if (!isset($pv_oceny_z_cw[$id_cw])) $pv_oceny_z_cw[$id_cw] = [];
            $pv_oceny_z_cw[$id_cw][] = [$idOceny, $val, $note, $date, $term];
            if ($val === 'zw' && !in_array($id_cw, $pv_cw_id_zwolniony)) {
                $pv_cw_id_zwolniony[] = $id_cw;
            }
        }

        $pv_suma_wazona = 0;
        $pv_suma_wag = 0;
        $pv_suma_zcw = 0;
        $pv_ile_cw_z_ocena = 0;
        $pv_all_zal = true;
        $pv_czy_ma_zwolnienie = false;
        ?>
        <TABLE CELLPADDING="2" CELLSPACING="0" BORDER="1" ALIGN="center" FRAME="border">
        <TR><TH>Ćwiczenie</TH>
        <?php foreach ($pv_terminy as $t): ?>
        <TH>Termin <?=$t?></TH>
        <?php endforeach; ?>
        <TH>Sprawdzian</TH><TH>Obecność</TH><TH COLSPAN="2">Sprawozdanie</TH><TH>Zaliczenie ćwiczenia</TH></TR>
        <?php
        foreach ($pv_subj_ex as $ex_row):
            $ID_cw   = intval($ex_row[0]);
            $cw_name = htmlspecialchars($ex_row[1] ?? '');

            if (in_array($ID_cw, $pv_cw_id_zwolniony)) continue;

            echo "<TR><TD ALIGN='center'>{$cw_name}</TD>";

            $czy_zwolniony_z_cw = false;
            $suma_z_cw = 0;
            $ile_ocen  = 0;
            $czy_zal_termin = false;

            foreach ($pv_terminy as $termin) {
                $found_in_term = false;
                if (isset($pv_oceny_z_cw[$ID_cw])) {
                    foreach ($pv_oceny_z_cw[$ID_cw] as $oc) {
                        if (intval($oc[4]) === $termin) {
                            $found_in_term = true;
                            $val = $oc[1];
                            if ($val === 'zw') {
                                $pv_czy_ma_zwolnienie = true;
                                echo '<TD><a href=""></a></TD>';
                                $czy_zwolniony_z_cw = true;
                            } else {
                                echo '<TD ALIGN="right">' . htmlspecialchars($val) . '</TD>';
                                if (is_numeric($val)) { $suma_z_cw += floatval($val); }
                                elseif ($val == "(0)") { $suma_z_cw += 2; }
                                else { $suma_z_cw += floatval(substr($val, 1, 1)); }
                                if (floatval(str_replace(',','.',$val)) >= 2.51 || $val === 'zw') $czy_zal_termin = true;
                            }
                            $ile_ocen++;
                            break;
                        }
                    }
                }
                if (!$found_in_term) echo '<TD></TD>';
            }

            // Sprawdzian (pusta kolumna – identycznie jak w st_login.php nie ma tam osobnej wartości, to środkowa kolumna sumy)
            if ($ile_ocen == 0) {
                echo '<TD>bez zaliczenia</TD>';
            } else {
                if ($czy_zwolniony_z_cw) {
                    echo '<TD></TD>';
                } else {
                    $pv_ile_cw_z_ocena++;
                    $avg_z_cw = $suma_z_cw / $ile_ocen;
                    echo '<TD ALIGN="right">' . number_format($avg_z_cw, 2, '.', '') . '</TD>';
                    $pv_suma_zcw += $avg_z_cw;
                    $cw_weight = $pv_weights[$ID_cw] ?? 1.0;
                    $pv_suma_wazona += $avg_z_cw * $cw_weight;
                    $pv_suma_wag   += $cw_weight;
                }
            }

            // Obecność
            echo '<TD ALIGN="left">';
            if (isset($pv_att[$ID_cw])) {
                $att_status = htmlspecialchars($pv_att[$ID_cw]);
                if ($att_status === 'O') {
                    echo "<span style='color:green;'>O</span>";
                    $ma_obecnosc = true;
                } elseif ($att_status === 'N') {
                    echo "<span style='color:red;'>N</span>";
                    $ma_obecnosc = false;
                } elseif ($att_status === 'U') {
                    echo "<span style='color:orange;'>U</span>";
                    $ma_obecnosc = true;
                } else {
                    echo $att_status; // pełna nazwa słowna: obecny, nieobecny, spóźniony itd.
                    $att_lower = strtolower(trim($att_status));
                    $ma_obecnosc = in_array($att_lower, ['obecny', 'spóźniony', 'odrobione']);
                    if ($att_lower === 'nieobecny') $ma_obecnosc = false;
                }
            } else {
                echo '&nbsp;';
                $ma_obecnosc = false;
            }
            echo '</TD>';

            // Sprawozdanie
            echo "<TD COLSPAN='2' ALIGN='left'>";
            if (isset($pv_lsbe[$ID_cw])) {
                $rep_status = $pv_lsbe[$ID_cw]['status'];
                $rep_comment = $pv_lsbe[$ID_cw]['comment'] ?? '';
                switch ($rep_status) {
                    case 'zal': $rep_txt = 'zaliczone'; break;
                    case 'zwr': $rep_txt = 'zwrot'; break;
                    default:    $rep_txt = 'oddane'; break;
                }
                echo "<span title='Komentarz: " . htmlspecialchars($rep_comment) . "'>{$rep_txt}</span>";
                $report_is_passed = ($pv_lsbe[$ID_cw]['is_passed'] ?? false) || ($rep_status === 'zal');
            } elseif (isset($pv_student_reports[$ID_cw]) && count($pv_student_reports[$ID_cw]) > 0) {
                $report_is_passed = false;
                $rep_count = count($pv_student_reports[$ID_cw]);
                echo "oddane ({$rep_count})";
            } else {
                $report_is_passed = false;
                echo '&nbsp;';
            }
            echo '</TD>';

            // Zaliczenie ćwiczenia – logika identyczna jak w panelu studenta (st_login.php)
            echo '<TD>';
            $is_cw_zaliczone = false;
            $cw_zal_text = '';

            $criteria       = $pv_cwCriteria[$ID_cw] ?? null;
            $req_grade      = $criteria ? $criteria['req_grade']      : false;
            $req_report     = $criteria ? $criteria['req_report']     : false;
            $req_attendance = $criteria ? $criteria['req_attendance'] : false;

            // Status sprawozdania
            $rep_status_raw   = $pv_lsbe[$ID_cw]['status'] ?? 'brak';
            $report_is_passed = ($pv_lsbe[$ID_cw]['is_passed'] ?? false) || ($rep_status_raw === 'zal');

            if ($czy_zwolniony_z_cw) {
                $cw_zal_text = 'zwolniony';
                $is_cw_zaliczone = true;
            } else {
                $reasons_fail = [];

                // Kryterium 1: Ocena >= 2.51
                $grade_condition_met = true;
                if ($req_grade) {
                    if ($ile_ocen == 0 || !$czy_zal_termin) {
                        $grade_condition_met = false;
                        $reasons_fail[] = 'ocena';
                    }
                }
                // Gdy req_grade=0: ocena nie jest wymagana, więc nie blokujemy zaliczenia
                // nawet jeśli student ma ocenę < 2.51 (inne kryteria decydują)

                // Kryterium 2: Zaliczone sprawozdanie
                $report_condition_met = true;
                if ($req_report) {
                    if (!$report_is_passed) {
                        $report_condition_met = false;
                        $reasons_fail[] = 'sprawozdanie';
                    }
                }

                // Kryterium 3: Obecność
                $attendance_condition_met = true;
                if ($req_attendance) {
                    if (!$ma_obecnosc) {
                        $attendance_condition_met = false;
                        $reasons_fail[] = 'obecność';
                    }
                }

                // Stare zachowanie gdy brak zdefiniowanych kryteriów
                if (!$req_grade && !$req_report && !$req_attendance && $ile_ocen > 0) {
                    $grade_condition_met      = $czy_zal_termin;
                    $attendance_condition_met = $ma_obecnosc;
                }

                $has_any_criteria = $req_grade || $req_report || $req_attendance;

                if (!$has_any_criteria && $ile_ocen == 0) {
                    $cw_zal_text = 'bez zaliczenia';
                    $is_cw_zaliczone = false;
                } elseif ($grade_condition_met && $report_condition_met && $attendance_condition_met) {
                    $avg_display = ($ile_ocen > 0 && isset($avg_z_cw)) ? number_format($avg_z_cw, 2, '.', '') : '';
                    $cw_zal_text = $avg_display;
                    $is_cw_zaliczone = true;
                } else {
                    $cw_zal_text = 'bez zaliczenia';
                    $is_cw_zaliczone = false;
                }
            }

            if (!$is_cw_zaliczone && !$czy_zwolniony_z_cw) {
                $pv_all_zal = false;
            }

            echo $cw_zal_text;
            echo '</TD></TR>';
        endforeach;

        // Wiersz sumy/średniej
        $pv_weighted_avg = ($pv_suma_wag > 0) ? number_format($pv_suma_wazona / $pv_suma_wag, 3, '.', '') : '0.000';
        echo "<TR><TD ALIGN='right' colspan='{$pv_ile_column_zlaczen}'>Średnia:</TD><TD ALIGN='right'>{$pv_weighted_avg}</TD><TD colspan='2' align='right'>Zaliczenie:</TD><TD ALIGN='right'>";

        // Ocena końcowa z bazy danych MySQL
        $pv_final_display = '';
        global $finalGradesFile;
        foreach (read_lines($finalGradesFile) as $fg) {
            $fgp = explode(';', $fg);
            if (count($fgp) >= 5 && intval($fgp[1]) === $ss_preview_stid && intval($fgp[2]) === $ss_preview_sid) {
                $pv_final_display = htmlspecialchars($fgp[3]) . '<br>' . htmlspecialchars($fgp[4]);
                break;
            }
        }
        echo $pv_final_display;

        if ($pv_weighted_avg >= 2.51 && $pv_all_zal) {
            if ($pv_final_display === '') echo 'zaliczone';
        } else {
            if ($pv_ile_cw_z_ocena == 0 && $pv_czy_ma_zwolnienie) {
                if ($pv_final_display === '') echo 'zaliczone';
            } else {
                echo 'bez zaliczenia';
            }
        }
        echo '</TD></TR>';
        ?>
        </TABLE>
        <P> (0) - nieobecność, nieoddana kartka itp.; oznacza ocenę 2</P>

        <?php else: // nie ma preview – wyświetl formularz szukania i ewentualne wyniki ?>

        <form method="get" action="login.php" style="margin-bottom:10px;">
            <input type="hidden" name="view_action" value="szukaj_studenta">
            Szukaj: <input type="text" name="ss_q" value="<?=htmlspecialchars($ss_q)?>" placeholder="imię lub nazwisko studenta">
            <input type="submit" value="Szukaj">
            <?php if ($ss_q !== ''): ?>
                <a href="login.php?view_action=szukaj_studenta">[Wyczyść]</a>
            <?php endif; ?>
        </form>

        <?php if ($ss_q !== '' && empty($ss_results)): ?>
            <p>Brak studentów pasujących do zapytania "<b><?=htmlspecialchars($ss_q)?></b>".</p>
        <?php elseif (!empty($ss_results)):
            // $ss_results = [ stid => ['stud'=>..., 'subjects'=>[ [sid, subj_name, owner_name], ... ]], ... ]
            foreach ($ss_results as $stid => $ss_entry):
                $ss_stud = $ss_entry['stud'];
                $ss_stud_name = htmlspecialchars("{$ss_stud[1]} {$ss_stud[2]}");
                $ss_stud_album = htmlspecialchars($ss_stud[5] ?? '');
        ?>
        <h4><?=$ss_stud_name?> <?=$ss_stud_album ? "({$ss_stud_album})" : ''?></h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Nazwa przedmiotu</th><th>Właściciel</th><th>Akcje</th></tr>
            <?php $i=0; foreach ($ss_entry['subjects'] as $sub_row):
                $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($sub_row['name'])?></td>
                <td><?=htmlspecialchars($sub_row['owner'])?></td>
                <td>
                    <a href="login.php?view_action=szukaj_studenta&ss_q=<?=urlencode($ss_q)?>&ss_stid=<?=$stid?>&ss_sid=<?=$sub_row['sid']?>">Podgląd ocen</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; // preview / search ?>
        <?php endif; // szukaj_studenta ?>


        <!-- ============================================================ -->
        <!--  26. HARMONOGRAM                                              -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'harmonogram'):
            $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        ?>
        <h3>Harmonogram zajęć</h3>

        <?php if ($sel_sid === 0): ?>
        <h4>Wybierz przedmiot</h4>
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4">
            <tr><th>Nazwa</th><th>Rok</th><th>Akcja</th></tr>
            <?php $i = 0; foreach ($subs as $s):
                $p = explode(';', $s, 5); $cls = ($i++ % 2 == 0) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td><?=htmlspecialchars($p[1])?></td>
                <td><?=htmlspecialchars($p[2])?></td>
                <td><a href="login.php?view_action=harmonogram&sid=<?=$p[0]?>">Wybierz</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($i === 0) echo "<tr><td colspan='3'>Brak dostępnych przedmiotów.</td></tr>"; ?>
        </table>

        <?php else:
            $harm_data         = $viewData['harmonogram_data'] ?? [];
            $harm_subject_name = $harm_data['subject_name'] ?? '';
            $harm_exercises    = $harm_data['exercises']    ?? [];
            $harm_sections     = $harm_data['sections']     ?? [];
            $harm_schedule     = $harm_data['schedule']     ?? [];
        ?>
        <A HREF="login.php?view_action=harmonogram"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h4>Harmonogram: <?=htmlspecialchars($harm_subject_name)?></h4>

        <?php if (empty($harm_sections)): ?>
            <p style="color:#c0392b;">Brak zdefiniowanych sekcji dla tego przedmiotu.
            <a href="login.php?view_action=manage_sections&sid=<?=$sel_sid?>">Dodaj sekcje</a>.</p>
        <?php elseif (empty($harm_exercises)): ?>
            <p style="color:#c0392b;">Brak ćwiczeń przypisanych do tego przedmiotu.
            <a href="login.php?view_action=manage_exercises&view=list&sid=<?=$sel_sid?>">Dodaj ćwiczenia</a>.</p>
        <?php else: ?>

        <form method="post" action="login.php?action=save_harmonogram&view_action=harmonogram&sid=<?=$sel_sid?>">
            <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
            <div style="overflow-x:auto;">
            <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4" style="width:100%;">
                <thead>
                <tr>
                    <th>Ćwiczenie</th>
                    <?php foreach ($harm_sections as $sec): ?>
                    <th><?=htmlspecialchars($sec['name'])?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php $row_i = 0; foreach ($harm_exercises as $ex):
                    $eid = intval($ex[0]);
                    $cls = ($row_i++ % 2 == 0) ? 'n0' : 'n1';
                ?>
                <tr class="<?=$cls?>">
                    <td><b><?=htmlspecialchars($ex[1])?></b></td>
                    <?php foreach ($harm_sections as $sec):
                        $sec_id = $sec['id'];
                        $saved_dt = $harm_schedule[$sec_id][$eid] ?? '';
                    ?>
                    <td align="center">
                        <input type="datetime-local" name="dt[<?=$sec_id?>][<?=$eid?>]"
                               value="<?=htmlspecialchars($saved_dt)?>"
                               style="font-size:0.85em;">
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <div style="margin-top:10px; text-align:center;">
                <input type="submit" value="Zapisz harmonogram">
            </div>
        </form>

        <?php endif; ?>
        <?php endif; // sel_sid ?>
        <?php endif; // harmonogram ?>


        <!-- ============================================================ -->
        <!--  27. WYDRUK SPRAWOZDAŃ                                        -->
        <!-- ============================================================ -->
        <?php if ($view_action === 'wydruk_sprawozdan'):
            $wsp_sid      = isset($_GET['sid'])      ? intval($_GET['sid'])      : 0;
            $wsp_status   = isset($_GET['wsp_status'])  ? trim($_GET['wsp_status'])  : 'zal';
            $wsp_eid      = isset($_GET['wsp_eid'])  ? intval($_GET['wsp_eid'])  : 0;
            $wsp_stid     = isset($_GET['wsp_stid']) ? intval($_GET['wsp_stid']) : 0;
            $wsp_page     = max(1, intval($_GET['wsp_page'] ?? 1));
            $wsp_per_page = 25;

            // Dopuszczalne statusy
            $allowed_statuses = ['zal', 'oddane', 'zwr'];
        ?>
        <h3>Wydruk sprawozdań</h3>

        <!-- Wybór przedmiotu -->
        <form method="get" action="login.php" style="margin-bottom:10px;">
            <input type="hidden" name="view_action" value="wydruk_sprawozdan">
            Przedmiot:
            <select name="sid" onchange="this.form.submit()">
                <option value="0">-- wybierz przedmiot --</option>
                <?php foreach ($subs as $s): $p = explode(';', $s, 4); ?>
                    <option value="<?=$p[0]?>" <?=($wsp_sid===intval($p[0])?'selected':'')?>>
                        <?=htmlspecialchars($p[1])?> (<?=htmlspecialchars($p[2])?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($wsp_sid > 0):
            // Pobierz dane do list filtrów
            $wsp_data = $viewData['wydruk_sprawozdan'] ?? [];
            $wsp_exercise_names  = $wsp_data['exercise_names']  ?? [];
            $wsp_enrolled_students = $wsp_data['enrolled_students'] ?? [];
            $wsp_subject_name    = $wsp_data['subject_name']    ?? '';
            $wsp_all_rows        = $wsp_data['all_rows']        ?? [];
        ?>

        <!-- Formularz filtrów -->
        <form method="get" action="login.php" style="margin-bottom:12px; background:#f5f5f5; padding:8px; border:1px solid #ccc;">
            <input type="hidden" name="view_action" value="wydruk_sprawozdan">
            <input type="hidden" name="sid" value="<?=$wsp_sid?>">
            <input type="hidden" name="wsp_page" value="1">
            <table border="0" cellpadding="4">
            <tr>
                <td><b>Typ:</b></td>
                <td>
                    <select name="wsp_status">
                        <option value="zal"    <?=($wsp_status==='zal'   ?'selected':'')?>>Zaliczone</option>
                        <option value="oddane" <?=($wsp_status==='oddane'?'selected':'')?>>Oddane (do sprawdzenia)</option>
                        <option value="zwr"    <?=($wsp_status==='zwr'   ?'selected':'')?>>Zwrot</option>
                    </select>
                </td>
                <td><b>Zadanie:</b></td>
                <td>
                    <select name="wsp_eid">
                        <option value="0">-- wszystkie --</option>
                        <?php foreach ($wsp_exercise_names as $eid_f => $ename_f): ?>
                            <option value="<?=$eid_f?>" <?=($wsp_eid===$eid_f?'selected':'')?>>
                                <?=htmlspecialchars($ename_f)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><b>Student:</b></td>
                <td>
                    <select name="wsp_stid">
                        <option value="0">-- wszyscy --</option>
                        <?php foreach ($wsp_enrolled_students as $stid_f => $stname_f): ?>
                            <option value="<?=$stid_f?>" <?=($wsp_stid===$stid_f?'selected':'')?>>
                                <?=htmlspecialchars($stname_f)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="submit" value="Filtruj"></td>
                <td><a href="login.php?view_action=wydruk_sprawozdan&sid=<?=$wsp_sid?>">Wyczyść filtry</a></td>
            </tr>
            </table>
        </form>

        <?php
        // Filtrowanie wierszy
        $wsp_filtered = [];
        foreach ($wsp_all_rows as $row) {
            // Filtr po statusie
            if ($wsp_status === 'zal' && !$row['is_zal']) continue;
            if ($wsp_status === 'oddane' && ($row['current_status'] === 'zal' || $row['current_status'] === 'zwr')) continue;
            if ($wsp_status === 'zwr' && $row['current_status'] !== 'zwr') continue;

            // Filtr po ćwiczeniu
            if ($wsp_eid > 0 && $row['eid'] !== $wsp_eid) continue;

            // Filtr po studencie
            if ($wsp_stid > 0 && $row['stid'] !== $wsp_stid) continue;

            $wsp_filtered[] = $row;
        }

        $wsp_total = count($wsp_filtered);
        $wsp_pages = max(1, (int)ceil($wsp_total / $wsp_per_page));
        if ($wsp_page > $wsp_pages) $wsp_page = $wsp_pages;
        $wsp_offset = ($wsp_page - 1) * $wsp_per_page;
        $wsp_page_rows = array_slice($wsp_filtered, $wsp_offset, $wsp_per_page);

        // Buduj base URL dla paginacji
        $wsp_base_url = 'login.php?view_action=wydruk_sprawozdan'
            . '&sid=' . $wsp_sid
            . '&wsp_status=' . urlencode($wsp_status)
            . '&wsp_eid=' . $wsp_eid
            . '&wsp_stid=' . $wsp_stid;

        function wsp_status_label($s, $is_zal) {
            if ($is_zal) return '<span style="color:#27ae60;font-weight:bold;">ZALICZONE</span>';
            if ($s === 'zwr') return '<span style="color:#c0392b;font-weight:bold;">ZWROT</span>';
            return '<span style="color:#e67e22;font-weight:bold;">ODDANE</span>';
        }
        ?>

        <p>Znalezione wyniki: <b><?=$wsp_total?></b> | Strona <?=$wsp_page?> z <?=$wsp_pages?></p>

        <?php if ($wsp_total === 0): ?>
            <p style="color:#888;">Brak sprawozdań pasujących do wybranych filtrów.</p>
        <?php else: ?>

        <!-- Paginacja góra -->
        <?php if ($wsp_pages > 1): ?>
        <div class="pagination">
            <?php
            $prev = $wsp_page - 1;
            $next = $wsp_page + 1;
            echo '<a href="' . $wsp_base_url . '&wsp_page=1"' . ($wsp_page===1?' class="disabled"':'') . '>&laquo; Pierwsza</a> ';
            echo '<a href="' . $wsp_base_url . '&wsp_page=' . max(1,$prev) . '"' . ($wsp_page===1?' class="disabled"':'') . '>&lsaquo; Poprz.</a> ';
            for ($pg = max(1,$wsp_page-3); $pg <= min($wsp_pages,$wsp_page+3); $pg++) {
                $cls_pg = ($pg === $wsp_page) ? ' class="active"' : '';
                echo '<a href="' . $wsp_base_url . '&wsp_page=' . $pg . '"' . $cls_pg . '>' . $pg . '</a> ';
            }
            echo '<a href="' . $wsp_base_url . '&wsp_page=' . min($wsp_pages,$next) . '"' . ($wsp_page>=$wsp_pages?' class="disabled"':'') . '>Nast. &rsaquo;</a> ';
            echo '<a href="' . $wsp_base_url . '&wsp_page=' . $wsp_pages . '"' . ($wsp_page>=$wsp_pages?' class="disabled"':'') . '>Ostatnia &raquo;</a>';
            ?>
        </div>
        <?php endif; ?>

        <div style="overflow-x:auto;">
        <table CELLPADDING="2" CELLSPACING="0" BORDER="1" class="border" cellpadding="4" style="width:100%;">
            <thead>
            <tr style="background:#ddd;">
                <th>Lp.</th>
                <th>Student</th>
                <th>Nr albumu</th>
                <th>Zadanie (ćwiczenie)</th>
                <th>Status</th>
                <th>Data przesłania</th>
                <th>Komentarz</th>
                <th>Plik</th>
            </tr>
            </thead>
            <tbody>
            <?php $wsp_lp = $wsp_offset + 1;
            foreach ($wsp_page_rows as $row):
                $cls = ($wsp_lp % 2 === 1) ? 'n0' : 'n1';
            ?>
            <tr class="<?=$cls?>">
                <td align="center"><?=$wsp_lp++?></td>
                <td><?=htmlspecialchars($row['student_name'])?></td>
                <td align="center"><?=htmlspecialchars($row['album'])?></td>
                <td><?=htmlspecialchars($row['exercise_name'])?></td>
                <td align="center"><?=wsp_status_label($row['current_status'], $row['is_zal'])?></td>
                <td align="center"><?=htmlspecialchars(preg_replace('/^(\d{4}-\d{2}-\d{2})_(\d{2})-(\d{2})-(\d{2})$/', '$1 $2:$3:$4', $row['date']))?></td>
                <td style="max-width:220px; font-size:0.88em;"><?=htmlspecialchars($row['comment'])?></td>
                <td align="center">
                    <?php if (!empty($row['path']) && $row['path'] !== 'brak_pliku'): ?>
                        <a href="<?=htmlspecialchars($row['path'])?>" target="_blank">Pobierz</a>
                    <?php else: ?>
                        <span style="color:#aaa;">brak</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Paginacja dół -->
        <?php if ($wsp_pages > 1): ?>
        <div class="pagination">
            <?php
            echo '<a href="' . $wsp_base_url . '&wsp_page=1"' . ($wsp_page===1?' class="disabled"':'') . '>&laquo; Pierwsza</a> ';
            echo '<a href="' . $wsp_base_url . '&wsp_page=' . max(1,$prev) . '"' . ($wsp_page===1?' class="disabled"':'') . '>&lsaquo; Poprz.</a> ';
            for ($pg = max(1,$wsp_page-3); $pg <= min($wsp_pages,$wsp_page+3); $pg++) {
                $cls_pg = ($pg === $wsp_page) ? ' class="active"' : '';
                echo '<a href="' . $wsp_base_url . '&wsp_page=' . $pg . '"' . $cls_pg . '>' . $pg . '</a> ';
            }
            echo '<a href="' . $wsp_base_url . '&wsp_page=' . min($wsp_pages,$next) . '"' . ($wsp_page>=$wsp_pages?' class="disabled"':'') . '>Nast. &rsaquo;</a> ';
            echo '<a href="' . $wsp_base_url . '&wsp_page=' . $wsp_pages . '"' . ($wsp_page>=$wsp_pages?' class="disabled"':'') . '>Ostatnia &raquo;</a>';
            ?>
        </div>
        <?php endif; ?>

        <?php endif; // wsp_total > 0 ?>
        <?php endif; // wsp_sid > 0 ?>
        <?php endif; // wydruk_sprawozdan ?>


    </div><!-- .compact-content -->
</div><!-- .compact-layout -->

<?php endif; ?>

<!-- Globalne okno modalne do edycji ocen (position:fixed – niezależne od scroll tabeli) -->
<div id="gradeEditOverlay" onclick="closeGradeEditModal()"></div>
<div id="gradeEditModal" onclick="event.stopPropagation()"></div>
</body>
</html>