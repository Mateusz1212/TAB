<?php /* Export, Announcements, Applications */ ?>
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




        <?php if ($view_action === 'manage_announcements'):
            $announcements = $viewData['announcements'] ?? [];
            $subjects_available = $viewData['subjects_list'] ?? [];
            $sub_map_ann = [];
            foreach ($subjects_available as $s) { $p = explode(';', $s); $sub_map_ann[intval($p[0])] = $p[1]; }
            $ann_readers = $viewData['ann_readers'] ?? [];
            $student_name_map = $viewData['student_name_map'] ?? [];
            $ann_readers_detail_aid = $viewData['ann_readers_detail_aid'] ?? 0;
            $ann_readers_detail_list = $viewData['ann_readers_detail_list'] ?? [];
            // mapa id prowadzącego - imię nazwisko
            $teacher_name_map = [];
            foreach ($teachers_list as $t) {
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
        <?php endif; ?>
