<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
if(!$id){flash('Invalid ID.','danger');redirect('index.php');}
$patient=$conn->query("SELECT * FROM patients WHERE patient_id=$id")->fetch_assoc();
if(!$patient){flash('Patient not found.','danger');redirect('index.php');}
$pageTitle='Edit Patient'; $activePage='patients'; $base='..'; $errors=[];
$insuranceList=$conn->query("SELECT insurance_id,insurance_name FROM insurance ORDER BY insurance_name")->fetch_all(MYSQLI_ASSOC);
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=sanitize($conn,$_POST['patient_name']??''); $dob=sanitize($conn,$_POST['date_of_birth']??'');
  $gender=sanitize($conn,$_POST['gender']??''); $address=sanitize($conn,$_POST['address']??'');
  $phone=sanitize($conn,$_POST['phone_number']??''); $blood=sanitize($conn,$_POST['blood_type']??'');
  $card=sanitize($conn,$_POST['card_id']??''); $insId=intval($_POST['insurance_id']??0);
  if(!$name)$errors[]='Patient name is required.';
  if(!$dob)$errors[]='Date of birth is required.';
  if(empty($errors)){
    $stmt=$conn->prepare("UPDATE patients SET patient_name=?,card_id=?,date_of_birth=?,gender=?,address=?,phone_number=?,blood_type=?,insurance_id=? WHERE patient_id=?");
    $stmt->bind_param('sssssssii',$name,$card,$dob,$gender,$address,$phone,$blood,$insId,$id);$stmt->execute();
    flash("Patient $name updated successfully.");redirect('index.php');
  }
  $patient=array_merge($patient,$_POST);
}
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Edit Patient</h2><p>Update patient information</p></div></div>
  <div class="content">
    <a href="index.php" class="back-link"><svg viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg> Back to Patients</a>
    <?php if($errors):?><div class="alert alert-danger"><?=implode('<br>',array_map('htmlspecialchars',$errors))?></div><?php endif;?>
    <form method="POST" class="form-card">
      <div class="form-title">Edit Patient — ID #<?=$id?></div>
      <div class="form-group"><label class="form-label">Full name <span>*</span></label><input type="text" name="patient_name" class="form-control" value="<?=htmlspecialchars($patient['patient_name'])?>" required></div>
      <div class="form-group"><label class="form-label">ID Card Number (KTP/NIK)</label><input type="text" name="card_id" class="form-control" value="<?=htmlspecialchars($patient['card_id']??'')?>"></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Date of birth <span>*</span></label><input type="date" name="date_of_birth" class="form-control" value="<?=htmlspecialchars($patient['date_of_birth'])?>" required></div>
        <div class="form-group"><label class="form-label">Gender <span>*</span></label>
          <select name="gender" class="form-control" required>
            <option value="Male" <?=$patient['gender']==='Male'?'selected':''?>>Male</option>
            <option value="Female" <?=$patient['gender']==='Female'?'selected':''?>>Female</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone number</label><input type="text" name="phone_number" class="form-control" value="<?=htmlspecialchars($patient['phone_number']??'')?>"></div>
        <div class="form-group"><label class="form-label">Blood type</label>
          <select name="blood_type" class="form-control">
            <option value="">-- Select --</option>
            <?php foreach(['A','B','AB','O'] as $b):?><option value="<?=$b?>" <?=($patient['blood_type']??'')===$b?'selected':''?>><?=$b?></option><?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Insurance</label>
        <select name="insurance_id" class="form-control">
          <option value="0">-- No Insurance --</option>
          <?php foreach($insuranceList as $ins):?><option value="<?=$ins['insurance_id']?>" <?=($patient['insurance_id']??0)==$ins['insurance_id']?'selected':''?>><?=htmlspecialchars($ins['insurance_name'])?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control"><?=htmlspecialchars($patient['address']??'')?></textarea></div>
      <div class="form-actions"><button type="submit" class="btn btn-accent">Save Changes</button><a href="index.php" class="btn btn-ghost">Cancel</a></div>
    </form>
  </div>
<?php include '../assets/footer.php';?>