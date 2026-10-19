<?php

require_once '../auth.php';
requireAdmin();

require_once '../config.php';
$pageTitle='Patients'; $activePage='patients'; $base='..';
$search=trim($_GET['search']??''); $blood=$_GET['blood']??'';
$per=10; $page=max(1,intval($_GET['page']??1)); $offset=($page-1)*$per;
$where=[]; $params=[]; $types='';
if($search!==''){$where[]="(patient_name LIKE ? OR phone_number LIKE ?)";$params[]="%$search%";$params[]="%$search%";$types.='ss';}
if($blood!==''){$where[]="blood_type=?";$params[]=$blood;$types.='s';}
$wsql=$where?' WHERE '.implode(' AND ',$where):'';
$cntStmt=$conn->prepare("SELECT COUNT(*) c FROM patients$wsql");
if($params)$cntStmt->bind_param($types,...$params);$cntStmt->execute();
$total=$cntStmt->get_result()->fetch_assoc()['c']; $totalPages=ceil($total/$per);
$stmt=$conn->prepare("SELECT * FROM patients$wsql ORDER BY patient_id DESC LIMIT ? OFFSET ?");
$ap=$params;$ap[]=$per;$ap[]=$offset;$stmt->bind_param($types.'ii',...$ap);$stmt->execute();
$patients=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalAll=$conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$totalMale=$conn->query("SELECT COUNT(*) c FROM patients WHERE gender='Male'")->fetch_assoc()['c'];
$totalFemale=$conn->query("SELECT COUNT(*) c FROM patients WHERE gender='Female'")->fetch_assoc()['c'];
$avgColors=['av-teal','av-purple','av-blue','av-amber','av-red','av-green'];
function ac($i){global $avgColors;return $avgColors[$i%count($avgColors)];}
function ini($n){$p=explode(' ',$n);return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));}
include '../assets/sidebar.php';
?>
  <div class="topbar">
    <div class="topbar-left"><h2>Patients</h2><p>Total <?=$totalAll?> patients registered</p></div>
    <a href="create.php" class="btn btn-accent"><svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> Add Patient</a>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Patients</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">all registered</div></div>
      <div class="stat s2"><div class="stat-label">Male</div><div class="stat-val"><?=$totalMale?></div><div class="stat-sub">male patients</div></div>
      <div class="stat s4"><div class="stat-label">Female</div><div class="stat-val"><?=$totalFemale?></div><div class="stat-sub">female patients</div></div>
    </div>
    <form method="GET" class="toolbar">
      <div class="search-wrap">
        <svg viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        <input type="text" name="search" class="search-input" placeholder="Search name or phone..." value="<?=htmlspecialchars($search)?>">
      </div>
      <select name="blood" class="filter-select">
        <option value="">All blood types</option>
        <?php foreach(['A','B','AB','O'] as $b):?><option value="<?=$b?>" <?=$blood===$b?'selected':''?>>Blood Type <?=$b?></option><?php endforeach;?>
      </select>
      <button type="submit" class="btn btn-ghost">Search</button>
      <?php if($search||$blood):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>
    <div class="table-card">
      <table class="tbl">
        <thead><tr><th>#</th><th>Patient</th><th>Date of Birth</th><th>Gender</th><th>Blood Type</th><th>Phone</th><th>Records</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($patients)):?><tr><td class="tbl-empty" colspan="8">No patients found.</td></tr>
        <?php else:foreach($patients as $i=>$p):
          $recCount=$conn->query("SELECT COUNT(*) c FROM medical_records WHERE patient_id={$p['patient_id']}")->fetch_assoc()['c'];
          $gBadge=$p['gender']==='Male'?'badge-blue':'badge-purple';
        ?>
          <tr>
            <td style="color:var(--text-3);font-size:12px;"><?=$offset+$i+1?></td>
            <td><div class="tbl-info"><div class="avatar <?=ac($i)?>"><?=ini($p['patient_name'])?></div><div><div class="tbl-name"><?=htmlspecialchars($p['patient_name'])?></div><div class="tbl-sub"><?=htmlspecialchars($p['address']??'-')?></div></div></div></td>
            <td style="font-size:12px;"><?=date('d M Y',strtotime($p['date_of_birth']))?></td>
            <td><span class="badge <?=$gBadge?>"><?=htmlspecialchars($p['gender'])?></span></td>
            <td><span class="badge badge-gray"><?=htmlspecialchars($p['blood_type']??'-')?></span></td>
            <td style="font-size:12px;"><?=htmlspecialchars($p['phone_number']??'-')?></td>
            <td><a href="../medical_records/index.php?patient=<?=$p['patient_id']?>" style="font-size:12px;color:var(--blue);text-decoration:none;"><?=$recCount?> records</a></td>
            <td><div style="display:flex;gap:5px;">
              <a href="detail.php?id=<?=$p['patient_id']?>" class="btn btn-ghost btn-sm">Detail</a>
              <a href="edit.php?id=<?=$p['patient_id']?>" class="btn btn-ghost btn-sm">Edit</a>
              <button onclick="confirmDelete(<?=$p['patient_id']?>,'<?=htmlspecialchars($p['patient_name'])?>')" class="btn btn-danger btn-sm">Delete</button>
            </div></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
      <?php if($totalPages>1):?>
      <div class="pagination">
        <span class="page-info">Showing <?=min($offset+1,$total)?>–<?=min($offset+$per,$total)?> of <?=$total?></span>
        <?php for($p2=1;$p2<=$totalPages;$p2++):?><a href="?page=<?=$p2?>&search=<?=urlencode($search)?>&blood=<?=urlencode($blood)?>" class="page-btn <?=$p2===$page?'active':''?>"><?=$p2?></a><?php endfor;?>
      </div>
      <?php endif;?>
    </div>
  </div>
  <div class="modal-bg" id="deleteModal">
    <div class="modal"><h3>Delete Patient</h3><p>Are you sure you want to delete <strong id="deleteName"></strong>? All related records will also be deleted.</p>
    <div class="modal-actions"><button onclick="document.getElementById('deleteModal').classList.remove('open')" class="btn btn-ghost">Cancel</button><a id="deleteLink" href="#" class="btn btn-danger">Yes, Delete</a></div></div>
  </div>
<script>function confirmDelete(id,name){document.getElementById('deleteName').textContent=name;document.getElementById('deleteLink').href='delete.php?id='+id;document.getElementById('deleteModal').classList.add('open');}</script>
<?php include '../assets/footer.php';?>
