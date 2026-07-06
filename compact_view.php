<?php
// Panel Prowadzącego (Widok Kompaktowy)
require_once __DIR__ . '/header.php';
?>
 
<?php if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher'): ?>
    <?php require __DIR__ . '/panel.php'; ?>
<?php else: ?>
 
<div class="compact-layout">
 
    <?php require __DIR__ . '/sidebar.php'; ?>
 
        <?php require __DIR__ . '/admin.php'; ?>
 
        <?php require __DIR__ . '/groups.php'; ?>
 
        <?php require __DIR__ . '/exercises.php'; ?>
 
        <?php require __DIR__ . '/grades.php'; ?>
 
        <?php require __DIR__ . '/reports.php'; ?>
 
        <?php require __DIR__ . '/attendance.php'; ?>
 
        <?php require __DIR__ . '/stats.php'; ?>
 
        <?php require __DIR__ . '/access.php'; ?>
 
        <?php require __DIR__ . '/export.php'; ?>
 
        <?php require __DIR__ . '/extras.php'; ?>
 
    </div>
</div>
 
<?php endif; ?>
 
<?php require __DIR__ . '/footer.php'; ?>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
