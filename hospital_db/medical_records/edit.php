<?php
require_once '../config.php';
$id     = intval($_GET['id']??0);
$record = $conn->query("SELECT * FROM medical_records WHERE medical_record_id=$id")->fetch_assoc();
if(!$record){ flash('Record not found.','danger'); redirect('index.php'); }

$pageTitle='Edit Medical Record'; $activePage='medical_records'; $base='..'; $errors=[];
$patientList  = $conn->query("SELECT patient_id,patient_name FROM patients ORDER BY patient_name")->fetch_all(MYSQLI_ASSOC);
$doctorList   = $conn->query("SELECT doctor_id,doctor_name FROM doctors ORDER BY doctor_name")->fetch_all(MYSQLI_ASSOC);
$medicineList = $conn->query("SELECT medicine_id,medicine_name,unit_price,stock,max_dose_per_prescription FROM medicines ORDER BY medicine_name")->fetch_all(MYSQLI_ASSOC);
$existingMeds = $conn->query("SELECT pd.*,m.medicine_name FROM prescription_details pd JOIN medicines m ON pd.medicine_id=m.medicine_id WHERE pd.medical_record_id=$id")->fetch_all(MYSQLI_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  $idP         = intval($_POST['patient_id']??0);
  $idD         = intval($_POST['doctor_id']??0);
  $vdate       = sanitize($conn, $_POST['visit_date']??'');
  $complaints  = sanitize($conn, $_POST['complaints']??'');
  $diag        = sanitize($conn, $_POST['diagnosis']??'');
  $prescription= sanitize($conn, $_POST['prescription']??'');
  $medIds      = $_POST['medicine_id']??[];
  $quantities  = $_POST['quantity']??[];

  if(!$idP)        $errors[] = 'Patient is required.';
  if(!$idD)        $errors[] = 'Doctor is required.';
  if(!$vdate)      $errors[] = 'Visit date is required.';
  if($vdate && $vdate < date('Y-m-d')) $errors[] = 'Visit date cannot be in the past.';
  if(!$complaints) $errors[] = 'Complaints are required.';

  // ── Overdose & stock validation ───────────────────────────────
  // Build a map of OLD quantities so we know what to "refund" virtually
  $oldQtyMap = [];
  foreach($existingMeds as $em){
    $oldQtyMap[intval($em['medicine_id'])] = intval($em['quantity']);
  }

  foreach($medIds as $idx => $medId){
    $medId = intval($medId);
    $qty   = intval($quantities[$idx]??0);
    if($medId <= 0 || $qty <= 0) continue;

    $mStmt = $conn->prepare("SELECT medicine_name, stock, max_dose_per_prescription FROM medicines WHERE medicine_id=?");
    $mStmt->bind_param('i', $medId); $mStmt->execute();
    $med = $mStmt->get_result()->fetch_assoc();
    if(!$med) continue;

    $maxDose = intval($med['max_dose_per_prescription']??0);

    // Effective available stock = current stock + old qty that was previously deducted
    // This prevents false "insufficient stock" when editing the same medicine
    $oldQty         = $oldQtyMap[$medId] ?? 0;
    $effectiveStock = intval($med['stock']) + $oldQty;

    if($maxDose > 0 && $qty > $maxDose){
      $errors[] = "Overdose risk: \"{$med['medicine_name']}\" max dose is $maxDose units. You entered $qty.";
    }
    if($qty > $effectiveStock){
      $errors[] = "Insufficient stock: \"{$med['medicine_name']}\" only has $effectiveStock units available.";
    }
  }

  if(empty($errors)){
    // Update main record
    $stmt = $conn->prepare("UPDATE medical_records SET patient_id=?,doctor_id=?,visit_date=?,complaints=?,diagnosis=?,prescription=? WHERE medical_record_id=?");
    $stmt->bind_param('iissssi', $idP, $idD, $vdate, $complaints, $diag, $prescription, $id);
    $stmt->execute();

    // ── STEP 1: Restore stock from OLD prescription ───────────────
    // Return whatever was previously deducted back to stock
    foreach($existingMeds as $em){
      $oldMedId = intval($em['medicine_id']);
      $oldQty   = intval($em['quantity']);
      $conn->query("UPDATE medicines SET stock = stock + $oldQty WHERE medicine_id = $oldMedId");
    }

    // ── STEP 2: Delete old prescription details ───────────────────
    $conn->query("DELETE FROM prescription_details WHERE medical_record_id=$id");

    // ── STEP 3: Insert new prescription & deduct new quantities ───
    foreach($medIds as $idx => $medId){
      $medId = intval($medId);
      $qty   = intval($quantities[$idx]??0);
      if($medId > 0 && $qty > 0){
        $stmt2 = $conn->prepare("INSERT INTO prescription_details (medical_record_id,medicine_id,quantity) VALUES (?,?,?)");
        $stmt2->bind_param('iii', $id, $medId, $qty);
        $stmt2->execute();

        // Deduct new quantity from stock
        $conn->query("UPDATE medicines SET stock = stock - $qty WHERE medicine_id = $medId AND stock >= $qty");
      }
    }

    // Recalculate payment
    $stmt3 = $conn->prepare("
      SELECT d.service_fee,
             COALESCE(i.discount_cover,0) as discount,
             COALESCE(SUM(pd.quantity * m.unit_price),0) as medicine_cost
      FROM medical_records mr
      JOIN doctors d ON mr.doctor_id=d.doctor_id
      JOIN patients p ON mr.patient_id=p.patient_id
      LEFT JOIN insurance i ON p.insurance_id=i.insurance_id
      LEFT JOIN prescription_details pd ON pd.medical_record_id=mr.medical_record_id
      LEFT JOIN medicines m ON pd.medicine_id=m.medicine_id
      WHERE mr.medical_record_id=?
      GROUP BY mr.medical_record_id
    ");
    $stmt3->bind_param('i', $id); $stmt3->execute();
    $calc  = $stmt3->get_result()->fetch_assoc();
    $total = ($calc['service_fee'] + $calc['medicine_cost']) * (1 - $calc['discount']/100);
    $stmtPay = $conn->prepare("UPDATE payments SET total_amount=? WHERE medical_record_id=?");
    $stmtPay->bind_param('di', $total, $id);
    $stmtPay->execute();

    flash("Medical record updated successfully."); redirect('index.php');
  }
  $record = array_merge($record, $_POST);
}
include '../assets/sidebar.php';

// Build medicine map for JS (include real-time stock info)
$medMap = [];
foreach($medicineList as $m){
  $medMap[$m['medicine_id']] = [
    'stock'    => intval($m['stock']),
    'max_dose' => intval($m['max_dose_per_prescription']??0),
    'name'     => $m['medicine_name'],
  ];
}

// Build old qty map for JS (so JS can show correct effective stock)
$oldQtyMapJS = [];
foreach($existingMeds as $em){
  $oldQtyMapJS[intval($em['medicine_id'])] = intval($em['quantity']);
}
?>
  <div class="topbar"><div class="topbar-left"><h2>Edit Medical Record</h2><p>Update visit record — ID #<?=$id?></p></div></div>
  <div class="content">
    <a href="index.php" class="back-link"><svg viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg> Back to Medical Records</a>
    <?php if($errors):?><div class="alert alert-danger"><?=implode('<br>',array_map('htmlspecialchars',$errors))?></div><?php endif;?>
    <form method="POST" class="form-card" style="max-width:700px;" onsubmit="return validatePrescription()">
      <div class="form-title">Edit Record</div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Patient <span>*</span></label>
          <select name="patient_id" class="form-control" required>
            <?php foreach($patientList as $p):?><option value="<?=$p['patient_id']?>" <?=$record['patient_id']==$p['patient_id']?'selected':''?>><?=htmlspecialchars($p['patient_name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Doctor <span>*</span></label>
          <select name="doctor_id" class="form-control" required>
            <?php foreach($doctorList as $d):?><option value="<?=$d['doctor_id']?>" <?=$record['doctor_id']==$d['doctor_id']?'selected':''?>><?=htmlspecialchars($d['doctor_name'])?></option><?php endforeach;?>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Visit date <span>*</span></label>
        <input type="date" name="visit_date" class="form-control" value="<?=htmlspecialchars($record['visit_date'])?>" min="<?=date('Y-m-d')?>" required>
        <small style="color:var(--text-3);font-size:11px;">Past dates are not selectable</small>
      </div>
      <div class="form-group"><label class="form-label">Complaints <span>*</span></label>
        <textarea name="complaints" class="form-control" required><?=htmlspecialchars($record['complaints'])?></textarea>
      </div>
      <div class="form-group"><label class="form-label">Diagnosis</label>
        <input type="text" name="diagnosis" class="form-control" value="<?=htmlspecialchars($record['diagnosis']??'')?>">
      </div>
      <div class="form-group"><label class="form-label">Prescription Notes</label>
        <textarea name="prescription" class="form-control"><?=htmlspecialchars($record['prescription']??'')?></textarea>
      </div>

      <!-- Medicine Section -->
      <div style="margin-top:20px;padding-top:18px;border-top:1px solid var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <span style="font-size:13px;font-weight:600;color:var(--text);">Prescribed Medicines</span>
          <button type="button" onclick="addMedicineRow()" class="btn btn-ghost btn-sm">+ Add Medicine</button>
        </div>
        <div id="overdoseWarning" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;font-size:12px;margin-bottom:12px;"></div>
        <div id="medicineRows">
          <?php if(!empty($existingMeds)):?>
            <?php foreach($existingMeds as $em):?>
            <div class="medicine-row" style="display:grid;grid-template-columns:1fr 130px 36px;gap:10px;margin-bottom:10px;align-items:start;">
              <div>
                <select name="medicine_id[]" class="form-control" onchange="onMedChange(this)">
                  <option value="">-- Select Medicine --</option>
                  <?php foreach($medicineList as $m):?>
                  <option value="<?=$m['medicine_id']?>"
                          data-stock="<?=$m['stock']?>"
                          data-max="<?=$m['max_dose_per_prescription']??0?>"
                          data-name="<?=htmlspecialchars($m['medicine_name'])?>"
                          <?=$em['medicine_id']==$m['medicine_id']?'selected':''?>>
                    <?=htmlspecialchars($m['medicine_name'])?> (Stock: <?=$m['stock']?>)
                  </option>
                  <?php endforeach;?>
                </select>
                <div class="med-hint" style="font-size:11px;color:var(--text-3);margin-top:3px;"></div>
              </div>
              <div>
                <input type="number" name="quantity[]" class="form-control qty-input"
                       placeholder="Qty" min="1" value="<?=$em['quantity']?>" oninput="checkQty(this)">
                <div class="qty-warn" style="font-size:11px;color:#b91c1c;margin-top:3px;"></div>
              </div>
              <button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm" style="padding:5px 8px;margin-top:2px;">✕</button>
            </div>
            <?php endforeach;?>
          <?php else:?>
            <div class="medicine-row" style="display:grid;grid-template-columns:1fr 130px 36px;gap:10px;margin-bottom:10px;align-items:start;">
              <div>
                <select name="medicine_id[]" class="form-control" onchange="onMedChange(this)">
                  <option value="">-- Select Medicine --</option>
                  <?php foreach($medicineList as $m):?>
                  <option value="<?=$m['medicine_id']?>" data-stock="<?=$m['stock']?>" data-max="<?=$m['max_dose_per_prescription']??0?>" data-name="<?=htmlspecialchars($m['medicine_name'])?>">
                    <?=htmlspecialchars($m['medicine_name'])?> (Stock: <?=$m['stock']?>)
                  </option>
                  <?php endforeach;?>
                </select>
                <div class="med-hint" style="font-size:11px;color:var(--text-3);margin-top:3px;"></div>
              </div>
              <div>
                <input type="number" name="quantity[]" class="form-control qty-input" placeholder="Qty" min="1" value="1" oninput="checkQty(this)">
                <div class="qty-warn" style="font-size:11px;color:#b91c1c;margin-top:3px;"></div>
              </div>
              <button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm" style="padding:5px 8px;margin-top:2px;">✕</button>
            </div>
          <?php endif;?>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-accent">Save Changes</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>

<script>
const medicines  = <?=json_encode(array_values($medicineList))?>;
const medMap     = <?=json_encode($medMap)?>;
// oldQtyMap: qty yang sudah dipotong sebelumnya per medicine_id
// dipakai supaya effective stock = current stock + old qty (biar tidak false error)
const oldQtyMap  = <?=json_encode($oldQtyMapJS)?>;

function getMedSelect(row){ return row.querySelector('select[name="medicine_id[]"]'); }
function getQtyInput(row) { return row.querySelector('input[name="quantity[]"]'); }
function getHint(row)     { return row.querySelector('.med-hint'); }
function getWarn(row)     { return row.querySelector('.qty-warn'); }

function onMedChange(sel){
  const row     = sel.closest('.medicine-row');
  const opt     = sel.selectedOptions[0];
  const hint    = getHint(row);
  const medId   = parseInt(sel.value||0);
  const maxDose = parseInt(opt?.dataset.max||0);
  const stock   = parseInt(opt?.dataset.stock||0);
  const oldQty  = oldQtyMap[medId] || 0;           // stok lama yang akan dikembalikan
  const effStock = stock + oldQty;                  // effective stock setelah refund

  if(sel.value){
    hint.textContent = `Effective stock: ${effStock} units | Max dose: ${maxDose} units/prescription`;
    getQtyInput(row).max = Math.min(maxDose||9999, effStock);
  } else {
    hint.textContent = '';
    getQtyInput(row).removeAttribute('max');
  }
  checkQty(getQtyInput(row));
}

function checkQty(input){
  const row     = input.closest('.medicine-row');
  const sel     = getMedSelect(row);
  const warn    = getWarn(row);
  const opt     = sel?.selectedOptions[0];
  if(!opt || !sel.value){ warn.textContent=''; return; }

  const medId   = parseInt(sel.value||0);
  const maxDose = parseInt(opt.dataset.max||0);
  const stock   = parseInt(opt.dataset.stock||0);
  const oldQty  = oldQtyMap[medId] || 0;
  const effStock= stock + oldQty;
  const qty     = parseInt(input.value||0);

  if(maxDose > 0 && qty > maxDose){
    warn.textContent = `⚠ Overdose! Max allowed: ${maxDose} units.`;
  } else if(qty > effStock){
    warn.textContent = `⚠ Insufficient stock! Only ${effStock} units available (current ${stock} + returning ${oldQty}).`;
  } else {
    warn.textContent = '';
  }
}

function addMedicineRow(){
  const container = document.getElementById('medicineRows');
  const div = document.createElement('div');
  div.className = 'medicine-row';
  div.style.cssText = 'display:grid;grid-template-columns:1fr 130px 36px;gap:10px;margin-bottom:10px;align-items:start;';
  let options = '<option value="">-- Select Medicine --</option>';
  medicines.forEach(m => {
    options += `<option value="${m.medicine_id}" data-stock="${m.stock}" data-max="${m.max_dose_per_prescription||0}" data-name="${m.medicine_name}">${m.medicine_name} (Stock: ${m.stock})</option>`;
  });
  div.innerHTML = `
    <div>
      <select name="medicine_id[]" class="form-control" onchange="onMedChange(this)">${options}</select>
      <div class="med-hint" style="font-size:11px;color:var(--text-3);margin-top:3px;"></div>
    </div>
    <div>
      <input type="number" name="quantity[]" class="form-control qty-input" placeholder="Qty" min="1" value="1" oninput="checkQty(this)">
      <div class="qty-warn" style="font-size:11px;color:#b91c1c;margin-top:3px;"></div>
    </div>
    <button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm" style="padding:5px 8px;margin-top:2px;">✕</button>
  `;
  container.appendChild(div);
}

function removeRow(btn){
  const rows = document.querySelectorAll('.medicine-row');
  if(rows.length > 1) btn.closest('.medicine-row').remove();
}

function validatePrescription(){
  const warns = document.querySelectorAll('.qty-warn');
  let hasError = false;
  warns.forEach(w => { if(w.textContent.trim()) hasError = true; });
  if(hasError){
    const banner = document.getElementById('overdoseWarning');
    banner.style.display = 'block';
    banner.textContent = 'Please fix the overdose or stock errors above before saving.';
    banner.scrollIntoView({behavior:'smooth',block:'center'});
    return false;
  }
  return true;
}

// Initialize hints for pre-filled rows on page load
document.querySelectorAll('.medicine-row select').forEach(sel => {
  if(sel.value) onMedChange(sel);
});
</script>
<?php include '../assets/footer.php';?>