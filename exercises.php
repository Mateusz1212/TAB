<?php /* Exercises, Deadlines / pass requirements */ ?>
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
        <?php endif; ?>

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

        // dla ćwiczeń
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
        <?php endif; ?>


