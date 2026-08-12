<?php
/**
 * EduGrant — Deadline Notification Generator
 *
 * Controlled, idempotent process that creates deadline reminders for
 * students based on their SAVED scholarships.
 *
 * Deduplication strategy: before inserting, check whether a notification
 * with the same user + title already exists, so the same event is never
 * duplicated even if this runs on every page load.
 */

require_once __DIR__ . '/functions.php';

function generateDeadlineNotifications(): int
{
    $pdo = db();
    $created = 0;

    $rows = $pdo->query(
        'SELECT ss.student_id, sp.user_id, s.id AS scholarship_id, s.title, s.deadline
         FROM saved_scholarships ss
         JOIN student_profiles sp ON sp.id = ss.student_id
         JOIN scholarships s ON s.id = ss.scholarship_id
         WHERE s.status = "approved"
           AND s.deadline IS NOT NULL
           AND s.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)'
    )->fetchAll();

    foreach ($rows as $row) {
        $days = (int)round((strtotime($row['deadline']) - strtotime(date('Y-m-d'))) / 86400);

        if ($days === 1) {
            $title = 'Your scholarship deadline is tomorrow';
            $message = 'The deadline for "' . $row['title'] . '" is tomorrow (' . formatDate($row['deadline']) . '). Make sure your requirements are ready.';
        } elseif ($days === 3) {
            $title = 'Your scholarship deadline is in 3 days';
            $message = 'The deadline for "' . $row['title'] . '" is in 3 days. Finalize your documents now.';
        } elseif ($days <= 7) {
            $title = 'Your scholarship deadline is approaching';
            $message = 'The deadline for "' . $row['title'] . '" is in ' . $days . ' days (' . formatDate($row['deadline']) . ').';
        } elseif ($days <= 14) {
            $title = 'Your saved scholarship deadline is approaching';
            $message = 'The deadline for "' . $row['title'] . '" is approaching (' . formatDate($row['deadline']) . ', ' . $days . ' days away).';
        } else {
            continue;
        }

        // Deduplication check (same user + same title)
        $check = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND title = ?');
        $check->execute([$row['user_id'], $title]);
        if ((int)$check->fetchColumn() === 0) {
            $ins = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, "deadline")');
            $ins->execute([$row['user_id'], $title, $message]);
            $created++;
        }
    }

    return $created;
}
