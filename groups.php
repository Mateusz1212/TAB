<?php /* Student groups/sections, Adding students to sections */ ?>
        <?php if ($view_action === 'manage_sections'){
            $sel_sid = $viewData['sel_sid'] ?? 0;
            if (isset($viewData['error_perm'])) { echo $viewData['error_perm']; }
            else {}
        ?>
        <h3>Zarządzanie Sekcjami</h3>
        <form method="get" action="panel.php">
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
            <form method="post" action="panel.php?action=add_defined_section">
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
                    <a href="panel.php?view_action=manage_sections&sid=<?=$sel_sid?>&sec_id=<?=$ds['id']?>" style="<?=$nameStyle?>">
                        <?=($isActive?'&raquo; ':'')?><?=htmlspecialchars($ds['name'])?>
                    </a>
                </td>
                <td>
                    <button onclick="toggleSection('editSecForm_<?=$ds['id']?>')" class="btn-sm">Edytuj</button>
                    <div id="editSecForm_<?=$ds['id']?>" class="hidden-section">
                        <form method="post" action="panel.php?action=edit_defined_section">
                            <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                            <input type="hidden" name="section_id" value="<?=$ds['id']?>">
                            Nowa nazwa: <input type="text" name="section_name" value="<?=htmlspecialchars($ds['name'])?>" required>
                            <input type="submit" value="Zapisz">
                        </form>
                    </div>
                    <a href="panel.php?action=delete_defined_section&sid=<?=$sel_sid?>&sec_id=<?=$ds['id']?>" onclick="return confirm('Usunąć sekcję i wszystkie przypisania?')" style="color:red;">Usuń</a>
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
            <a href="panel.php?view_action=batch_add_students_view&sid=<?=$sel_sid?>&sec_id=<?=$current_sec_id?>">[+ Dodaj studentów do sekcji]</a>
            &nbsp;|&nbsp;
            <form method="post" action="panel.php?action=sort_and_save_section" style="display:inline;">
                <input type="hidden" name="subject_id" value="<?=$sel_sid?>">
                <input type="hidden" name="section_id" value="<?=$current_sec_id?>">
                <input type="submit" value="&#8645; Sortuj A-Z">
            </form>
        </div>
        <form method="post" action="panel.php?action=move_students_section">
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
                        <a href="panel.php?action=remove_student_from_section&st_id=<?=$st[4]?>&sid=<?=$sel_sid?>&sec_id=<?=$current_sec_id?>" onclick="return confirm('Usunąć studenta z sekcji?')" style="color:red; font-weight:bold;">&times;</a>
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
			<?php } ?>
			<?php } ?>
        <?php } ?>




        <?php if ($view_action === 'batch_add_students_view'):
            $sel_sid = $viewData['sel_sid'] ?? 0;
            if (isset($viewData['error_perm'])) { echo $viewData['error_perm']; }
            else{}
        ?>
        <h3>Dodawanie studentów do przedmiotu</h3>
        <form method="get" action="panel.php">
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
                <a href="panel.php?view_action=manage_sections&sid=<?=$sel_sid?>">Przejdź do zarządzania sekcjami</a>
            </div>
        <?php else:
            $pre_sec_id = $viewData['pre_sec_id'] ?? 0;
        ?>
        <br>
        <form method="post" action="panel.php?action=batch_add_students_process">
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
        <?php endif; ?>