<?php /* Exemptions, Student logs, Student search */ ?>
        <?php if ($view_action === 'manage_exemptions'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
        ?>
        <h3>Zwolnienia z ćwiczeń</h3>
        <form method="get" action="panel.php">
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
        <form method="post" action="panel.php?action=save_exemptions&view_action=manage_exemptions&sid=<?=$sel_sid?>">
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
        <?php endif; ?>




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
        <form method="get" action="panel.php">
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
            <a href="panel.php?view_action=student_logs_view">[Wyczyść filtry]</a>
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
            <a href="panel.php?view_action=student_logs_view&page=<?=max(1,$page-1)?><?=$filterParams?>" class="<?=($page<=1?'disabled':'')?>">&laquo;</a>
            <?php for ($pg = 1; $pg <= $total_pages; $pg++): ?>
                <a href="panel.php?view_action=student_logs_view&page=<?=$pg?><?=$filterParams?>" class="<?=($pg===$page?'active':'')?>"><?=$pg?></a>
            <?php endfor; ?>
            <a href="panel.php?view_action=student_logs_view&page=<?=min($total_pages,$page+1)?><?=$filterParams?>" class="<?=($page>=$total_pages?'disabled':'')?>">&raquo;</a>
        </div>
        <small>Pokazano <?=($total_items>0?$offset+1:0)?>-<?=min($offset+$perPage,$total_items)?> z <?=$total_items?></small>
        <?php endif; ?>
        <?php endif; ?>




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
        <A HREF="panel.php?view_action=szukaj_studenta&ss_q=<?=urlencode($ss_q)?>&ss_stid=<?=$ss_preview_stid?>"><IMG SRC="left.png" WIDTH="16" HEIGHT="9" BORDER="0" ALT="wstecz"></A>
        <h4>Oceny studenta: <?=$pv_stName?> <?= $pv_stAlbum ? "({$pv_stAlbum})" : '' ?></h4>
        <h4>Przedmiot: <?=htmlspecialchars($pv_subj_name)?> <?=htmlspecialchars($pv_subj_rok)?></h4>

        <?php
        // tabela ocen
        $pv_myGrades_all = $pv_grades; 

        // Ustalanie terminów
        $pv_terminy = [];
        foreach ($pv_myGrades_all as $g) {
            $p = explode(';', $g, 9);
            $pv_terminy[] = intval($p[8] ?? 1);
        }
        $pv_terminy = array_unique($pv_terminy);
        if (count($pv_terminy) == 0) $pv_terminy = [1];
        sort($pv_terminy);

        $pv_ile_column_zlaczen = 3 + count($pv_terminy) - 1;

        // Oceny wg ćwiczenia i terminu
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

            // Sprawdzian
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
                    echo $att_status;
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

            // Zaliczenie ćwiczenia
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

                // gdy brak zdefiniowanych kryteriów
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

        // Ocena końcowa
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

        <?php else: ?>

        <form method="get" action="panel.php" style="margin-bottom:10px;">
            <input type="hidden" name="view_action" value="szukaj_studenta">
            Szukaj: <input type="text" name="ss_q" value="<?=htmlspecialchars($ss_q)?>" placeholder="imię lub nazwisko studenta">
            <input type="submit" value="Szukaj">
            <?php if ($ss_q !== ''): ?>
                <a href="panel.php?view_action=szukaj_studenta">[Wyczyść]</a>
            <?php endif; ?>
        </form>

        <?php if ($ss_q !== '' && empty($ss_results)): ?>
            <p>Brak studentów pasujących do zapytania "<b><?=htmlspecialchars($ss_q)?></b>".</p>
        <?php elseif (!empty($ss_results)):
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
                    <a href="panel.php?view_action=szukaj_studenta&ss_q=<?=urlencode($ss_q)?>&ss_stid=<?=$stid?>&ss_sid=<?=$sub_row['sid']?>">Podgląd ocen</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>
        <?php endif; ?>