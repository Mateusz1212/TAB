<?php /* Grades, Batch grading, Final grades */ ?>
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

                // mapa definicji ćwiczeń 
                $exercises_defs_map = [];
                foreach (read_lines($exercisesFile) as $l) {
                    $p = explode(';', $l);
                    $exercises_defs_map[intval($p[0])] = $p;
                }

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
        // Szablony danych dla modalnego okna edycji ocen
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
        <?php endif; ?>




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
        <?php endif; ?>




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
        <?php endif; ?>


