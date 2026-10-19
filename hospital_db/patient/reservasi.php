<?php
require_once '../config.php';
require_once '../auth.php';
requirePatient();
$patientId=$_SESSION['patient_id']??0;
// if(!$patientId){flash('Please link your account to a patient record first.','danger');redirect('dashboard.php');}
$errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $idD=intval($_POST['doctor_id']??0);
    $idR=intval($_POST['room_id']??0);
    $time=sanitize($conn,$_POST['examination_time']??'');
    $notes=sanitize($conn,$_POST['notes']??'');
    if(!$idD) $errors[]='Doctor is required.';
    if(!$idR) $errors[]='Room is required.';
    if($time && date('Y-m-d', strtotime($time)) < date('Y-m-d')) $errors[] = 'Examination date cannot be in the past.';
  if(!$time) $errors[]='Examination time is required.';
    if(empty($errors)){
        $stmt=$conn->prepare("INSERT INTO examination_schedules (patient_id,doctor_id,room_id,examination_time,schedule_status,notes) VALUES (?,?,?,?,'Waiting',?)");
        $stmt->bind_param('iiiss',$patientId,$idD,$idR,$time,$notes);$stmt->execute();
        flash('Reservation created successfully!');redirect('dashboard.php');
    }
}

$doctorList=$conn->query("SELECT doctor_id,doctor_name,specialization,practice_schedule FROM doctors ORDER BY doctor_name")->fetch_all(MYSQLI_ASSOC);
$roomList=$conn->query("SELECT room_id,room_name,room_type FROM rooms WHERE status='Available' ORDER BY room_name")->fetch_all(MYSQLI_ASSOC);
$mySchedules=$conn->query("SELECT es.*,d.doctor_name,d.specialization,r.room_name FROM examination_schedules es JOIN doctors d ON es.doctor_id=d.doctor_id JOIN rooms r ON es.room_id=r.room_id WHERE es.patient_id=$patientId ORDER BY es.examination_time DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservation — Sehat Bersama Hospital</title>
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
.logout-btn{padding:6px 14px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:#fff;font-size:12px;cursor:pointer;text-decoration:none;}
.content{padding:28px;max-width:1000px;margin:0 auto;}
.page-title{font-size:20px;font-weight:700;margin-bottom:4px;}
.page-sub{font-size:13px;color:#888;margin-bottom:24px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.form-card{background:#fff;border-radius:12px;padding:24px;border:1px solid rgba(0,0,0,0.07);}
.form-title{font-size:15px;font-weight:600;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid rgba(0,0,0,0.07);}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:500;color:#5F5E5A;margin-bottom:6px;}
.form-label span{color:#A32D2D;}
.form-control{width:100%;padding:9px 12px;font-size:13px;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;outline:none;transition:.2s;}
.form-control:focus{border-color:#378ADD;box-shadow:0 0 0 3px rgba(55,138,221,0.12);}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:500;font-family:inherit;cursor:pointer;border:none;transition:.2s;}
.btn-primary{background:#185FA5;color:#fff;}
.btn-primary:hover{background:#0C447C;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-danger{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.alert-success{background:#EAF3DE;color:#3B6D11;border:1px solid #C0DD97;}
.table-card{background:#fff;border-radius:12px;border:1px solid rgba(0,0,0,0.07);overflow:hidden;margin-top:24px;}
.table-head{padding:14px 18px;border-bottom:1px solid rgba(0,0,0,0.07);font-size:14px;font-weight:600;}
.tbl{width:100%;border-collapse:collapse;}
.tbl th{padding:10px 18px;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;background:#faf9f5;border-bottom:1px solid rgba(0,0,0,0.07);text-align:left;}
.tbl td{padding:12px 18px;font-size:13px;border-bottom:1px solid rgba(0,0,0,0.05);}
.tbl tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:500;}
.badge-amber{background:#FAEEDA;color:#854F0B;}
.badge-teal{background:#E1F5EE;color:#0F6E56;}
.badge-red{background:#FCEBEB;color:#A32D2D;}
.doctor-card{background:#f8f7f3;border-radius:8px;padding:12px;margin-bottom:8px;cursor:pointer;border:2px solid transparent;transition:.2s;}
.doctor-card:hover,.doctor-card.selected{border-color:#378ADD;background:#E6F1FB;}
.doctor-name{font-size:13px;font-weight:600;}
.doctor-spec{font-size:11px;color:#888;margin-top:2px;}
.doctor-sched{font-size:11px;color:#5F5E5A;margin-top:4px;}
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
    <a href="reservasi.php" class="nav-link active">Reservation</a>
    <a href="rekam_medis.php" class="nav-link">Medical Records</a>
    <a href="profile.php" class="nav-link">Profile</a>
  </nav>
  <div class="topbar-user">
    <span style="font-size:13px;"><?=htmlspecialchars($_SESSION['patient_name'])?></span>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<div class="content">
  <div class="page-title">Make a Reservation</div>
  <div class="page-sub">Book an examination schedule with your preferred doctor</div>

  <?php if(!empty($_SESSION['flash'])):?>
  <div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div>
  <?php unset($_SESSION['flash']);endif;?>

  <?php if($errors):?>
  <div class="alert alert-danger"><?=implode('<br>',$errors)?></div>
  <?php endif;?>

  <div class="grid-2">
    <div>
      <div class="form-card">
        <div class="form-title">Select Doctor</div>
        <?php foreach($doctorList as $d):?>
        <div class="doctor-card" onclick="selectDoctor(<?=$d['doctor_id']?>,'<?=htmlspecialchars($d['doctor_name'])?>')" id="doc-<?=$d['doctor_id']?>">
          <div class="doctor-name"><?=htmlspecialchars($d['doctor_name'])?></div>
          <div class="doctor-spec"><?=htmlspecialchars($d['specialization'])?></div>
          <div class="doctor-sched">🕐 <?=htmlspecialchars($d['practice_schedule']??'-')?></div>
        </div>
        <?php endforeach;?>
      </div>
    </div>

    <div>
      <form method="POST" class="form-card">
        <div class="form-title">Reservation Details</div>
        <input type="hidden" name="doctor_id" id="selectedDoctorId" value="<?=intval($_POST['doctor_id']??0)?>">
        <div class="form-group">
          <label class="form-label">Selected Doctor</label>
          <input type="text" id="selectedDoctorName" class="form-control" placeholder="Click a doctor on the left" readonly value="<?=htmlspecialchars(isset($_POST['doctor_id']) ? ($doctorList[array_search($_POST['doctor_id'],array_column($doctorList,'doctor_id'))]['doctor_name']??'') : '')?>">
        </div>
        <div class="form-group">
          <label class="form-label">Room <span>*</span></label>
          <select name="room_id" class="form-control" required>
            <option value="">-- Select Room --</option>
            <?php foreach($roomList as $r):?>
            <option value="<?=$r['room_id']?>" <?=($_POST['room_id']??'')==$r['room_id']?'selected':''?>><?=htmlspecialchars($r['room_name'])?> (<?=htmlspecialchars($r['room_type'])?>)</option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Examination Time <span>*</span></label>
          <input type="datetime-local" name="examination_time" class="form-control" value="<?=htmlspecialchars($_POST['examination_time']??'')?>" min="<?=date('Y-m-d')?>T00:00" required onchange="blockPastDate(this)">
          <small style="color:var(--text-3);font-size:11px;">Past dates are not selectable</small>
        </div>
        <div class="form-group">
          <label class="form-label">Notes / Complaints</label>
          <textarea name="notes" class="form-control" placeholder="Describe your symptoms or reason for visit..."><?=htmlspecialchars($_POST['notes']??'')?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Book Reservation</button>
      </form>
    </div>
  </div>

  <div class="table-card">
    <div class="table-head">My Reservations</div>
    <table class="tbl">
      <thead><tr><th>Doctor</th><th>Room</th><th>Time</th><th>Status</th><th>Notes</th></tr></thead>
      <tbody>
      <?php if(empty($mySchedules)):?>
        <tr><td colspan="5" style="text-align:center;padding:30px;color:#aaa;">No reservations yet</td></tr>
      <?php else:foreach($mySchedules as $s):
        $bc=['Waiting'=>'badge-amber','Completed'=>'badge-teal','Cancelled'=>'badge-red'];
      ?>
        <tr>
          <td><div style="font-weight:500;font-size:13px;"><?=htmlspecialchars($s['doctor_name'])?></div><div style="font-size:11px;color:#888;"><?=htmlspecialchars($s['specialization'])?></div></td>
          <td style="font-size:12px;"><?=htmlspecialchars($s['room_name'])?></td>
          <td><div style="font-size:12px;font-weight:500;"><?=date('d M Y',strtotime($s['examination_time']))?></div><div style="font-size:11px;color:#888;"><?=date('H:i',strtotime($s['examination_time']))?> WIB</div></td>
          <td><span class="badge <?=$bc[$s['schedule_status']]??''?>"><?=$s['schedule_status']?></span></td>
          <td style="font-size:12px;color:#888;"><?=htmlspecialchars($s['notes']??'-')?></td>
        </tr>
      <?php endforeach;endif;?>
      </tbody>
    </table>
  </div>
</div>

<script>
function selectDoctor(id, name){
  document.querySelectorAll('.doctor-card').forEach(c=>c.classList.remove('selected'));
  document.getElementById('doc-'+id).classList.add('selected');
  document.getElementById('selectedDoctorId').value = id;
  document.getElementById('selectedDoctorName').value = name;
}
<?php if(!empty($_POST['doctor_id'])):?>
document.getElementById('doc-<?=$_POST['doctor_id']?>').classList.add('selected');
<?php endif;?>
</script>
</body>
</html>