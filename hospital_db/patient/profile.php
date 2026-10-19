<?php
require_once '../config.php';
require_once '../auth.php';
requirePatient();
$accountId=$_SESSION['patient_account_id'];
$account=$conn->query("SELECT * FROM patient_accounts WHERE account_id=$accountId")->fetch_assoc();
$errors=[];$success=false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']??'');
    $phone=trim($_POST['phone']??'');
    $password=$_POST['password']??'';
    $confirm=$_POST['confirm_password']??'';
    if(!$name) $errors[]='Name is required.';
    if($password && strlen($password)<6) $errors[]='Password must be at least 6 characters.';
    if($password && $password!==$confirm) $errors[]='Passwords do not match.';
    if(empty($errors)){
        // Cari patient_id berdasarkan phone
        $patientId=null;
        if($phone){
            $findP=$conn->prepare("SELECT patient_id FROM patients WHERE phone_number=?");
            $findP->bind_param('s',$phone);$findP->execute();
            $found=$findP->get_result()->fetch_assoc();
            if($found) $patientId=$found['patient_id'];
        }
        if($password){
            $hashed=password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("UPDATE patient_accounts SET name=?,phone=?,password=?,patient_id=? WHERE account_id=?");
            $stmt->bind_param('sssii',$name,$phone,$hashed,$patientId,$accountId);
        } else {
            $stmt=$conn->prepare("UPDATE patient_accounts SET name=?,phone=?,patient_id=? WHERE account_id=?");
            $stmt->bind_param('ssii',$name,$phone,$patientId,$accountId);
        }
        $stmt->execute();
        $_SESSION['patient_name']=$name;
        $_SESSION['patient_id']=$patientId;
        $account=array_merge($account,['name'=>$name,'phone'=>$phone,'patient_id'=>$patientId]);
        $success=true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile — Sehat Bersama Hospital</title>
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
.content{padding:28px;max-width:500px;margin:0 auto;}
.page-title{font-size:20px;font-weight:700;margin-bottom:24px;}
.form-card{background:#fff;border-radius:12px;padding:24px;border:1px solid rgba(0,0,0,0.07);}
.form-title{font-size:15px;font-weight:600;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid rgba(0,0,0,0.07);}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:500;color:#5F5E5A;margin-bottom:6px;}
.form-control{width:100%;padding:9px 12px;font-size:13px;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;outline:none;transition:.2s;}
.form-control:focus{border-color:#378ADD;box-shadow:0 0 0 3px rgba(55,138,221,0.12);}
.form-control[readonly]{background:#f5f4f0;color:#888;}
.form-hint{font-size:11px;color:#888;margin-top:4px;}
.btn{padding:10px 20px;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;transition:.2s;}
.btn-primary{background:#185FA5;color:#fff;}
.btn-primary:hover{background:#0C447C;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-danger{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.alert-success{background:#EAF3DE;color:#3B6D11;border:1px solid #C0DD97;}
.info-box{background:#E6F1FB;border-radius:8px;padding:12px 16px;font-size:12px;color:#0C447C;margin-bottom:16px;border:1px solid #93C5F5;}
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
    <a href="rekam_medis.php" class="nav-link">Medical Records</a>
    <a href="profile.php" class="nav-link active">Profile</a>
  </nav>
  <div class="topbar-user">
    <span style="font-size:13px;"><?=htmlspecialchars($_SESSION['patient_name'])?></span>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </div>
</div>

<div class="content">
  <div class="page-title">My Profile</div>
  <?php if($errors):?><div class="alert alert-danger"><?=implode('<br>',$errors)?></div><?php endif;?>
  <?php if($success):?><div class="alert alert-success">Profile updated successfully!</div><?php endif;?>

  <div class="form-card">
    <div class="form-title">Account Information</div>
    <?php if(!$account['patient_id']):?>
    <div class="info-box">⚠️ Your account is not linked to a patient record. Enter your registered phone number below to link your medical history.</div>
    <?php else:?>
    <div class="info-box">✅ Account linked to Patient ID #<?=$account['patient_id']?></div>
    <?php endif;?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="text" class="form-control" value="<?=htmlspecialchars($account['email'])?>" readonly>
      </div>
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($account['name'])?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($account['phone']??'')?>" placeholder="Enter registered phone to link records">
        <div class="form-hint">Must match the phone number in our patient database</div>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span style="color:#aaa;font-weight:400;">(leave blank to keep current)</span></label>
        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>
</div>
</body>
</html>