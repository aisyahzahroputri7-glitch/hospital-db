<?php

require_once '../auth.php';
requireAdmin();

require_once '../config.php';
$pageTitle='Medical Records'; $activePage='medical_records'; $base='..';
$search=trim($_GET['search']??''); $docFilter=intval($_GET['doctor']??0); $patFilter=intval($_GET['patient']??0);
$where=[]; $params=[]; $types='';
if($search!==''){$where[]="(p.patient_name LIKE ? OR mr.diagnosis LIKE ? OR mr.complaints LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";$types.='sss';}
if($docFilter){$where[]="mr.doctor_id=?";$params[]=$docFilter;$types.='i';}
if($patFilter){$where[]="mr.patient_id=?";$params[]=$patFilter;$types.='i';}
$wsql=$where?' WHERE '.implode(' AND ',$where):'';
$sql="SELECT mr.*,p.patient_name,p.blood_type,d.doctor_name,d.specialization FROM medical_records mr JOIN patients p ON mr.patient_id=p.patient_id JOIN doctors d ON mr.doctor_id=d.doctor_id$wsql ORDER BY mr.visit_date DESC";
$stmt=$conn->prepare($sql); if($params)$stmt->bind_param($types,...$params);
$stmt->execute(); $records=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$doctorList=$conn->query("SELECT doctor_id,doctor_name FROM doctors ORDER BY doctor_name")->fetch_all(MYSQLI_ASSOC);
$totalAll=$conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'];
$thisMonth=$conn->query("SELECT COUNT(*) c FROM medical_records WHERE MONTH(visit_date)=MONTH(NOW()) AND YEAR(visit_date)=YEAR(NOW())")->fetch_assoc()['c'];
$uniquePatients=$conn->query("SELECT COUNT(DISTINCT patient_id) c FROM medical_records")->fetch_assoc()['c'];
$avgColors=['av-teal','av-purple','av-blue','av-amber','av-red','av-green'];
function ac($i){global $avgColors;return $avgColors[$i%count($avgColors)];}
function ini($n){$p=explode(' ',$n);return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));}
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Medical Records</h2><p><?=count($records)?> records <?=($search||$docFilter||$patFilter)?'found':'stored'?></p></div>
    <a href="create.php" class="btn btn-accent"><svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> Add Record</a>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Records</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">all time</div></div>
      <div class="stat s2"><div class="stat-label">This Month</div><div class="stat-val"><?=$thisMonth?></div><div class="stat-sub"><?=date('F Y')?></div></div>
      <div class="stat s3"><div class="stat-label">Unique Patients</div><div class="stat-val"><?=$uniquePatients?></div><div class="stat-sub">ever visited</div></div>
    </div>
    <form method="GET" class="toolbar">
      <div class="search-wrap"><svg viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg><input type="text" name="search" class="search-input" placeholder="Search patient or diagnosis..." value="<?=htmlspecialchars($search)?>"></div>
      <select name="doctor" class="filter-select"><option value="">All doctors</option><?php foreach($doctorList as $d):?><option value="<?=$d['doctor_id']?>" <?=$docFilter===$d['doctor_id']?'selected':''?>><?=htmlspecialchars($d['doctor_name'])?></option><?php endforeach;?></select>
      <button type="submit" class="btn btn-ghost">Filter</button>
      <?php if($search||$docFilter||$patFilter):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>
    <?php if(empty($records)):?>
    <div style="text-align:center;padding:60px 20px;color:var(--text-3);">
      <p style="font-size:14px;font-weight:500;">No medical records found</p>
      <p style="font-size:12px;margin-top:4px;"><a href="create.php" style="color:var(--blue);">Add a new medical record</a></p>
    </div>
    <?php else:?>
    <div class="card-grid">
      <?php foreach($records as $i=>$r):?>
      <div class="rec-card">
        <div class="rec-head">
          <div class="rec-patient"><div class="avatar <?=ac($i)?>"><?=ini($r['patient_name'])?></div><div><div class="pname"><?=htmlspecialchars($r['patient_name'])?></div><div class="pdate"><?=date('d M Y',strtotime($r['visit_date']))?></div></div></div>
          <span class="badge badge-blue" style="font-size:10px;"><?=htmlspecialchars($r['diagnosis'])?></span>
        </div>
        <div class="divider"></div>
        <div class="field-lbl">Complaints</div><div class="field-val"><?=htmlspecialchars($r['complaints'])?></div>
        <div class="field-lbl">Prescription</div>
        <div class="field-val"><?=htmlspecialchars($r['prescription']??'-')?></div>

        <?php
        // Ambil obat dari prescription_details
        $meds=$conn->query("SELECT pd.quantity, m.medicine_name, m.unit_price FROM prescription_details pd JOIN medicines m ON pd.medicine_id=m.medicine_id WHERE pd.medical_record_id={$r['medical_record_id']}")->fetch_all(MYSQLI_ASSOC);
        if(!empty($meds)):?>
        <div class="field-lbl" style="margin-top:6px;">Medicines</div>
        <div style="display:flex;flex-direction:column;gap:4px;margin-top:2px;">
          <?php foreach($meds as $med):?>
          <div style="display:flex;justify-content:space-between;font-size:12px;background:var(--bg);padding:4px 8px;border-radius:6px;">
            <span><?=htmlspecialchars($med['medicine_name'])?></span>
            <span style="color:var(--text-3);"><?=$med['quantity']?> pcs</span>
          </div>
          <?php endforeach;?>
        </div>
        <?php endif;?>
        <?php if($r['blood_type']):?><span class="badge badge-gray" style="font-size:10px;margin-top:2px;">Blood Type <?=htmlspecialchars($r['blood_type'])?></span><?php endif;?>
        <div class="rec-foot">
          <span class="rec-doc"><?=htmlspecialchars($r['doctor_name'])?> · <?=htmlspecialchars($r['specialization'])?></span>
          <div class="rec-actions"><a href="edit.php?id=<?=$r['medical_record_id']?>" class="btn btn-ghost btn-sm">Edit</a>
        <a href="print.php?id=<?=$r['medical_record_id']?>" target="_blank" class="btn btn-ghost btn-sm" style="color:var(--blue);">Print</a><button onclick="confirmDelete(<?=$r['medical_record_id']?>,'<?=htmlspecialchars($r['patient_name'])?>')" class="btn btn-danger btn-sm">Delete</button></div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
  <div class="modal-bg" id="deleteModal"><div class="modal"><h3>Delete Record</h3><p>Are you sure you want to delete the medical record of <strong id="deleteName"></strong>?</p><div class="modal-actions"><button onclick="document.getElementById('deleteModal').classList.remove('open')" class="btn btn-ghost">Cancel</button><a id="deleteLink" href="#" class="btn btn-danger">Yes, Delete</a></div></div></div>
<script>function confirmDelete(id,name){document.getElementById('deleteName').textContent=name;document.getElementById('deleteLink').href='delete.php?id='+id;document.getElementById('deleteModal').classList.add('open');}</script>
<?php include '../assets/footer.php';?>
