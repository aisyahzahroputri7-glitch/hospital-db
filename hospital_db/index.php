<?php
require_once 'config.php';
$pageTitle='Dashboard'; $activePage='dashboard'; $base='.';
include 'assets/sidebar.php';

$totalPatients   = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$totalDoctors    = $conn->query("SELECT COUNT(*) c FROM doctors")->fetch_assoc()['c'];
$totalToday      = $conn->query("SELECT COUNT(*) c FROM examination_schedules WHERE DATE(examination_time)=CURDATE() AND schedule_status='Waiting'")->fetch_assoc()['c'];
$totalRecords    = $conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'];
$totalRoomsAvail = $conn->query("SELECT COUNT(*) c FROM rooms WHERE status='Available'")->fetch_assoc()['c'];
$totalRoomsAll   = $conn->query("SELECT COUNT(*) c FROM rooms")->fetch_assoc()['c'];

$todaySchedules = $conn->query("
    SELECT es.*, p.patient_name, d.doctor_name, r.room_name
    FROM examination_schedules es
    JOIN patients p ON es.patient_id = p.patient_id
    JOIN doctors d  ON es.doctor_id  = d.doctor_id
    JOIN rooms r    ON es.room_id    = r.room_id
    WHERE DATE(es.examination_time) = CURDATE()
    ORDER BY es.examination_time ASC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$recentRecords = $conn->query("
    SELECT mr.*, p.patient_name, d.doctor_name
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.patient_id
    JOIN doctors d  ON mr.doctor_id  = d.doctor_id
    ORDER BY mr.visit_date DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$avgColors = ['av-teal','av-purple','av-blue','av-amber','av-red','av-green'];
function ac($i){global $avgColors;return $avgColors[$i%count($avgColors)];}
function ini($n){$p=explode(' ',$n);return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));}
function statusBadge($s){
    $m=['Waiting'=>['badge-amber','Waiting'],'Completed'=>['badge-teal','Completed'],'Cancelled'=>['badge-red','Cancelled']];
    $d=$m[$s]??['badge-gray',$s];
    return '<span class="badge '.$d[0].'">'.$d[1].'</span>';
}
?>
  <div class="topbar">
    <div class="topbar-left"><h2>Dashboard</h2><p>Welcome — <?=date('l, d F Y')?></p></div>
    <a href="examination_schedules/create.php" class="btn btn-accent">
      <svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      New Schedule
    </a>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?>
    <div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div>
    <?php unset($_SESSION['flash']);endif;?>

    <div class="stats" style="grid-template-columns:repeat(5,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Patients</div><div class="stat-val"><?=$totalPatients?></div><div class="stat-sub">registered</div></div>
      <div class="stat s2"><div class="stat-label">Total Doctors</div><div class="stat-val"><?=$totalDoctors?></div><div class="stat-sub">active</div></div>
      <div class="stat s3"><div class="stat-label">Today's Schedule</div><div class="stat-val"><?=$totalToday?></div><div class="stat-sub">waiting</div></div>
      <div class="stat s4"><div class="stat-label">Medical Records</div><div class="stat-val"><?=$totalRecords?></div><div class="stat-sub">total</div></div>
      <div class="stat" style="border-left-color:#639922;"><div class="stat-label">Rooms Available</div><div class="stat-val"><?=$totalRoomsAvail?></div><div class="stat-sub">of <?=$totalRoomsAll?> rooms</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="table-card">
        <div class="table-head"><span class="table-title">Today's appointments</span><a href="examination_schedules/index.php" class="btn btn-ghost btn-sm">View all</a></div>
        <table class="tbl">
          <thead><tr><th>Patient</th><th>Doctor</th><th>Time</th><th>Status</th></tr></thead>
          <tbody>
          <?php if(empty($todaySchedules)):?>
            <tr><td class="tbl-empty" colspan="4">No appointments today</td></tr>
          <?php else:foreach($todaySchedules as $i=>$s):?>
            <tr>
              <td><div class="tbl-info"><div class="avatar <?=ac($i)?>"><?=ini($s['patient_name'])?></div><div class="tbl-name"><?=htmlspecialchars($s['patient_name'])?></div></div></td>
              <td><div class="tbl-sub"><?=htmlspecialchars($s['doctor_name'])?></div></td>
              <td style="font-size:12px;"><?=date('H:i',strtotime($s['examination_time']))?></td>
              <td><?=statusBadge($s['schedule_status'])?></td>
            </tr>
          <?php endforeach;endif;?>
          </tbody>
        </table>
      </div>

      <div class="table-card">
        <div class="table-head"><span class="table-title">Recent medical records</span><a href="medical_records/index.php" class="btn btn-ghost btn-sm">View all</a></div>
        <table class="tbl">
          <thead><tr><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Date</th></tr></thead>
          <tbody>
          <?php if(empty($recentRecords)):?>
            <tr><td class="tbl-empty" colspan="4">No records yet</td></tr>
          <?php else:foreach($recentRecords as $i=>$r):?>
            <tr>
              <td><div class="tbl-info"><div class="avatar <?=ac($i)?>"><?=ini($r['patient_name'])?></div><div class="tbl-name"><?=htmlspecialchars($r['patient_name'])?></div></div></td>
              <td><div class="tbl-sub"><?=htmlspecialchars($r['doctor_name'])?></div></td>
              <td><span class="badge badge-blue" style="font-size:10px;"><?=htmlspecialchars($r['diagnosis'])?></span></td>
              <td style="font-size:11px;color:var(--text-3);"><?=date('d M Y',strtotime($r['visit_date']))?></td>
            </tr>
          <?php endforeach;endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include 'assets/footer.php';?>
