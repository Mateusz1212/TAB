<?php /*  Subjects, Student database, New teacher */ ?>
        <?php if ($view_action === 'add_subject'): ?>
        <h3>Przedmioty Aktywne</h3>
        <button onclick="toggleSection('addSubjectForm')" style="margin-bottom:8px;">[+] Dodaj Przedmiot</button>
        <div id="addSubjectForm" class="hidden-section">
            <form method="post" action="panel.php?action=add_subject&view_action=add_subject">
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
                    <a href="panel.php?view=subject&sid=<?=$sid?>&view_action=add_grade">Oceny</a>
                    <?php if ($is_owner): ?>
                     | <a href="panel.php?view_action=edit_subject_form&sid=<?=$sid?>">Edytuj</a>
                     | <a href="panel.php?view_action=manage_exercises&view=list&sid=<?=$sid?>">Ćw.</a>
                     | <a href="panel.php?view_action=manage_sections&sid=<?=$sid?>">Sekcje</a>
                     | <a href="panel.php?view_action=manage_access&view=details&sid=<?=$sid?>">Dostęp</a>
                     | <a href="#" onclick="toggleSection('rolloverForm_<?=$sid?>'); return false;">Nowy rocznik</a>
                     | <a href="panel.php?action=archive_subject&sid=<?=$sid?>" onclick="return confirm('Archiwizować?')">Archiwizuj</a>
                     | <a href="panel.php?action=delete_subject&sid=<?=$sid?>" onclick="return confirm('Usunąć trwale?')" style="color:red;">Usuń</a>
                    <?php else: ?>
                     | <span style="color:#aaa;">brak upr.</span>
                    <?php endif; ?>
                    <?php if ($is_owner): ?>
                    <div id="rolloverForm_<?=$sid?>" class="hidden-section">
                        <p><b>Utwórz nowy rocznik</b><br>
                        Przeniesienie do archiwum z kopiowaniem ćwiczeń (bez studentów).<br>Czy potwierdzasz?</p>
                        <form method="post" action="panel.php?action=rollover_subject">
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
                    <a href="panel.php?action=restore_subject&sid=<?=$sid?>">Przywróć</a> |
                    <a href="panel.php?view_action=edit_subject_form&sid=<?=$sid?>">Edytuj</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; ?>


        <?php if ($view_action === 'edit_subject_form' && isset($_GET['sid'])):
            $s_data = $viewData['subject_to_edit'] ?? null;
            if ($s_data):
        ?>
        <A HREF="panel.php?view_action=add_subject"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Edycja: <?=htmlspecialchars($s_data[1])?></h3>
        <form method="post" action="panel.php?action=edit_subject">
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




        <?php if ($view_action === 'add_student'):
            $q_student = trim($_GET['q_student'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 20;
            // Użycie listy studentów przefiltrowanej do uczelni zalogowanego prowadzącego
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
        <form method="get" action="panel.php" style="margin-bottom:8px;">
            <input type="hidden" name="view_action" value="add_student">
            Szukaj: <input type="text" name="q_student" value="<?=htmlspecialchars($q_student)?>" placeholder="nazwisko, imię lub album">
            <input type="submit" value="Szukaj">
            <?php if ($q_student !== ''): ?>
                <a href="panel.php?view_action=add_student">[Wyczyść]</a>
            <?php endif; ?>
        </form>
        <button onclick="toggleSection('addStudentForm')" style="margin-bottom:8px;">[+] Dodaj Studenta</button>
        <div id="addStudentForm" class="hidden-section">
            <form method="post" action="panel.php?action=add_student&view_action=add_student">
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
                    <a href="panel.php?view_action=edit_student_form&sid=<?=$s[4]?>">Edytuj</a> |
                    <a href="panel.php?action=delete_student&sid=<?=$s[4]?>" onclick="return confirm('Usunąć studenta?')" style="color:red;">Usuń</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <a href="panel.php?view_action=add_student&page=<?=max(1,$page-1)?>&q_student=<?=urlencode($q_student)?>" class="<?=($page<=1?'disabled':'')?>">&laquo;</a>
            <?php for ($pg = 1; $pg <= $total_pages; $pg++): ?>
                <a href="panel.php?view_action=add_student&page=<?=$pg?>&q_student=<?=urlencode($q_student)?>" class="<?=($pg===$page?'active':'')?>"><?=$pg?></a>
            <?php endfor; ?>
            <a href="panel.php?view_action=add_student&page=<?=min($total_pages,$page+1)?>&q_student=<?=urlencode($q_student)?>" class="<?=($page>=$total_pages?'disabled':'')?>">&raquo;</a>
        </div>
        <small>Pokazano <?=($total>0?($page-1)*$perPage+1:0)?>-<?=min($page*$perPage,$total)?> z <?=$total?></small>
        <?php endif; ?>
        <?php endif; ?>


        <?php if ($view_action === 'edit_student_form' && isset($_GET['sid'])):
            $st_data = $viewData['st_data'] ?? null;
            if ($st_data):
        ?>
        <A HREF="panel.php?view_action=add_student"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h3>Edycja studenta: <?=htmlspecialchars($st_data[1] . ' ' . $st_data[2])?></h3>
        <form method="post" action="panel.php?action=edit_student">
            <input type="hidden" name="sid" value="<?=intval($_GET['sid'])?>">
            Imię: <input type="text" name="imie" value="<?=htmlspecialchars($st_data[1])?>"><br>
            Nazwisko: <input type="text" name="nazwisko" value="<?=htmlspecialchars($st_data[2])?>"><br>
            Hasło (puste = bez zmian): <input type="text" name="password" placeholder="nowe hasło..."><br>
            Nr albumu: <input type="text" name="nr_albumu" value="<?=htmlspecialchars($st_data[5]??'')?>"><br>
            <input type="submit" value="Zapisz zmiany">
        </form>
        <?php else: echo "<p>Nie znaleziono studenta.</p>"; endif; ?>
        <?php endif; ?>




        <?php if ($view_action === 'add_teacher_view'): ?>
        <h3>Dodaj Nowego Prowadzącego</h3>
        <form method="post" action="panel.php?action=add_teacher">
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