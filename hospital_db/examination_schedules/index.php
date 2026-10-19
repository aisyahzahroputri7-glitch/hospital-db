<?php

require_once '../auth.php';
requireAdmin();

require_once '../config.php';
$pageTitle='Examination Schedules'; $activePage='schedules'; $base='..';
$search=trim($_GET['search']??''); $status=$_GET['status']??''; $date=$_GET['date']??'';
$where=[]; $params=[]; $types='';
if($search!==''){$where[]="(p.patient_name LIKE ? OR d.doctor_name LIKE ?)";$params[]="%$search%";$params[]="%$search%";$types.='ss';}
if($status!==''){$where[]="es.schedule_status=?";$params[]=$status;$types.='s';}
if($date!==''){$where[]="DATE(es.examination_time)=?";$params[]=$date;$types.='s';}
$wsql=$where?' WHERE '.implode(' AND ',$where):'';
$sql="SELECT es.*,p.patient_name,d.doctor_name,d.specialization,r.room_name FROM examination_schedules es JOIN patients p ON es.patient_id=p.patient_id JOIN doctors d ON es.doctor_id=d.doctor_id JOIN rooms r ON es.room_id=r.room_id$wsql ORDER BY es.examination_time DESC";
$stmt=$conn->prepare($sql); if($params)$stmt->bind_param($types,...$params);
$stmt->execute(); $schedules=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalAll=$conn->query("SELECT COUNT(*) c FROM examination_schedules")->fetch_assoc()['c'];
$totalWaiting=$conn->query("SELECT COUNT(*) c FROM examination_schedules WHERE schedule_status='Waiting'")->fetch_assoc()['c'];
$totalDone=$conn->query("SELECT COUNT(*) c FROM examination_schedules WHERE schedule_status='Completed'")->fetch_assoc()['c'];
$totalToday=$conn->query("SELECT COUNT(*) c FROM examination_schedules WHERE DATE(examination_time)=CURDATE()")->fetch_assoc()['c'];
$avgColors=['av-teal','av-purple','av-blue','av-amber','av-red','av-green'];
function ac($i){global $avgColors;return $avgColors[$i%count($avgColors)];}
function ini($n){$p=explode(' ',$n);return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));}
function statusBadge($s){$m=['Waiting'=>'badge-amber','Completed'=>'badge-teal','Cancelled'=>'badge-red'];return '<span class="badge '.($m[$s]??'badge-gray').'">'.htmlspecialchars($s).'</span>';}
include '../assets/sidebar.php';
?>
  <div class="topbar">
    <div class="topbar-left"><h2>Examination Schedules</h2><p>Total <?=$totalAll?> schedules recorded</p></div>
    <a href="create.php" class="btn btn-accent"><svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> New Schedule</a>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="stats">
      <div class="stat"><div class="stat-label">Total Schedules</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">all time</div></div>
      <div class="stat s3"><div class="stat-label">Waiting</div><div class="stat-val"><?=$totalWaiting?></div><div class="stat-sub">pending</div></div>
      <div class="stat s2"><div class="stat-label">Completed</div><div class="stat-val"><?=$totalDone?></div><div class="stat-sub">done</div></div>
      <div class="stat s4"><div class="stat-label">Today</div><div class="stat-val"><?=$totalToday?></div><div class="stat-sub"><?=date('d M Y')?></div></div>
    </div>
    <form method="GET" class="toolbar">
      <div class="search-wrap">
        <svg viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        <input type="text" name="search" class="search-input" placeholder="Search patient or doctor..." value="<?=htmlspecialchars($search)?>">
      </div>
      <select name="status" class="filter-select">
        <option value="">All status</option>
        <?php foreach(['Waiting','Completed','Cancelled'] as $s):?><option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=$s?></option><?php endforeach;?>
      </select>
      <input type="date" name="date" class="filter-select" value="<?=htmlspecialchars($date)?>" title="Filter by date">
      <button type="submit" class="btn btn-ghost">Filter</button>
      <?php if($search||$status||$date):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>
    <div class="table-card">
      <table class="tbl">
        <thead><tr><th>#</th><th>Patient</th><th>Doctor</th><th>Room</th><th>Exam Time</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($schedules)):?><tr><td class="tbl-empty" colspan="8">No schedules found.</td></tr>
        <?php else:foreach($schedules as $i=>$s):?>
          <tr>
            <td style="color:var(--text-3);font-size:12px;"><?=$i+1?></td>
            <td><div class="tbl-info"><div class="avatar <?=ac($i)?>"><?=ini($s['patient_name'])?></div><div class="tbl-name"><?=htmlspecialchars($s['patient_name'])?></div></div></td>
            <td><div style="font-size:13px;"><?=htmlspecialchars($s['doctor_name'])?></div><div class="tbl-sub"><?=htmlspecialchars($s['specialization'])?></div></td>
            <td style="font-size:12px;"><?=htmlspecialchars($s['room_name'])?></td>
            <td><div style="font-size:12px;font-weight:500;"><?=date('d M Y',strtotime($s['examination_time']))?></div><div class="tbl-sub"><?=date('H:i',strtotime($s['examination_time']))?> WIB</div></td>
            <td><?=statusBadge($s['schedule_status'])?></td>
            <td style="font-size:12px;color:var(--text-2);max-width:140px;"><?=htmlspecialchars($s['notes']??'-')?></td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <?php if($s['schedule_status']==='Waiting'):?>
                <a href="complete.php?id=<?=$s['schedule_id']?>" class="btn btn-ghost btn-sm" style="color:var(--teal);" onclick="return confirm('Mark this schedule as completed?')">Done</a>
                <?php endif;?>
                <a href="edit.php?id=<?=$s['schedule_id']?>" class="btn btn-ghost btn-sm">Edit</a>
                <button onclick="confirmDelete(<?=$s['schedule_id']?>,'<?=htmlspecialchars($s['patient_name'])?>')" class="btn btn-danger btn-sm">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal-bg" id="deleteModal">
    <div class="modal"><h3>Delete Schedule</h3><p>Are you sure you want to delete the schedule for <strong id="deleteName"></strong>?</p>
    <div class="modal-actions"><button onclick="document.getElementById('deleteModal').classList.remove('open')" class="btn btn-ghost">Cancel</button><a id="deleteLink" href="#" class="btn btn-danger">Yes, Delete</a></div></div>
  </div>
<script>function confirmDelete(id,name){document.getElementById('deleteName').textContent=name;document.getElementById('deleteLink').href='delete.php?id='+id;document.getElementById('deleteModal').classList.add('open');}</script>
<?php include '../assets/footer.php';?>
