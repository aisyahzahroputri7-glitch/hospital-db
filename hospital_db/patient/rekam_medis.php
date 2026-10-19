<?php
require_once '../config.php';
require_once '../auth.php';
requirePatient();
$patientId=$_SESSION['patient_id']??0;
if(!$patientId){ ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Medical Records</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0efeb;display:flex;align-items:center;justify-content:center;min-height:100vh;}</style>
</head><body><div style="background:#fff;padding:32px;border-radius:12px;text-align:center;"><h3>Account not linked to patient record</h3><p style="margin:12px 0;color:#888;font-size:13px;">Please update your phone number in profile settings.</p><a href="profile.php" style="color:#185FA5;">Go to Profile</a></div></body></html>
<?php exit; }

$records=$conn->query("
    SELECT mr.*,d.doctor_name,d.specialization,
    (SELECT GROUP_CONCAT(m.medicine_name,' x',pd.quantity SEPARATOR ', ') FROM prescription_details pd JOIN medicines m ON pd.medicine_id=m.medicine_id WHERE pd.medical_record_id=mr.medical_record_id) as medicines
    FROM medical_records mr
    JOIN doctors d ON mr.doctor_id=d.doctor_id
    WHERE mr.patient_id=$patientId
    ORDER BY mr.visit_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical Records — Sehat Bersama Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0efeb;color:#1a1a18;font-size:14px;}
.topbar{background:#185FA5;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;}
.topbar-brand{display:flex;align-items:center;gap:10px;color:#fff;}
.topbar-brand .icon{width:32px;height:32px;background:rgba(255,255,255,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.topbar-brand h1{font-size:15px;font-weight:600;}
.topbar-nav{display:flex;align-items:center;gap:8px;}
.nav-link{padding:7px 14px;border-radius:8px;color:rgba(255,255,255,0.7);font-size:13px;text-decoration:none;transition:.2s;}
.nav-link:hover,.nav-link.active{background:rgba(255,255,255,0.15);color:#fff;}
.topbar-user{display:flex;align-items:center;gap:10px;color:#fff;}
.logout-btn{padding:6px 14px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:#fff;font-size:12px;text-decoration:none;}
.content{padding:28px;max-width:900px;margin:0 auto;}
.page-title{font-size:20px;font-weight:700;margin-bottom:4px;}
.page-sub{font-size:13px;color:#888;margin-bottom:24px;}
.rec-card{background:#fff;border-radius:12px;border:1px solid rgba(0,0,0,0.07);padding:20px;margin-bottom:14px;}
.rec-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;}
.rec-diag{font-size:16px;font-weight:700;color:#1a1a18;}
.rec-date{font-size:12px;color:#888;margin-top:3px;}
.badge{display:inline-flex;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:500;}
.badge-blue{background:#E6F1FB;color:#0C447C;}
.divider{height:1px;background:rgba(0,0,0,0.07);margin:14px 0;}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.info-lbl{font-size:10px;color:#888;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
.info-val{font-size:13px;color:#1a1a18;line-height:1.5;}
.rec-footer{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid rgba(0,0,0,0.07);}
.doc-info{font-size:12px;color:#888;}
.btn-print{padding:6px 14px;background:#E6F1FB;color:#0C447C;border:none;border-radius:8px;font-size:12px;cursor:pointer;text-decoration:none;}
.empty{text-align:center;padding:60px 20px;background:#fff;border-radius:12px;}
.empty h3{font-size:16px;font-weight:600;margin-bottom:8px;}
.empty p{font-size:13px;color:#888;}
.medicines-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
.med-tag{background:#f0efeb;padding:4px 10px;border-radius:6px;font-size:12px;color:#5F5E5A;}
</style>
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#5DCAA5" stroke-width="3" stroke-linecap="round"/></svg></div>
    <h1>Sehat Bersama Hospital</h1>
  </div>
  <nav class="topbar-nav">
    <a href="dashboard.php" class="nav-link">Dashboard</a>
    <a href="reservasi.php" class="nav-link">Reservation</a>
    <a href="rekam_medis.php" class="nav-link active">Medical Records</a>
    <a href="profile.php" class="nav-link">Profile</a>
  </nav>
  <div class="topbar-user">
    <span style="font-size:13px;"><?=htmlspecialchars($_SESSION['patient_name'])?></span>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<div class="content">
  <div class="page-title">My Medical Records</div>
  <div class="page-sub">Complete history of your visits to Sehat Bersama Hospital</div>

  <?php if(empty($records)):?>
  <div class="empty">
    <h3>No medical records yet</h3>
    <p>Your visit history will appear here after your first examination.</p>
  </div>
  <?php else:foreach($records as $r):?>
  <div class="rec-card">
    <div class="rec-top">
      <div>
        <div class="rec-diag"><?=htmlspecialchars($r['diagnosis']??'No diagnosis')?></div>
        <div class="rec-date"><?=date('d F Y',strtotime($r['visit_date']))?></div>
      </div>
      <span class="badge badge-blue">Visit #<?=$r['medical_record_id']?></span>
    </div>
    <div class="divider"></div>
    <div class="info-grid">
      <div>
        <div class="info-lbl">Complaints</div>
        <div class="info-val"><?=htmlspecialchars($r['complaints'])?></div>
      </div>
      <div>
        <div class="info-lbl">Prescription Notes</div>
        <div class="info-val"><?=htmlspecialchars($r['prescription']??'-')?></div>
      </div>
    </div>
    <?php if($r['medicines']):?>
    <div style="margin-top:12px;">
      <div class="info-lbl">Medicines Prescribed</div>
      <div class="medicines-list">
        <?php foreach(explode(', ',$r['medicines']) as $med):?>
        <span class="med-tag">💊 <?=htmlspecialchars($med)?></span>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif;?>
    <div class="rec-footer">
      <div class="doc-info">Dr. <?=htmlspecialchars($r['doctor_name'])?> · <?=htmlspecialchars($r['specialization'])?></div>
      <a href="../medical_records/print.php?id=<?=$r['medical_record_id']?>" target="_blank" class="btn-print">Print Record</a>
    </div>
  </div>
  <?php endforeach;endif;?>
</div>
</body>
</html>