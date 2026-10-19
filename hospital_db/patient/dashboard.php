<?php
require_once '../config.php';
require_once '../auth.php';
requirePatient();

$patientId=$_SESSION['patient_id']??0;
$patientData=null;
$medicalRecords=[];
$schedules=[];

if($patientId){
    $patientData=$conn->query("SELECT * FROM patients WHERE patient_id=$patientId")->fetch_assoc();
    $medicalRecords=$conn->query("SELECT mr.*,d.doctor_name,d.specialization FROM medical_records mr JOIN doctors d ON mr.doctor_id=d.doctor_id WHERE mr.patient_id=$patientId ORDER BY mr.visit_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    $schedules=$conn->query("SELECT es.*,d.doctor_name,d.specialization,r.room_name FROM examination_schedules es JOIN doctors d ON es.doctor_id=d.doctor_id JOIN rooms r ON es.room_id=r.room_id WHERE es.patient_id=$patientId ORDER BY es.examination_time DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard — Sehat Bersama Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0efeb;color:#1a1a18;font-size:14px;min-height:100vh;}
.topbar{background:#185FA5;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;}
.topbar-brand{display:flex;align-items:center;gap:10px;color:#fff;}
.topbar-brand .icon{width:32px;height:32px;background:rgba(255,255,255,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.topbar-brand h1{font-size:15px;font-weight:600;}
.topbar-nav{display:flex;align-items:center;gap:8px;}
.nav-link{padding:7px 14px;border-radius:8px;color:rgba(255,255,255,0.7);font-size:13px;text-decoration:none;transition:.2s;}
.nav-link:hover,.nav-link.active{background:rgba(255,255,255,0.15);color:#fff;}
.topbar-user{display:flex;align-items:center;gap:10px;color:#fff;}
.user-avatar{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;}
.logout-btn{padding:6px 14px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:#fff;font-size:12px;cursor:pointer;text-decoration:none;transition:.2s;}
.logout-btn:hover{background:rgba(255,255,255,0.2);}
.content{padding:28px;max-width:1000px;margin:0 auto;}
.welcome{background:linear-gradient(135deg,#185FA5,#0C447C);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;}
.welcome h2{font-size:20px;font-weight:700;}
.welcome p{font-size:13px;opacity:0.8;margin-top:4px;}
.welcome-actions{display:flex;gap:10px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:500;font-family:inherit;cursor:pointer;text-decoration:none;border:none;transition:.2s;}
.btn-white{background:#fff;color:#185FA5;}
.btn-white:hover{background:#f0f0f0;}
.btn-outline{background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.3);}
.btn-outline:hover{background:rgba(255,255,255,0.2);}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;}
.stat{background:#fff;border-radius:12px;padding:18px;border:1px solid rgba(0,0,0,0.07);}
.stat-label{font-size:11px;color:#888;font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
.stat-val{font-size:28px;font-weight:700;color:#1a1a18;}
.stat-sub{font-size:11px;color:#aaa;margin-top:3px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.card{background:#fff;border-radius:12px;border:1px solid rgba(0,0,0,0.07);overflow:hidden;}
.card-head{padding:14px 18px;border-bottom:1px solid rgba(0,0,0,0.07);display:flex;align-items:center;justify-content:space-between;}
.card-title{font-size:14px;font-weight:600;}
.card-link{font-size:12px;color:#185FA5;text-decoration:none;}
.tbl{width:100%;border-collapse:collapse;}
.tbl th{padding:10px 18px;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.05em;background:#faf9f5;border-bottom:1px solid rgba(0,0,0,0.07);text-align:left;}
.tbl td{padding:12px 18px;font-size:13px;border-bottom:1px solid rgba(0,0,0,0.05);vertical-align:middle;}
.tbl tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:500;}
.badge-teal{background:#E1F5EE;color:#0F6E56;}
.badge-amber{background:#FAEEDA;color:#854F0B;}
.badge-red{background:#FCEBEB;color:#A32D2D;}
.badge-blue{background:#E6F1FB;color:#0C447C;}
.empty{text-align:center;padding:30px;color:#aaa;font-size:13px;}
.no-patient{background:#fff;border-radius:12px;padding:32px;text-align:center;border:1px solid rgba(0,0,0,0.07);}
.no-patient h3{font-size:16px;font-weight:600;margin-bottom:8px;}
.no-patient p{font-size:13px;color:#888;margin-bottom:16px;}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    <div class="icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#5DCAA5" stroke-width="3" stroke-linecap="round"/></svg></div>
    <h1>Sehat Bersama Hospital</h1>
  </div>
  <nav class="topbar-nav">
    <a href="dashboard.php" class="nav-link active">Dashboard</a>
    <a href="reservasi.php" class="nav-link">Reservation</a>
    <a href="rekam_medis.php" class="nav-link">Medical Records</a>
    <a href="profile.php" class="nav-link">Profile</a>
  </nav>
  <div class="topbar-user">
    <div class="user-avatar"><?=strtoupper(substr($_SESSION['patient_name'],0,1))?></div>
    <span style="font-size:13px;"><?=htmlspecialchars($_SESSION['patient_name'])?></span>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<div class="content">
  <div class="welcome">
    <div>
      <h2>Welcome, <?=htmlspecialchars($_SESSION['patient_name'])?>! 👋</h2>
      <p><?=$patientData ? 'Patient ID #'.$patientId.' · '.htmlspecialchars($patientData['patient_name']) : 'Your account is not yet linked to a patient record.'?></p>
    </div>
    <div class="welcome-actions">
      <a href="reservasi.php" class="btn btn-white">+ New Reservation</a>
      <a href="rekam_medis.php" class="btn btn-outline">My Records</a>
    </div>
  </div>

  <?php if(!$patientData):?>
  <div class="no-patient">
    <h3>Account Not Linked</h3>
    <p>Your account is not yet linked to a patient record. Please update your phone number in profile settings to link your medical history.</p>
    <a href="profile.php" class="btn" style="background:#185FA5;color:#fff;display:inline-flex;">Update Profile</a>
  </div>
  <?php else:?>

  <div class="stats">
    <?php
    $totalVisits=$conn->query("SELECT COUNT(*) c FROM medical_records WHERE patient_id=$patientId")->fetch_assoc()['c'];
    $totalSchedules=$conn->query("SELECT COUNT(*) c FROM examination_schedules WHERE patient_id=$patientId")->fetch_assoc()['c'];
    $pendingSchedules=$conn->query("SELECT COUNT(*) c FROM examination_schedules WHERE patient_id=$patientId AND schedule_status='Waiting'")->fetch_assoc()['c'];
    ?>
    <div class="stat"><div class="stat-label">Total Visits</div><div class="stat-val"><?=$totalVisits?></div><div class="stat-sub">medical records</div></div>
    <div class="stat"><div class="stat-label">Total Schedules</div><div class="stat-val"><?=$totalSchedules?></div><div class="stat-sub">all time</div></div>
    <div class="stat"><div class="stat-label">Upcoming</div><div class="stat-val"><?=$pendingSchedules?></div><div class="stat-sub">waiting schedules</div></div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-head"><span class="card-title">Recent Medical Records</span><a href="rekam_medis.php" class="card-link">View all</a></div>
      <table class="tbl">
        <thead><tr><th>Diagnosis</th><th>Doctor</th><th>Date</th></tr></thead>
        <tbody>
        <?php if(empty($medicalRecords)):?>
          <tr><td colspan="3" class="empty">No records yet</td></tr>
        <?php else:foreach($medicalRecords as $r):?>
          <tr>
            <td><span class="badge badge-blue"><?=htmlspecialchars($r['diagnosis']??'—')?></span></td>
            <td style="font-size:12px;"><?=htmlspecialchars($r['doctor_name'])?></td>
            <td style="font-size:12px;color:#888;"><?=date('d M Y',strtotime($r['visit_date']))?></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-head"><span class="card-title">Upcoming Schedules</span><a href="reservasi.php" class="card-link">+ New</a></div>
      <table class="tbl">
        <thead><tr><th>Doctor</th><th>Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php if(empty($schedules)):?>
          <tr><td colspan="3" class="empty">No schedules yet</td></tr>
        <?php else:foreach($schedules as $s):
          $bc=['Waiting'=>'badge-amber','Completed'=>'badge-teal','Cancelled'=>'badge-red'];
          $b=$bc[$s['schedule_status']]??'';
        ?>
          <tr>
            <td style="font-size:12px;"><?=htmlspecialchars($s['doctor_name'])?></td>
            <td style="font-size:12px;"><?=date('d M H:i',strtotime($s['examination_time']))?></td>
            <td><span class="badge <?=$b?>"><?=$s['schedule_status']?></span></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif;?>
</div>
</body>
</html>