<?php
header('Content-Type: text/html;');
date_default_timezone_set('Europe/Warsaw');

include 'functions.php';

session_start();
$action = $_REQUEST['action'] ?? '';
if ($action === 'toggle_view') {
    $_SESSION['view_mode'] = ($_SESSION['view_mode'] ?? 'modern') === 'modern' ? 'compact' : 'modern';
    // Przekierowanie zwrotne, aby zachować parametry GET (np. sid, view_action)
    $params = $_GET;
    unset($params['action']); // usuwamy action=toggle_view z URL
    $query = http_build_query($params);
    header("Location: login.php" . ($query ? '?' . $query : ''));
    exit;
}
$view_action = $_REQUEST['view_action'] ?? '';
$view = $_REQUEST['view'] ?? '';

function get_subject_permissions($sid, $me_id, $subject_owner_id) {
    if (intval($subject_owner_id) === intval($me_id)) {
        return [
            'manage_exercises'       => true,
            'manage_exercises_scope' => 'all',
            'manage_sections'        => true,
            'grading_scope'          => 'all',
            'grading_own'            => true,
            'grading_all'            => true,
            'exemptions'             => true,
            'final_grades'           => true,
            'announcements'          => true,
        ];
    }

    global $subjectAccessFile;
    $lines = read_lines($subjectAccessFile);
    foreach ($lines as $l) {
        $p = explode(';', $l, 4);
        if (count($p) >= 2 && intval($p[0]) === intval($sid) && intval($p[1]) === intval($me_id)) {
            $json  = isset($p[3]) ? $p[3] : '';
            $perms = json_decode($json, true);
            if (!is_array($perms)) {
                return [
                    'manage_exercises'       => false,
                    'manage_exercises_scope' => 'none',
                    'manage_sections'        => false,
                    'grading_scope'          => 'own',
                    'grading_own'            => false,
                    'grading_all'            => false,
                    'exemptions'             => false,
                    'final_grades'           => false,
                    'announcements'          => false,
                ];
            }

            // Obsługa nowego modelu (manage_exercises_scope) oraz starego (manage_exercises + exemptions)
            if (isset($perms['manage_exercises_scope'])) {
                $ex_scope = $perms['manage_exercises_scope']; // 'none'|'own'|'all'
            } elseif (!empty($perms['manage_exercises'])) {
                // Migracja ze starego modelu: był manage_exercises=1 → scope='all'
                $ex_scope = 'all';
            } else {
                $ex_scope = 'none';
            }

            // Obsługa nowego modelu oceniania (grading_own + grading_all) oraz starego (grading_scope)
            if (isset($perms['grading_own']) || isset($perms['grading_all'])) {
                $grading_own = !empty($perms['grading_own']);
                $grading_all = !empty($perms['grading_all']);
            } else {
                // Migracja: stary model grading_scope radio
                $old_scope   = $perms['grading_scope'] ?? 'own';
                $grading_own = ($old_scope === 'own' || $old_scope === 'all');
                $grading_all = ($old_scope === 'all');
            }

            // grading_scope do kompatybilności z resztą kodu
            if ($grading_all) {
                $grading_scope = 'all';
            } elseif ($grading_own) {
                $grading_scope = 'own';
            } else {
                $grading_scope = 'none';
            }

            return [
                'manage_exercises'       => ($ex_scope !== 'none'),
                'manage_exercises_scope' => $ex_scope,
                'manage_sections'        => !empty($perms['manage_sections']),
                'grading_scope'          => $grading_scope,
                'grading_own'            => $grading_own,
                'grading_all'            => $grading_all,
                // zwolnienia wynikają z uprawnień do zarządzania ćwiczeniami
                'exemptions'             => ($ex_scope !== 'none'),
                'final_grades'           => !empty($perms['final_grades']),
                'announcements'          => !empty($perms['announcements']),
            ];
        }
    }
    return null;
}

$login_err = '';
if (isset($_POST['imie'])) {
    $im = trim($_POST['imie']); 
    $nz = isset($_POST['nazwisko']) ? trim($_POST['nazwisko']) : '';
    $pw = $_POST['password'] ?? '';

    $u = find_user($im, $nz, $pw);
    if ($u && $u['role'] === 'teacher') {
        $_SESSION['user'] = $u;
        header("Location: login.php");
        exit;
    } else {
        $login_err = "Błędne inicjały, hasło lub brak uprawnień.";
    }
}

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher') {
    $me = $_SESSION['user'];
    include 'actions.php'; 
}

$viewData = [];

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher') {
    $me = $_SESSION['user'];

	$user_uczelnia_name = get_uczelnia_name($me['id_uczelni']);
	$viewData['user_uczelnia'] = $user_uczelnia_name;
    $msg = $_GET['msg'] ?? '';
    $err = $_GET['err'] ?? '';

    $allUsers = read_lines($usersFile);
    $students = [];
    $teachers_list = [];
    foreach ($allUsers as $l) {
        $p = explode(';', $l);
        if ($p[0] === 'student') {
            $nr_albumu = isset($p[5]) ? $p[5] : '';
            $student_data = array_slice($p, 0, 5);
            $student_data[5] = $nr_albumu;
            $students[] = $student_data;
        }
        if ($p[0] === 'teacher') {
            $t_uczelnia = isset($p[6]) ? trim($p[6]) : '';
            $my_uczelnia = isset($me['id_uczelni']) ? trim($me['id_uczelni']) : '';
            
            if ($t_uczelnia === $my_uczelnia) {
                $teachers_list[] = ['id' => intval($p[4]), 'name' => "{$p[1]} {$p[2]}", 'inicjaly' => $p[5] ?? ''];
            }
        }
    }

    $all_subs_raw = read_lines($subjectsFile);
    $subs = [];
    $archived_subs = [];
    $my_id = intval($me['id']);
    $access_lines = read_lines($subjectAccessFile);
    $shared_sids = [];
    foreach($access_lines as $al) {
        $parts = explode(';', $al);
        if(count($parts) >= 2 && intval($parts[1]) === $my_id) {
            $shared_sids[intval($parts[0])] = true;
        }
    }

    $defined_sections_all = read_lines($definedSectionsFile);
    $enroll_lines_all = read_lines($enrollFile);
    $stats_map = [];

    foreach ($all_subs_raw as $s) {
        $p = explode(';', $s);
        $sid = intval($p[0]);
        $owner_id = isset($p[3]) ? intval($p[3]) : 0;
        $status = isset($p[4]) ? trim($p[4]) : 'active';

        if ($owner_id === $my_id || isset($shared_sids[$sid])) {
            if ($status === 'archived') {
                $archived_subs[] = $s;
            } else {
                $subs[] = $s;
            }
            
            $sec_count = 0;
            foreach ($defined_sections_all as $ds) {
                $dsp = explode(';', $ds);
                if (count($dsp) >= 2 && intval($dsp[1]) === $sid) $sec_count++;
            }
            $stud_count = 0;
            foreach ($enroll_lines_all as $el) {
                $elp = explode(';', $el);
                if (count($elp) >= 2 && intval($elp[1]) === $sid) $stud_count++;
            }
            $stats_map[$sid] = ['sec_count' => $sec_count, 'stud_count' => $stud_count];
        }
    }
    $viewData['subject_stats'] = $stats_map;

    if ($view_action === 'edit_subject_form' && isset($_GET['sid'])) {
        $target_sid = intval($_GET['sid']);
        foreach ($all_subs_raw as $s) {
            $p = explode(';', $s);
            if (intval($p[0]) === $target_sid) {
                if (isset($p[3]) && intval($p[3]) === intval($me['id'])) {
                    $viewData['subject_to_edit'] = $p;
                }
                break;
            }
        }
    }
	
    if ($view_action === 'edit_student_form' && isset($_GET['sid'])) {
        $target_sid = intval($_GET['sid']);
        foreach ($students as $s) {
            if (intval($s[4]) === $target_sid) {
                $viewData['st_data'] = $s;
                break;
            }
        }
    }

    if ($view_action === 'add_student') {
        // Zbierz ID nauczycieli z tej samej uczelni co zalogowany prowadzący
        $my_uczelnia_as = trim($me['id_uczelni'] ?? '');
        $teacher_ids_uczelnia_as = [];
        foreach ($allUsers as $ul) {
            $up = explode(';', $ul);
            if ($up[0] === 'teacher') {
                $t_ucz = trim($up[6] ?? '');
                if ($my_uczelnia_as !== '' && $t_ucz === $my_uczelnia_as) {
                    $teacher_ids_uczelnia_as[] = intval($up[4]);
                }
            }
        }

        // Zbierz ID przedmiotów należących do tych nauczycieli
        $sids_uczelnia_as = [];
        foreach ($all_subs_raw as $sl) {
            $sp = explode(';', $sl);
            $s_owner = intval($sp[3] ?? 0);
            $s_status = trim($sp[4] ?? 'active');
            if ($s_status !== 'archived' && in_array($s_owner, $teacher_ids_uczelnia_as)) {
                $sids_uczelnia_as[] = intval($sp[0]);
            }
        }

        // Zbierz ID studentów zapisanych na te przedmioty
        $student_ids_uczelnia_as = [];
        foreach (read_lines($enrollFile) as $el) {
            $ep = explode(';', $el);
            if (count($ep) >= 2 && in_array(intval($ep[1]), $sids_uczelnia_as)) {
                $student_ids_uczelnia_as[intval($ep[0])] = true;
            }
        }

        // Filtruj tablicę $students
        $students_filtered = [];
        foreach ($students as $stud) {
            if (isset($student_ids_uczelnia_as[intval($stud[4])])) {
                $students_filtered[] = $stud;
            }
        }
        $viewData['students_filtered'] = $students_filtered;
    }

    if ($view_action === 'manage_exercises' && $view === 'list' && isset($_GET['sid'])) {
        $sid = intval($_GET['sid']);
        $perm_ok = false;
        $ex_scope = 'none';
        if ($sid > 0) {
            $owner_id = 0;
            foreach ($subs as $s) { $p=explode(';',$s); if(intval($p[0])===$sid){$owner_id=intval($p[3]); break;} }

            $perms = get_subject_permissions($sid, $me['id'], $owner_id);
            if ($perms && ($perms['manage_exercises_scope'] ?? 'none') !== 'none') {
                $perm_ok = true;
                $ex_scope = $perms['manage_exercises_scope'];
            }
        }

        if (!$perm_ok && $sid > 0) {
            $viewData['error_perm'] = "Brak uprawnień do zarządzania ćwiczeniami w tym przedmiocie.";
        } else {
            foreach ($subs as $s) {
                $p = explode(';', $s);
                if (intval($p[0]) === $sid) {
                    $viewData['subLine'] = $p;
                    break;
                }
            }

            $teachers_surnames = [];
            foreach ($allUsers as $u_line) {
                $parts = explode(';', $u_line);
                if ($parts[0] === 'teacher') {
                    $teachers_surnames[intval($parts[4])] = $parts[2];
                }
            }
            $viewData['teachers_surnames'] = $teachers_surnames;
            $viewData['manage_exercises_scope'] = $ex_scope;

            if (isset($viewData['subLine'])) {
                $all_defs = read_lines($exercisesFile);
                $exercises_map = [];
                foreach ($all_defs as $def_line) {
                    $dp = explode(';', $def_line);
                    $exercises_map[intval($dp[0])] = $dp;
                }
                $links = read_lines($subjectExerciseFile);
                $viewData['my_exercises'] = [];

                foreach ($links as $l) {
                    $p = explode(';', $l);
                    if (count($p) >= 2 && intval($p[0]) === $sid) {
                        $eid = intval($p[1]);
                        if (!isset($exercises_map[$eid])) continue;
                        $ex = $exercises_map[$eid];
                        // Jeśli zakres to 'own', pokazuj tylko ćwiczenia przypisane do zalogowanego prowadzącego
                        if ($ex_scope === 'own') {
                            $ex_teacher_id = isset($ex[4]) ? intval($ex[4]) : 0;
                            if ($ex_teacher_id !== intval($me['id'])) continue;
                        }
                        $viewData['my_exercises'][] = $ex;
                    }
                }
            }
        }
    }

    if ($view_action === 'assign_exercise_to_subject') {
        $viewData['assigned_ex_lines'] = read_lines($subjectExerciseFile);
        $viewData['all_exercises'] = read_lines($exercisesFile);
    }

    if ($view_action === 'enroll') {
        $viewData['enrollments'] = read_lines($enrollFile);
    }

    if ($view_action === 'manage_announcements') {
        // Zbuduj zbiór przedmiotów, do których prowadzący ma uprawnienie do ogłoszeń
        $allowed_subs_for_announcements = [];
        $allowed_sids_for_announcements = []; // tylko ID przedmiotów
        foreach ($subs as $s) {
            $p = explode(';', $s);
            $sid = intval($p[0]);
            $owner_id = intval($p[3]);

            $perms = get_subject_permissions($sid, $me['id'], $owner_id);
            if ($perms && $perms['announcements']) {
                $allowed_subs_for_announcements[] = $s;
                $allowed_sids_for_announcements[] = $sid;
            }
        }
        $viewData['subjects_list'] = $allowed_subs_for_announcements;

        // Filtruj ogłoszenia: pokaż tylko te, których target to 'global' LUB należą do dozwolonego przedmiotu
        $all_announcements = read_lines($announcementsFile);
        $filtered_announcements = [];
        $has_any_ann_perm = count($allowed_sids_for_announcements) > 0;
        foreach ($all_announcements as $ann_line) {
            $ap = explode(';', $ann_line);
            if (count($ap) < 5) continue;
            $ann_target = $ap[4];
            if ($ann_target === 'global') {
                // Ogłoszenia globalne widzi każdy prowadzący mający uprawnienie do ogłoszeń na przynajmniej jednym przedmiocie
                if ($has_any_ann_perm) {
                    $filtered_announcements[] = $ann_line;
                }
            } else {
                // Ogłoszenie przypisane do konkretnego przedmiotu — pokaż tylko jeśli ma uprawnienie
                if (in_array(intval($ann_target), $allowed_sids_for_announcements)) {
                    $filtered_announcements[] = $ann_line;
                }
            }
        }
        $viewData['announcements'] = $filtered_announcements;

        // Ogłoszenia ukryte (przeczytane) – z bazy danych MySQL
        global $hiddenAnnouncementsFile;
        $ann_readers = []; // aid => [student_id, ...]
        foreach (read_lines($hiddenAnnouncementsFile) as $hl) {
            $hp = explode(';', $hl);
            if (count($hp) >= 2) {
                $h_stid = intval($hp[0]);
                $h_aid  = intval($hp[1]);
                $ann_readers[$h_aid][] = $h_stid;
            }
        }
        $viewData['ann_readers'] = $ann_readers;

        // Zbuduj mapę id_studenta => imię nazwisko
        $student_name_map = [];
        foreach (read_lines($usersFile) as $ul) {
            $up = explode(';', $ul);
            if ($up[0] === 'student') {
                $student_name_map[intval($up[4])] = $up[1] . ' ' . $up[2];
            }
        }
        $viewData['student_name_map'] = $student_name_map;

        // Jeśli żądanie szczegółów "kto przeczytał" konkretne ogłoszenie
        $ann_readers_detail_aid = isset($_GET['ann_readers']) ? intval($_GET['ann_readers']) : 0;
        if ($ann_readers_detail_aid > 0) {
            $readers_ids = $ann_readers[$ann_readers_detail_aid] ?? [];
            $readers_list = [];
            foreach ($readers_ids as $rid) {
                $readers_list[] = $student_name_map[$rid] ?? "ID: $rid";
            }
            $viewData['ann_readers_detail_aid'] = $ann_readers_detail_aid;
            $viewData['ann_readers_detail_list'] = $readers_list;
        }
    }
	
    if ($view_action === 'progress_view') {
        $selected_sid = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
        $viewData['selected_sid'] = $selected_sid;

        if ($selected_sid > 0) {
            foreach ($subs as $s) {
                $p = explode(';', $s);
                if (intval($p[0]) === $selected_sid) {
                    $viewData['subject_name'] = $p[1];
                    break;
                }
            }

            $assigned_exercises = [];
            foreach (read_lines($subjectExerciseFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[0]) === $selected_sid) {
                    $assigned_exercises[] = intval($p[1]);
                }
            }

            $enrolled_students = [];
            foreach (read_lines($enrollFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[1]) === $selected_sid) {
                    $enrolled_students[] = intval($p[0]);
                }
            }

            // Wczytaj kryteria zaliczenia (3 kolumny: req_grade, req_report, req_attendance)
            $requirements = [];
            foreach (read_lines($deadlinesFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 3 && intval($p[0]) === $selected_sid) {
                    $eid_d = intval($p[1]);
                    if (count($p) >= 5) {
                        $requirements[$eid_d] = [
                            'req_grade'      => (intval($p[2]) === 1),
                            'req_report'     => (intval($p[3]) === 1),
                            'req_attendance' => (intval($p[4]) === 1),
                        ];
                    } else {
                        $requirements[$eid_d] = [
                            'req_grade'      => false,
                            'req_report'     => (intval($p[2]) === 1),
                            'req_attendance' => false,
                        ];
                    }
                }
            }

            $grades_data = [];
            foreach (read_lines($gradesFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 8 && intval($p[2]) === $selected_sid) {
                    $st = intval($p[1]);
                    $ex = intval($p[7]);
                    $val = strtolower(trim($p[4]));
                    $grades_data[$st][$ex][] = $val;
                }
            }

            $att_data = [];
            foreach (read_lines($exerciseAttendanceFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 5 && intval($p[2]) === $selected_sid) {
                    $st = intval($p[1]);
                    $ex = intval($p[3]);
                    $att_data[$st][$ex] = trim($p[4]);
                }
            }

            $history_map = [];
            foreach(read_lines($reportHistoryFile) as $h) {
                $p = explode(';', $h, 5);
                if (count($p) >= 4) {
                    $rid = intval($p[1]);
                    $history_map[$rid][] = ['date' => $p[2], 'status' => $p[3]];
                }
            }
            foreach ($history_map as $rid => &$entries) {
                usort($entries, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
            }
            unset($entries);

            // Mapa zaliczonych sprawozdań – uwzględnia 'zal' kiedykolwiek w historii
            $reports_status = [];
            foreach (read_lines($reportsFile) as $r) {
                $p = explode(';', $r);
                if (count($p) >= 4 && intval($p[2]) === $selected_sid) {
                    $rid = intval($p[0]);
                    $st  = intval($p[1]);
                    $ex  = intval($p[3]);
                    foreach ($history_map[$rid] ?? [] as $he) {
                        if ($he['status'] === 'zal') {
                            $reports_status[$st][$ex] = 'zal';
                            break;
                        }
                    }
                }
            }

            $progress_ranking = [];

            foreach ($students as $stud) {
                $st_id = intval($stud[4]);
                if (!in_array($st_id, $enrolled_students)) continue;

                $total_assigned = count($assigned_exercises);
                $exempted_count = 0;
                $passed_count = 0;

                foreach ($assigned_exercises as $eid) {
                    $student_grades = $grades_data[$st_id][$eid] ?? [];

                    // Zwolnienie
                    if (in_array('zw', $student_grades)) {
                        $exempted_count++;
                        continue;
                    }

                    // Oblicz wartości faktyczne
                    $grade_ok = false;
                    foreach ($student_grades as $g) {
                        $g_clean = str_replace(',', '.', $g);
                        if (is_numeric($g_clean) && floatval($g_clean) >= 2.51) {
                            $grade_ok = true;
                            break;
                        }
                    }
                    $att_status = $att_data[$st_id][$eid] ?? '';
                    $att_ok     = in_array($att_status, ['obecny', 'spóźniony', 'odrobione']);
                    $report_ok  = ($reports_status[$st_id][$eid] ?? '') === 'zal';

                    // Pobierz kryteria
                    $crit = $requirements[$eid] ?? ['req_grade' => false, 'req_report' => false, 'req_attendance' => false];
                    $req_grade      = $crit['req_grade'];
                    $req_report     = $crit['req_report'];
                    $req_attendance = $crit['req_attendance'];
                    $has_any = $req_grade || $req_report || $req_attendance;

                    if (!$has_any) {
                        $passed = $grade_ok;
                    } else {
                        $passed = true;
                        if ($req_grade      && !$grade_ok)  $passed = false;
                        if ($req_report     && !$report_ok) $passed = false;
                        if ($req_attendance && !$att_ok)    $passed = false;
                    }

                    if ($passed) $passed_count++;
                }

                $denominator = $total_assigned - $exempted_count;
                $percentage = 0;
                if ($denominator > 0) {
                    $percentage = ($passed_count / $denominator) * 100;
                } else {
                    $percentage = ($total_assigned > 0) ? 100 : 0;
                }

                $progress_ranking[] = [
                    'name'    => "{$stud[1]} {$stud[2]}",
                    'album'   => $stud[5],
                    'passed'  => $passed_count,
                    'denom'   => $denominator,
                    'exempt'  => $exempted_count,
                    'percent' => $percentage
                ];
            }

            usort($progress_ranking, function($a, $b) {
                if (abs($a['percent'] - $b['percent']) < 0.001) return 0;
                return ($a['percent'] < $b['percent']) ? 1 : -1;
            });

            $viewData['progress_ranking'] = $progress_ranking;
        }
    }
	
    if ($view_action === 'student_grades_view') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $sel_stid = isset($_GET['stid']) ? intval($_GET['stid']) : 0;
        
        $viewData['sel_sid'] = $sel_sid;
        $viewData['sel_stid'] = $sel_stid;

        if ($sel_sid > 0) {
            $viewData['enrolled'] = array_filter(read_lines($enrollFile), function($l) use($sel_sid){ 
                return intval(explode(';',$l)[1]) === $sel_sid;
            });

            // Budujemy mapę definicji ćwiczeń (eid => dane)
            $exercises_defs_map_sg = [];
            foreach (read_lines($exercisesFile) as $c) {
                $cp = explode(';', $c, 5);
                $exercises_defs_map_sg[intval($cp[0])] = $cp;
            }
            // Zachowujemy kolejność z subjectExerciseFile
            $viewData['subject_exercises'] = [];
            foreach (read_lines($subjectExerciseFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[0]) !== $sel_sid) continue;
                $eid_sg = intval($p[1]);
                if (isset($exercises_defs_map_sg[$eid_sg])) {
                    $viewData['subject_exercises'][] = $exercises_defs_map_sg[$eid_sg];
                }
            }

            if ($sel_stid > 0) {
                $student_grades = [];
                foreach (read_lines($gradesFile) as $gl) {
                    $p = explode(';', $gl);
                    if (count($p) >= 9 && intval($p[1]) === $sel_stid && intval($p[2]) === $sel_sid) {
                        $eid = intval($p[7]);
                        $term = intval($p[8]);
                        $val = $p[4];
                        $student_grades[$eid][$term] = $val;
                    }
                }
                $viewData['student_grades'] = $student_grades;

                foreach ($students as $s) {
                    if (intval($s[4]) === $sel_stid) {
                        $viewData['selected_student_data'] = $s;
                        break;
                    }
                }
            }
        }
    }
	
    if ($view_action === 'enforce_tasks') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $viewData['sel_sid'] = $sel_sid;

        if ($sel_sid > 0) {
            foreach ($subs as $s) {
                $p = explode(';', $s);
                if (intval($p[0]) === $sel_sid) {
                    $viewData['subject_name'] = $p[1];
                    break;
                }
            }

            $assigned_eids = [];
            foreach (read_lines($subjectExerciseFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[0]) === $sel_sid) $assigned_eids[] = intval($p[1]);
            }

            $all_exercises = [];
            foreach (read_lines($exercisesFile) as $c) {
                $cp = explode(';', $c, 4);
                if (in_array(intval($cp[0]), $assigned_eids)) {
                    $all_exercises[intval($cp[0])] = $cp[1];
                }
            }

            // Wczytaj kryteria zaliczenia (3 kolumny: req_grade, req_report, req_attendance)
            $crit_map = [];
            $deadlines_map = [];
            foreach (read_lines($deadlinesFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 3 && intval($p[0]) === $sel_sid) {
                    $eid = intval($p[1]);
                    // Terminy: t1 jest w p[5] dla nowego formatu (p[2..4] = kryteria, p[5..8] = terminy)
                    // Stary format: p[2]=req_report, p[3]=t1; nowy: p[2]=req_grade, p[3]=req_report, p[4]=req_att, p[5]=t1
                    if (count($p) >= 5) {
                        $crit_map[$eid] = [
                            'req_grade'      => (intval($p[2]) === 1),
                            'req_report'     => (intval($p[3]) === 1),
                            'req_attendance' => (intval($p[4]) === 1),
                        ];
                        $t1 = $p[5] ?? '';
                    } else {
                        $crit_map[$eid] = [
                            'req_grade'      => false,
                            'req_report'     => (intval($p[2]) === 1),
                            'req_attendance' => false,
                        ];
                        $t1 = $p[3] ?? '';
                    }
                    if (!empty($t1)) {
                        $deadlines_map[$eid] = $t1;
                    }
                }
            }

            $enrolled_students = [];
            $enrolled_ids = [];
            foreach (read_lines($enrollFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[1]) === $sel_sid) {
                    $enrolled_ids[] = intval($p[0]);
                }
            }
            foreach ($students as $stud) {
                if (in_array(intval($stud[4]), $enrolled_ids)) {
                    $enrolled_students[intval($stud[4])] = [
                        'name'  => $stud[1] . ' ' . $stud[2],
                        'album' => $stud[5]
                    ];
                }
            }

            $grades_cache = [];
            foreach (read_lines($gradesFile) as $gl) {
                $p = explode(';', $gl);
                if (count($p) >= 8 && intval($p[2]) === $sel_sid) {
                    $stId = intval($p[1]);
                    $exId = intval($p[7]);
                    $val  = strtolower(trim($p[4]));
                    $grades_cache[$stId][$exId][] = $val;
                }
            }

            $att_cache = [];
            foreach (read_lines($exerciseAttendanceFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 5 && intval($p[2]) === $sel_sid) {
                    $att_cache[intval($p[1])][intval($p[3])] = trim($p[4]);
                }
            }

            // Mapa zaliczonych sprawozdań
            $rh_map = [];
            foreach (read_lines($reportHistoryFile) as $h) {
                $p = explode(';', $h, 5);
                if (count($p) >= 4) {
                    $rh_map[intval($p[1])][] = $p[3];
                }
            }
            $rep_passed_cache = [];
            foreach (read_lines($reportsFile) as $r) {
                $p = explode(';', $r);
                if (count($p) >= 4 && intval($p[2]) === $sel_sid) {
                    $rid = intval($p[0]);
                    $st  = intval($p[1]);
                    $ex  = intval($p[3]);
                    foreach ($rh_map[$rid] ?? [] as $st_h) {
                        if ($st_h === 'zal') {
                            $rep_passed_cache[$st][$ex] = true;
                            break;
                        }
                    }
                }
            }

            $enforcement_list = [];

            foreach ($all_exercises as $eid => $ename) {
                $deadline = $deadlines_map[$eid] ?? null;
                if (!$deadline) continue;

                $crit = $crit_map[$eid] ?? ['req_grade' => false, 'req_report' => false, 'req_attendance' => false];
                $req_grade      = $crit['req_grade'];
                $req_report     = $crit['req_report'];
                $req_attendance = $crit['req_attendance'];
                $has_any = $req_grade || $req_report || $req_attendance;

                $students_at_risk = [];

                foreach ($enrolled_students as $stId => $stData) {
                    $student_grades = $grades_cache[$stId][$eid] ?? [];

                    // Zwolnieni pomijani
                    if (in_array('zw', $student_grades)) continue;

                    // Oblicz wartości faktyczne
                    $has_positive = false;
                    foreach ($student_grades as $g) {
                        $clean_g = str_replace(',', '.', $g);
                        if (is_numeric($clean_g) && floatval($clean_g) >= 2.51) {
                            $has_positive = true;
                            break;
                        }
                    }
                    $att_status = $att_cache[$stId][$eid] ?? '';
                    $att_ok     = in_array($att_status, ['obecny', 'spóźniony', 'odrobione']);
                    $rep_ok     = $rep_passed_cache[$stId][$eid] ?? false;

                    // Czy ćwiczenie zaliczone wg kryteriów?
                    if (!$has_any) {
                        $passed = $has_positive;
                    } else {
                        $passed = true;
                        if ($req_grade      && !$has_positive) $passed = false;
                        if ($req_report     && !$rep_ok)       $passed = false;
                        if ($req_attendance && !$att_ok)       $passed = false;
                    }

                    if (!$passed) {
                        $students_at_risk[] = [
                            'id'    => $stId,
                            'name'  => $stData['name'],
                            'album' => $stData['album']
                        ];
                    }
                }

                if (count($students_at_risk) > 0) {
                    $enforcement_list[] = [
                        'eid'      => $eid,
                        'ename'    => $ename,
                        'deadline' => $deadline,
                        'students' => $students_at_risk
                    ];
                }
            }

            usort($enforcement_list, function($a, $b) {
                return strtotime($a['deadline']) - strtotime($b['deadline']);
            });

            $viewData['enforcement_list'] = $enforcement_list;
        }
    }
	

    if ($view_action === 'manage_exercises' && $view === 'edit_form' && isset($_GET['eid'])) {
        $target_eid = intval($_GET['eid']);
        $allc = read_lines($exercisesFile);
        foreach ($allc as $c) {
            $cp = explode(';', $c, 5);
            if (intval($cp[0]) === $target_eid) {
                $viewData['exercise_to_edit'] = $cp;
                break;
            }
        }
    }

    if ($view_action === 'manage_exemptions') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $viewData['sel_sid'] = $sel_sid;

        if ($sel_sid > 0) {
            $owner_id = 0;
            foreach ($subs as $s) {
                $p = explode(';', $s);
                if (intval($p[0]) === $sel_sid) {
                    $owner_id = intval($p[3]);
                    break;
                }
            }
            $perms = get_subject_permissions($sel_sid, $me['id'], $owner_id);
            $ex_scope = ($perms['manage_exercises_scope'] ?? 'none');

            if (!$perms || $ex_scope === 'none') {
                $viewData['error_perm'] = "Brak uprawnień do zarządzania zwolnieniami w tym przedmiocie.";
                $sel_sid = 0;
                $viewData['sel_sid'] = 0;
            } else {
                $viewData['manage_exercises_scope'] = $ex_scope;
            }

            if ($sel_sid > 0) {
                $viewData['enrolled'] = array_filter(read_lines($enrollFile), function($l) use($sel_sid){
                    return intval(explode(';',$l)[1]) === $sel_sid;
                });

                // Budujemy mapę definicji ćwiczeń (eid => dane)
                $exercises_defs_map_ex = [];
                foreach (read_lines($exercisesFile) as $c) {
                    $cp = explode(';', $c, 5);
                    $exercises_defs_map_ex[intval($cp[0])] = $cp;
                }
                // Przy zakresie 'own' — tylko ćwiczenia przypisane do zalogowanego prowadzącego
                // Zachowujemy kolejność z subjectExerciseFile
                $viewData['subject_exercises'] = [];
                foreach (read_lines($subjectExerciseFile) as $l) {
                    $p = explode(';', $l);
                    if (intval($p[0]) !== $sel_sid) continue;
                    $eid_ex = intval($p[1]);
                    if (!isset($exercises_defs_map_ex[$eid_ex])) continue;
                    $cp = $exercises_defs_map_ex[$eid_ex];
                    if ($ex_scope === 'own') {
                        $ex_teacher_id = isset($cp[4]) ? intval($cp[4]) : 0;
                        if ($ex_teacher_id !== intval($me['id'])) continue;
                    }
                    $viewData['subject_exercises'][] = $cp;
                }

                $current_exemptions = [];
                $grades_exist_map = [];
                foreach (read_lines($gradesFile) as $gl) {
                    $p = explode(';', $gl);
                    if (count($p) >= 8 && intval($p[2]) === $sel_sid) {
                        $stId = intval($p[1]);
                        $eid = intval($p[7]);

                        if ($val = strtolower(trim($p[4]))) {
                            if ($val === 'zw') {
                                $current_exemptions[$stId][$eid] = true;
                            } elseif ($val !== '' && $val !== 'nb') {
                                $grades_exist_map[$stId][$eid] = true;
                            }
                        }
                    }
                }
                $viewData['current_exemptions'] = $current_exemptions;
                $viewData['grades_exist_map'] = $grades_exist_map;
            }
        }
    }

    if ($view_action === 'add_grade' && $view === 'subject' && isset($_GET['sid'])) {
        $sid = intval($_GET['sid']);
        foreach ($subs as $s_check) {
            $p = explode(';', $s_check, 4);
            if (intval($p[0]) === $sid) { $viewData['subLine'] = $p; break; }
        }
        if (isset($viewData['subLine'])) {
            $viewData['all_grades'] = read_lines($gradesFile);
            $viewData['all_att'] = read_lines($attFile);
            $viewData['all_exercises'] = read_lines($exercisesFile);
            $viewData['subject_exercises'] = array_filter(read_lines($subjectExerciseFile), function($l) use($sid){ 
                $p=explode(';',$l); return intval($p[0])===intval($sid); 
            });
            $defined_sections = [];
            foreach (read_lines($definedSectionsFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 3 && intval($p[1]) === $sid) {
                    $defined_sections[] = ['id' => intval($p[0]), 'name' => $p[2]];
                }
            }
            $viewData['defined_sections'] = $defined_sections;

            $student_section_map = [];
            foreach (read_lines($sectionsFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 4 && intval($p[2]) === $sid) {
                    $student_section_map[intval($p[1])] = intval($p[3]);
                }
            }
            $viewData['student_section_map'] = $student_section_map;
            $viewData['selected_sec_id'] = isset($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
        }
    }

    if ($view_action === 'manage_reports' && $view === 'subject' && isset($_GET['sid'])) {
        $sid = intval($_GET['sid']);
        foreach ($subs as $s_check) {
            $p = explode(';', $s_check, 4);
            if (intval($p[0]) === $sid) { $viewData['subLine'] = $p; break; }
        }
        if (isset($viewData['subLine'])) {
            $viewData['exercise_names'] = [];
            foreach (read_lines($exercisesFile) as $c) {
                $cp = explode(';', $c, 3);
                $viewData['exercise_names'][intval($cp[0])] = $cp[1];
            }
            $allReports = read_lines($reportsFile);
            // Filtrowanie: Przedmiot musi się zgadzać ORAZ (adresat to zalogowany user LUB brak adresata - kompatybilność wsteczna)
            $me_id_filter = intval($me['id']);
            $subjectReports = array_filter($allReports, function($l) use($sid, $me_id_filter){
                $p = explode(';', $l);
                // Indeks 8 to teacher_id (licząc od 0)
                $target_teacher_id = isset($p[8]) ? intval($p[8]) : 0;
                
                // Pokaż jeśli: Zgadza się przedmiot I (adresat to JA lub adresat nieustalony/0)
                return count($p) >= 7 && intval($p[2]) === $sid && ($target_teacher_id === 0 || $target_teacher_id === $me_id_filter);
            });
            
            $allHistory = read_lines($reportHistoryFile);
            $reportHistory = [];
            foreach($allHistory as $h_line) {
                $p = explode(';', $h_line, 5);
                if (count($p) >= 5) {
                    $report_id = intval($p[1]);
                    if (!isset($reportHistory[$report_id])) {
                        $reportHistory[$report_id] = [];
                    }
                    $history_comment_content = str_replace(',', ';', $p[4]);
                    array_unshift($reportHistory[$report_id], ['date' => $p[2], 'status' => $p[3], 'comment' => $history_comment_content]);
                }
            }
            $viewData['reportHistory'] = $reportHistory;

            $pairs_with_pass = [];
            foreach ($subjectReports as $r) {
                $p = explode(';', $r);
                $repId = intval($p[0]);
                $stId = intval($p[1]);
                $exId = intval($p[3]);

                $current_status = 'do_sprawdzenia';
                if (isset($reportHistory[$repId])) {
                    $current_entry = $reportHistory[$repId][0];
                    $current_status = $current_entry['status'];
                }
                if ($current_status === 'zal') {
                    $pairs_with_pass["{$stId}-{$exId}"] = true;
                }
            }

            $reports_passed = [];
            $reports_pending = [];
            foreach ($subjectReports as $r) {
                $p = explode(';', $r);
                $stId = intval($p[1]);
                $exId = intval($p[3]);
                if (isset($pairs_with_pass["{$stId}-{$exId}"])) {
                    $reports_passed[] = ['line' => $r];
                } else {
                    $reports_pending[] = ['line' => $r];
                }
            }
            $viewData['reports_passed'] = $reports_passed;
            $viewData['reports_pending'] = $reports_pending;
        }
            $existingGradesMap = [];
            foreach (read_lines($gradesFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 8 && intval($p[2]) === $sid) {
                    $existingGradesMap[intval($p[1])][intval($p[7])] = true;
                }
            }
            $existingAttMap = [];
            foreach (read_lines($exerciseAttendanceFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 5 && intval($p[2]) === $sid) {
                    $existingAttMap[intval($p[1])][intval($p[3])] = true;
                }
            }
            $viewData['existingGradesMap'] = $existingGradesMap;
            $viewData['existingAttMap'] = $existingAttMap;
    }

    if ($view_action === 'manage_exercise_att' && $view === 'subject' && isset($_GET['sid'])) {
        $sid = intval($_GET['sid']);
        foreach ($subs as $s_check) {
            $p = explode(';', $s_check, 4);
            if (intval($p[0]) === $sid) { 
                $viewData['subLine'] = $p; 
                break; 
            }
        }
        if (isset($viewData['subLine'])) {
            $sub_owner_id = intval($viewData['subLine'][3]);
            
            $all_ex_defs = [];
            foreach(read_lines($exercisesFile) as $exline) {
                 $exp = explode(';', $exline);
                 $all_ex_defs[intval($exp[0])] = isset($exp[4]) ? intval($exp[4]) : 0;
            }

            $sub_owner_id = intval($viewData['subLine'][3]);
            
            $all_ex_defs = [];
            foreach(read_lines($exercisesFile) as $exline) {
                 $exp = explode(';', $exline);
                 $all_ex_defs[intval($exp[0])] = isset($exp[4]) ? intval($exp[4]) : 0;
            }
            $perms = get_subject_permissions($sid, $me['id'], $sub_owner_id);
            $viewData['assigned_ex'] = array_filter(read_lines($subjectExerciseFile), function($l) use($sid, $me, $sub_owner_id, $all_ex_defs, $perms){ 
                $p = explode(';', $l);
                if (intval($p[0]) !== $sid) return false;
                $eid = intval($p[1]);
                $ex_teacher_id = $all_ex_defs[$eid] ?? 0;
                if ($perms && isset($perms['grading_scope']) && $perms['grading_scope'] === 'all') {
                    return true;
                }
                if ($ex_teacher_id === intval($me['id'])) {
                    return true;
                }
                return false;
            });
            $viewData['all_exercises'] = read_lines($exercisesFile);
        }
    }

    if ($view_action === 'manage_exercise_att' && $view === 'subject_exercise') {
        $sid = intval($_GET['sid'] ?? 0);
        $eid = intval($_GET['eid'] ?? 0);
        foreach ($subs as $s_check) {
            $p = explode(';', $s_check, 4);
            if (intval($p[0]) === $sid) { $viewData['subLine'] = $p; break; }
        }
        foreach (read_lines($exercisesFile) as $c_check) {
            $p = explode(';', $c_check, 3);
            if (intval($p[0]) === $eid) { $viewData['exLine'] = $p; break; }
        }
        
        if (isset($viewData['subLine']) && isset($viewData['exLine'])) {
            $viewData['enrolled'] = array_filter(read_lines($enrollFile), function($l) use($sid){ return intval(explode(';',$l)[1]) === $sid;});
            $viewData['student_info'] = [];
            foreach ($students as $stud) {
                $viewData['student_info'][intval($stud[4])] = ['name' => "{$stud[1]} {$stud[2]}", 'album' => $stud[5]]; 
            }
            
            $current_att = [];
            foreach (read_lines($exerciseAttendanceFile) as $att_line) {
                $p = explode(';', $att_line);
                if (count($p) >= 5 && intval($p[2]) === $sid && intval($p[3]) === $eid) {
                    $current_att[intval($p[1])] = ['id' => intval($p[0]), 'status' => $p[4], 'date' => $p[5] ?? 'b/d'];
                }
            }
			$exemptions_map = [];
            foreach (read_lines($gradesFile) as $gl) {
                $gp = explode(';', $gl);
                if (count($gp) >= 8 && intval($gp[2]) === $sid && intval($gp[7]) === $eid) {
                    if (strtolower(trim($gp[4])) === 'zw') {
                        $exemptions_map[intval($gp[1])] = true;
                    }
                }
            }
            $viewData['exemptions_map'] = $exemptions_map;

            $viewData['current_att'] = $current_att;
            $viewData['current_att'] = $current_att;
        }
    }

    if ($view_action === 'statistics') {
        $selected_sid = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
        $stats_type = isset($_GET['stats_type']) ? $_GET['stats_type'] : 'avg';
        $sort_desc = isset($_GET['sort_desc']);
        
        $viewData['selected_sid'] = $selected_sid;
        $viewData['stats_type'] = $stats_type;
        $viewData['sort_desc'] = $sort_desc;

        if ($selected_sid > 0) {
            $allowed = false;
            foreach ($subs as $s) {
                $p = explode(';', $s);
                if (intval($p[0]) === $selected_sid) {
                    $allowed = true;
                    $viewData['subject_name'] = $p[1];
                    break;
                }
            }
            if ($allowed) {
                $enrolled_students = [];
                foreach (read_lines($enrollFile) as $l) {
                    $p = explode(';', $l);
                    if (intval($p[1]) === $selected_sid) {
                        $enrolled_students[] = intval($p[0]);
                    }
                }
                
                $assigned_exercises = [];
                foreach (read_lines($subjectExerciseFile) as $l) {
                    $p = explode(';', $l);
                    if (intval($p[0]) === $selected_sid) $assigned_exercises[] = intval($p[1]);
                }
                $viewData['assigned_exercises'] = $assigned_exercises;

                $exercise_names = [];
                foreach (read_lines($exercisesFile) as $c) {
                    $cp = explode(';', $c, 3);
                    if (in_array(intval($cp[0]), $assigned_exercises)) {
                        $exercise_names[intval($cp[0])] = $cp[1];
                    }
                }
                $viewData['exercise_names'] = $exercise_names;
                
                $grades_all = read_lines($gradesFile);
                if ($stats_type === 'avg') {
                    $exercise_weights = [];
                    foreach (read_lines($exercisesFile) as $c) {
                        $cp = explode(';', $c, 4);
                        if (isset($cp[3])) {
                            $exercise_weights[intval($cp[0])] = intval($cp[3]);
                        } else {
                            $exercise_weights[intval($cp[0])] = 1;
                        }
                    }

                    $stats_data = [];
                    foreach ($students as $stud) {
                        $sid_stud = intval($stud[4]);
                        if (!in_array($sid_stud, $enrolled_students)) continue;

                        $exercise_averages = [];
                        $weighted_sum = 0;
                        $total_weight = 0;
                        
                        foreach ($assigned_exercises as $eid) {
                            $student_exercise_grades = [];
                            foreach ($grades_all as $g_line) {
                                $p = explode(';', $g_line);
                                if (isset($p[7]) && intval($p[1]) === $sid_stud && intval($p[2]) === $selected_sid && intval($p[7]) === $eid) {
                                    $val_str = strtolower(trim($p[4]));
                                    if (is_numeric(str_replace(',', '.', $val_str))) {
                                        $val = floatval(str_replace(',', '.', $val_str));
                                        if ($val > 0) {
                                            $student_exercise_grades[] = $val;
                                        }
                                    }
                                }
                            }
                            
                            if (count($student_exercise_grades) > 0) {
                                $avg_ex = array_sum($student_exercise_grades) / count($student_exercise_grades);
                                $exercise_averages[$eid] = $avg_ex;
                                $w = isset($exercise_weights[$eid]) ? $exercise_weights[$eid] : 1;
                                $weighted_sum += ($avg_ex * $w);
                                $total_weight += $w;
                            } else {
                                $exercise_averages[$eid] = null;
                            }
                        }
                        
                        $final_avg = ($total_weight > 0) ? $weighted_sum / $total_weight : 0;
                        
                        $stats_data[] = [
                            'name' => $stud[1] . ' ' . $stud[2],
                            'album' => $stud[5],
                            'ex_avgs' => $exercise_averages,
                            'final_avg' => $final_avg
                        ];
                    }
                    if ($sort_desc) {
                        usort($stats_data, function ($a, $b) {
                            if ($a['final_avg'] == $b['final_avg']) return 0;
                            return ($a['final_avg'] < $b['final_avg']) ? 1 : -1;
                        });
                    }
                    $viewData['stats_data'] = $stats_data;
                }

                elseif ($stats_type === 'distribution') {
                    $distribution = ['2.0'=>0, '3.0'=>0, '3.5'=>0, '4.0'=>0, '4.5'=>0, '5.0'=>0, 'Inne'=>0];
                    foreach ($grades_all as $g_line) {
                        $p = explode(';', $g_line);
                        if (isset($p[2]) && intval($p[2]) === $selected_sid) {
                            $raw_val = str_replace(',', '.', $p[4]);
                            $val = number_format(floatval($raw_val), 1);
                            if ($val == '0.0') continue; 

                            if (isset($distribution[$val])) {
                                $distribution[$val]++;
                            } else {
                                $found = false;
                                foreach(array_keys($distribution) as $k) {
                                    if (abs(floatval($k) - floatval($val)) < 0.01) {
                                        $distribution[$k]++;
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) $distribution['Inne']++;
                            }
                        }
                    }
                    $viewData['stats_distribution'] = $distribution;
                }

                elseif ($stats_type === 'attendance') {
                    $att_stats = [];
                    $att_lines = read_lines($exerciseAttendanceFile);
                    
                    foreach ($students as $stud) {
                        $sid_stud = intval($stud[4]);
                        if (!in_array($sid_stud, $enrolled_students)) continue;
                        
                        $present = 0;
                        $total = 0;
                        
                        foreach ($att_lines as $al) {
                            $p = explode(';', $al);
                            if (count($p) >= 5 && intval($p[1]) === $sid_stud && intval($p[2]) === $selected_sid) {
                                $total++;
                                if ($p[4] === 'obecny' || $p[4] === 'spóźniony' || $p[4] === 'odrobione') {
                                    $present++;
                                }
                            }
                        }
                        
                        $percent = ($total > 0) ? ($present / $total) * 100 : 0;
                        $att_stats[] = [
                            'name' => $stud[1] . ' ' . $stud[2],
                            'album' => $stud[5],
                            'present' => $present,
                            'total' => $total,
                            'percent' => $percent
                        ];
                    }
                    usort($att_stats, function ($a, $b) {
                        return ($a['percent'] < $b['percent']) ? 1 : -1;
                    });
                    $viewData['stats_attendance'] = $att_stats;
                }

                elseif ($stats_type === 'attendance_detail') {
                    $att_detail_stats = [];
                    $att_lines = read_lines($exerciseAttendanceFile);
                    $all_statuses = ['obecny', 'nieobecny', 'nieobecność usprawiedliwiona', 'spóźniony', 'odrobione', 'niewykonane', 'nie sprawdzono'];

                    foreach ($students as $stud) {
                        $sid_stud = intval($stud[4]);
                        if (!in_array($sid_stud, $enrolled_students)) continue;

                        $counts = array_fill_keys($all_statuses, 0);
                        $total = 0;

                        foreach ($att_lines as $al) {
                            $p = explode(';', $al);
                            if (count($p) >= 5 && intval($p[1]) === $sid_stud && intval($p[2]) === $selected_sid) {
                                $total++;
                                $status = trim($p[4]);
                                if (isset($counts[$status])) {
                                    $counts[$status]++;
                                }
                            }
                        }

                        $present = $counts['obecny'] + $counts['spóźniony'] + $counts['odrobione'];
                        $percent = ($total > 0) ? ($present / $total) * 100 : 0;

                        $att_detail_stats[] = [
                            'name'    => $stud[1] . ' ' . $stud[2],
                            'album'   => $stud[5],
                            'counts'  => $counts,
                            'total'   => $total,
                            'present' => $present,
                            'percent' => $percent,
                        ];
                    }
                    usort($att_detail_stats, function ($a, $b) {
                        return ($a['percent'] < $b['percent']) ? 1 : -1;
                    });
                    $viewData['stats_attendance_detail'] = $att_detail_stats;
                    $viewData['att_detail_statuses'] = $all_statuses;
                }

                elseif ($stats_type === 'pass_rate') {
                    $pass_count = 0;
                    $fail_count = 0;
                    
                    foreach ($students as $stud) {
                        $sid_stud = intval($stud[4]);
                        if (!in_array($sid_stud, $enrolled_students)) continue;

                        $sum = 0; $cnt = 0;
                        foreach ($assigned_exercises as $eid) {
                            $grades_for_ex = [];
                            foreach ($grades_all as $g_line) {
                                $p = explode(';', $g_line);
                                if (isset($p[7]) && intval($p[1]) === $sid_stud && intval($p[2]) === $selected_sid && intval($p[7]) === $eid) {
                                    $v = floatval(str_replace(',', '.', $p[4]));
                                    if ($v > 0) $grades_for_ex[] = $v;
                                }
                            }
                            if (count($grades_for_ex) > 0) {
                                $sum += (array_sum($grades_for_ex) / count($grades_for_ex));
                                $cnt++;
                            }
                        }
                        $final = ($cnt > 0) ? ($sum / $cnt) : 0;
                        
                        if ($final >= 3.0) $pass_count++;
                        else $fail_count++;
                    }
                    $viewData['pass_stats'] = ['pass' => $pass_count, 'fail' => $fail_count];
                }

                elseif ($stats_type === 'difficulty') {
                    $diff_stats = [];
                    foreach ($assigned_exercises as $eid) {
                        $sum = 0;
                        $count = 0;
                        foreach ($grades_all as $g_line) {
                            $p = explode(';', $g_line);
                            if (isset($p[7]) && intval($p[2]) === $selected_sid && intval($p[7]) === $eid) {
                                $v = floatval(str_replace(',', '.', $p[4]));
                                if ($v > 0) {
                                    $sum += $v;
                                    $count++;
                                }
                            }
                        }
                        $avg = ($count > 0) ? ($sum / $count) : 0;
                        $diff_stats[] = [
                            'name' => $exercise_names[$eid] ?? "Ćw $eid",
                            'avg' => $avg,
                            'count' => $count
                        ];
                    }
                    usort($diff_stats, function ($a, $b) {
                        if ($a['avg'] == $b['avg']) return 0;
                        return ($a['avg'] > $b['avg']) ? 1 : -1;
                    });
                    $viewData['diff_stats'] = $diff_stats;
                }
            }
        }
    }

    if ($view_action === 'manage_deadlines') {
        $selected_sid = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
        $viewData['selected_sid'] = $selected_sid;
        if ($selected_sid > 0) {
            // Sprawdzenie uprawnień do zarządzania ćwiczeniami
            $owner_id = 0;
            foreach ($subs as $s) { $p=explode(';',$s); if(intval($p[0])===$selected_sid){$owner_id=intval($p[3]); break;} }
            $perms = get_subject_permissions($selected_sid, $me['id'], $owner_id);
            $ex_scope = ($perms['manage_exercises_scope'] ?? 'none');

            if (!$perms || $ex_scope === 'none') {
                $viewData['deadlines_perm_error'] = "Brak uprawnień do zarządzania wymaganiami zaliczenia ćwiczeń.";
            } else {
                $viewData['manage_exercises_scope'] = $ex_scope;
                foreach ($subs as $s) {
                    $p = explode(';', $s);
                    if (intval($p[0]) === $selected_sid) {
                        $viewData['subName'] = $p[1];
                        break;
                    }
                }

                // Pobierz ID ćwiczeń przypisanych do przedmiotu
                $all_assigned = array_filter(read_lines($subjectExerciseFile), function($l) use($selected_sid){ $p=explode(';',$l); return intval($p[0]) === $selected_sid;});

                if ($ex_scope === 'own') {
                    // Ogranicz do ćwiczeń przypisanych do zalogowanego prowadzącego
                    $ex_teacher_map = [];
                    foreach (read_lines($exercisesFile) as $el) {
                        $ep = explode(';', $el);
                        $ex_teacher_map[intval($ep[0])] = isset($ep[4]) ? intval($ep[4]) : 0;
                    }
                    $all_assigned = array_filter($all_assigned, function($l) use($ex_teacher_map, $me) {
                        $p = explode(';', $l);
                        $eid = intval($p[1]);
                        return ($ex_teacher_map[$eid] ?? 0) === intval($me['id']);
                    });
                }
                $viewData['assigned_exercises'] = $all_assigned;

                $deadlines_data = [];
                foreach (read_lines($deadlinesFile) as $l) {
                    $p = explode(';', $l);
                    if (count($p) >= 5 && intval($p[0]) === $selected_sid) {
                        if (count($p) >= 9) {
                            $deadlines_data[intval($p[1])] = [
                                'req_grade'      => intval($p[2]),
                                'req_report'     => intval($p[3]),
                                'req_attendance' => intval($p[4]),
                                't1' => $p[5] ?? '', 't2' => $p[6] ?? '',
                                't3' => $p[7] ?? '', 't4' => $p[8] ?? '',
                                'req' => intval($p[3]),
                            ];
                        } else {
                            $deadlines_data[intval($p[1])] = [
                                'req_grade'      => 0,
                                'req_report'     => intval($p[2]),
                                'req_attendance' => 0,
                                't1' => $p[3] ?? '', 't2' => $p[4] ?? '',
                                't3' => $p[5] ?? '', 't4' => $p[6] ?? '',
                                'req' => intval($p[2]),
                            ];
                        }
                    }
                }
                $viewData['deadlines_data'] = $deadlines_data;
                $viewData['all_exercises'] = read_lines($exercisesFile);
            }
        }
    }

    if ($view_action === 'manage_access' && $view === 'details' && isset($_GET['sid'])) {
        $sid = intval($_GET['sid']);
        foreach ($subs as $s) {
            $p = explode(';', $s);
            if (intval($p[0]) === $sid && intval($p[3]) === intval($me['id'])) {
                $viewData['subLine'] = $p;
                break;
            }
        }
        if (isset($viewData['subLine'])) {
            $access_list = [];
            foreach (read_lines($subjectAccessFile) as $apl) {
                $ap = explode(';', $apl, 4);
                
                if (intval($ap[0]) === $sid) {
                    $tid_acc = intval($ap[1]);
                    $t_name = 'Nieznany';
                    foreach ($teachers_list as $t) {
                        if ($t['id'] === $tid_acc) { 
                            $t_name = $t['name'] . " (" . $t['inicjaly'] . ")"; 
                            break; 
                        }
                    }
                    $perms_json = isset($ap[3]) ? $ap[3] : '';
                    $perms = json_decode($perms_json, true);
                    if (!is_array($perms)) $perms = [];

                    $access_list[] = ['id' => $tid_acc, 'name' => $t_name, 'perms' => $perms];
                }
            }
            $viewData['access_list'] = $access_list;
        }
    }

    if ($view_action === 'batch_grading') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $viewData['sel_sid'] = $sel_sid;
        if ($sel_sid > 0) {
             $assigned_eids = [];
            foreach (read_lines($subjectExerciseFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[0]) === $sel_sid) $assigned_eids[] = intval($p[1]);
            }
            $allc = read_lines($exercisesFile);
            $viewData['subject_exercises'] = [];
            $sub_owner_id = 0;
            foreach ($subs as $s_temp) {
                $sp = explode(';', $s_temp);
                if (intval($sp[0]) === $sel_sid) {
                    $sub_owner_id = intval($sp[3]);
                    break;
                }
            }
            $sub_owner_id = 0;
            foreach ($subs as $s_temp) {
                $sp = explode(';', $s_temp);
                if (intval($sp[0]) === $sel_sid) {
                    $sub_owner_id = intval($sp[3]);
                    break;
                }
            }
            
            $perms = get_subject_permissions($sel_sid, $me['id'], $sub_owner_id);

            foreach ($allc as $c) {
                $cp = explode(';', $c); 
                $eid = intval($cp[0]);
                $ex_teacher_id = isset($cp[4]) ? intval($cp[4]) : 0;

                if (in_array($eid, $assigned_eids)) {
                    $can_grade_this = false;
                    
                    if ($perms && isset($perms['grading_scope']) && $perms['grading_scope'] === 'all') {
                        $can_grade_this = true;
                    } elseif ($ex_teacher_id === intval($me['id'])) {
                        $can_grade_this = true;
                    }

                    if ($can_grade_this) {
                        $viewData['subject_exercises'][] = $cp;
                    }
                }
            }
        }
        $sel_eid = isset($_GET['eid']) ? intval($_GET['eid']) : 0;
        $viewData['sel_eid'] = $sel_eid;
        if ($sel_eid > 0) {
            $viewData['enrolled'] = array_filter(read_lines($enrollFile), function($l) use($sel_sid){ return intval(explode(';',$l)[1]) === $sel_sid;});
            $current_grades = [];
            foreach (read_lines($gradesFile) as $gl) {
                $p = explode(';', $gl);
                if (count($p) >= 9 && intval($p[2]) === $sel_sid && intval($p[7]) === $sel_eid) {
                    $current_grades[intval($p[1])][intval($p[8])] = $p[4];
                }
            }
            $current_att_map = [];
            foreach (read_lines($exerciseAttendanceFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 5 && intval($p[2]) === $sel_sid && intval($p[3]) === $sel_eid) {
                    $current_att_map[intval($p[1])] = trim($p[4]);
                }
            }
            $viewData['current_att_map'] = $current_att_map;
            $viewData['current_grades'] = $current_grades;
        }
    }

    if ($view_action === 'manage_sections') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
		$perm_ok = false;
        if ($sel_sid > 0) {
            $owner_id = 0;
            foreach ($subs as $s) { $p=explode(';',$s); if(intval($p[0])===$sel_sid){$owner_id=intval($p[3]); break;} }
            
            $perms = get_subject_permissions($sel_sid, $me['id'], $owner_id);
            if ($perms && $perms['manage_sections']) {
                $perm_ok = true;
            }
        }
        
        if (!$perm_ok && $sel_sid > 0) {
            $viewData['error_perm'] = "Brak uprawnień do zarządzania sekcjami w tym przedmiocie.";
        } else {
			$sel_sec_id = isset($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
			
			$viewData['sel_sid'] = $sel_sid;
			$viewData['sel_sec_id'] = $sel_sec_id;

			if ($sel_sid > 0) {
				$defined_sections = [];
				foreach (read_lines($definedSectionsFile) as $l) {
					$p = explode(';', $l);
					if (count($p) >= 3 && intval($p[1]) === $sel_sid) {
						$defined_sections[] = ['id' => intval($p[0]), 'name' => $p[2]];
					}
				}
				$viewData['defined_sections'] = $defined_sections;
				if (isset($_GET['edit_sec_id'])) {
					$edit_id = intval($_GET['edit_sec_id']);
					$viewData['edit_sec_id'] = $edit_id;

					foreach ($defined_sections as $ds) {
						if ($ds['id'] === $edit_id) {
							$viewData['section_to_edit'] = $ds;
							break;
						}
					}
				}

				if ($sel_sec_id > 0) {
					$enrolled_ids = [];
					foreach (read_lines($enrollFile) as $l) {
						$p = explode(';', $l);
						if (intval($p[1]) === $sel_sid) $enrolled_ids[] = intval($p[0]);
					}
					
					$section_map = [];
					foreach (read_lines($sectionsFile) as $l) {
						$p = explode(';', $l);
						if (count($p) >= 4 && intval($p[2]) === $sel_sid) {
							$section_map[intval($p[1])] = intval($p[3]);
						}
					}

					$students_in_section = [];
					foreach ($students as $s) {
						$stId = intval($s[4]);
						if (in_array($stId, $enrolled_ids)) {
							if (isset($section_map[$stId]) && $section_map[$stId] === $sel_sec_id) {
								$students_in_section[] = $s;
							}
						}
					}
					$viewData['students_in_section'] = $students_in_section;
					foreach($defined_sections as $ds) {
						if ($ds['id'] === $sel_sec_id) {
							$viewData['current_section_name'] = $ds['name'];
							break;
						}
					}
				}
			}
		}
    }

    if ($view_action === 'batch_add_students_view') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
		$perm_ok = false;
        if ($sel_sid > 0) {
            $owner_id = 0;
            foreach ($subs as $s) { $p=explode(';',$s); if(intval($p[0])===$sel_sid){$owner_id=intval($p[3]); break;} }
            
            $perms = get_subject_permissions($sel_sid, $me['id'], $owner_id);
            if ($perms && $perms['manage_sections']) {
                $perm_ok = true;
            }
        }
        
        if (!$perm_ok && $sel_sid > 0) {
            $viewData['error_perm'] = "Brak uprawnień do zarządzania sekcjami w tym przedmiocie.";
        } else {
			$pre_sec_id = isset($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
			
			$viewData['sel_sid'] = $sel_sid;
			$viewData['pre_sec_id'] = $pre_sec_id;
			
			if ($sel_sid > 0) {
				$defined_sections = [];
				foreach (read_lines($definedSectionsFile) as $l) {
					$p = explode(';', $l);
					if (count($p) >= 3 && intval($p[1]) === $sel_sid) {
						$defined_sections[] = ['id' => intval($p[0]), 'name' => $p[2]];
					}
				}
				$viewData['defined_sections'] = $defined_sections;
			}
		}
    }
	
    if ($view_action === 'student_logs_view') {
        global $logsFile;
        
        $filter_student = trim($_GET['f_student'] ?? '');
        $filter_date_from = $_GET['f_date_from'] ?? '';
        $filter_date_to = $_GET['f_date_to'] ?? '';
        $filter_device = $_GET['f_device'] ?? '';

        $studentsMap = [];
        foreach ($students as $s) {
            $studentsMap[intval($s[4])] = [
                'name' => $s[1] . ' ' . $s[2],
                'album' => $s[5],
                'search_str' => strtolower($s[1] . ' ' . $s[2] . ' ' . $s[5])
            ];
        }

        $allLogs = read_lines($logsFile);
        $processedLogs = [];
        $loginStats = [];

        foreach ($allLogs as $l) {
            $p = explode(';', $l);
            if (count($p) < 5) continue;
            
            $sid = intval($p[1]);
            $date = $p[2];
            $ip = $p[3];
            $dev = $p[4];

            if ($filter_student !== '') {
                if (isset($studentsMap[$sid])) {
                    if (strpos($studentsMap[$sid]['search_str'], strtolower($filter_student)) === false) continue;
                } else {
                    continue;
                }
            }

            if ($filter_date_from !== '' && $date < $filter_date_from . ' 00:00:00') continue;
            if ($filter_date_to !== '' && $date > $filter_date_to . ' 23:59:59') continue;

            if ($filter_device !== '') {
                if ($filter_device === 'mobile' && strpos($dev, 'Mobile') === false) continue;
                if ($filter_device === 'desktop' && strpos($dev, 'Komputer') === false) continue;
            }

            if (!isset($loginStats[$sid])) $loginStats[$sid] = 0;
            $loginStats[$sid]++;

            $stName = isset($studentsMap[$sid]) ? $studentsMap[$sid]['name'] : 'Usunięty (ID: '.$sid.')';
            $stAlbum = isset($studentsMap[$sid]) ? $studentsMap[$sid]['album'] : '-';

            $processedLogs[] = [
                'id' => $p[0],
                'sid' => $sid,
                'st_name' => $stName,
                'st_album' => $stAlbum,
                'date' => $date,
                'ip' => $ip,
                'device' => $dev
            ];
        }

        usort($processedLogs, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        arsort($loginStats);
        $topLogins = array_slice($loginStats, 0, 5, true);
        $topLoginsView = [];
        foreach($topLogins as $sid => $count) {
            $name = isset($studentsMap[$sid]) ? $studentsMap[$sid]['name'] : 'Usunięty';
            $album = isset($studentsMap[$sid]) ? $studentsMap[$sid]['album'] : '';
            $topLoginsView[] = ['name' => $name, 'album' => $album, 'count' => $count];
        }

        $viewData['logs'] = $processedLogs;
        $viewData['top_logins'] = $topLoginsView;
        $viewData['filters'] = [
            'student' => $filter_student,
            'd_from' => $filter_date_from,
            'd_to' => $filter_date_to,
            'dev' => $filter_device
        ];
    }
	
    if ($view_action === 'final_grades_view') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $viewData['sel_sid'] = $sel_sid;

        if ($sel_sid > 0) {
            $owner_id = 0;
            foreach ($subs as $s) {
                $p = explode(';', $s);
                if (intval($p[0]) === $sel_sid) {
                    $viewData['subject_name'] = $p[1];
                    $owner_id = intval($p[3]);
                    break;
                }
            }

            $perms = get_subject_permissions($sel_sid, $me['id'], $owner_id);
            
            if (!$perms || !$perms['final_grades']) {
                $viewData['error_perm'] = "Brak uprawnień do wystawiania ocen końcowych w tym przedmiocie.";
                $sel_sid = 0;
                $viewData['sel_sid'] = 0;
            }


            $enrolled_ids = [];
            foreach (read_lines($enrollFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[1]) === $sel_sid) $enrolled_ids[] = intval($p[0]);
            }
            $student_list = [];
            foreach ($students as $s) {
                if (in_array(intval($s[4]), $enrolled_ids)) {
                    $student_list[] = $s;
                }
            }
            $viewData['student_list'] = $student_list;

            $assigned_exercises = [];
            foreach (read_lines($subjectExerciseFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[0]) === $sel_sid) $assigned_exercises[] = intval($p[1]);
            }
            
            $weights = [];
            foreach (read_lines($exercisesFile) as $c) {
                $cp = explode(';', $c, 4);
                if (in_array(intval($cp[0]), $assigned_exercises)) {
                    $weights[intval($cp[0])] = isset($cp[3]) ? intval($cp[3]) : 1;
                }
            }
            
            $all_grades = read_lines($gradesFile);
            
            // Wczytaj kryteria zaliczenia (3 kolumny: req_grade, req_report, req_attendance)
            $requirements = [];
            foreach (read_lines($deadlinesFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 3 && intval($p[0]) === $sel_sid) {
                    $eid_d = intval($p[1]);
                    if (count($p) >= 5) {
                        $requirements[$eid_d] = [
                            'req_grade'      => (intval($p[2]) === 1),
                            'req_report'     => (intval($p[3]) === 1),
                            'req_attendance' => (intval($p[4]) === 1),
                        ];
                    } else {
                        // Stary format – tylko req_report w p[2]
                        $requirements[$eid_d] = [
                            'req_grade'      => false,
                            'req_report'     => (intval($p[2]) === 1),
                            'req_attendance' => false,
                        ];
                    }
                }
            }

            $att_map = [];
            foreach (read_lines($exerciseAttendanceFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 5 && intval($p[2]) === $sel_sid) {
                    $att_map[intval($p[1])][intval($p[3])] = trim($p[4]);
                }
            }

            $reportHistoryRaw = read_lines($reportHistoryFile);
            $reportHistory = [];
            foreach($reportHistoryRaw as $h) {
                $p = explode(';', $h, 5);
                if(count($p) >= 4) {
                    $reportHistory[intval($p[1])][] = ['date'=>$p[2], 'status'=>$p[3]];
                }
            }
            foreach ($reportHistory as &$rh) {
                usort($rh, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
            }
            // Mapa zaliczonych sprawozdań: uwzględnia 'zal' kiedykolwiek w historii
            $reportPassedMap = [];
            foreach(read_lines($reportsFile) as $r) {
                $p = explode(';', $r);
                if(count($p) >= 4 && intval($p[2]) === $sel_sid) {
                    $rid = intval($p[0]);
                    $st  = intval($p[1]);
                    $ex  = intval($p[3]);
                    foreach ($reportHistory[$rid] ?? [] as $he) {
                        if ($he['status'] === 'zal') {
                            $reportPassedMap[$st][$ex] = true;
                            break;
                        }
                    }
                }
            }

            $student_grades_map = [];
            foreach ($all_grades as $g) {
                $p = explode(';', $g);
                if (count($p) >= 8 && intval($p[2]) === $sel_sid) {
                    $stId = intval($p[1]);
                    $eid = intval($p[7]);
                    $val = strtolower(trim($p[4]));
                    $student_grades_map[$stId][$eid][] = $val;
                }
            }

            $final_grades_saved = [];
            $final_grades_lines = read_lines($finalGradesFile);
            foreach ($final_grades_lines as $fl) {
                $p = explode(';', $fl);
                if (count($p) >= 7 && intval($p[2]) === $sel_sid) {
                    $final_grades_saved[intval($p[1])] = [
                        'grade'   => $p[3],
                        'comment' => isset($p[4]) ? $p[4] : ''
                    ];
                }
            }
            $viewData['final_grades_saved'] = $final_grades_saved;

            $calculated_avgs = [];

            foreach ($student_list as $stud) {
                $stId = intval($stud[4]);
                $weighted_sum = 0;
                $weight_total = 0;
                $all_passed = true;

                foreach ($assigned_exercises as $eid) {
                    $grades_for_ex = $student_grades_map[$stId][$eid] ?? [];
                    $is_exempt = in_array('zw', $grades_for_ex);

                    // Oblicz średnią ważoną (zawsze, niezależnie od zwolnienia)
                    $vals = [];
                    foreach ($grades_for_ex as $v_str) {
                        $v_clean = str_replace(',', '.', $v_str);
                        if (is_numeric($v_clean)) {
                            $v = floatval($v_clean);
                            if ($v > 0) $vals[] = $v;
                        }
                    }
                    $ex_weight = $weights[$eid] ?? 1;
                    if (count($vals) > 0) {
                        $ex_avg = array_sum($vals) / count($vals);
                        $weighted_sum += ($ex_avg * $ex_weight);
                        $weight_total += $ex_weight;
                    }

                    // Zwolnieni nie wpływają na is_complete
                    if ($is_exempt) {
                        continue;
                    }

                    // Oblicz wartości faktyczne
                    $has_positive_grade = false;
                    foreach ($grades_for_ex as $v_str) {
                        $v_clean = str_replace(',', '.', $v_str);
                        if (is_numeric($v_clean) && floatval($v_clean) >= 2.51) {
                            $has_positive_grade = true;
                            break;
                        }
                    }
                    $att_status = $att_map[$stId][$eid] ?? '';
                    $att_ok = in_array($att_status, ['obecny', 'spóźniony', 'odrobione']);
                    $rep_passed = $reportPassedMap[$stId][$eid] ?? false;

                    // Pobierz kryteria dla tego ćwiczenia
                    $crit = $requirements[$eid] ?? ['req_grade' => false, 'req_report' => false, 'req_attendance' => false];
                    $req_grade      = $crit['req_grade'];
                    $req_report     = $crit['req_report'];
                    $req_attendance = $crit['req_attendance'];
                    $has_any_criterion = $req_grade || $req_report || $req_attendance;

                    $exercise_passed = true;
                    if (!$has_any_criterion) {
                        // Brak kryteriów – zaliczone gdy ocena ≥ 2.51
                        if (!$has_positive_grade) $exercise_passed = false;
                    } else {
                        if ($req_grade      && !$has_positive_grade) $exercise_passed = false;
                        if ($req_report     && !$rep_passed)         $exercise_passed = false;
                        if ($req_attendance && !$att_ok)             $exercise_passed = false;
                    }

                    if (!$exercise_passed) {
                        $all_passed = false;
                    }
                }

                $final_avg = ($weight_total > 0) ? ($weighted_sum / $weight_total) : 0;
                $calculated_avgs[$stId] = [
                    'avg'         => $final_avg,
                    'is_complete' => $all_passed
                ];
            }
            $viewData['calculated_avgs'] = $calculated_avgs;
        }
    }
	
	if ($view_action === 'manage_applications') {
        global $applicationsFile;
        $viewData['tab'] = $_GET['tab'] ?? 'pending';
        
        $subMap = [];
        foreach ($subs as $s) {
             $p = explode(';', $s);
             $subMap[intval($p[0])] = $p[1];
        }
        $stMap = [];
        foreach ($allUsers as $uLine) {
            $p = explode(';', $uLine);
            if ($p[0] === 'student') {
                $stMap[intval($p[4])] = "{$p[1]} {$p[2]} ({$p[5]})";
            }
        }

        $allApps = read_lines($applicationsFile);
        $myApps = [];
        
        foreach ($allApps as $appLine) {
            $p = explode(';', $appLine);
            if (count($p) >= 10 && intval($p[2]) === intval($me['id'])) {
                $status = $p[7];
                // Filtrowanie po zakładkach
                if ($viewData['tab'] === 'pending' && $status === 'pending') {
                    $myApps[] = $p;
                } elseif ($viewData['tab'] === 'accepted' && $status === 'accepted') {
                    $myApps[] = $p;
                } elseif ($viewData['tab'] === 'rejected' && $status === 'rejected') {
                    $myApps[] = $p;
                }
            }
        }
        
        usort($myApps, function($a, $b) {
            return strtotime($b[6]) - strtotime($a[6]);
        });

        $viewData['applications_list'] = $myApps;
        $viewData['subjects_map'] = $subMap;
        $viewData['students_map'] = $stMap;
    }
	
	if ($view_action === 'edit_reports') {
        $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        if ($sel_sid > 0) {
            // Nazwa przedmiotu
            $subject_name = '';
            foreach ($subs as $s) { $p = explode(';', $s); if (intval($p[0]) === $sel_sid) { $subject_name = $p[1]; break; } }

            // Ćwiczenia przypisane do przedmiotu
            $assigned_eids = [];
            foreach (read_lines($subjectExerciseFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[0]) === $sel_sid) $assigned_eids[] = intval($p[1]);
            }
            $exercises = [];
            foreach (read_lines($exercisesFile) as $c) {
                $cp = explode(';', $c, 4);
                if (in_array(intval($cp[0]), $assigned_eids)) $exercises[] = $cp;
            }
            $exercise_names = [];
            foreach ($exercises as $ex) $exercise_names[intval($ex[0])] = $ex[1];

            // Studenci zapisani do przedmiotu
            $enrolled_ids = [];
            foreach (read_lines($enrollFile) as $l) {
                $p = explode(';', $l);
                if (intval($p[1]) === $sel_sid) $enrolled_ids[] = intval($p[0]);
            }
            $enrolled_students = [];
            $student_names = [];
            foreach ($students as $s) {
                $sid_s = intval($s[4]);
                if (in_array($sid_s, $enrolled_ids)) {
                    $enrolled_students[$sid_s] = "{$s[1]} {$s[2]} ({$s[5]})";
                    $student_names[$sid_s] = "{$s[1]} {$s[2]}";
                }
            }

            // Historia wszystkich sprawozdań z przedmiotu
            $all_reps_raw = read_lines($reportsFile);
            $reports_for_subject = [];
            foreach ($all_reps_raw as $r) {
                $p = explode(';', $r);
                if (count($p) >= 4 && intval($p[2]) === $sel_sid) {
                    $reports_for_subject[intval($p[0])] = ['rid' => intval($p[0]), 'stId' => intval($p[1]), 'exId' => intval($p[3])];
                }
            }

            // Historia statusów – czytamy z reportHistoryFile
            // Format linii: lineIndex;report_id;date;status;comment
            $history_all = [];
            $raw_history = read_lines($reportHistoryFile);
            foreach ($raw_history as $idx => $h_line) {
                $p = explode(';', $h_line, 5);
                if (count($p) < 4) continue;
                $rid = intval($p[1]);
                if (!isset($reports_for_subject[$rid])) continue;
                $history_all[$rid][] = [
    'hid'     => intval($p[0]),  // ← poprawnie: ID z pierwszej kolumny
    'date'    => $p[2],
    'status'  => trim($p[3]),
    'comment' => isset($p[4]) ? trim($p[4]) : ''
];
            }

            // Budujemy mapę [stId][exId] => info
            $reports_map = [];
            foreach ($reports_for_subject as $rid => $info) {
                $reports_map[$info['stId']][$info['exId']] = [
                    'rid'    => $rid,
                    'status' => isset($history_all[$rid][0]) ? $history_all[$rid][0]['status'] : 'pending'
                ];
            }

            $viewData['edit_reports_data'] = [
                'subject_name'      => $subject_name,
                'exercises'         => $exercises,
                'exercise_names'    => $exercise_names,
                'enrolled_students' => $enrolled_students,
                'student_names'     => $student_names,
                'reports'           => $reports_map,
                'history_all'       => $history_all,
            ];
        }
    }
}

    // ============================================================
    // 25. SZUKAJ STUDENTA
    // ============================================================
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher' && $view_action === 'szukaj_studenta') {
        $ss_q = trim($_GET['ss_q'] ?? '');
        $ss_preview_stid = isset($_GET['ss_stid']) ? intval($_GET['ss_stid']) : 0;
        $ss_preview_sid  = isset($_GET['ss_sid'])  ? intval($_GET['ss_sid'])  : 0;

        // Zbierz ID nauczycieli z tej samej uczelni
        $my_uczelnia_ss = trim($me['id_uczelni'] ?? '');
        $teacher_ids_same_uczelnia = [];
        $teacher_name_map = [];
        foreach (read_lines($usersFile) as $ul) {
            $up = explode(';', $ul);
            if ($up[0] === 'teacher') {
                $t_ucz = trim($up[6] ?? '');
                $tid   = intval($up[4]);
                $teacher_name_map[$tid] = "{$up[1]} {$up[2]}";
                if ($my_uczelnia_ss !== '' && $t_ucz === $my_uczelnia_ss) {
                    $teacher_ids_same_uczelnia[] = $tid;
                }
            }
        }

        // Zbierz przedmioty należące do tej samej uczelni (właściciel jest nauczycielem tej uczelni)
        $ss_subj_of_uczelnia = []; // sid => [id, name, rok, owner_id]
        foreach (read_lines($subjectsFile) as $sl) {
            $sp = explode(';', $sl);
            $s_owner = intval($sp[3] ?? 0);
            $s_status = trim($sp[4] ?? 'active');
            if ($s_status !== 'archived' && in_array($s_owner, $teacher_ids_same_uczelnia)) {
                $ss_subj_of_uczelnia[intval($sp[0])] = $sp;
            }
        }

        // Jeśli jest zapytanie szukania – znajdź studentów i ich przedmioty
        if ($ss_q !== '') {
            $ss_results = [];
            // Wczytaj enroll: student_id => [sid, ...]
            $enroll_by_student = [];
            foreach (read_lines($enrollFile) as $el) {
                $ep = explode(';', $el);
                $e_stid = intval($ep[0]);
                $e_sid  = intval($ep[1]);
                if (!isset($enroll_by_student[$e_stid])) $enroll_by_student[$e_stid] = [];
                $enroll_by_student[$e_stid][] = $e_sid;
            }

            foreach ($students as $stud) {
                $stid = intval($stud[4]);
                $full = $stud[1] . ' ' . $stud[2];
                if (stripos($full, $ss_q) !== false) {
                    $stud_sids = $enroll_by_student[$stid] ?? [];
                    $subj_for_stud = [];
                    foreach ($stud_sids as $sid_e) {
                        if (isset($ss_subj_of_uczelnia[$sid_e])) {
                            $sp = $ss_subj_of_uczelnia[$sid_e];
                            $owner_nm = $teacher_name_map[intval($sp[3])] ?? '?';
                            $subj_for_stud[] = ['sid' => $sid_e, 'name' => $sp[1], 'rok' => $sp[2], 'owner' => $owner_nm];
                        }
                    }
                    if (!empty($subj_for_stud)) {
                        $ss_results[$stid] = ['stud' => $stud, 'subjects' => $subj_for_stud];
                    }
                }
            }
            $viewData['szukaj_wyniki'] = $ss_results;
        }

        // Jeśli jest podgląd ocen studenta dla konkretnego przedmiotu
        if ($ss_preview_stid > 0 && $ss_preview_sid > 0) {
            // Znajdź dane studenta
            $pv_student = null;
            foreach ($students as $stud) {
                if (intval($stud[4]) === $ss_preview_stid) { $pv_student = $stud; break; }
            }

            // Znajdź dane przedmiotu
            $pv_subj_name = '';
            $pv_subj_rok  = '';
            foreach (read_lines($subjectsFile) as $sl) {
                $sp = explode(';', $sl);
                if (intval($sp[0]) === $ss_preview_sid) {
                    $pv_subj_name = $sp[1];
                    $pv_subj_rok  = $sp[2];
                    break;
                }
            }

            // Oceny studenta w tym przedmiocie
            $pv_grades = [];
            foreach (read_lines($gradesFile) as $gl) {
                $gp = explode(';', $gl, 9);
                if (count($gp) >= 8 && intval($gp[1]) === $ss_preview_stid && intval($gp[2]) === $ss_preview_sid) {
                    $pv_grades[] = $gl;
                }
            }

            // Ćwiczenia przypisane do przedmiotu – kolejność z subjectExerciseFile
            $pv_ex_defs_map = [];
            foreach (read_lines($exercisesFile) as $el) {
                $ep = explode(';', $el, 5);
                $pv_ex_defs_map[intval($ep[0])] = $ep;
            }
            $pv_subj_ex = [];
            foreach (read_lines($subjectExerciseFile) as $sl) {
                $sp = explode(';', $sl);
                if (intval($sp[0]) === $ss_preview_sid) {
                    $eid_pv = intval($sp[1]);
                    if (isset($pv_ex_defs_map[$eid_pv])) {
                        $pv_subj_ex[] = $pv_ex_defs_map[$eid_pv];
                    }
                }
            }

            // Obecność
            $pv_attendance = [];
            foreach (read_lines($exerciseAttendanceFile) as $al) {
                $ap = explode(';', $al);
                if (count($ap) >= 5 && intval($ap[1]) === $ss_preview_stid && intval($ap[2]) === $ss_preview_sid) {
                    $pv_attendance[intval($ap[3])] = trim($ap[4]);
                }
            }

            // Sprawozdania studenta w tym przedmiocie
            $pv_student_reports = [];
            foreach (read_lines($reportsFile) as $rl) {
                $rp = explode(';', $rl, 8);
                if (count($rp) >= 4 && intval($rp[1]) === $ss_preview_stid && intval($rp[2]) === $ss_preview_sid) {
                    $cwid_r = intval($rp[3]);
                    if (!isset($pv_student_reports[$cwid_r])) $pv_student_reports[$cwid_r] = [];
                    $pv_student_reports[$cwid_r][] = ['id' => $rp[0], 'path' => $rp[4] ?? '', 'comment' => $rp[5] ?? '', 'date' => $rp[6] ?? ''];
                }
            }

            // Historia statusów sprawozdań
            $pv_report_history = [];
            $pv_report_has_zal = [];
            foreach (read_lines($reportHistoryFile) as $hl) {
                $hp = explode(';', $hl, 5);
                if (count($hp) < 4) continue;
                $rid = intval($hp[1]);
                $status_h = $hp[3];
                $date_h   = $hp[2];
                $comment_h = str_replace(',', ';', $hp[4] ?? '');
                if ($status_h === 'zal') $pv_report_has_zal[$rid] = ['status' => 'zal', 'comment' => $comment_h, 'date' => $date_h];
                $cur = ['status' => $status_h, 'comment' => $comment_h, 'date' => $date_h];
                if (!isset($pv_report_history[$rid]) || $date_h > $pv_report_history[$rid]['date']) {
                    $pv_report_history[$rid] = $cur;
                }
            }
            foreach ($pv_report_has_zal as $rid => $zal_e) {
                if (isset($pv_report_history[$rid])) $pv_report_history[$rid]['is_passed'] = true;
            }

            // Mapa ExerciseID => latestStatus
            $pv_lsbe = [];
            foreach ($pv_student_reports as $cwid_r => $reps) {
                $latest_r = end($reps);
                $rid = intval($latest_r['id']);
                $st  = $pv_report_history[$rid]['status'] ?? 'do_spr';
                $com = $pv_report_history[$rid]['comment'] ?? '';
                $isp = $pv_report_history[$rid]['is_passed'] ?? ($st === 'zal');
                $pv_lsbe[$cwid_r] = ['report_id' => $rid, 'status' => $st, 'comment' => $com, 'is_passed' => $isp];
            }

            // Kryteria zaliczenia
            $pv_cwCriteria = [];
            foreach (read_lines($deadlinesFile) as $dl) {
                $dp = explode(';', $dl);
                if (count($dp) >= 3 && intval($dp[0]) === $ss_preview_sid) {
                    $eid_d = intval($dp[1]);
                    if (count($dp) >= 5) {
                        $pv_cwCriteria[$eid_d] = ['req_grade' => intval($dp[2]) === 1, 'req_report' => intval($dp[3]) === 1, 'req_attendance' => intval($dp[4]) === 1];
                    } else {
                        $pv_cwCriteria[$eid_d] = ['req_grade' => false, 'req_report' => intval($dp[2]) === 1, 'req_attendance' => false];
                    }
                }
            }

            // Wagi ćwiczeń
            $pv_weights = [];
            foreach (read_lines($exercisesFile) as $el) {
                $ep = explode(';', $el);
                $pv_weights[intval($ep[0])] = (count($ep) >= 4) ? floatval(str_replace(',', '.', $ep[3])) : 1.0;
            }

            $viewData['szukaj_preview'] = [
                'student'         => $pv_student,
                'subject_name'    => $pv_subj_name,
                'subject_rok'     => $pv_subj_rok,
                'grades'          => $pv_grades,
                'subject_exercises' => $pv_subj_ex,
                'attendance'      => $pv_attendance,
                'studentReports'  => $pv_student_reports,
                'latestStatusByExercise' => $pv_lsbe,
                'cwCriteria'      => $pv_cwCriteria,
                'weights'         => $pv_weights,
            ];
        }
    }

// ====== WYDRUK SPRAWOZDAŃ – ładowanie danych widoku ======
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher' && $view_action === 'wydruk_sprawozdan') {
    $wsp_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;

    if ($wsp_sid > 0 && has_subject_access($wsp_sid, intval($me['id']))) {
        // Nazwa przedmiotu
        $wsp_subject_name = '';
        foreach ($subs as $s) {
            $p = explode(';', $s, 4);
            if (intval($p[0]) === $wsp_sid) { $wsp_subject_name = $p[1]; break; }
        }

        // Nazwy ćwiczeń przypisanych do przedmiotu
        $wsp_assigned_eids = [];
        foreach (read_lines($subjectExerciseFile) as $l) {
            $p = explode(';', $l);
            if (intval($p[0]) === $wsp_sid) $wsp_assigned_eids[] = intval($p[1]);
        }
        $wsp_exercise_names = [];
        foreach (read_lines($exercisesFile) as $l) {
            $p = explode(';', $l, 3);
            if (in_array(intval($p[0]), $wsp_assigned_eids)) {
                $wsp_exercise_names[intval($p[0])] = $p[1];
            }
        }

        // Lista studentów zapisanych na przedmiot (id => "Imię Nazwisko (album)")
        $wsp_enrolled_ids = [];
        foreach (read_lines($enrollFile) as $l) {
            $p = explode(';', $l);
            if (intval($p[1]) === $wsp_sid) $wsp_enrolled_ids[] = intval($p[0]);
        }
        $wsp_enrolled_students = []; // stid => "Imię Nazwisko"
        $wsp_student_data = [];      // stid => ['name'=>..., 'album'=>...]
        foreach ($students as $stud) {
            $stid = intval($stud[4]);
            if (in_array($stid, $wsp_enrolled_ids)) {
                $name = $stud[1] . ' ' . $stud[2];
                $wsp_enrolled_students[$stid] = $name;
                $wsp_student_data[$stid] = ['name' => $name, 'album' => $stud[5] ?? ''];
            }
        }

        // Historia statusów sprawozdań – najnowszy wpis per report_id
        $wsp_history_latest = []; // rid => ['status', 'comment', 'date']
        $wsp_history_has_zal = []; // rid => true
        foreach (read_lines($reportHistoryFile) as $hl) {
            $hp = explode(';', $hl, 5);
            if (count($hp) < 4) continue;
            $rid_h = intval($hp[1]);
            $date_h = $hp[2];
            $status_h = trim($hp[3]);
            $comment_h = str_replace(',', ';', trim($hp[4] ?? ''));
            if ($status_h === 'zal') $wsp_history_has_zal[$rid_h] = true;
            if (!isset($wsp_history_latest[$rid_h]) || $date_h > $wsp_history_latest[$rid_h]['date']) {
                $wsp_history_latest[$rid_h] = ['status' => $status_h, 'comment' => $comment_h, 'date' => $date_h];
            }
        }

        // Sprawozdania przedmiotu – jeden wiersz per sprawozdanie (najnowsze zgłoszenie)
        // Format reportsFile: id;student_id;subject_id;exercise_id;path;comment;date[;...;teacher_id]
        // Bierzemy ostatnie (najnowsze) sprawozdanie per (student, ćwiczenie)
        $wsp_latest_per_pair = []; // "stid-eid" => report row array
        foreach (read_lines($reportsFile) as $rl) {
            $rp = explode(';', $rl);
            if (count($rp) < 7) continue;
            if (intval($rp[2]) !== $wsp_sid) continue;
            $stid_r = intval($rp[1]);
            $eid_r  = intval($rp[3]);
            $key = "{$stid_r}-{$eid_r}";
            $date_r = $rp[6] ?? '';
            // Zachowaj najpóźniejsze
            if (!isset($wsp_latest_per_pair[$key]) || $date_r > $wsp_latest_per_pair[$key][6]) {
                $wsp_latest_per_pair[$key] = $rp;
            }
        }

        // Zbuduj płaską listę wierszy do wyświetlenia
        $wsp_all_rows = [];
        foreach ($wsp_latest_per_pair as $key => $rp) {
            $rid  = intval($rp[0]);
            $stid = intval($rp[1]);
            $eid  = intval($rp[3]);
            $path = $rp[4] ?? '';
            $comment_r = $rp[5] ?? '';
            $date_r    = $rp[6] ?? '';

            $hist = $wsp_history_latest[$rid] ?? null;
            $current_status = $hist ? $hist['status'] : 'do_sprawdzenia';
            $hist_comment   = $hist ? $hist['comment'] : $comment_r;
            $is_zal = !empty($wsp_history_has_zal[$rid]);

            $st_data  = $wsp_student_data[$stid] ?? ['name' => "ID:$stid", 'album' => ''];
            $ex_name  = $wsp_exercise_names[$eid] ?? "ID:$eid";

            $wsp_all_rows[] = [
                'rid'           => $rid,
                'stid'          => $stid,
                'eid'           => $eid,
                'student_name'  => $st_data['name'],
                'album'         => $st_data['album'],
                'exercise_name' => $ex_name,
                'current_status'=> $current_status,
                'is_zal'        => $is_zal,
                'date'          => $date_r,
                'comment'       => $hist_comment,
                'path'          => $path,
            ];
        }

        // Sortuj: po nazwisku studenta, potem po ćwiczeniu
        usort($wsp_all_rows, function($a, $b) {
            $cmp = strcmp($a['student_name'], $b['student_name']);
            if ($cmp !== 0) return $cmp;
            return strcmp($a['exercise_name'], $b['exercise_name']);
        });

        $viewData['wydruk_sprawozdan'] = [
            'subject_name'      => $wsp_subject_name,
            'exercise_names'    => $wsp_exercise_names,
            'enrolled_students' => $wsp_enrolled_students,
            'all_rows'          => $wsp_all_rows,
        ];
    }
}


if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher' && $view_action === 'harmonogram') {
    $sel_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;

    if ($sel_sid > 0) {
        $subLine_h = null;
        foreach ($subs as $s_h) { $p_h = explode(';', $s_h, 4); if (intval($p_h[0]) === $sel_sid) { $subLine_h = $p_h; break; } }

        if ($subLine_h) {
            // Ćwiczenia przypisane do przedmiotu
            $assigned_eids_h = [];
            foreach (read_lines($subjectExerciseFile) as $l) { $p = explode(';', $l); if (intval($p[0]) === $sel_sid) $assigned_eids_h[] = intval($p[1]); }
            $exercises_h = [];
            foreach (read_lines($exercisesFile) as $l) {
                $p = explode(';', $l);
                if (in_array(intval($p[0]), $assigned_eids_h)) $exercises_h[] = $p;
            }

            // Sekcje – format: section_id;subject_id;name
            $sections_h = [];
            foreach (read_lines($definedSectionsFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 3 && intval($p[1]) === $sel_sid) {
                    $sections_h[] = ['id' => intval($p[0]), 'name' => trim($p[2])];
                }
            }

            // Załaduj zapisany harmonogram – format: id;subject_id;section_id;exercise_id;datetime
            $schedule_h = [];
            ensure_file($harmonogramFile);
            foreach (read_lines($harmonogramFile) as $l) {
                $p = explode(';', $l);
                if (count($p) >= 5 && intval($p[1]) === $sel_sid) {
                    $sec_id_h = intval($p[2]);
                    $ex_id_h  = intval($p[3]);
                    $dt_h     = trim($p[4]);
                    $schedule_h[$sec_id_h][$ex_id_h] = $dt_h;
                }
            }

            $viewData['harmonogram_data'] = [
                'subject_name' => $subLine_h[1],
                'exercises'    => $exercises_h,
                'sections'     => $sections_h,
                'schedule'     => $schedule_h,
            ];
        }
    }
}

if (isset($_SESSION['view_mode']) && $_SESSION['view_mode'] === 'compact') {
    include 'compact_view.php';
} else {
    include 'compact_view.php';
}
?>