<?php
require_once 'config.php';
// if(isAdmin()) { redirect('index.php'); }
// if(isPatient()) { redirect('patient/dashboard.php'); }

$error = $_GET['error'] ?? '';
$tab = $_GET['tab'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sehat Bersama Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#0a0a0a;color:#fff;overflow-x:hidden;}

/* HERO SECTION */
.hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;overflow:hidden;background:linear-gradient(135deg,#0a1628 0%,#0d2137 50%,#0a1628 100%);}
.hero-bg{position:absolute;inset:0;overflow:hidden;}
.circle{position:absolute;border-radius:50%;opacity:0.07;animation:float 6s ease-in-out infinite;}
.circle-1{width:600px;height:600px;background:#5DCAA5;top:-200px;right:-200px;animation-delay:0s;}
.circle-2{width:400px;height:400px;background:#378ADD;bottom:-100px;left:-100px;animation-delay:2s;}
.circle-3{width:300px;height:300px;background:#5DCAA5;top:50%;left:30%;animation-delay:4s;}
@keyframes float{0%,100%{transform:translateY(0px);}50%{transform:translateY(-30px);}}

/* CROSS ANIMATION */
.cross-container{position:absolute;inset:0;pointer-events:none;}
.cross{position:absolute;opacity:0.15;animation:twinkle 3s ease-in-out infinite;}
.cross svg{width:24px;height:24px;}
@keyframes twinkle{0%,100%{opacity:0.1;transform:scale(1);}50%{opacity:0.3;transform:scale(1.2);}}

.hero-content{position:relative;z-index:2;text-align:center;padding:40px 20px;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(93,202,165,0.1);border:1px solid rgba(93,202,165,0.3);border-radius:20px;padding:6px 16px;font-size:12px;color:#5DCAA5;margin-bottom:24px;}
.hero-title{font-size:clamp(36px,6vw,72px);font-weight:800;line-height:1.1;margin-bottom:16px;}
.hero-title span{background:linear-gradient(135deg,#5DCAA5,#378ADD);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.hero-sub{font-size:16px;color:rgba(255,255,255,0.5);max-width:500px;margin:0 auto 40px;line-height:1.6;}
.hero-stats{display:flex;gap:40px;justify-content:center;margin-bottom:48px;}
.hero-stat{text-align:center;}
.hero-stat-val{font-size:28px;font-weight:700;color:#5DCAA5;}
.hero-stat-lbl{font-size:12px;color:rgba(255,255,255,0.4);margin-top:2px;}
.scroll-btn{display:inline-flex;align-items:center;gap:8px;background:rgba(93,202,165,0.1);border:1px solid rgba(93,202,165,0.3);border-radius:12px;padding:12px 24px;color:#5DCAA5;font-size:14px;cursor:pointer;transition:.3s;animation:bounce 2s ease-in-out infinite;}
.scroll-btn:hover{background:rgba(93,202,165,0.2);}
@keyframes bounce{0%,100%{transform:translateY(0);}50%{transform:translateY(6px);}}

/* LOGIN SECTION */
.login-section{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f0efeb;padding:60px 20px;}
.login-wrapper{width:100%;max-width:440px;}
.login-logo{text-align:center;margin-bottom:32px;}
.login-logo h1{font-size:20px;font-weight:700;color:#1a1a18;}
.login-logo p{font-size:13px;color:#888;margin-top:4px;}
.login-logo .cross-icon{width:48px;height:48px;background:#1a1a18;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}

/* TABS */
.tabs{display:flex;background:#e5e4e0;border-radius:12px;padding:4px;margin-bottom:24px;}
.tab{flex:1;padding:10px;text-align:center;border-radius:9px;font-size:13px;font-weight:500;cursor:pointer;transition:.2s;color:#888;border:none;background:transparent;font-family:inherit;}
.tab.active{background:#fff;color:#1a1a18;box-shadow:0 1px 4px rgba(0,0,0,0.1);}

/* FORM */
.login-card{background:#fff;border-radius:16px;padding:28px;border:1px solid rgba(0,0,0,0.08);}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:500;color:#5F5E5A;margin-bottom:6px;}
.form-control{width:100%;padding:10px 14px;font-size:13px;border:1px solid rgba(0,0,0,0.12);border-radius:10px;font-family:inherit;outline:none;transition:.2s;background:#fafaf8;}
.form-control:focus{border-color:#5DCAA5;box-shadow:0 0 0 3px rgba(93,202,165,0.15);background:#fff;}
.btn-login{width:100%;padding:12px;background:#1a1a18;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;transition:.2s;margin-top:4px;}
.btn-login:hover{background:#2c2c2a;}
.btn-register{width:100%;padding:12px;background:transparent;color:#1a1a18;border:1px solid rgba(0,0,0,0.15);border-radius:10px;font-size:14px;font-weight:500;font-family:inherit;cursor:pointer;transition:.2s;margin-top:8px;text-decoration:none;display:block;text-align:center;}
.btn-register:hover{background:#f5f4f0;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-danger{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.alert-info{background:#E6F1FB;color:#0C447C;border:1px solid #93C5F5;}
.divider{display:flex;align-items:center;gap:12px;margin:16px 0;color:#aaa;font-size:12px;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(0,0,0,0.08);}
</style>
</head>
<body>

<!-- HERO SECTION -->
<section class="hero" id="hero">
  <div class="hero-bg">
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
    <div class="circle circle-3"></div>
  </div>

  <!-- Floating cross icons -->
  <div class="cross-container">
    <?php
    $positions=[['10%','15%','0s'],['85%','20%','1s'],['20%','70%','2s'],['75%','65%','0.5s'],['50%','10%','1.5s'],['40%','80%','2.5s'],['90%','45%','3s'],['5%','50%','1s']];
    foreach($positions as $p):?>
    <div class="cross" style="left:<?=$p[0]?>;top:<?=$p[1]?>;animation-delay:<?=$p[2]?>;">
      <svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#5DCAA5" stroke-width="2.5" stroke-linecap="round"/></svg>
    </div>
    <?php endforeach;?>
  </div>

  <div class="hero-content">
    <div class="hero-badge">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#5DCAA5" stroke-width="2.5" stroke-linecap="round"/></svg>
      Hospital Information System
    </div>
    <h1 class="hero-title">
      Sehat Bersama<br><span>Hospital</span>
    </h1>
    <p class="hero-sub">Sistem informasi rumah sakit modern untuk manajemen pasien, dokter, dan layanan kesehatan yang lebih baik.</p>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hero-stat-val">9+</div><div class="hero-stat-lbl">Tables</div></div>
      <div class="hero-stat"><div class="hero-stat-val">3</div><div class="hero-stat-lbl">Triggers</div></div>
      <div class="hero-stat"><div class="hero-stat-val">100%</div><div class="hero-stat-lbl">Automated</div></div>
    </div>
    <button class="scroll-btn" onclick="document.getElementById('login-section').scrollIntoView({behavior:'smooth'})">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="#5DCAA5" stroke-width="2" stroke-linecap="round"/></svg>
      Login to System
    </button>
  </div>
</section>

<!-- LOGIN SECTION -->
<section class="login-section" id="login-section">
  <div class="login-wrapper">
    <div class="login-logo">
      <div class="cross-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="#5DCAA5" stroke-width="3" stroke-linecap="round"/></svg>
      </div>
      <h1>Sehat Bersama Hospital</h1>
      <p>Sign in to your account</p>
    </div>

    <!-- TABS -->
    <div class="tabs">
      <button class="tab <?=$tab==='admin'?'active':''?>" onclick="switchTab('admin')">Admin / Staff</button>
      <button class="tab <?=$tab==='patient'?'active':''?>" onclick="switchTab('patient')">Patient</button>
    </div>

    <!-- ADMIN LOGIN -->
    <div id="tab-admin" <?=$tab==='patient'?'style="display:none"':''?>>
      <?php if($error==='pending'):?>
        <div class="alert alert-danger" style="background:#FAEEDA;color:#854F0B;border:1px solid #F5C97A;">
        ⏳ Your account is pending approval from super admin. Please wait.
        </div>
        <?php elseif($error==='rejected'):?>
        <div class="alert alert-danger">
        ❌ Your registration has been rejected. Please contact the hospital administrator.
        </div>
      <?php elseif($error==='admin_failed'):?>
      <div class="alert alert-danger">Invalid email or password.</div>
      <?php elseif($error==='not_admin_email'):?>
      <div class="alert alert-danger">Please use your hospital email (@sehatbersama.hospital.com).</div>
      <?php endif;?>
      <div class="login-card">
        <form method="POST" action="process_login.php">
          <input type="hidden" name="type" value="admin">
          <div class="form-group">
            <label class="form-label">Hospital Email</label>
            <input type="email" name="email" class="form-control" placeholder="name@sehatbersama.hospital.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
          </div>
          <button type="submit" class="btn-login">Sign In as Admin</button>
        </form>
        <div class="divider">or</div>
        <a href="register_admin.php" class="btn-register">Register as New Admin</a>
      </div>
    </div>

    <!-- PATIENT LOGIN -->
    <div id="tab-patient" <?=$tab==='admin'?'style="display:none"':''?>>
      <?php if($error==='patient_failed'):?>
      <div class="alert alert-danger">Invalid email or password.</div>
      <?php elseif($error==='patient_not_found'):?>
      <div class="alert alert-danger">No patient record found with your data.</div>
      <?php endif;?>
      <div class="login-card">
        <div class="alert alert-info" style="margin-bottom:16px;">Already visited our hospital? Login with your registered account.</div>
        <form method="POST" action="process_login.php">
          <input type="hidden" name="type" value="patient">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
          </div>
          <button type="submit" class="btn-login">Sign In as Patient</button>
        </form>
        <div class="divider">or</div>
        <a href="register_patient.php" class="btn-register">Create Patient Account</a>
      </div>
    </div>
  </div>
</section>

<script>
function switchTab(tab){
  document.getElementById('tab-admin').style.display = tab==='admin' ? 'block' : 'none';
  document.getElementById('tab-patient').style.display = tab==='patient' ? 'block' : 'none';
  document.querySelectorAll('.tab').forEach((t,i)=>{
    t.classList.toggle('active', (tab==='admin'&&i===0)||(tab==='patient'&&i===1));
  });
}
</script>
</body>
</html>