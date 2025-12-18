<?php
/**
 * ------------------------------------------------------------------------
 *  Author : Ngonidzashe Jiji
 *  Handles: Instagram: @davidrichchild
 *           Telegram: t.me/david_richchild
 *           TikTok: davidrichchild
 *  URLs    : https://arrissadata.com
 *            https://arrissatechnologies.com
 *            https://arrissa.trade
 *
 *  Course  : https://www.udemy.com/course/6804721
 *
 *  Permission:
 *    You are granted permission to use, copy, modify, and distribute this
 *    software and its source code for personal or commercial projects,
 *    provided that the author details above remain intact and visible in
 *    the distributed software (including any compiled or minified form).
 *
 *  Requirements:
 *    - Keep the author name, handles, URLs, and course link in this header
 *      (or an equivalent attribution location in distributed builds).
 *    - You may NOT remove or obscure the attribution.
 *
 *  Disclaimer:
 *    This software is provided "AS IS", without warranty of any kind,
 *    express or implied. The author is not liable for any claim, damages,
 *    or other liability arising from the use of this software.
 *
 *  Version: 1.0
 *  Date:    2025-09-20
 * ------------------------------------------------------------------------
 */
// event-id-reference.php

// Load shared DB config
require_once '../dbconfig.php';

// The $pdo connection is already created in dbconfig.php, so we can use it directly

// Fetch all unique consistent_event_ids with a representative event_name and affected currencies
$sql = "
    SELECT
      e.consistent_event_id,
      MIN(e.event_name) AS event_name,
      GROUP_CONCAT(DISTINCT e.currency ORDER BY e.currency ASC SEPARATOR ', ') AS currencies
    FROM economic_events AS e
    GROUP BY e.consistent_event_id
    ORDER BY event_name ASC
";
$stmt   = $pdo->query($sql);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Available Events</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  
</head>
<body>
<div class="container py-5">
  <p><a href="index.php" class="btn btn-outline-light mb-4">← Back to Home</a></p>
  <h2 class="mb-4">📌 All Available Economic Events</h2>

  <div class="mb-3 search-box">
    <input type="text" class="form-control" id="searchInput" placeholder="Search by event name or ID...">
  </div>

  <!-- Copy All IDs Button -->
  <button id="copyAllBtn" class="btn btn-outline-light mb-3">Copy All IDs</button>

  <div class="table-responsive table-rounded">
    <table class="table table-dark table-bordered table-hover" id="eventsTable">
      <thead>
        <tr>
          <th>Event Name</th>
          <th>Consistent Event ID</th>
          <th>Currencies</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $event): ?>
          <tr>
            <td><?= htmlspecialchars($event['event_name']) ?></td>
            <td>
              <?= htmlspecialchars($event['consistent_event_id']) ?>
              <span
                class="material-icons copy-icon"
                data-id="<?= htmlspecialchars($event['consistent_event_id']) ?>"
                title="Copy ID"
              >content_copy</span>
            </td>
            <td class="small-text"><?= htmlspecialchars($event['currencies']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  // Search filter
  const input = document.getElementById("searchInput");
  const rows  = document.querySelectorAll("#eventsTable tbody tr");
  input.addEventListener("keyup", function () {
    const term = this.value.toLowerCase();
    rows.forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
    });
  });

  // Copy individual ID with animation
  document.querySelectorAll('.copy-icon').forEach(icon => {
    icon.addEventListener('click', () => {
      const text = icon.dataset.id;
      navigator.clipboard.writeText(text).then(() => {
        icon.classList.add('copied');
        setTimeout(() => icon.classList.remove('copied'), 300);
      });
    });
  });

  // Copy all filtered IDs
  document.getElementById('copyAllBtn').addEventListener('click', () => {
    const visibleIcons = Array.from(document.querySelectorAll('#eventsTable tbody tr'))
      .filter(row => row.style.display !== 'none')
      .map(row => row.querySelector('.copy-icon').dataset.id);

    if (visibleIcons.length) {
      const allIds = visibleIcons.join(',');
      navigator.clipboard.writeText(allIds);
      // Optional: brief button feedback (no popup)
      const btn = document.getElementById('copyAllBtn');
      const originalText = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(() => btn.textContent = originalText, 800);
    }
  });
</script>
</body>
</html>