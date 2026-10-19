<?php
require_once '../config.php';
$pageTitle='Room Beds'; $activePage='rooms'; $base='..';
$roomId=intval($_GET['id']??0);
if(!$roomId){flash('Invalid room.','danger');redirect('index.php');}

// Handle status update for a bed
if($_SERVER['REQUEST_METHOD']==='POST'){
  $bedNum=sanitize($conn,$_POST['bed_num']??'');
  $newStatus=sanitize($conn,$_POST['bed_status']??'');
  if($bedNum&&$newStatus){
    // Ambil FRESH dari DB
    $fresh=$conn->query("SELECT * FROM rooms WHERE room_id=$roomId")->fetch_assoc();
    $cap=$fresh['capacity'];
    
    // Decode existing, fallback ke array kosong
    $existing=[];
    $raw=$fresh['bed_number']??'';
    if($raw && $raw[0]==='{'){
      $decoded=json_decode($raw,true);
      if(is_array($decoded)) $existing=$decoded;
    }
    
    // Inisialisasi semua bed dulu kalau belum ada
    for($b=1;$b<=$cap;$b++){
      $key='Bed-'.$b;
      if(!isset($existing[$key])) $existing[$key]='Available';
    }
    
    // Update bed yang dipilih
    $existing[$bedNum]=$newStatus;
    
    // Simpan ke DB
    $json=$conn->real_escape_string(json_encode($existing));
    $conn->query("UPDATE rooms SET bed_number='$json' WHERE room_id=$roomId");
    
    // Hitung ulang status room
    $fullCount=count(array_filter($existing,fn($s)=>$s==='Full'));
    $inactCount=count(array_filter($existing,fn($s)=>$s==='Inactive'));
    $availCount=count(array_filter($existing,fn($s)=>$s==='Available'));
    
    if($availCount===0 && $fullCount>0){
      $conn->query("UPDATE rooms SET status='Full' WHERE room_id=$roomId");
    } elseif($availCount===0 && $fullCount===0){
      $conn->query("UPDATE rooms SET status='Inactive' WHERE room_id=$roomId");
    } else {
      $conn->query("UPDATE rooms SET status='Available' WHERE room_id=$roomId");
    }
    
    flash("Bed $bedNum status updated to $newStatus.");
    redirect('beds.php?id='.$roomId);
  }
}

// Load room SETELAH POST selesai
$room=$conn->query("SELECT * FROM rooms WHERE room_id=$roomId")->fetch_assoc();
if(!$room){flash('Room not found.','danger');redirect('index.php');}

$cap=$room['capacity'];
$bedData=[];
$raw=$room['bed_number']??'';
if($raw && $raw[0]==='{'){
  $decoded=json_decode($raw,true);
  if(is_array($decoded)) $bedData=$decoded;
}
// Inisialisasi tampilan
for($b=1;$b<=$cap;$b++){
  $key='Bed-'.$b;
  if(!isset($bedData[$key])) $bedData[$key]='Available';
}

$typeColors=['Clinic'=>'#0F6E56','Emergency'=>'#A32D2D','Inpatient'=>'#185FA5','Surgery'=>'#854F0B','ICU'=>'#534AB7'];
$roomColor=$typeColors[$room['room_type']]??'#1a1a18';

include '../assets/sidebar.php';
?>
  <div class="topbar">
    <div class="topbar-left">
      <h2><?=htmlspecialchars($room['room_name'])?></h2>
      <p><?=htmlspecialchars($room['room_type'])?> · <?=$cap?> beds total</p>
    </div>
    <a href="index.php" class="btn btn-ghost">← Back to Rooms</a>
  </div>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])):?><div class="alert alert-<?=$_SESSION['flash']['type']?>"><?=htmlspecialchars($_SESSION['flash']['msg'])?></div><?php unset($_SESSION['flash']);endif;?>

    <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;background:var(--card);padding:14px 18px;border-radius:var(--r-lg);border:1px solid var(--border);">
      <span style="font-size:13px;font-weight:600;color:var(--text);">Bed Status:</span>
      <span style="display:flex;align-items:center;gap:6px;font-size:13px;"><span style="width:12px;height:12px;border-radius:50%;background:#3B6D11;display:inline-block;"></span> Available</span>
      <span style="display:flex;align-items:center;gap:6px;font-size:13px;"><span style="width:12px;height:12px;border-radius:50%;background:#A32D2D;display:inline-block;"></span> Full</span>
      <span style="display:flex;align-items:center;gap:6px;font-size:13px;"><span style="width:12px;height:12px;border-radius:50%;background:#888780;display:inline-block;"></span> Inactive</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:24px;">
      <?php for($i=1;$i<=$cap;$i++):
        $bedKey='Bed-'.$i;
        $bedStatus=$bedData[$bedKey];
        if($bedStatus==='Full'){$bg='#FCEBEB';$border='#F09595';$color='#A32D2D';}
        elseif($bedStatus==='Inactive'){$bg='#F1EFE8';$border='#C8C6BE';$color='#5F5E5A';}
        else{$bg='#EAF3DE';$border='#C0DD97';$color='#3B6D11';}
      ?>
      <div onclick="openBedModal('<?=$bedKey?>','<?=$bedStatus?>')"
           style="background:<?=$bg?>;border:2px solid <?=$border?>;border-radius:14px;padding:18px 14px;text-align:center;cursor:pointer;transition:all .15s;user-select:none;"
           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <svg viewBox="0 0 40 32" fill="none" style="width:40px;height:32px;margin:0 auto 10px;display:block;">
          <rect x="2" y="14" width="36" height="12" rx="3" fill="<?=$color?>" opacity="0.2"/>
          <rect x="2" y="14" width="36" height="12" rx="3" stroke="<?=$color?>" stroke-width="1.5"/>
          <rect x="4" y="10" width="14" height="6" rx="2" fill="<?=$color?>" opacity="0.3"/>
          <rect x="4" y="10" width="14" height="6" rx="2" stroke="<?=$color?>" stroke-width="1.5"/>
          <line x1="6" y1="26" x2="6" y2="30" stroke="<?=$color?>" stroke-width="2" stroke-linecap="round"/>
          <line x1="34" y1="26" x2="34" y2="30" stroke="<?=$color?>" stroke-width="2" stroke-linecap="round"/>
          <?php if($bedStatus==='Full'):?>
          <circle cx="28" cy="8" r="5" fill="<?=$color?>" opacity="0.8"/>
          <?php endif;?>
        </svg>
        <div style="font-size:13px;font-weight:600;color:<?=$color?>;"><?=$bedKey?></div>
        <div style="margin-top:5px;">
          <span style="display:inline-block;background:<?=$color?>;color:#fff;font-size:10px;font-weight:500;padding:2px 8px;border-radius:20px;"><?=$bedStatus?></span>
        </div>
      </div>
      <?php endfor;?>
    </div>

    <?php
      $availCount=count(array_filter($bedData,fn($s)=>$s==='Available'));
      $fullCount=count(array_filter($bedData,fn($s)=>$s==='Full'));
      $inactCount=count(array_filter($bedData,fn($s)=>$s==='Inactive'));
    ?>
    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
      <div class="stat"><div class="stat-label">Available Beds</div><div class="stat-val"><?=$availCount?></div><div class="stat-sub">ready</div></div>
      <div class="stat s4"><div class="stat-label">Full / Occupied</div><div class="stat-val"><?=$fullCount?></div><div class="stat-sub">occupied</div></div>
      <div class="stat" style="border-left-color:#888780;"><div class="stat-label">Inactive</div><div class="stat-val"><?=$inactCount?></div><div class="stat-sub">not in use</div></div>
    </div>
  </div>

  <div class="modal-bg" id="bedModal">
    <div class="modal" style="width:340px;">
      <h3 id="modalBedTitle">Update Bed Status</h3>
      <p style="font-size:13px;color:var(--text-2);margin-bottom:16px;">Select the current status for this bed.</p>
      <form method="POST">
        <input type="hidden" name="bed_num" id="modalBedNum">
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
          <label onclick="selectBedStatus('Available')" id="opt-Available"
            style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:2px solid #C0DD97;background:#EAF3DE;cursor:pointer;transition:.15s;">
            <span style="width:14px;height:14px;border-radius:50%;background:#3B6D11;flex-shrink:0;"></span>
            <div><div style="font-size:13px;font-weight:600;color:#3B6D11;">Available</div><div style="font-size:11px;color:#5a8a30;">Bed is empty and ready to use</div></div>
          </label>
          <label onclick="selectBedStatus('Full')" id="opt-Full"
            style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:2px solid var(--border-2);background:var(--card);cursor:pointer;transition:.15s;">
            <span style="width:14px;height:14px;border-radius:50%;background:#A32D2D;flex-shrink:0;"></span>
            <div><div style="font-size:13px;font-weight:600;color:#A32D2D;">Full / Occupied</div><div style="font-size:11px;color:var(--text-3);">Bed is currently occupied by a patient</div></div>
          </label>
          <label onclick="selectBedStatus('Inactive')" id="opt-Inactive"
            style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:2px solid var(--border-2);background:var(--card);cursor:pointer;transition:.15s;">
            <span style="width:14px;height:14px;border-radius:50%;background:#888780;flex-shrink:0;"></span>
            <div><div style="font-size:13px;font-weight:600;color:#5F5E5A;">Inactive</div><div style="font-size:11px;color:var(--text-3);">Bed is under maintenance or disabled</div></div>
          </label>
        </div>
        <input type="hidden" name="bed_status" id="modalBedStatus" value="">
        <div class="modal-actions">
          <button type="button" onclick="document.getElementById('bedModal').classList.remove('open')" class="btn btn-ghost">Cancel</button>
          <button type="submit" class="btn btn-accent">Save Status</button>
        </div>
      </form>
    </div>
  </div>

<script>
function openBedModal(bedKey, currentStatus) {
  document.getElementById('modalBedTitle').textContent = 'Update ' + bedKey;
  document.getElementById('modalBedNum').value = bedKey;
  document.getElementById('modalBedStatus').value = currentStatus;
  highlightOption(currentStatus);
  document.getElementById('bedModal').classList.add('open');
}

function highlightOption(status) {
  const styles = {
    'Available': {border:'2px solid #C0DD97', bg:'#EAF3DE'},
    'Full':      {border:'2px solid #F09595', bg:'#FCEBEB'},
    'Inactive':  {border:'2px solid #C8C6BE', bg:'#F1EFE8'},
  };
  ['Available','Full','Inactive'].forEach(s => {
    const el = document.getElementById('opt-' + s);
    if(s === status){el.style.border=styles[s].border;el.style.background=styles[s].bg;}
    else{el.style.border='2px solid var(--border-2)';el.style.background='var(--card)';}
  });
}

function selectBedStatus(status) {
  document.getElementById('modalBedStatus').value = status;
  highlightOption(status);
}
</script>
<?php include '../assets/footer.php';?>