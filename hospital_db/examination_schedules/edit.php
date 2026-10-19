<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
$schedule=$conn->query("SELECT * FROM examination_schedules WHERE schedule_id=$id")->fetch_assoc();
if(!$schedule){flash('Schedule not found.','danger');redirect('index.php');}
$pageTitle='Edit Schedule'; $activePage='schedules'; $base='..'; $errors=[];
$patientList=$conn->query("SELECT patient_id,patient_name FROM patients ORDER BY patient_name")->fetch_all(MYSQLI_ASSOC);
$doctorList=$conn->query("SELECT doctor_id,doctor_name,specialization FROM doctors ORDER BY doctor_name")->fetch_all(MYSQLI_ASSOC);
$roomList=$conn->query("SELECT room_id,room_name,room_type FROM rooms ORDER BY room_name")->fetch_all(MYSQLI_ASSOC);
if($_SERVER['REQUEST_METHOD']==='POST'){
  $idP=intval($_POST['patient_id']??0); $idD=intval($_POST['doctor_id']??0); $idR=intval($_POST['room_id']??0);
  $time=sanitize($conn,$_POST['examination_time']??'');
  $status=sanitize($conn,$_POST['schedule_status']??'Waiting');
  $notes=sanitize($conn,$_POST['notes']??'');
  if(!$idP)$errors[]='Patient is required.';
  if(!$idD)$errors[]='Doctor is required.';
  if(!$idR)$errors[]='Room is required.';
  if(!$time)$errors[]='Examination time is required.';
  if($time && date('Y-m-d', strtotime($time)) < date('Y-m-d'))$errors[]='Examination date cannot be in the past.';
  if(empty($errors)){
    $stmt=$conn->prepare("UPDATE examination_schedules SET patient_id=?,doctor_id=?,room_id=?,examination_time=?,schedule_status=?,notes=? WHERE schedule_id=?");
    $stmt->bind_param('iiisssi',$idP,$idD,$idR,$time,$status,$notes,$id);$stmt->execute();
    flash("Schedule updated successfully.");redirect('index.php');
  }
  $schedule=array_merge($schedule,$_POST);
}
$timeLocal=date('Y-m-d\TH:i',strtotime($schedule['examination_time']));
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Edit Schedule</h2><p>Update examination schedule</p></div></div>
  <div class="content">
    <a href="index.php" class="back-link"><svg viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg> Back to Schedules</a>
    <?php if($errors):?><div class="alert alert-danger"><?=implode('<br>',array_map('htmlspecialchars',$errors))?></div><?php endif;?>
    <form method="POST" class="form-card">
      <div class="form-title">Edit Schedule — ID #<?=$id?></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Patient <span>*</span></label>
          <select name="patient_id" class="form-control" required>
            <?php foreach($patientList as $p):?><option value="<?=$p['patient_id']?>" <?=$schedule['patient_id']==$p['patient_id']?'selected':''?>><?=htmlspecialchars($p['patient_name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Doctor <span>*</span></label>
          <select name="doctor_id" class="form-control" required>
            <?php foreach($doctorList as $d):?><option value="<?=$d['doctor_id']?>" <?=$schedule['doctor_id']==$d['doctor_id']?'selected':''?>><?=htmlspecialchars($d['doctor_name'])?> (<?=htmlspecialchars($d['specialization'])?>)</option><?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Room <span>*</span></label>
          <select name="room_id" class="form-control" required>
            <?php foreach($roomList as $r):?><option value="<?=$r['room_id']?>" <?=$schedule['room_id']==$r['room_id']?'selected':''?>><?=htmlspecialchars($r['room_name'])?> (<?=htmlspecialchars($r['room_type'])?>)</option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Examination time <span>*</span></label>
          <input type="datetime-local" name="examination_time" class="form-control" value="<?=$timeLocal?>" min="<?=date('Y-m-d')?>T00:00" required onchange="blockPastDate(this)">
          <small style="color:var(--text-3);font-size:11px;">Past dates are not selectable</small>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Status</label>
        <select name="schedule_status" class="form-control">
          <?php foreach(['Waiting','Completed','Cancelled'] as $s):?><option value="<?=$s?>" <?=$schedule['schedule_status']===$s?'selected':''?>><?=$s?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control"><?=htmlspecialchars($schedule['notes']??'')?></textarea></div>
      <div class="form-actions"><button type="submit" class="btn btn-accent">Save Changes</button><a href="index.php" class="btn btn-ghost">Cancel</a></div>
    </form>
  </div>
<script>
function blockPastDate(input){
  const today  = new Date(); today.setHours(0,0,0,0);
  const picked = new Date(input.value);
  const hint   = input.nextElementSibling;
  if(input.value && picked < today){
    input.value = '';
    hint.textContent = '⚠ Past date is not allowed. Please pick today or later.';
    hint.style.color = '#b91c1c';
  } else {
    hint.textContent = 'Past dates are not selectable';
    hint.style.color = 'var(--text-3)';
  }
}
</script>
<?php include '../assets/footer.php';?>