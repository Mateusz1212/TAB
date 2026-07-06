<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Panel Prowadzącego (Widok Kompaktowy)</title>
    <link rel="stylesheet" href="compact_style.css">
    <style>
        .th-tooltip { position: relative; cursor: help; }
        .th-tooltip .tooltip-content {
            display: none; position: absolute; bottom: 100%; left: 50%;
            transform: translateX(-50%); background: #333; color: #fff;
            padding: 8px; border-radius: 4px; white-space: nowrap;
            z-index: 100; font-size: 12px; font-weight: normal; min-width: 200px; white-space: normal;
        }
        .th-tooltip:hover .tooltip-content { display: block; }
        .draggable-row.over { border: 2px dashed #000; background-color: #f0f0f0; }
        .draggable-row.drag-active { opacity: 0.4; }
        .grade-cell { cursor: pointer; min-width: 40px; transition: background 0.2s; }
        .grade-cell:hover { background-color: #e0e0e0; }
        #gradeEditOverlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.35); z-index:9000; }
        #gradeEditModal { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border:2px solid #555; padding:16px; z-index:9001; min-width:340px; max-width:95vw; max-height:90vh; overflow-y:auto; box-shadow:0 4px 24px rgba(0,0,0,0.25); }
        .grades-table-wrap { overflow-x:auto; }
        .grades-table { border-collapse:collapse; }
        .grades-table .col-sticky-student { position:sticky; left:0; z-index:2; background:#fff; }
        .grades-table .col-sticky-add { position:sticky; left:0; z-index:2; background:#fff; }
        .grades-table thead .col-sticky-student,
        .grades-table thead .col-sticky-add { z-index:3; background:#ddd; }
        .grades-table tr.n0 .col-sticky-student,
        .grades-table tr.n0 .col-sticky-add { background:#f9f9f9; }
        .grades-table tr.n1 .col-sticky-student,
        .grades-table tr.n1 .col-sticky-add { background:#fff; }
        .btn-sm { padding: 2px 8px; font-size: 13px; cursor: pointer; background: #eee; border: 1px solid #ccc; }
        .progress-bar-outer { background:#ddd; width:100%; height:16px; border:1px solid #ccc; }
        .progress-bar-inner { height:100%; text-align:center; color:#fff; font-size:11px; line-height:16px; text-shadow:1px 1px 1px #000; }
        .pagination { margin: 10px 0; }
        .pagination a { display:inline-block; margin:1px; padding:3px 7px; border:1px solid #999; text-decoration:none; color:#333; }
        .pagination a.active { background:#ABABAB; font-weight:bold; }
        .pagination a.disabled { color:#ccc; pointer-events:none; }
    </style>
    <script>
        function toggleSection(id) {
            var el = document.getElementById(id);
            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }
        function toggleDesc(id) {
            var shortSpan = document.getElementById('desc_short_' + id);
            var fullSpan = document.getElementById('desc_full_' + id);
            if (shortSpan.style.display === 'none') {
                shortSpan.style.display = 'inline';
                fullSpan.style.display = 'none';
            } else {
                shortSpan.style.display = 'none';
                fullSpan.style.display = 'inline';
            }
        }
        function toggleAllStudents(source) {
            var checkboxes = document.getElementsByClassName('st-chk');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function toggleAll(source, className) {
            var checkboxes = document.getElementsByClassName(className);
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function closeSection(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        }
        function openGradeEditModal(cellId) {
            var src = document.getElementById(cellId);
            if (!src) return;
            var modal = document.getElementById('gradeEditModal');
            modal.innerHTML = src.innerHTML;
            document.getElementById('gradeEditOverlay').style.display = 'block';
            modal.style.display = 'block';
            var closeBtn = modal.querySelector('.grade-modal-close');
            if (closeBtn) closeBtn.onclick = closeGradeEditModal;
        }
        // Sticky columns – ustaw left dla kolumny "Dodaj" na podstawie szerokości kolumny "Student"
        document.addEventListener('DOMContentLoaded', function() {
            var table = document.querySelector('.grades-table');
            if (!table) return;
            var studentTh = table.querySelector('thead .col-sticky-student');
            if (!studentTh) return;
            var w = studentTh.offsetWidth;
            var style = document.createElement('style');
            style.textContent = '.grades-table .col-sticky-add { left: ' + w + 'px; }';
            document.head.appendChild(style);

            // Upewnij się że modal jest ukryty przy starcie strony
            var o = document.getElementById('gradeEditOverlay');
            var m = document.getElementById('gradeEditModal');
            if (o) o.style.display = 'none';
            if (m) { m.style.display = 'none'; m.innerHTML = ''; }
        });
        function closeGradeEditModal() {
            document.getElementById('gradeEditOverlay').style.display = 'none';
            document.getElementById('gradeEditModal').style.display = 'none';
        }
        function openRolloverForm(sid) {
            var el = document.getElementById('rolloverForm_' + sid);
            if (el) {
                el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
            }
        }

        /* Obsługa okna dodawania/edycji ocen */
        function openAddGradeInline(stId) {
            toggleSection('addGradeInline_' + stId);
        }
        function openEditGradeInline(stId, exId) {
            toggleSection('editGradeInline_' + stId + '_' + exId);
        }
    </script>
</head>
<body>
