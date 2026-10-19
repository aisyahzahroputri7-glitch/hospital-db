<?php
require_once '../config.php';
require_once '../auth.php';
requireAdmin();
if(!isSuperAdmin()){flash('Access denied. Super admin only.','danger');redirect('../index.php');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=intval($_POST['admin_id']??0);
    $action=$_POST['action']??'';
    if($action==='approve'){
        $conn->query("UPDATE admins SET status='approved' WHERE admin_id=$id");
        flash('Admin approved successfully.');
    } elseif($action==='reject'){
        $conn->query("UPDATE admins SET status='rejected' WHERE admin_id=$id");
        flash('Admin rejected.','danger');
    }
    // Mark notif as read
    $conn->query("UPDATE admin_notifications SET is_read=1 WHERE related_id=$id AND type='new_admin'");
    redirect('approve.php');
}

$pendingAdmins=$conn->query("SELECT * FROM admins WHERE status='pending' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$allAdmins=$conn->query("SELECT * FROM admins ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Admins — Sehat Bersama Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0efeb;color:#1a1a18;font-size:14px;display:flex;}
.sidebar{width:220px;background:#1a1a18;min-height:100vh;flex-shrink:0;position:sticky;top:0;height:100vh;}
.sidebar-brand{padding:22px 18px 18px;border-bottom:1px solid rgba(255,255,255,0.06);}
.sidebar-brand h1{font-size:14px;font-weight:600;color:#fff;}
.sidebar-brand span{font-size:11px;color:rgba(255,255,255,0.3);display:block;margin-top:2px;}
.nav-section{font-size:10px;font-weight:600;color:rgba(255,255,255,0.25);text-transform:uppercase;letter-spacing:.09em;padding:16px 18px 5px;}
.nav-link{display:flex;align-items:center;gap:9px;padding:9px 18px;color:rgba(255,255,255,0.45);font-size:13px;text-decoration:none;transition:.15s;border-left:2px solid transparent;}
.nav-link:hover{color:rgba(255,255,255,0.8);background:rgba(255,255,255,0.05);}
.nav-link.active{color:#fff;border-left-color:#5DCAA5;background:rgba(93,202,165,0.08);font-weight:500;}
.main{flex:1;display:flex;flex-direction:column;}
.topbar{background:#fff;border-bottom:1px solid rgba(0,0,0,0.08);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between;}
.topbar h2{font-size:15px;font-weight:600;}
.content{padding:24px 28px;}
.table-card{background:#fff;border-radius:12px;border:1px solid rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.table-head{padding:14px 18px;border-bottom:1px solid rgba(0,0,0,0.07);display:flex;align-items:center;justify-content:space-between;}
.table-title{font-size:14px;font-weight:600;}
.tbl{width:100%;border-collapse:collapse;}
.tbl th{padding:10px 18px;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;background:#faf9f5;border-bottom:1px solid rgba(0,0,0,0.07);text-align:left;}
.tbl td{padding:12px 18px;font-size:13px;border-bottom:1px solid rgba(0,0,0,0.05);}
.tbl tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:500;}
.badge-amber{background:#FAEEDA;color:#854F0B;}
.badge-teal{background:#E1F5EE;color:#0F6E56;}
.badge-red{background:#FCEBEB;color:#A32D2D;}
.badge-gray{background:#F1EFE8;color:#5F5E5A;}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:500;font-family:inherit;cursor:pointer;border:none;transition:.2s;}
.btn-approve{background:#E1F5EE;color:#0F6E56;}
.btn-approve:hover{background:#C5EDDB;}
.btn-reject{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.btn-reject:hover{background:#F7C1C1;}
.alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.alert-success{background:#EAF3DE;color:#3B6D11;border:1px solid #C0DD97;}
.alert-danger{background:#FCEBEB;color:#A32D2D;border:1px solid #F09595;}
.empty{text-align:center;padding:30px;color:#aaa;font-size:13px;}
.logout-link{color:rgba(255,255,255,0.4);font-size:12px;text-decoration:none;padding:14px 18px;display:block;margin-top:auto;border-top:1px solid rgba(255,255,255,0.06);}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-brand"><h1>Sehat Bersama Hospital</h1><span>Admin Panel</span></div>
  <div class="nav-section">Main</div>
  <a href="../index.php" class="nav-link">Dashboard</a>
  <div class="nav-section">Management</div>
  <a href="../patients/index.php" class="nav-link">Patients</a>
  <a href="../doctors/index.php" class="nav-link">Doctors</a>
  <a href="../rooms/index.php" class="nav-link">Rooms</a>
  <a href="../examination_schedules/index.php" class="nav-link">Schedules</a>
  <a href="../medical_records/index.php" class="nav-link">Medical Records</a>
  <a href="../payments/index.php" class="nav-link">Payments</a>
  <?php if(isSuperAdmin()):?>
  <div class="nav-section">Super Admin</div>
  <a href="approve.php" class="nav-link active">Manage Admins</a>
  <?php endif;?>
  <a href="../logout.php" class="logout-link">Logout — <?=htmlspecialchars($_SESSION['admin_name'])?></a>
</div>

<div class="main">
  <div class="topbar"><h2>Manage Admin Accounts</h2></div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?>
    <div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div>
    <?php unset($_SESSION['flash']);endif;?>

    <div class="table-card">
      <div class="table-head"><span class="table-title">⏳ Pending Approval (<?=count($pendingAdmins)?>)</span></div>
      <table class="tbl">
        <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($pendingAdmins)):?>
          <tr><td colspan="4" class="empty">No pending registrations</td></tr>
        <?php else:foreach($pendingAdmins as $a):?>
          <tr>
            <td style="font-weight:500;"><?=htmlspecialchars($a['name'])?></td>
            <td style="color:#888;"><?=htmlspecialchars($a['email'])?></td>
            <td style="font-size:12px;color:#888;"><?=date('d M Y H:i',strtotime($a['created_at']))?></td>
            <td>
              <div style="display:flex;gap:8px;">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="admin_id" value="<?=$a['admin_id']?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="btn btn-approve">✓ Approve</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this admin?')">
                  <input type="hidden" name="admin_id" value="<?=$a['admin_id']?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn btn-reject">✗ Reject</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>

    <div class="table-card">
      <div class="table-head"><span class="table-title">All Admin Accounts</span></div>
      <table class="tbl">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th></tr></thead>
        <tbody>
        <?php foreach($allAdmins as $a):
          $sc=['approved'=>'badge-teal','pending'=>'badge-amber','rejected'=>'badge-red'];
        ?>
          <tr>
            <td style="font-weight:500;"><?=htmlspecialchars($a['name'])?></td>
            <td style="color:#888;font-size:12px;"><?=htmlspecialchars($a['email'])?></td>
            <td><span class="badge <?=$a['is_super']?'badge-teal':'badge-gray'?>"><?=$a['is_super']?'Super Admin':'Admin'?></span></td>
            <td><span class="badge <?=$sc[$a['status']]??'badge-gray'?>"><?=ucfirst($a['status'])?></span></td>
            <td style="font-size:12px;color:#888;"><?=date('d M Y',strtotime($a['created_at']))?></td>
          </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>