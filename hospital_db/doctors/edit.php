<?php
require_once '../config.php';
$id = intval($_GET['id']??0);
$doctor = $conn->query("SELECT * FROM doctors WHERE doctor_id=$id")->fetch_assoc();
if(!$doctor){ flash('Doctor not found.','danger'); redirect('index.php'); }
$pageTitle='Edit Doctor'; $activePage='doctors'; $base='..'; $errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name    = sanitize($conn, $_POST['doctor_name']??'');
  $spes    = sanitize($conn, $_POST['specialization']??'');
  $phone   = sanitize($conn, $_POST['phone_number']??'');
  $sched   = sanitize($conn, $_POST['practice_schedule']??'');
  $fee     = floatval($_POST['service_fee']??0);

  // ── Validation: all fields required ──────────────────────────────
  if(!$name)                      $errors[] = 'Doctor name is required.';
  if(!$spes)                      $errors[] = 'Specialization is required.';
  if(!$phone)                     $errors[] = 'Phone number is required.';
  elseif(!preg_match('/^[0-9+\-\s]{8,20}$/', $phone))
                                  $errors[] = 'Phone number format is invalid (8-20 digits).';
  if(!$sched)                     $errors[] = 'Practice schedule is required.';
  if($fee <= 0)                   $errors[] = 'Service fee must be greater than 0.';

  if(empty($errors)){
    $stmt = $conn->prepare("UPDATE doctors SET doctor_name=?,specialization=?,phone_number=?,practice_schedule=?,service_fee=? WHERE doctor_id=?");
    $stmt->bind_param('ssssdi', $name, $spes, $phone, $sched, $fee, $id);
    $stmt->execute();
    flash("Doctor $name updated successfully.");
    redirect('index.php');
  }
  $doctor = array_merge($doctor, $_POST);
}
include '../assets/sidebar.php';
$specs = ['General','Internal Medicine','Surgery','Pediatrics','Cardiology','Neurology','Dermatology','Ophthalmology','ENT','Dentistry','Orthopedics','Radiology'];
?>
  <div class="topbar"><div class="topbar-left"><h2>Edit Doctor</h2><p>Update doctor information — all fields are required</p></div></div>
  <div class="content">
    <a href="index.php" class="back-link"><svg viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg> Back to Doctors</a>
    <?php if($errors):?><div class="alert alert-danger"><?=implode('<br>',array_map('htmlspecialchars',$errors))?></div><?php endif;?>
    <form method="POST" class="form-card">
      <div class="form-title">Edit Doctor — ID #<?=$id?></div>

      <!-- Full Name -->
      <div class="form-group">
        <label class="form-label">Full name <span>*</span></label>
        <input type="text" name="doctor_name" class="form-control"
               value="<?=htmlspecialchars($doctor['doctor_name'])?>" required>
      </div>

      <div class="form-row">
        <!-- Specialization -->
        <div class="form-group">
          <label class="form-label">Specialization <span>*</span></label>
          <select name="specialization" class="form-control" required>
            <option value="">-- Select --</option>
            <?php foreach($specs as $s):?>
              <option value="<?=$s?>" <?=$doctor['specialization']===$s?'selected':''?>><?=$s?></option>
            <?php endforeach;?>
          </select>
        </div>
        <!-- Phone Number -->
        <div class="form-group">
          <label class="form-label">Phone number <span>*</span></label>
          <input type="text" name="phone_number" class="form-control" placeholder="08xxxxxxxxxx"
                 value="<?=htmlspecialchars($doctor['phone_number']??'')?>" required>
          <small style="color:var(--text-3);font-size:11px;">Format: 8-20 digits, e.g. 08123456789</small>
        </div>
      </div>

      <div class="form-row">
        <!-- Practice Schedule -->
        <div class="form-group">
          <label class="form-label">Practice schedule <span>*</span></label>
          <input type="text" name="practice_schedule" class="form-control"
                 placeholder="e.g. Monday-Friday 08:00-14:00"
                 value="<?=htmlspecialchars($doctor['practice_schedule']??'')?>" required>
        </div>
        <!-- Service Fee -->
        <div class="form-group">
          <label class="form-label">Service fee (Rp) <span>*</span></label>
          <input type="number" name="service_fee" class="form-control"
                 value="<?=htmlspecialchars($doctor['service_fee']??'')?>" min="1" required>
          <small style="color:var(--text-3);font-size:11px;">Must be greater than 0</small>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-accent">Save Changes</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
<?php include '../assets/footer.php';?>