<?php
require_once 'config.php';
// if(isAdmin()) redirect('index.php');
$errors=[];$success=false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']??'');
    $email=trim($_POST['email']??'');
    $password=$_POST['password']??'';
    $confirm=$_POST['confirm_password']??'';

    if(!$name) $errors[]='Name is required.';
    if(!$email) $errors[]='Email is required.';
    if(!isAdminEmail($email)) $errors[]='Must use hospital email (@sehatbersama.hospital.com).';
    if(strlen($password)<6) $errors[]='Password must be at least 6 characters.';
    if($password!==$confirm) $errors[]='Passwords do not match.';

    if(empty($errors)){
        // Cek email sudah ada
        $chk=$conn->prepare("SELECT admin_id FROM admins WHERE email=?");
        $chk->bind_param('s',$email);$chk->execute();
        if($chk->get_result()->num_rows>0){
            $errors[]='Email already registered.';
        } else {
            $hashed=password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("INSERT INTO admins (name,email,password,status,is_super) VALUES (?,?,?,'pending',0)");
            $stmt->bind_param('sss',$name,$email,$hashed);$stmt->execute();
            $newId=$conn->insert_id;
            // Buat notifikasi untuk super admin
            $notifMsg="New admin registration: $name ($email) is waiting for approval.";
            $notifType='new_admin';
            $notifStmt=$conn->prepare("INSERT INTO admin_notifications (type,message,related_id) VALUES (?,?,?)");
            $notifStmt->bind_param('ssi',$notifType,$notifMsg,$newId);
            $notifStmt->execute();
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
<title>Register Admin — Sehat Bersama Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0efeb;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:16px;padding:32px;width:100%;max-width:440px;border:1px solid rgba(0,0,0,0.08);}
.logo{text-align:center;margin-bottom:24px;}
.logo-icon{width:44px;height:44px;background:#1a1a18;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;}
.logo h2{font-size:18px;font-weight:700;color:#1a1a18;}
.logo p{font-size:12px;color:#888;margin-top:3px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:500;color:#5F5E5A;margin-bottom:6px;}
.form-control{width:100%;padding:10px 14px;font-size:13px;border:1px solid rgba(0,0,0,0.12);border-radius:10px;font-family:inherit;outline:none;transition:.2s;background:#fafaf8;}
.form-control:focus{border-color:#5DCAA5;box-shadow:0 0 0 3px rgba(93,202,165,0.15);}
.form-hint{font-size:11px;color:#888;margin-top:4px;}
.btn{width:100%;padding:12px;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;transition:.2s;}
.btn-primary{background:#1a1a18;color:#fff;}
.btn-primary:hover{background:#2c2c2a;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-danger{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.alert-success{background:#EAF3DE;color:#3B6D11;border:1px solid #C0DD97;}
.back{text-align:center;margin-top:16px;font-size:13px;color:#888;}
.back a{color:#1a1a18;font-weight:500;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#5DCAA5" stroke-width="3" stroke-linecap="round"/></svg></div>
    <h2>Register as Admin</h2>
    <p>Sehat Bersama Hospital</p>
  </div>

  <?php if($success):?>
  <div class="alert alert-success">
    Registration successful! Your account is pending approval from the super admin. You will be notified once approved.
  </div>
  <div class="back"><a href="login.php">← Back to Login</a></div>
  <?php else:?>

  <?php if($errors):?>
  <div class="alert alert-danger"><?=implode('<br>',$errors)?></div>
  <?php endif;?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Full Name *</label>
      <input type="text" name="name" class="form-control" placeholder="Your full name" value="<?=htmlspecialchars($_POST['name']??'')?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Hospital Email *</label>
      <input type="email" name="email" class="form-control" placeholder="name@sehatbersama.hospital.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required>
      <div class="form-hint">Must use @sehatbersama.hospital.com domain</div>
    </div>
    <div class="form-group">
      <label class="form-label">Password *</label>
      <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
    </div>
    <div class="form-group">
      <label class="form-label">Confirm Password *</label>
      <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
    </div>
    <button type="submit" class="btn btn-primary">Submit Registration</button>
  </form>
  <div class="back"><a href="login.php">← Back to Login</a></div>
  <?php endif;?>
</div>
</body>
</html>