<?php
require_once '../config.php';
$id = intval($_GET['id'] ?? 0);
$patient_id = intval($_GET['patient'] ?? 0);

// Print single record
if ($id) {
    $records = $conn->query("
        SELECT mr.*, p.patient_name, p.date_of_birth, p.gender, p.blood_type, p.address, p.phone_number,
               d.doctor_name, d.specialization
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE mr.medical_record_id = $id
    ")->fetch_all(MYSQLI_ASSOC);
}
// Print all records of one patient
elseif ($patient_id) {
    $records = $conn->query("
        SELECT mr.*, p.patient_name, p.date_of_birth, p.gender, p.blood_type, p.address, p.phone_number,
               d.doctor_name, d.specialization
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE mr.patient_id = $patient_id
        ORDER BY mr.visit_date DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

if (empty($records)) { die('No records found.'); }
$patient = $records[0];
$age = date_diff(date_create($patient['date_of_birth']), date_create('today'))->y;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Record — <?= htmlspecialchars($patient['patient_name']) ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a18; background: #fff; padding: 40px; }

  .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid #1a1a18; margin-bottom: 24px; }
  .hospital-name { font-size: 20px; font-weight: 700; color: #1a1a18; }
  .hospital-sub  { font-size: 12px; color: #888; margin-top: 3px; }
  .doc-title { text-align: right; }
  .doc-title h2 { font-size: 16px; font-weight: 600; color: #185FA5; }
  .doc-title p  { font-size: 11px; color: #888; margin-top: 3px; }

  .patient-box { background: #f5f4f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .patient-field label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 3px; }
  .patient-field span  { font-size: 13px; font-weight: 500; color: #1a1a18; }

  .record { border: 1px solid #e0dfda; border-radius: 8px; margin-bottom: 16px; overflow: hidden; }
  .record-header { background: #185FA5; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; }
  .record-header h3 { font-size: 14px; font-weight: 600; }
  .record-header span { font-size: 12px; opacity: .8; }
  .record-body { padding: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .record-field label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 4px; }
  .record-field p { font-size: 13px; color: #1a1a18; line-height: 1.5; }
  .record-field.full { grid-column: 1 / -1; }
  .record-doctor { background: #f5f4f0; padding: 10px 16px; font-size: 12px; color: #5F5E5A; border-top: 1px solid #e0dfda; }

  .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e0dfda; display: flex; justify-content: space-between; font-size: 11px; color: #888; }
  .signature-box { text-align: center; }
  .signature-box .sig-line { width: 160px; border-bottom: 1px solid #1a1a18; margin: 40px auto 6px; }
  .signature-box p { font-size: 12px; color: #1a1a18; }

  @media print {
    body { padding: 20px; }
    .no-print { display: none !important; }
    .record { break-inside: avoid; }
  }
</style>
</head>
<body>

<!-- Print Button (hidden on print) -->
<div class="no-print" style="margin-bottom:24px;display:flex;gap:10px;">
  <button onclick="window.print()" style="padding:10px 24px;background:#185FA5;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;">
    Print / Save as PDF
  </button>
  <button onclick="window.close()" style="padding:10px 16px;background:#f5f4f0;color:#1a1a18;border:1px solid #d0cfc8;border-radius:8px;font-size:13px;cursor:pointer;">
    Close
  </button>
</div>

<!-- Hospital Header -->
<div class="header">
  <div>
    <div class="hospital-name">Sehat Bersama Hospital</div>
    <div class="hospital-sub">Hospital Information System</div>
    <div class="hospital-sub">Printed: <?= date('d F Y, H:i') ?></div>
  </div>
  <div class="doc-title">
    <h2>Medical Record Report</h2>
    <p><?= $id ? 'Single Visit Record' : 'Complete Visit History' ?></p>
  </div>
</div>

<!-- Patient Info -->
<div class="patient-box">
  <div class="patient-field"><label>Patient Name</label><span><?= htmlspecialchars($patient['patient_name']) ?></span></div>
  <div class="patient-field"><label>Date of Birth</label><span><?= date('d F Y', strtotime($patient['date_of_birth'])) ?></span></div>
  <div class="patient-field"><label>Age</label><span><?= $age ?> years old</span></div>
  <div class="patient-field"><label>Gender</label><span><?= htmlspecialchars($patient['gender']) ?></span></div>
  <div class="patient-field"><label>Blood Type</label><span><?= htmlspecialchars($patient['blood_type'] ?? '-') ?></span></div>
  <div class="patient-field"><label>Phone</label><span><?= htmlspecialchars($patient['phone_number'] ?? '-') ?></span></div>
  <div class="patient-field" style="grid-column:1/-1"><label>Address</label><span><?= htmlspecialchars($patient['address'] ?? '-') ?></span></div>
</div>

<!-- Medical Records -->
<?php foreach ($records as $i => $r): ?>
<div class="record">
  <div class="record-header">
    <h3><?= htmlspecialchars($r['diagnosis'] ?? 'No Diagnosis') ?></h3>
    <span><?= date('d F Y', strtotime($r['visit_date'])) ?></span>
  </div>
  <div class="record-body">
    <div class="record-field full">
      <label>Complaints</label>
      <p><?= htmlspecialchars($r['complaints']) ?></p>
    </div>
    <div class="record-field">
      <label>Diagnosis</label>
      <p><?= htmlspecialchars($r['diagnosis'] ?? '-') ?></p>
    </div>
    <div class="record-field">
      <label>Prescription</label>
      <p><?= htmlspecialchars($r['prescription'] ?? '-') ?></p>
    </div>
  </div>
  <div class="record-doctor">
    Handled by: <strong><?= htmlspecialchars($r['doctor_name']) ?></strong> — <?= htmlspecialchars($r['specialization']) ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Footer & Signature -->
<div class="footer">
  <div>
    <p>Total records: <?= count($records) ?></p>
    <p style="margin-top:4px;">Document generated by Hospital Information System</p>
  </div>
  <div class="signature-box">
    <div class="sig-line"></div>
    <p>Doctor's Signature</p>
  </div>
</div>

</body>
</html>