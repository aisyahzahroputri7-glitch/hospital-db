<?php $base = isset($base) ? $base : '..'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Hospital System') ?> — Sehat Bersama Hospital</title>
<link rel="stylesheet" href="<?= $base ?>/assets/style.css">
</head>
<body>
<div class="sidebar">
  <div class="sidebar-brand">
    <h1>Sehat Bersama Hospital</h1>
    <span>Hospital Information System</span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main Menu</div>
    <a href="<?= $base ?>/index.php" class="nav-link <?= ($activePage==='dashboard')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="9" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="1" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
      Dashboard
    </a>

    <div class="nav-section">Master Data</div>
    <a href="<?= $base ?>/patients/index.php" class="nav-link <?= ($activePage==='patients')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.2"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Patients
    </a>
    <a href="<?= $base ?>/doctors/index.php" class="nav-link <?= ($activePage==='doctors')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="4" r="2.5" stroke="currentColor" stroke-width="1.2"/><path d="M3 13c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M11 8v4M9 10h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Doctors
    </a>
    <a href="<?= $base ?>/rooms/index.php" class="nav-link <?= ($activePage==='rooms')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="2" y="4" width="12" height="10" rx="1" stroke="currentColor" stroke-width="1.2"/><path d="M6 14V9h4v5" stroke="currentColor" stroke-width="1.2"/><path d="M2 4l6-3 6 3" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
      Rooms
    </a>

    <div class="nav-section">Transactions</div>
    <a href="<?= $base ?>/examination_schedules/index.php" class="nav-link <?= ($activePage==='schedules')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="2" y="3" width="12" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 1v4M11 1v4M2 7h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Exam Schedules
    </a>
    <a href="<?= $base ?>/medical_records/index.php" class="nav-link <?= ($activePage==='medical_records')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="3" y="1" width="10" height="14" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M6 5h4M6 8h4M6 11h2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Medical Records
    </a>

    <div class="nav-section">Management</div>
    <a href="<?= $base ?>/insurance/index.php" class="nav-link <?= ($activePage==='insurance')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="2" y="3" width="12" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M8 6v4M6 8h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Insurance
    </a>
    <a href="<?= $base ?>/medicines/index.php" class="nav-link <?= ($activePage==='medicines')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="3" y="2" width="10" height="13" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M6 6h4M6 9h4M6 12h2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
      Medicines
    </a>
    <a href="<?= $base ?>/payments/index.php" class="nav-link <?= ($activePage==='payments')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="4" width="14" height="10" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M1 7h14" stroke="currentColor" stroke-width="1.2"/><circle cx="4" cy="10" r="1" fill="currentColor"/></svg>
      Payments
    </a>

    <div class="nav-section">Analytics</div>
    <a href="<?= $base ?>/reports.php" class="nav-link <?= ($activePage==='reports')?'active':'' ?>">
      <span class="nav-dot"></span>
      <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="8" width="3" height="7" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="6" y="5" width="3" height="10" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="11" y="2" width="3" height="13" rx="1" stroke="currentColor" stroke-width="1.2"/></svg>
      Reports
    </a>

  </nav>
  <div class="sidebar-footer">&copy; <?= date('Y') ?> Sehat Bersama Hospital</div>
</div>
<div class="main">