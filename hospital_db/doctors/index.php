<?php
require_once '../auth.php';
requireAdmin();

require_once '../config.php';
$pageTitle='Doctors'; $activePage='doctors'; $base='..';
$search=trim($_GET['search']??''); $spes=$_GET['specialization']??'';
$per=10; $page=max(1,intval($_GET['page']??1)); $offset=($page-1)*$per;
$where=[]; $params=[]; $types='';
if($search!==''){$where[]="(doctor_name LIKE ? OR phone_number LIKE ?)";$params[]="%$search%";$params[]="%$search%";$types.='ss';}
if($spes!==''){$where[]="specialization=?";$params[]=$spes;$types.='s';}
$wsql=$where?' WHERE '.implode(' AND ',$where):'';
$cntStmt=$conn->prepare("SELECT COUNT(*) c FROM doctors$wsql");
if($params)$cntStmt->bind_param($types,...$params);$cntStmt->execute();
$total=$cntStmt->get_result()->fetch_assoc()['c'];
$stmt=$conn->prepare("SELECT * FROM doctors$wsql ORDER BY doctor_id ASC LIMIT ? OFFSET ?");
$ap=$params;$ap[]=$per;$ap[]=$offset;$stmt->bind_param($types.'ii',...$ap);$stmt->execute();
$doctors=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$spesList=$conn->query("SELECT DISTINCT specialization FROM doctors ORDER BY specialization")->fetch_all(MYSQLI_ASSOC);
$totalAll=$conn->query("SELECT COUNT(*) c FROM doctors")->fetch_assoc()['c'];
$avgColors=['av-teal','av-purple','av-blue','av-amber','av-red','av-green'];
function ac($i){global $avgColors;return $avgColors[$i%count($avgColors)];}
function ini($n){$p=explode(' ',$n);return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));}
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Doctors</h2><p>Total <?=$totalAll?> doctors registered</p></div><a href="create.php" class="btn btn-accent"><svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> Add Doctor</a></div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Doctors</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">registered</div></div>
      <div class="stat s2"><div class="stat-label">Specializations</div><div class="stat-val"><?=$conn->query("SELECT COUNT(DISTINCT specialization) c FROM doctors")->fetch_assoc()['c']?></div><div class="stat-sub">types</div></div>
      <div class="stat s3"><div class="stat-label">Results</div><div class="stat-val"><?=$total?></div><div class="stat-sub">found</div></div>
    </div>
    <form method="GET" class="toolbar">
      <div class="search-wrap"><svg viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg><input type="text" name="search" class="search-input" placeholder="Search doctor name..." value="<?=htmlspecialchars($search)?>"></div>
      <select name="specialization" class="filter-select"><option value="">All specializations</option><?php foreach($spesList as $s):?><option value="<?=htmlspecialchars($s['specialization'])?>" <?=$spes===$s['specialization']?'selected':''?>><?=htmlspecialchars($s['specialization'])?></option><?php endforeach;?></select>
      <button type="submit" class="btn btn-ghost">Search</button><?php if($search||$spes):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>
    <div class="table-card"><table class="tbl">
      <thead><tr><th>#</th><th>Doctor</th><th>Specialization</th><th>Phone</th><th>Practice Schedule</th><th>Patients</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($doctors)):?><tr><td class="tbl-empty" colspan="7">No doctors found.</td></tr>
      <?php else:foreach($doctors as $i=>$d):
        $tot=$conn->query("SELECT COUNT(*) c FROM medical_records WHERE doctor_id={$d['doctor_id']}")->fetch_assoc()['c'];
        $bc=['General'=>'badge-teal','Internal Medicine'=>'badge-blue','Surgery'=>'badge-red','Pediatrics'=>'badge-purple','Cardiology'=>'badge-amber'];
        $b=$bc[$d['specialization']]??'badge-gray';
      ?>
        <tr>
          <td style="color:var(--text-3);font-size:12px;"><?=$offset+$i+1?></td>
          <td><div class="tbl-info"><div class="avatar <?=ac($i)?>"><?=ini($d['doctor_name'])?></div><div class="tbl-name"><?=htmlspecialchars($d['doctor_name'])?></div></div></td>
          <td><span class="badge <?=$b?>"><?=htmlspecialchars($d['specialization'])?></span></td>
          <td style="font-size:12px;"><?=htmlspecialchars($d['phone_number']??'-')?></td>
          <td style="font-size:12px;color:var(--text-2);"><?=htmlspecialchars($d['practice_schedule']??'-')?></td>
          <td><span style="font-weight:500;"><?=$tot?></span><span style="font-size:11px;color:var(--text-3);"> patients</span></td>
          <td><div style="display:flex;gap:5px;"><a href="edit.php?id=<?=$d['doctor_id']?>" class="btn btn-ghost btn-sm">Edit</a><button onclick="confirmDelete(<?=$d['doctor_id']?>,'<?=htmlspecialchars($d['doctor_name'])?>')" class="btn btn-danger btn-sm">Delete</button></div></td>
        </tr>
      <?php endforeach;endif;?>
      </tbody>
    </table></div>
  </div>
  <div class="modal-bg" id="deleteModal"><div class="modal"><h3>Delete Doctor</h3><p>Are you sure you want to delete <strong id="deleteName"></strong>?</p><div class="modal-actions"><button onclick="document.getElementById('deleteModal').classList.remove('open')" class="btn btn-ghost">Cancel</button><a id="deleteLink" href="#" class="btn btn-danger">Yes, Delete</a></div></div></div>
<script>function confirmDelete(id,name){document.getElementById('deleteName').textContent=name;document.getElementById('deleteLink').href='delete.php?id='+id;document.getElementById('deleteModal').classList.add('open');}</script>
<?php include '../assets/footer.php';?>
