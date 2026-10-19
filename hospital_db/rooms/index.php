<?php
require_once '../config.php';
$pageTitle='Rooms'; $activePage='rooms'; $base='..';
$typeFilter=$_GET['type']??''; $statusFilter=$_GET['status']??'';
$where=[]; $params=[]; $types='';
if($typeFilter!==''){$where[]="room_type=?";$params[]=$typeFilter;$types.='s';}
if($statusFilter!==''){$where[]="status=?";$params[]=$statusFilter;$types.='s';}
$wsql=$where?' WHERE '.implode(' AND ',$where):'';
$stmt=$conn->prepare("SELECT * FROM rooms$wsql ORDER BY room_type,room_name");
if($params)$stmt->bind_param($types,...$params);$stmt->execute();
$rooms=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalAll=$conn->query("SELECT COUNT(*) c FROM rooms")->fetch_assoc()['c'];
$totalAvail=$conn->query("SELECT COUNT(*) c FROM rooms WHERE status='Available'")->fetch_assoc()['c'];
$totalFull=$conn->query("SELECT COUNT(*) c FROM rooms WHERE status='Full'")->fetch_assoc()['c'];
$typeColors=['Clinic'=>'badge-teal','Emergency'=>'badge-red','Inpatient'=>'badge-blue','Surgery'=>'badge-amber','ICU'=>'badge-purple'];
$statusColors=['Available'=>'badge-teal','Full'=>'badge-red','Inactive'=>'badge-gray'];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='add'){
    $rn=sanitize($conn,$_POST['room_name']??''); $rt=sanitize($conn,$_POST['room_type']??'');
    $cap=intval($_POST['capacity']??1); $st=sanitize($conn,$_POST['status']??'Available');
    $bed=sanitize($conn,$_POST['bed_number']??'');
    if($rn&&$rt){
      $stmt2=$conn->prepare("INSERT INTO rooms (room_name,room_type,capacity,bed_number,status) VALUES (?,?,?,?,?)");
      $stmt2->bind_param('ssiss',$rn,$rt,$cap,$bed,$st);$stmt2->execute();
    }
    flash("Room $rn added successfully.");redirect('index.php');
  }
  if($act==='edit'){
    $id=intval($_POST['id']??0);
    $rn=sanitize($conn,$_POST['room_name']??''); $rt=sanitize($conn,$_POST['room_type']??'');
    $cap=intval($_POST['capacity']??1); $st=sanitize($conn,$_POST['status']??'Available');
    $bed=sanitize($conn,$_POST['bed_number']??'');
    $stmt2=$conn->prepare("UPDATE rooms SET room_name=?,room_type=?,capacity=?,bed_number=?,status=? WHERE room_id=?");
    $stmt2->bind_param('ssissi',$rn,$rt,$cap,$bed,$st,$id);$stmt2->execute();
    flash("Room $rn updated successfully.");redirect('index.php');
  }
}
include '../assets/sidebar.php';
?>
  <div class="topbar"><div class="topbar-left"><h2>Rooms</h2><p><?=$totalAll?> rooms registered</p></div>
    <button onclick="document.getElementById('modalAdd').classList.add('open')" class="btn btn-accent"><svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> Add Room</button>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>
    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Rooms</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">all types</div></div>
      <div class="stat s2"><div class="stat-label">Available</div><div class="stat-val"><?=$totalAvail?></div><div class="stat-sub">ready to use</div></div>
      <div class="stat s4"><div class="stat-label">Full</div><div class="stat-val"><?=$totalFull?></div><div class="stat-sub">at capacity</div></div>
    </div>
    <form method="GET" class="toolbar">
      <select name="type" class="filter-select"><option value="">All types</option><?php foreach(['Clinic','Emergency','Inpatient','Surgery','ICU'] as $t):?><option value="<?=$t?>" <?=$typeFilter===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select>
      <select name="status" class="filter-select"><option value="">All status</option><?php foreach(['Available','Full','Inactive'] as $s):?><option value="<?=$s?>" <?=$statusFilter===$s?'selected':''?>><?=$s?></option><?php endforeach;?></select>
      <button type="submit" class="btn btn-ghost">Filter</button>
      <?php if($typeFilter||$statusFilter):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>
    <div class="table-card"><table class="tbl">
      <thead><tr><th>#</th><th>Room Name</th><th>Type</th><th>Capacity</th><th>Bed Number</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($rooms)):?><tr><td class="tbl-empty" colspan="7">No rooms found.</td></tr>
      <?php else:foreach($rooms as $i=>$r):
        $tc=$typeColors[$r['room_type']]??'badge-gray';
        $sc=$statusColors[$r['status']]??'badge-gray';
      ?>
        <tr>
          <td style="color:var(--text-3);font-size:12px;"><?=$i+1?></td>
          <td><a href="beds.php?id=<?=$r['room_id']?>" style="font-size:13px;font-weight:500;color:var(--blue);text-decoration:none;"><?=htmlspecialchars($r['room_name'])?></a></td>
          <td><span class="badge <?=$tc?>"><?=htmlspecialchars($r['room_type'])?></span></td>
          <td><span style="font-weight:500;"><?=$r['capacity']?></span><span style="font-size:11px;color:var(--text-3);"> beds</span></td>
          <td style="font-size:12px;color:var(--text-2);"><?=htmlspecialchars($r['bed_number']??'-')?></td>
          <td><span class="badge <?=$sc?>"><?=htmlspecialchars($r['status'])?></span></td>
          <td><div style="display:flex;gap:5px;"><button onclick='editRoom(<?=json_encode($r)?>)' class="btn btn-ghost btn-sm">Edit</button><button onclick="confirmDelete(<?=$r['room_id']?>,'<?=htmlspecialchars($r['room_name'])?>')" class="btn btn-danger btn-sm">Delete</button></div></td>
        </tr>
      <?php endforeach;endif;?>
      </tbody>
    </table></div>
  </div>

  <!-- Modal Add -->
  <div class="modal-bg" id="modalAdd">
    <div class="modal" style="width:420px;"><h3>Add Room</h3>
      <form method="POST" style="margin-top:16px;"><input type="hidden" name="action" value="add">
        <div class="form-group"><label class="form-label">Room name <span>*</span></label><input type="text" name="room_name" class="form-control" placeholder="e.g. General Clinic A" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Type <span>*</span></label>
            <select name="room_type" class="form-control" required><?php foreach(['Clinic','Emergency','Inpatient','Surgery','ICU'] as $t):?><option><?=$t?></option><?php endforeach;?></select>
          </div>
          <div class="form-group"><label class="form-label">Capacity</label>
            <input type="number" name="capacity" id="addCap" class="form-control" value="1" min="1" oninput="generateBedOptions('addBed', this.value, '')">
          </div>
        </div>
        <div class="form-group"><label class="form-label">Bed Number</label>
          <select name="bed_number" id="addBed" class="form-control">
            <option value="">-- Set capacity first --</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" class="form-control"><?php foreach(['Available','Full','Inactive'] as $s):?><option><?=$s?></option><?php endforeach;?></select>
        </div>
        <div class="modal-actions" style="margin-top:16px;">
          <button type="button" onclick="document.getElementById('modalAdd').classList.remove('open')" class="btn btn-ghost">Cancel</button>
          <button type="submit" class="btn btn-accent">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal-bg" id="modalEdit">
    <div class="modal" style="width:420px;"><h3>Edit Room</h3>
      <form method="POST" style="margin-top:16px;"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="editId">
        <div class="form-group"><label class="form-label">Room name <span>*</span></label><input type="text" name="room_name" id="editName" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Type</label>
            <select name="room_type" id="editType" class="form-control"><?php foreach(['Clinic','Emergency','Inpatient','Surgery','ICU'] as $t):?><option><?=$t?></option><?php endforeach;?></select>
          </div>
          <div class="form-group"><label class="form-label">Capacity</label>
            <input type="number" name="capacity" id="editCap" class="form-control" min="1" oninput="generateBedOptions('editBed', this.value, document.getElementById('editBedCurrent').value)">
          </div>
        </div>
        <input type="hidden" id="editBedCurrent" value="">
        <div class="form-group"><label class="form-label">Bed Number</label>
          <select name="bed_number" id="editBed" class="form-control"></select>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" id="editStatus" class="form-control"><?php foreach(['Available','Full','Inactive'] as $s):?><option><?=$s?></option><?php endforeach;?></select>
        </div>
        <div class="modal-actions" style="margin-top:16px;">
          <button type="button" onclick="document.getElementById('modalEdit').classList.remove('open')" class="btn btn-ghost">Cancel</button>
          <button type="submit" class="btn btn-accent">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Delete -->
  <div class="modal-bg" id="deleteModal">
    <div class="modal"><h3>Delete Room</h3><p>Are you sure you want to delete room <strong id="deleteName"></strong>?</p>
    <div class="modal-actions"><button onclick="document.getElementById('deleteModal').classList.remove('open')" class="btn btn-ghost">Cancel</button><a id="deleteLink" href="#" class="btn btn-danger">Yes, Delete</a></div>
    </div>
  </div>

<script>
function generateBedOptions(selectId, capacity, currentVal) {
  const sel = document.getElementById(selectId);
  sel.innerHTML = '<option value="">-- No specific bed --</option>';
  const cap = parseInt(capacity) || 0;
  for (let i = 1; i <= cap; i++) {
    const val = 'Bed-' + i;
    const opt = document.createElement('option');
    opt.value = val;
    opt.textContent = val;
    if (val === currentVal) opt.selected = true;
    sel.appendChild(opt);
  }
}

function editRoom(r) {
  document.getElementById('editId').value = r.room_id;
  document.getElementById('editName').value = r.room_name;
  document.getElementById('editType').value = r.room_type;
  document.getElementById('editCap').value = r.capacity;
  document.getElementById('editBedCurrent').value = r.bed_number ?? '';
  document.getElementById('editStatus').value = r.status;
  generateBedOptions('editBed', r.capacity, r.bed_number ?? '');
  document.getElementById('modalEdit').classList.add('open');
}

function confirmDelete(id, name) {
  document.getElementById('deleteName').textContent = name;
  document.getElementById('deleteLink').href = 'delete.php?id=' + id;
  document.getElementById('deleteModal').classList.add('open');
}

// Generate bed options saat modal Add dibuka
document.querySelector('[onclick="document.getElementById(\'modalAdd\').classList.add(\'open\')"]').addEventListener('click', function() {
  const cap = document.getElementById('addCap').value;
  generateBedOptions('addBed', cap, '');
});
</script>
<?php include '../assets/footer.php';?>