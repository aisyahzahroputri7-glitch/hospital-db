<?php
require_once '../config.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) { flash('Invalid ID.', 'danger'); redirect('index.php'); }

$patient = $conn->query("SELECT * FROM patients WHERE patient_id=$id")->fetch_assoc();
if (!$patient) { flash('Patient not found.', 'danger'); redirect('index.php'); }

$pageTitle = 'Patient Detail'; $activePage = 'patients'; $base = '..';

$medicalRecords = $conn->query("
    SELECT mr.*, d.doctor_name, d.specialization
    FROM medical_records mr
    JOIN doctors d ON mr.doctor_id = d.doctor_id
    WHERE mr.patient_id = $id
    ORDER BY mr.visit_date DESC
")->fetch_all(MYSQLI_ASSOC);

$schedules = $conn->query("
    SELECT es.*, d.doctor_name, d.specialization, r.room_name
    FROM examination_schedules es
    JOIN doctors d ON es.doctor_id = d.doctor_id
    JOIN rooms r ON es.room_id = r.room_id
    WHERE es.patient_id = $id
    ORDER BY es.examination_time DESC
")->fetch_all(MYSQLI_ASSOC);

$totalVisits    = count($medicalRecords);
$totalSchedules = count($schedules);
$lastVisit = $totalVisits > 0 ? date('d M Y', strtotime($medicalRecords[0]['visit_date'])) : 'No visits yet';
$age = date_diff(date_create($patient['date_of_birth']), date_create('today'))->y;

function statusBadge($s) {
    $m = ['Waiting'=>'badge-amber','Completed'=>'badge-teal','Cancelled'=>'badge-red'];
    return '<span class="badge '.($m[$s]??'badge-gray').'">'.htmlspecialchars($s).'</span>';
}

include '../assets/sidebar.php';
?>

<style>
.detail-header { background: var(--card); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
.detail-avatar { width: 72px; height: 72px; border-radius: 50%; background: #E1F5EE; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 600; color: #0F6E56; flex-shrink: 0; }
.detail-name { font-size: 20px; font-weight: 600; color: var(--text); }
.detail-sub  { font-size: 13px; color: var(--text-2); margin-top: 4px; }
.detail-meta { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
.detail-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }
.info-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; margin-bottom: 20px; }
.info-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 18px 20px; }
.info-card-title { font-size: 12px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 14px; }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--text-3); }
.info-value { font-weight: 500; color: var(--text); text-align: right; }
.section-title { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.section-title span { font-size: 11px; font-weight: 400; color: var(--text-3); }
.rec-timeline { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.rec-item { background: var(--card); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 16px 18px; display: flex; gap: 16px; transition: border-color .15s; }
.rec-item:hover { border-color: var(--border-2); }
.rec-date-col { text-align: center; flex-shrink: 0; width: 60px; }
.rec-day  { font-size: 22px; font-weight: 600; color: var(--text); line-height: 1; }
.rec-month { font-size: 11px; color: var(--text-3); margin-top: 2px; }
.rec-year  { font-size: 10px; color: var(--text-3); }
.rec-divider { width: 1px; background: var(--border); flex-shrink: 0; }
.rec-body { flex: 1; min-width: 0; }
.rec-diag { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
.rec-doctor { font-size: 12px; color: var(--text-3); margin-bottom: 8px; }
.rec-detail-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.rec-field-lbl { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
.rec-field-val { font-size: 12px; color: var(--text); }
.empty-state { text-align: center; padding: 40px; color: var(--text-3); background: var(--card); border: 1px solid var(--border); border-radius: var(--r-lg); }
.empty-state p { font-size: 13px; margin-top: 8px; }
</style>

  <div class="topbar">
    <div class="topbar-left"><h2>Patient Detail</h2><p>Complete patient information and history</p></div>
    <div style="display:flex;gap:8px;">
      <a href="edit.php?id=<?=$id?>" class="btn btn-ghost">Edit Patient</a>
      <a href="index.php" class="btn btn-ghost">
        <svg viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back
      </a>
    </div>
  </div>

  <div class="content">

    <div class="detail-header">
      <div class="detail-avatar"><?= strtoupper(substr($patient['patient_name'],0,1)) ?></div>
      <div>
        <div class="detail-name"><?= htmlspecialchars($patient['patient_name']) ?></div>
        <div class="detail-sub">Patient ID #<?=$id?> &nbsp;·&nbsp; <?=$age?> years old &nbsp;·&nbsp; <?=htmlspecialchars($patient['gender'])?></div>
        <div class="detail-meta">
          <span class="badge badge-gray">Blood Type <?=htmlspecialchars($patient['blood_type']??'-')?></span>
          <span class="badge <?=$patient['gender']==='Male'?'badge-blue':'badge-purple'?>"><?=htmlspecialchars($patient['gender'])?></span>
          <span class="badge badge-teal"><?=$totalVisits?> Visit<?=$totalVisits!=1?'s':''?></span>
        </div>
      </div>
      <div class="detail-actions">
        <a href="../examination_schedules/create.php" class="btn btn-accent btn-sm">+ New Schedule</a>
        <a href="../medical_records/create.php" class="btn btn-ghost btn-sm">+ Add Record</a>
        <a href="../medical_records/print.php?patient=<?=$id?>" target="_blank" class="btn btn-ghost btn-sm" style="color:var(--blue);">Print All Records</a>
      </div>
    </div>

    <div class="info-grid">
      <div class="info-card">
        <div class="info-card-title">Personal Information</div>
        <div class="info-row"><span class="info-label">Full Name</span><span class="info-value"><?=htmlspecialchars($patient['patient_name'])?></span></div>
        <div class="info-row"><span class="info-label">Date of Birth</span><span class="info-value"><?=date('d F Y',strtotime($patient['date_of_birth']))?></span></div>
        <div class="info-row"><span class="info-label">Age</span><span class="info-value"><?=$age?> years old</span></div>
        <div class="info-row"><span class="info-label">Gender</span><span class="info-value"><?=htmlspecialchars($patient['gender'])?></span></div>
        <div class="info-row"><span class="info-label">Blood Type</span><span class="info-value"><?=htmlspecialchars($patient['blood_type']??'-')?></span></div>
        <div class="info-row"><span class="info-label">Phone</span><span class="info-value"><?=htmlspecialchars($patient['phone_number']??'-')?></span></div>
        <div class="info-row"><span class="info-label">Address</span><span class="info-value" style="max-width:200px;"><?=htmlspecialchars($patient['address']??'-')?></span></div>
      </div>
      <div class="info-card">
        <div class="info-card-title">Medical Summary</div>
        <div class="info-row"><span class="info-label">Total Visits</span><span class="info-value"><?=$totalVisits?></span></div>
        <div class="info-row"><span class="info-label">Total Schedules</span><span class="info-value"><?=$totalSchedules?></span></div>
        <div class="info-row"><span class="info-label">Last Visit</span><span class="info-value"><?=$lastVisit?></span></div>
        <div class="info-row"><span class="info-label">Registered</span><span class="info-value"><?=date('d M Y',strtotime($patient['created_at']))?></span></div>
        <?php if($totalVisits>0): ?>
        <div class="info-row"><span class="info-label">Last Diagnosis</span><span class="info-value"><?=htmlspecialchars($medicalRecords[0]['diagnosis']??'-')?></span></div>
        <div class="info-row"><span class="info-label">Last Doctor</span><span class="info-value"><?=htmlspecialchars($medicalRecords[0]['doctor_name'])?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="section-title">Medical Records History <span><?=$totalVisits?> record<?=$totalVisits!=1?'s':''?></span></div>

    <?php if(empty($medicalRecords)): ?>
    <div class="empty-state" style="margin-bottom:20px;">
      <p>No medical records found for this patient.</p>
      <a href="../medical_records/create.php" style="font-size:12px;color:var(--blue);text-decoration:none;">Add first record →</a>
    </div>
    <?php else: ?>
    <div class="rec-timeline">
      <?php foreach($medicalRecords as $r): ?>
      <div class="rec-item">
        <div class="rec-date-col">
          <div class="rec-day"><?=date('d',strtotime($r['visit_date']))?></div>
          <div class="rec-month"><?=date('M',strtotime($r['visit_date']))?></div>
          <div class="rec-year"><?=date('Y',strtotime($r['visit_date']))?></div>
        </div>
        <div class="rec-divider"></div>
        <div class="rec-body">
          <div class="rec-diag"><?=htmlspecialchars($r['diagnosis']??'No diagnosis')?></div>
          <div class="rec-doctor"><?=htmlspecialchars($r['doctor_name'])?> · <?=htmlspecialchars($r['specialization'])?></div>
          <div class="rec-detail-row">
            <div><div class="rec-field-lbl">Complaints</div><div class="rec-field-val"><?=htmlspecialchars($r['complaints'])?></div></div>
            <div><div class="rec-field-lbl">Prescription</div><div class="rec-field-val"><?=htmlspecialchars($r['prescription']??'-')?></div></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="section-title">Examination Schedules <span><?=$totalSchedules?> schedule<?=$totalSchedules!=1?'s':''?></span></div>

    <?php if(empty($schedules)): ?>
    <div class="empty-state">
      <p>No schedules found for this patient.</p>
      <a href="../examination_schedules/create.php" style="font-size:12px;color:var(--blue);text-decoration:none;">Create schedule →</a>
    </div>
    <?php else: ?>
    <div class="table-card">
      <table class="tbl">
        <thead><tr><th>Doctor</th><th>Room</th><th>Exam Time</th><th>Status</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach($schedules as $s): ?>
          <tr>
            <td><div style="font-size:13px;font-weight:500;"><?=htmlspecialchars($s['doctor_name'])?></div><div class="tbl-sub"><?=htmlspecialchars($s['specialization'])?></div></td>
            <td style="font-size:12px;"><?=htmlspecialchars($s['room_name'])?></td>
            <td><div style="font-size:12px;font-weight:500;"><?=date('d M Y',strtotime($s['examination_time']))?></div><div class="tbl-sub"><?=date('H:i',strtotime($s['examination_time']))?> WIB</div></td>
            <td><?=statusBadge($s['schedule_status'])?></td>
            <td style="font-size:12px;color:var(--text-2);"><?=htmlspecialchars($s['notes']??'-')?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>

<?php include '../assets/footer.php'; ?>