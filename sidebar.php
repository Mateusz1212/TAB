    // SIDEBAR 
    <div class="compact-sidebar">
        <h3>Panel Sterowania</h3>
        <div>
            Zalogowany: <b><?php echo htmlspecialchars("{$me['imie']} {$me['nazwisko']}"); ?></b><br>
            Uczelnia: <?php echo htmlspecialchars($viewData['user_uczelnia'] ?? '-'); ?>
        </div>
        <hr>
        <div>
            <a href="login.php?action=toggle_view<?php echo (!empty($_SERVER['QUERY_STRING']) ? '&' . str_replace('action=toggle_view', '', $_SERVER['QUERY_STRING']) : ''); ?>">
                [Przełącz na Widok Nowoczesny]
            </a>
            <br><br>
            <a href="login.php">[Menu Główne]</a>
            &nbsp;|&nbsp;
            <a href="login.php?action=logout">[Wyloguj]</a>
        </div>
        <hr>
        <h4>Menu Główne</h4>
        <ul style="padding-left: 20px;">
            <?php
            $menu_items = [
                'add_subject'              => '1. Przedmioty',
                'manage_sections'          => '2. Sekcje studentów',
                'batch_add_students_view'  => '3. Dodawanie studentów',
                'add_student'              => '4. Baza studentów',
                'manage_exercises'         => '5. Ćwiczenia',
                'add_grade'                => '6. Oceny',
                'manage_reports'           => '7. Sprawozdania',
                'change_password_view'     => '8. Zmiana hasła',
                'add_teacher_view'         => '9. Nowy prowadzący',
                'manage_exercise_att'      => '10. Obecności',
                'statistics'               => '11. Statystyki',
                'manage_deadlines'         => '12. Wymagania zaliczenia ćwiczeń',
                'manage_access'            => '13. Współprowadzący',
                'batch_grading'            => '14. Oceny seryjne',
                'export_view'              => '15. Eksport (CSV/PDF)',
                'manage_announcements'     => '16. Ogłoszenia',
                'progress_view'            => '17. Ranking postępów',
                'manage_exemptions'        => '18. Zwolnienia',
                'student_grades_view'      => '19. Przegląd ocen studenta',
                'enforce_tasks'            => '20. Egzekwowanie zadań',
                'student_logs_view'        => '21. Logi studentów',
                'final_grades_view'        => '22. Oceny końcowe',
                'manage_applications'      => '23. Podania',
                'edit_reports'             => '24. Edycja sprawozdań',
				'szukaj_studenta'          => '25. Szukaj studenta',
                'harmonogram'              => '26. Harmonogram',
                'wydruk_sprawozdan'        => '27. Wydruk sprawozdań',
            ];
            foreach ($menu_items as $action_key => $description) {
                $active = ($view_action === $action_key) ? ' style="font-weight:bold;"' : '';
                echo "<li{$active}><a href='login.php?view_action={$action_key}'>{$description}</a></li>";
            }
            ?>
        </ul>
    </div>
    // CONTENT
    <div class="compact-content">
        <?php
        if (!empty($msg)) echo "<div class='success-msg'>" . htmlspecialchars($msg) . "</div>";
        if (!empty($err)) echo "<div class='error-msg'>" . htmlspecialchars($err) . "</div>";
        ?>
        <script>
        (function() {
            var redirectPending = sessionStorage.getItem('after_report_redirect');
            var hasMsgInUrl = (window.location.search.indexOf('msg=') !== -1);
            var hasViewAction = (window.location.search.indexOf('view_action=') !== -1);
            if (redirectPending === 'manage_reports' && hasMsgInUrl && !hasViewAction) {
                sessionStorage.removeItem('after_report_redirect');
                window.location.replace('login.php?view_action=manage_reports');
            }
        })();
        </script>

