<?php /* Attendance, Task enforcement */ ?>
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
        <?php endif; ?>




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
        <?php endif; ?>


