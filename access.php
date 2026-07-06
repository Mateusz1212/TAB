<?php /* teachers/access, Change password, Schedule */ ?>
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
                    // Zarządzanie ćwiczeniami
                    $ex_scope_disp = $p['manage_exercises_scope'] ?? null;
                    if ($ex_scope_disp === null) {
                        $ex_scope_disp = !empty($p['manage_exercises']) ? 'all' : 'none';
                    }
                    if ($ex_scope_disp === 'own') {
                        $p_desc[] = "Zarz.ćwicz.(własne)+Wymagania+Zwolnienia";
                    } elseif ($ex_scope_disp === 'all') {
                        $p_desc[] = "Zarz.ćwicz.(wszystkie)+Wymagania+Zwolnienia";
                    }

                    if (!empty($p['manage_sections'])) $p_desc[] = "Zarz.sekcjami";

                    // Ocenianie
                    $g_own = !empty($p['grading_own']);
                    $g_all = !empty($p['grading_all']);
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
        <?php endif; ?>




        <?php if ($view_action === 'change_password_view'): ?>
        <h3>Zmień hasło (prowadzący)</h3>
        <form method="post" action="login.php?action=change_password&view_action=change_password_view">
            Stare: <input type="password" name="old"><br>
            Nowe: <input type="password" name="new"><br>
            <input type="submit" value="Zmień hasło">
        </form>
        <?php endif; ?>




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
        <?php endif; ?>
        <?php endif; ?>


