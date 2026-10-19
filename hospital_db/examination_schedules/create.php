<?php
require_once '../config.php';
$pageTitle='New Schedule'; $activePage='schedules'; $base='..'; $errors=[];
$patientList = $conn->query("SELECT patient_id,patient_name FROM patients ORDER BY patient_name")->fetch_all(MYSQLI_ASSOC);
$doctorList  = $conn->query("SELECT doctor_id,doctor_name,specialization FROM doctors ORDER BY doctor_name")->fetch_all(MYSQLI_ASSOC);
$roomList    = $conn->query("SELECT room_id,room_name,room_type FROM rooms WHERE status='Available' ORDER BY room_name")->fetch_all(MYSQLI_ASSOC);

// Minimum allowed datetime = today 00:00 (past dates are blocked)
$todayMin = date('Y-m-d') . 'T00:00';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $idP  = intval($_POST['patient_id']??0);
  $idD  = intval($_POST['doctor_id']??0);
  $idR  = intval($_POST['room_id']??0);
  $time = sanitize($conn, $_POST['examination_time']??'');
  $notes= sanitize($conn, $_POST['notes']??'');

  if(!$idP)  $errors[] = 'Patient is required.';
  if(!$idD)  $errors[] = 'Doctor is required.';
  if(!$idR)  $errors[] = 'Room is required.';
  if(!$time) $errors[] = 'Examination time is required.';

  // ── Block past dates (server-side) ───────────────────────────
  if($time && $time < date('Y-m-d')){
    $errors[] = 'Examination date cannot be in the past. Please select today or a future date.';
  }

  if(empty($errors)){
    $stmt = $conn->prepare("INSERT INTO examination_schedules (patient_id,doctor_id,room_id,examination_time,schedule_status,notes) VALUES (?,?,?,?,'Waiting',?)");
    $stmt->bind_param('iiiss', $idP, $idD, $idR, $time, $notes);
    $stmt->execute();
    flash("Examination schedule has been created successfully."); redirect('index.php');
  }
}
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>New Examination Schedule</h2><p>Book an appointment for a patient</p></div></div>
  <div class="content">
    <a href="index.php" class="back-link"><svg viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg> Back to Schedules</a>
    <?php if($errors):?><div class="alert alert-danger"><?=implode('<br>',array_map('htmlspecialchars',$errors))?></div><?php endif;?>
    <form method="POST" class="form-card">
      <div class="form-title">Schedule Details</div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Patient <span>*</span></label>
          <select name="patient_id" class="form-control" required>
            <option value="">-- Select Patient --</option>
            <?php foreach($patientList as $p):?><option value="<?=$p['patient_id']?>" <?=($_POST['patient_id']??'')==$p['patient_id']?'selected':''?>><?=htmlspecialchars($p['patient_name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Doctor <span>*</span></label>
          <select name="doctor_id" class="form-control" required>
            <option value="">-- Select Doctor --</option>
            <?php foreach($doctorList as $d):?><option value="<?=$d['doctor_id']?>" <?=($_POST['doctor_id']??'')==$d['doctor_id']?'selected':''?>><?=htmlspecialchars($d['doctor_name'])?> (<?=htmlspecialchars($d['specialization'])?>)</option><?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Room <span>*</span></label>
          <select name="room_id" class="form-control" required>
            <option value="">-- Select Room --</option>
            <?php foreach($roomList as $r):?><option value="<?=$r['room_id']?>" <?=($_POST['room_id']??'')==$r['room_id']?'selected':''?>><?=htmlspecialchars($r['room_name'])?> (<?=htmlspecialchars($r['room_type'])?>)</option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Examination time <span>*</span></label>
          <!-- min attribute blocks past dates in the browser date picker -->
          <input type="datetime-local" name="examination_time" class="form-control"
                 value="<?=htmlspecialchars($_POST['examination_time']??'')?>"
                 min="<?=$todayMin?>"
                 required>
          <small style="color:var(--text-3);font-size:11px;">Past dates are not selectable</small>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" placeholder="Additional notes (optional)..."><?=htmlspecialchars($_POST['notes']??'')?></textarea>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-accent">Save Schedule</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>

<script>
// Extra client-side guard: if user manually types a past date, warn immediately
document.querySelector('input[name="examination_time"]').addEventListener('change', function(){
  const val     = this.value;
  const today   = new Date(); today.setHours(0,0,0,0);
  const picked  = new Date(val);
  const hint    = this.nextElementSibling;
  if(val && picked < today){
    this.value = '';
    hint.textContent = '⚠ Past date is not allowed. Please pick today or later.';
    hint.style.color = '#b91c1c';
  } else {
    hint.textContent = 'Past dates are not selectable';
    hint.style.color = 'var(--text-3)';
  }
});
</script>
<?php include '../assets/footer.php';?>