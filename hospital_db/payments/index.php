<?php

require_once '../auth.php';
requireAdmin();

require_once '../config.php';
$pageTitle='Payments'; $activePage='payments'; $base='..';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='pay'){
    $id=intval($_POST['id']??0);
    $conn->query("UPDATE payments SET status='Paid' WHERE payment_id=$id");
    flash("Payment marked as paid.");redirect('index.php');
  }
  if($act==='cancel'){
    $id=intval($_POST['id']??0);
    $conn->query("UPDATE payments SET status='Cancelled' WHERE payment_id=$id");
    flash("Payment cancelled.","danger");redirect('index.php');
  }
}
$statusFilter=$_GET['status']??'';
$wsql=$statusFilter?"WHERE py.status='".sanitize($conn,$statusFilter)."'":'';
$payments=$conn->query("
  SELECT py.*,mr.visit_date,mr.diagnosis,p.patient_name,ins.insurance_name,ins.discount_cover,d.doctor_name,d.service_fee
  FROM payments py
  JOIN medical_records mr ON py.medical_record_id=mr.medical_record_id
  JOIN patients p ON mr.patient_id=p.patient_id
  JOIN doctors d ON mr.doctor_id=d.doctor_id
  LEFT JOIN insurance ins ON p.insurance_id=ins.insurance_id
  $wsql
  ORDER BY py.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
$totalPaid=$conn->query("SELECT SUM(total_amount) s FROM payments WHERE status='Paid'")->fetch_assoc()['s']??0;
$totalPending=$conn->query("SELECT COUNT(*) c FROM payments WHERE status='Pending'")->fetch_assoc()['c'];
$totalAll=$conn->query("SELECT COUNT(*) c FROM payments")->fetch_assoc()['c'];
$statusColors=['Paid'=>'badge-teal','Pending'=>'badge-amber','Cancelled'=>'badge-red'];
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Payments</h2><p><?=$totalAll?> payment records</p></div></div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Revenue</div><div class="stat-val" style="font-size:20px;">Rp <?=number_format($totalPaid,0,',','.')?></div><div class="stat-sub">paid</div></div>
      <div class="stat s3"><div class="stat-label">Pending</div><div class="stat-val"><?=$totalPending?></div><div class="stat-sub">awaiting payment</div></div>
      <div class="stat s2"><div class="stat-label">Total Records</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">all time</div></div>
    </div>
    <form method="GET" class="toolbar">
      <select name="status" class="filter-select"><option value="">All status</option><?php foreach(['Pending','Paid','Cancelled'] as $s):?><option value="<?=$s?>" <?=$statusFilter===$s?'selected':''?>><?=$s?></option><?php endforeach;?></select>
      <button type="submit" class="btn btn-ghost">Filter</button>
      <?php if($statusFilter):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>
    <div class="table-card"><table class="tbl">
      <thead><tr><th>#</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Visit Date</th><th>Insurance</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($payments)):?><tr><td class="tbl-empty" colspan="9">No payments found.</td></tr>
      <?php else:foreach($payments as $i=>$py):
        $sc=$statusColors[$py['status']]??'badge-gray';
      ?>
        <tr>
          <td style="color:var(--text-3);font-size:12px;"><?=$i+1?></td>
          <td style="font-weight:500;"><?=htmlspecialchars($py['patient_name'])?></td>
          <td style="font-size:12px;"><?=htmlspecialchars($py['doctor_name'])?></td>
          <td><span class="badge badge-blue" style="font-size:10px;"><?=htmlspecialchars($py['diagnosis']??'-')?></span></td>
          <td style="font-size:12px;"><?=date('d M Y',strtotime($py['visit_date']))?></td>
          <td style="font-size:12px;"><?=htmlspecialchars($py['insurance_name']??'None')?> <?=$py['discount_cover']>0?'<span class="badge badge-teal" style="font-size:10px;">'.$py['discount_cover'].'%</span>':''?></td>
          <td style="font-weight:600;">Rp <?=number_format($py['total_amount'],0,',','.')?></td>
          <td><span class="badge <?=$sc?>"><?=htmlspecialchars($py['status'])?></span></td>
          <td><div style="display:flex;gap:5px;flex-wrap:wrap;">
            <?php if($py['status']==='Pending'):?>
            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="pay"><input type="hidden" name="id" value="<?=$py['payment_id']?>"><button type="submit" class="btn btn-accent btn-sm">Pay</button></form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this payment?')"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?=$py['payment_id']?>"><button type="submit" class="btn btn-danger btn-sm">Cancel</button></form>
            <?php endif;?>
          </div></td>
        </tr>
      <?php endforeach;endif;?>
      </tbody>
    </table></div>
  </div>
<?php include '../assets/footer.php';?>