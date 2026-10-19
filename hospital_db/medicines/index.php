<?php
require_once '../auth.php';
requireAdmin();
require_once '../config.php';
$pageTitle='Medicines'; $activePage='medicines'; $base='..';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $act = $_POST['action']??'';

  if($act==='add'){
    $name         = sanitize($conn, $_POST['medicine_name']??'');
    $price        = floatval($_POST['unit_price']??0);
    $initial_stock = intval($_POST['initial_stock']??0);
    $max_dose     = intval($_POST['max_dose_per_prescription']??0);

    if($name && $initial_stock > 0 && $max_dose > 0){
      // remaining_stock starts equal to initial_stock on creation
      $stmt = $conn->prepare("INSERT INTO medicines (medicine_name,unit_price,initial_stock,stock,max_dose_per_prescription) VALUES (?,?,?,?,?)");
      $stmt->bind_param('sdiii', $name, $price, $initial_stock, $initial_stock, $max_dose);
      $stmt->execute();
      flash("Medicine $name added.");
    } else {
      flash("All medicine fields are required and stock/max dose must be > 0.", "danger");
    }
    redirect('index.php');
  }

  if($act==='edit'){
    $id           = intval($_POST['id']??0);
    $name         = sanitize($conn, $_POST['medicine_name']??'');
    $price        = floatval($_POST['unit_price']??0);
    $initial_stock = intval($_POST['initial_stock']??0);
    $remaining    = intval($_POST['stock']??0);
    $max_dose     = intval($_POST['max_dose_per_prescription']??0);

    // remaining stock must not exceed initial stock
    if($remaining > $initial_stock) $remaining = $initial_stock;

    $stmt = $conn->prepare("UPDATE medicines SET medicine_name=?,unit_price=?,initial_stock=?,stock=?,max_dose_per_prescription=? WHERE medicine_id=?");
    $stmt->bind_param('sdiiii', $name, $price, $initial_stock, $remaining, $max_dose, $id);
    $stmt->execute();
    flash("Medicine $name updated.");
    redirect('index.php');
  }

  if($act==='restock'){
    $id=intval($_POST['id']??0);
    $qty=intval($_POST['restock_qty']??0);
    if($id>0 && $qty>0){
        $stmt=$conn->prepare("SELECT medicine_name, stock FROM medicines WHERE medicine_id=?");
        $stmt->bind_param('i',$id);$stmt->execute();
        $med=$stmt->get_result()->fetch_assoc();
        $newStock=$med['stock']+$qty;
        $stmt2=$conn->prepare("UPDATE medicines SET stock=?, initial_stock=? WHERE medicine_id=?");
        $stmt2->bind_param('iii',$newStock,$newStock,$id);$stmt2->execute();
        flash("Restock {$med['medicine_name']} successful! Stock increased by $qty units to $newStock units.");
    }
    redirect('index.php');
  }

  if($act==='delete'){
    $id  = intval($_POST['id']??0);
    $stmt = $conn->prepare("SELECT medicine_name FROM medicines WHERE medicine_id=?");
    $stmt->bind_param('i',$id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if($row){
      $stmt2 = $conn->prepare("DELETE FROM medicines WHERE medicine_id=?");
      $stmt2->bind_param('i',$id); $stmt2->execute();
      flash("Medicine {$row['medicine_name']} deleted.");
    }
    redirect('index.php');
  }
}

$search = trim($_GET['search']??'');
$params=[]; $types=''; $wsql='';
if($search!==''){
  $wsql  = "WHERE medicine_name LIKE ?";
  $params[] = "%$search%";
  $types   .= 's';
}
$stmt = $conn->prepare("SELECT * FROM medicines $wsql ORDER BY medicine_name");
if($params) $stmt->bind_param($types,...$params);
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalAll  = $conn->query("SELECT COUNT(*) c FROM medicines")->fetch_assoc()['c'];
$lowStock  = $conn->query("SELECT COUNT(*) c FROM medicines WHERE stock < 50")->fetch_assoc()['c'];
include '../assets/sidebar.php';
?>
  <div class="topbar">
    <div class="topbar-left"><h2>Medicines</h2><p><?=$totalAll?> medicines in inventory</p></div>
    <button onclick="document.getElementById('modalAdd').classList.add('open')" class="btn btn-accent">
      <svg viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg> Add Medicine
    </button>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?>
      <div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div>
      <?php unset($_SESSION['flash']);endif;?>

    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Total Medicines</div><div class="stat-val"><?=$totalAll?></div><div class="stat-sub">types</div></div>
      <div class="stat s4"><div class="stat-label">Low Stock</div><div class="stat-val"><?=$lowStock?></div><div class="stat-sub">below 50 units</div></div>
      <div class="stat s2"><div class="stat-label">Results</div><div class="stat-val"><?=count($medicines)?></div><div class="stat-sub">shown</div></div>
    </div>

    <form method="GET" class="toolbar">
      <div class="search-wrap">
        <svg viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.2"/><path d="M10 10l3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
        <input type="text" name="search" class="search-input" placeholder="Search medicine name..." value="<?=htmlspecialchars($search)?>">
      </div>
      <button type="submit" class="btn btn-ghost">Search</button>
      <?php if($search):?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif;?>
    </form>

    <div class="table-card"><table class="tbl">
      <thead><tr><th>#</th><th>Medicine Name</th><th>Unit Price</th><th>Max Dose/Rx</th><th>Initial Stock</th><th>Remaining Stock</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($medicines)):?>
        <tr><td class="tbl-empty" colspan="7">No medicines found.</td></tr>
      <?php else:foreach($medicines as $i=>$m):
        $sc = $m['stock'] < 50 ? 'badge-red' : 'badge-teal';
        // Calculate usage percentage for remaining stock bar
        $pct = $m['initial_stock'] > 0 ? round($m['stock']/$m['initial_stock']*100) : 0;
        $barColor = $pct > 50 ? 'var(--teal)' : ($pct > 20 ? 'var(--amber,#f59e0b)' : 'var(--danger,#ef4444)');
      ?>
        <tr>
          <td style="color:var(--text-3);font-size:12px;"><?=$i+1?></td>
          <td style="font-weight:500;"><?=htmlspecialchars($m['medicine_name'])?></td>
          <td>Rp <?=number_format($m['unit_price'],0,',','.')?></td>
          <td><span class="badge badge-blue"><?=$m['max_dose_per_prescription']??'-'?> units</span></td>
          <td style="font-size:12px;color:var(--text-2);"><?=number_format($m['initial_stock'])?> units</td>
          <td>
            <span class="badge <?=$sc?>"><?=$m['stock']?> units</span>
            <div style="margin-top:4px;background:var(--border);border-radius:4px;height:4px;width:80px;">
              <div style="width:<?=$pct?>%;background:<?=$barColor?>;height:4px;border-radius:4px;transition:width .3s;"></div>
            </div>
            <div style="font-size:10px;color:var(--text-3);margin-top:2px;"><?=$pct?>% remaining</div>
          </td>
          <td><div style="display:flex;gap:5px;">
            <button onclick='editMed(<?=json_encode($m)?>)' class="btn btn-ghost btn-sm">Edit</button>
            <?php if($m['stock'] <= ($m['restock_threshold']??50)):?>
            <button onclick='restockMed(<?=json_encode($m)?>)' class="btn btn-sm" style="background:#FAEEDA;color:#854F0B;border:1px solid #F5C97A;">Restock</button>
            <?php endif;?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this medicine?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?=$m['medicine_id']?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div></td>
        </tr>
      <?php endforeach;endif;?>
      </tbody>
    </table></div>
  </div>

  <!-- ── Add Medicine Modal ─────────────────────────────────────── -->
  <div class="modal-bg" id="modalAdd"><div class="modal"><h3>Add Medicine</h3>
    <form method="POST" style="margin-top:16px;" onsubmit="return validateAdd(this)">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label">Medicine name <span>*</span></label>
        <input type="text" name="medicine_name" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Unit price (Rp) <span>*</span></label>
          <input type="number" name="unit_price" class="form-control" value="0" min="0" required>
        </div>
        <div class="form-group">
          <label class="form-label">Initial stock <span>*</span></label>
          <input type="number" name="initial_stock" class="form-control" value="0" min="1" required>
          <small style="color:var(--text-3);font-size:11px;">Starting quantity in warehouse</small>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Max dose per prescription <span>*</span></label>
        <input type="number" name="max_dose_per_prescription" class="form-control" value="0" min="1" required>
        <small style="color:var(--text-3);font-size:11px;">Max units a doctor can prescribe at once (overdose guard)</small>
      </div>
      <div class="modal-actions" style="margin-top:16px;">
        <button type="button" onclick="document.getElementById('modalAdd').classList.remove('open')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-accent">Save</button>
      </div>
    </form>
  </div></div>

  <!-- ── Edit Medicine Modal ───────────────────────────────────── -->
  <div class="modal-bg" id="modalEdit"><div class="modal"><h3>Edit Medicine</h3>
    <form method="POST" style="margin-top:16px;" onsubmit="return validateEdit(this)">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="form-group">
        <label class="form-label">Medicine name <span>*</span></label>
        <input type="text" name="medicine_name" id="editName" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Unit price (Rp)</label>
          <input type="number" name="unit_price" id="editPrice" class="form-control" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Initial stock <span>*</span></label>
          <input type="number" name="initial_stock" id="editInitial" class="form-control" min="1" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Remaining stock</label>
          <input type="number" name="stock" id="editStock" class="form-control" min="0">
          <small style="color:var(--text-3);font-size:11px;">Cannot exceed initial stock</small>
        </div>
        <div class="form-group">
          <label class="form-label">Max dose per prescription <span>*</span></label>
          <input type="number" name="max_dose_per_prescription" id="editMaxDose" class="form-control" min="1" required>
        </div>
      </div>
      <div class="modal-actions" style="margin-top:16px;">
        <button type="button" onclick="document.getElementById('modalEdit').classList.remove('open')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>

  <div class="modal-bg" id="modalRestock">
  <div class="modal">
    <h3>Restock Medicine</h3>
    <form method="POST" style="margin-top:16px;">
      <input type="hidden" name="action" value="restock">
      <input type="hidden" name="id" id="restockId">
      <div class="form-group">
        <label class="form-label">Medicine</label>
        <input type="text" id="restockName" class="form-control" readonly style="background:#f5f4f0;color:#888;">
      </div>
      <div class="form-group">
        <label class="form-label">Current Stock</label>
        <input type="text" id="restockCurrentStock" class="form-control" readonly style="background:#f5f4f0;color:#888;">
      </div>
      <div class="form-group">
        <label class="form-label">Restock Quantity <span style="color:#A32D2D;">*</span></label>
        <input type="number" name="restock_qty" id="restockQty" class="form-control" placeholder="Enter quantity to add" min="1" required>
        <div class="form-hint">This amount will be added to current stock.</div>
      </div>
      <div class="modal-actions" style="margin-top:16px;">
        <button type="button" onclick="document.getElementById('modalRestock').classList.remove('open')" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-accent">Confirm Restock</button>
      </div>
    </form>
  </div>
</div>

<script>
function editMed(r){
  document.getElementById('editId').value      = r.medicine_id;
  document.getElementById('editName').value    = r.medicine_name;
  document.getElementById('editPrice').value   = r.unit_price;
  document.getElementById('editInitial').value = r.initial_stock ?? r.stock;
  document.getElementById('editStock').value   = r.stock;
  document.getElementById('editMaxDose').value = r.max_dose_per_prescription ?? 1;
  document.getElementById('modalEdit').classList.add('open');
}

// Validate remaining stock <= initial stock before editing
function validateEdit(form){
  const initial   = parseInt(form.initial_stock.value) || 0;
  const remaining = parseInt(form.stock.value) || 0;
  const maxDose   = parseInt(form.max_dose_per_prescription.value) || 0;
  if(remaining > initial){
    alert('Remaining stock cannot exceed initial stock (' + initial + ' units).'); 
    return false;
  }
  if(maxDose > initial){
    alert('Max dose per prescription cannot exceed initial stock (' + initial + ' units).');
    return false;
  }
  return true;
}

function validateAdd(form){
  const initial = parseInt(form.initial_stock.value) || 0;
  const maxDose = parseInt(form.max_dose_per_prescription.value) || 0;
  if(maxDose > initial){
    alert('Max dose per prescription cannot exceed initial stock (' + initial + ' units).');
    return false;
  }
  return true;
}

function restockMed(r){
    document.getElementById('restockId').value = r.medicine_id;
    document.getElementById('restockName').value = r.medicine_name;
    document.getElementById('restockCurrentStock').value = r.stock + ' units';
    document.getElementById('restockQty').value = '';
    document.getElementById('modalRestock').classList.add('open');
}
</script>
<?php include '../assets/footer.php';?>