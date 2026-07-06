<?php /* Statistics, Progress ranking, Student grade overview */ ?>
        <?php if ($view_action === 'statistics'):
            $selected_sid = $viewData['selected_sid'] ?? 0;
            $stats_type = $viewData['stats_type'] ?? 'avg';
            $sort_desc = $viewData['sort_desc'] ?? false;
        ?>
        <h3>Statystyki</h3>
        <form method="get" action="panel.php">
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




        <?php if ($view_action === 'progress_view'):
            $selected_sid = $viewData['selected_sid'] ?? 0;
        ?>
        <h3>Postęp ćwiczeń studentów</h3>
        <form method="get" action="panel.php">
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




        <?php if ($view_action === 'student_grades_view'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
            $sel_stid = $viewData['sel_stid'] ?? 0;
        ?>
        <h3>Przegląd ocen studenta</h3>
        <form method="get" action="panel.php">
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
        <?php endif; ?>