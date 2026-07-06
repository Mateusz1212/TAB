<?php /* ===== Reports, Report editing, Report printout ===== */ ?>
        <?php

        // Helper statusu
        function rpt_status_label_c($s) {
            if ($s === 'zal') return '<span style="color:#27ae60;font-weight:bold;">ZAL</span>';
            if ($s === 'zwr') return '<span style="color:#c0392b;font-weight:bold;">ZWR</span>';
            return '<span style="color:#e67e22;font-weight:bold;">DO SPR.</span>';
        }

        // WIDOK SZCZEGÓŁOWY SPRAWOZDANIA
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
        <A HREF="panel.php?view_action=manage_reports"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
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
            <form method="post" action="panel.php?action=evaluate_report" onsubmit="sessionStorage.setItem('after_report_redirect','manage_reports');">
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
        endif;

        // Lista oczekujących sprawozdań
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
                <td><a href="panel.php?view_action=manage_reports&view=report_detail&rid=<?=$pr['rid']?>&sid=<?=$pr['subId']?>">Wejdź</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php endif; ?>






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
                echo "<td><a href='panel.php?view_action=edit_reports&sid={$p[0]}'>Wybierz</a></td>";
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
        <A HREF="panel.php?view_action=edit_reports"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h4>Przedmiot: <?php echo htmlspecialchars($er_subject_name); ?></h4>

        <h4>Wpisanie zaliczonego sprawozdania (bez pliku studenta)</h4>
        <form method="post" action="panel.php?action=er_add_report&view_action=edit_reports&sid=<?php echo $sel_sid; ?>">
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
                    <form method="post" action="panel.php?action=er_edit_history&view_action=edit_reports&sid=<?= $sel_sid ?>" style="margin:0;" id="f_<?= $hid ?>">
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
                    <a href="panel.php?action=er_delete_history&view_action=edit_reports&sid=<?= $sel_sid ?>&hid=<?= $hid ?>&rid=<?= $rid ?>"
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
        <?php endif; ?>




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

        // Wybór przedmiotu
        <form method="get" action="panel.php" style="margin-bottom:10px;">
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
            // Pobieranie danych do list filtrów
            $wsp_data = $viewData['wydruk_sprawozdan'] ?? [];
            $wsp_exercise_names  = $wsp_data['exercise_names']  ?? [];
            $wsp_enrolled_students = $wsp_data['enrolled_students'] ?? [];
            $wsp_subject_name    = $wsp_data['subject_name']    ?? '';
            $wsp_all_rows        = $wsp_data['all_rows']        ?? [];
        ?>

        // Formularz filtrów
        <form method="get" action="panel.php" style="margin-bottom:12px; background:#f5f5f5; padding:8px; border:1px solid #ccc;">
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
                <td><a href="panel.php?view_action=wydruk_sprawozdan&sid=<?=$wsp_sid?>">Wyczyść filtry</a></td>
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

        $wsp_base_url = 'panel.php?view_action=wydruk_sprawozdan'
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

        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>