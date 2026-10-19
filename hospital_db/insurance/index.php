<?php

require_once '../auth.php';
requireAdmin();

require_once '../config.php';
$pageTitle='Insurance'; $activePage='insurance'; $base='..';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='add'){
    $name=sanitize($conn,$_POST['insurance_name']??''); $disc=floatval($_POST['discount_cover']??0);
    if($name){$conn->query("INSERT INTO insurance (insurance_name,discount_cover) VALUES ('$name',$disc)");flash("Insurance $name added.");}
    redirect('index.php');
  }
  if($act==='edit'){
    $id=intval($_POST['id']??0); $name=sanitize($conn,$_POST['insurance_name']??''); $disc=floatval($_POST['discount_cover']??0);
    $stmt=$conn->prepare("UPDATE insurance SET insurance_name=?,discount_cover=? WHERE insurance_id=?");
    $stmt->bind_param('sdi',$name,$disc,$id);$stmt->execute();
    flash("Insurance $name updated.");redirect('index.php');
  }
  if($act==='delete'){
    $id=intval($_POST['id']??0);
    $row=$conn->query("SELECT insurance_name FROM insurance WHERE insurance_id=$id")->fetch_assoc();
    if($row){$conn->query("DELETE FROM insurance WHERE insurance_id=$id");flash("Insurance {$row['insurance_name']} deleted.");}
    redirect('index.php');
  }
}
$insurances=$conn->query("SELECT i.*,(SELECT COUNT(*) FROM patients p WHERE p.insurance_id=i.insurance_id) as patient_count FROM insurance i ORDER BY insurance_id")->fetch_all(MYSQLI_ASSOC);
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Insurance</h2><p><?=count($insurances)?> insurance providers</p></div>
    <button onclick="document.getElementById('modalAdd').classList.add('open')" class="btn btn-accent"><svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> Add Insurance</button>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="table-card"><table class="tbl">
      <thead><tr><th>#</th><th>Insurance Name</th><th>Discount Cover (%)</th><th>Patients</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($insurances)):?><tr><td class="tbl-empty" colspan="5">No insurance data.</td></tr>
      <?php else:foreach($insurances as $i=>$ins):?>
        <tr>
          <td style="color:var(--text-3);font-size:12px;"><?=$i+1?></td>
          <td style="font-weight:500;"><?=htmlspecialchars($ins['insurance_name'])?></td>
          <td><span class="badge badge-teal"><?=$ins['discount_cover']?>%</span></td>
          <td><?=$ins['patient_count']?> patients</td>
          <td><div style="display:flex;gap:5px;">
            <button onclick='editIns(<?=json_encode($ins)?>)' class="btn btn-ghost btn-sm">Edit</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this insurance?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$ins['insurance_id']?>"><button type="submit" class="btn btn-danger btn-sm">Delete</button></form>
          </div></td>
        </tr>
      <?php endforeach;endif;?>
      </tbody>
    </table></div>
  </div>

  <div class="modal-bg" id="modalAdd"><div class="modal"><h3>Add Insurance</h3>
    <form method="POST" style="margin-top:16px;"><input type="hidden" name="action" value="add">
      <div class="form-group"><label class="form-label">Insurance name <span>*</span></label><input type="text" name="insurance_name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Discount cover (%)</label><input type="number" name="discount_cover" class="form-control" value="0" min="0" max="100" step="0.01"></div>
      <div class="modal-actions" style="margin-top:16px;"><button type="button" onclick="document.getElementById('modalAdd').classList.remove('open')" class="btn btn-ghost">Cancel</button><button type="submit" class="btn btn-accent">Save</button></div>
    </form>
  </div></div>

  <div class="modal-bg" id="modalEdit"><div class="modal"><h3>Edit Insurance</h3>
    <form method="POST" style="margin-top:16px;"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="editId">
      <div class="form-group"><label class="form-label">Insurance name <span>*</span></label><input type="text" name="insurance_name" id="editName" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Discount cover (%)</label><input type="number" name="discount_cover" id="editDisc" class="form-control" min="0" max="100" step="0.01"></div>
      <div class="modal-actions" style="margin-top:16px;"><button type="button" onclick="document.getElementById('modalEdit').classList.remove('open')" class="btn btn-ghost">Cancel</button><button type="submit" class="btn btn-accent">Save Changes</button></div>
    </form>
  </div></div>

<script>function editIns(r){document.getElementById('editId').value=r.insurance_id;document.getElementById('editName').value=r.insurance_name;document.getElementById('editDisc').value=r.discount_cover;document.getElementById('modalEdit').classList.add('open');}</script>
<?php include '../assets/footer.php';?>