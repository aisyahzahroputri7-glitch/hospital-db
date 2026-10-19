<?php
require_once 'config.php';
// if(isPatient()) redirect('patient/dashboard.php');
$errors=[];$success=false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']??'');
    $email=trim($_POST['email']??'');
    $phone=trim($_POST['phone']??'');
    $password=$_POST['password']??'';
    $confirm=$_POST['confirm_password']??'';

    if(!$name) $errors[]='Name is required.';
    if(!$email) $errors[]='Email is required.';
    if(strlen($password)<6) $errors[]='Password must be at least 6 characters.';
    if($password!==$confirm) $errors[]='Passwords do not match.';

    if(empty($errors)){
        $chk=$conn->prepare("SELECT account_id FROM patient_accounts WHERE email=?");
        $chk->bind_param('s',$email);$chk->execute();
        if($chk->get_result()->num_rows>0){
            $errors[]='Email already registered.';
        } else {
            // Cari apakah pasien ini sudah ada di tabel patients berdasarkan nama & phone
            $patientId=null;
            if($phone){
                $findP=$conn->prepare("SELECT patient_id FROM patients WHERE phone_number=?");
                $findP->bind_param('s',$phone);$findP->execute();
                $found=$findP->get_result()->fetch_assoc();
                if($found) $patientId=$found['patient_id'];
            }
            $hashed=password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("INSERT INTO patient_accounts (patient_id,name,email,password,phone) VALUES (?,?,?,?,?)");
            $stmt->bind_param('issss',$patientId,$name,$email,$hashed,$phone);$stmt->execute();
            $success=true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Registration — Sehat Bersama Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Plus Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0efeb;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:16px;padding:32px;width:100%;max-width:440px;border:1px solid rgba(0,0,0,0.08);}
.logo{text-align:center;margin-bottom:24px;}
.logo-icon{width:44px;height:44px;background:#185FA5;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;}
.logo h2{font-size:18px;font-weight:700;color:#1a1a18;}
.logo p{font-size:12px;color:#888;margin-top:3px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:500;color:#5F5E5A;margin-bottom:6px;}
.form-control{width:100%;padding:10px 14px;font-size:13px;border:1px solid rgba(0,0,0,0.12);border-radius:10px;font-family:inherit;outline:none;transition:.2s;background:#fafaf8;}
.form-control:focus{border-color:#378ADD;box-shadow:0 0 0 3px rgba(55,138,221,0.15);}
.form-hint{font-size:11px;color:#888;margin-top:4px;}
.btn{width:100%;padding:12px;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;transition:.2s;}
.btn-primary{background:#185FA5;color:#fff;}
.btn-primary:hover{background:#0C447C;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-danger{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.alert-success{background:#EAF3DE;color:#3B6D11;border:1px solid #C0DD97;}
.alert-info{background:#E6F1FB;color:#0C447C;border:1px solid #93C5F5;}
.back{text-align:center;margin-top:16px;font-size:13px;color:#888;}
.back a{color:#185FA5;font-weight:500;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="#fff" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></div>
    <h2>Patient Registration</h2>
    <p>Sehat Bersama Hospital</p>
  </div>

  <?php if($success):?>
  <div class="alert alert-success">Account created successfully! You can now login.</div>
  <div class="back"><a href="login.php?tab=patient">← Login Now</a></div>
  <?php else:?>

  <div class="alert alert-info">Already visited our hospital? Enter your registered phone number to link your medical records.</div>

  <?php if($errors):?>
  <div class="alert alert-danger"><?=implode('<br>',$errors)?></div>
  <?php endif;?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Full Name *</label>
      <input type="text" name="name" class="form-control" placeholder="Your full name" value="<?=htmlspecialchars($_POST['name']??'')?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Email *</label>
      <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Phone Number</label>
      <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx (to link your records)" value="<?=htmlspecialchars($_POST['phone']??'')?>">
      <div class="form-hint">Enter your registered phone to link existing medical records</div>
    </div>
    <div class="form-group">
      <label class="form-label">Password *</label>
      <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
    </div>
    <div class="form-group">
      <label class="form-label">Confirm Password *</label>
      <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
    </div>
    <button type="submit" class="btn btn-primary">Create Account</button>
  </form>
  <div class="back"><a href="login.php?tab=patient">← Already have an account? Login</a></div>
  <?php endif;?>
</div>
</body>
</html>