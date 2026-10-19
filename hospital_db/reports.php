<?php

require_once 'auth.php';      // untuk file di root
requireAdmin();               // untuk file di root

require_once 'config.php';
$pageTitle='Reports'; $activePage='reports'; $base='.';
include 'assets/sidebar.php';

$totalPatients=$conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$totalDoctors=$conn->query("SELECT COUNT(*) c FROM doctors")->fetch_assoc()['c'];
$totalRecords=$conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'];
$totalRevenue=$conn->query("SELECT SUM(total_amount) s FROM payments WHERE status='Paid'")->fetch_assoc()['s']??0;
$totalPending=$conn->query("SELECT SUM(total_amount) s FROM payments WHERE status='Pending'")->fetch_assoc()['s']??0;

$bySpec=$conn->query("SELECT d.specialization,COUNT(mr.medical_record_id) as total FROM doctors d LEFT JOIN medical_records mr ON d.doctor_id=mr.doctor_id GROUP BY d.specialization ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);
$byMonth=$conn->query("SELECT DATE_FORMAT(visit_date,'%b %Y') as month,COUNT(*) as total FROM medical_records WHERE visit_date >= DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY YEAR(visit_date),MONTH(visit_date) ORDER BY visit_date ASC")->fetch_all(MYSQLI_ASSOC);
$topDiag=$conn->query("SELECT diagnosis,COUNT(*) as total FROM medical_records WHERE diagnosis IS NOT NULL AND diagnosis!='' GROUP BY diagnosis ORDER BY total DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$insUsage=$conn->query("SELECT i.insurance_name,COUNT(p.patient_id) as total FROM insurance i LEFT JOIN patients p ON p.insurance_id=i.insurance_id GROUP BY i.insurance_id ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);
$lowStock=$conn->query("SELECT * FROM medicines WHERE stock < 100 ORDER BY stock ASC")->fetch_all(MYSQLI_ASSOC);

// Rekapan harian (7 hari terakhir)
$dailyRecap=$conn->query("
    SELECT DATE(visit_date) as tgl,
    COUNT(*) as total_visits,
    COUNT(DISTINCT patient_id) as total_patients,
    (SELECT COALESCE(SUM(total_amount),0) FROM payments py JOIN medical_records mr2 ON py.medical_record_id=mr2.medical_record_id WHERE DATE(mr2.visit_date)=DATE(mr.visit_date) AND py.status='Paid') as revenue
    FROM medical_records mr
    WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(visit_date)
    ORDER BY tgl DESC
")->fetch_all(MYSQLI_ASSOC);

// Rekapan mingguan (4 minggu terakhir)
$weeklyRecap=$conn->query("
    SELECT YEAR(visit_date) as yr, WEEK(visit_date) as wk,
    MIN(DATE(visit_date)) as start_date,
    MAX(DATE(visit_date)) as end_date,
    COUNT(*) as total_visits,
    COUNT(DISTINCT patient_id) as total_patients,
    COALESCE((SELECT SUM(py.total_amount) FROM payments py JOIN medical_records mr2 ON py.medical_record_id=mr2.medical_record_id WHERE WEEK(mr2.visit_date)=WEEK(mr.visit_date) AND YEAR(mr2.visit_date)=YEAR(mr.visit_date) AND py.status='Paid'),0) as revenue
    FROM medical_records mr
    WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY YEAR(visit_date), WEEK(visit_date)
    ORDER BY yr DESC, wk DESC
")->fetch_all(MYSQLI_ASSOC);

// Rekapan bulanan (12 bulan terakhir)
$monthlyRecap=$conn->query("
    SELECT DATE_FORMAT(visit_date,'%M %Y') as bulan,
    MONTH(visit_date) as mn, YEAR(visit_date) as yr,
    COUNT(*) as total_visits,
    COUNT(DISTINCT patient_id) as total_patients,
    COALESCE((SELECT SUM(py.total_amount) FROM payments py JOIN medical_records mr2 ON py.medical_record_id=mr2.medical_record_id WHERE MONTH(mr2.visit_date)=MONTH(mr.visit_date) AND YEAR(mr2.visit_date)=YEAR(mr.visit_date) AND py.status='Paid'),0) as revenue
    FROM medical_records mr
    WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(visit_date), MONTH(visit_date)
    ORDER BY yr DESC, mn DESC
")->fetch_all(MYSQLI_ASSOC);

// Rekapan tahunan
$yearlyRecap=$conn->query("
    SELECT YEAR(visit_date) as tahun,
    COUNT(*) as total_visits,
    COUNT(DISTINCT patient_id) as total_patients,
    COALESCE((SELECT SUM(py.total_amount) FROM payments py JOIN medical_records mr2 ON py.medical_record_id=mr2.medical_record_id WHERE YEAR(mr2.visit_date)=YEAR(mr.visit_date) AND py.status='Paid'),0) as revenue
    FROM medical_records mr
    GROUP BY YEAR(visit_date)
    ORDER BY tahun DESC
")->fetch_all(MYSQLI_ASSOC);

?>

  <div class="topbar"><div class="topbar-left"><h2>Reports & Analytics</h2><p>Hospital performance overview</p></div></div>
  <div class="content">
    <div class="stats">
      <div class="stat"><div class="stat-label">Total Patients</div><div class="stat-val"><?=$totalPatients?></div><div class="stat-sub">registered</div></div>
      <div class="stat s2"><div class="stat-label">Total Doctors</div><div class="stat-val"><?=$totalDoctors?></div><div class="stat-sub">active</div></div>
      <div class="stat s3"><div class="stat-label">Medical Records</div><div class="stat-val"><?=$totalRecords?></div><div class="stat-sub">total visits</div></div>
      <div class="stat s4"><div class="stat-label">Total Revenue</div><div class="stat-val" style="font-size:18px;">Rp <?=number_format($totalRevenue,0,',','.')?></div><div class="stat-sub">paid</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="table-card">
        <div class="table-head"><span class="table-title">Visits by Specialization</span></div>
        <table class="tbl"><thead><tr><th>Specialization</th><th>Total Visits</th></tr></thead><tbody>
        <?php foreach($bySpec as $r):?>
          <tr><td><?=htmlspecialchars($r['specialization'])?></td><td><span style="font-weight:600;"><?=$r['total']?></span></td></tr>
        <?php endforeach;?>
        </tbody></table>
      </div>
      <div class="table-card">
        <div class="table-head"><span class="table-title">Top 5 Diagnoses</span></div>
        <table class="tbl"><thead><tr><th>Diagnosis</th><th>Count</th></tr></thead><tbody>
        <?php foreach($topDiag as $r):?>
          <tr><td><?=htmlspecialchars($r['diagnosis'])?></td><td><span class="badge badge-blue"><?=$r['total']?></span></td></tr>
        <?php endforeach;?>
        </tbody></table>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div class="table-card">
        <div class="table-head"><span class="table-title">Monthly Visits (Last 6 Months)</span></div>
        <table class="tbl"><thead><tr><th>Month</th><th>Visits</th></tr></thead><tbody>
        <?php foreach($byMonth as $r):?>
          <tr><td><?=htmlspecialchars($r['month'])?></td><td><span style="font-weight:600;"><?=$r['total']?></span></td></tr>
        <?php endforeach;?>
        </tbody></table>
      </div>
      <div class="table-card">
        <div class="table-head"><span class="table-title">Insurance Usage</span></div>
        <table class="tbl"><thead><tr><th>Insurance</th><th>Patients</th></tr></thead><tbody>
        <?php foreach($insUsage as $r):?>
          <tr><td><?=htmlspecialchars($r['insurance_name'])?></td><td><span style="font-weight:600;"><?=$r['total']?></span></td></tr>
        <?php endforeach;?>
        </tbody></table>
      </div>
    </div>

    <div class="table-card">
      <div class="table-head"><span class="table-title">Low Stock Medicines (below 100 units)</span></div>
      <table class="tbl"><thead><tr><th>#</th><th>Medicine</th><th>Stock</th><th>Unit Price</th></tr></thead><tbody>
      <?php if(empty($lowStock)):?><tr><td class="tbl-empty" colspan="4">All medicines have sufficient stock.</td></tr>
      <?php else:foreach($lowStock as $i=>$m):$sc=$m['stock']<50?'badge-red':'badge-amber';?>
        <tr><td style="color:var(--text-3);font-size:12px;"><?=$i+1?></td><td style="font-weight:500;"><?=htmlspecialchars($m['medicine_name'])?></td><td><span class="badge <?=$sc?>"><?=$m['stock']?> units</span></td><td>Rp <?=number_format($m['unit_price'],0,',','.')?></td></tr>
      <?php endforeach;endif;?>
      </tbody></table>
    </div>

    <!-- RECAP SECTION -->
    <div style="margin-top:8px;">

  </div>

  <!-- Tab buttons -->
  <div style="display:flex;gap:8px;margin-bottom:16px;padding-left:4px;">
    <?php foreach(['daily'=>'Daily (7 Days)','weekly'=>'Weekly (4 Weeks)','monthly'=>'Monthly (12 Months)','yearly'=>'Yearly'] as $k=>$v):?>
    <button onclick="switchRecap('<?=$k?>')" id="tab-<?=$k?>"
      style="padding:8px 18px;border-radius:8px;font-size:13px;font-weight:500;font-family:inherit;cursor:pointer;border:1px solid rgba(0,0,0,0.1);transition:.2s;"
      class="recap-tab">
      <?=$v?>
    </button>
    <?php endforeach;?>
  </div>

  <!-- DAILY -->
  <div id="recap-daily" class="recap-panel">
    <div class="table-card">
      <div class="table-head"><span class="table-title">Daily Recap — Last 7 Days</span></div>
      <table class="tbl">
        <thead><tr><th>Date</th><th>Total Visits</th><th>Unique Patients</th><th>Revenue (Paid)</th></tr></thead>
        <tbody>
        <?php if(empty($dailyRecap)):?>
          <tr><td colspan="4" style="text-align:center;padding:30px;color:#aaa;">No data</td></tr>
        <?php else:foreach($dailyRecap as $r):?>
          <tr>
            <td style="font-weight:500;"><?=date('D, d M Y',strtotime($r['tgl']))?></td>
            <td><span style="font-weight:600;"><?=$r['total_visits']?></span> visits</td>
            <td><?=$r['total_patients']?> patients</td>
            <td style="font-weight:600;color:#0F6E56;">Rp <?=number_format($r['revenue'],0,',','.')?></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- WEEKLY -->
  <div id="recap-weekly" class="recap-panel" style="display:none;">
    <div class="table-card">
      <div class="table-head"><span class="table-title">Weekly Recap — Last 4 Weeks</span></div>
      <table class="tbl">
        <thead><tr><th>Week Period</th><th>Total Visits</th><th>Unique Patients</th><th>Revenue (Paid)</th></tr></thead>
        <tbody>
        <?php if(empty($weeklyRecap)):?>
          <tr><td colspan="4" style="text-align:center;padding:30px;color:#aaa;">No data</td></tr>
        <?php else:foreach($weeklyRecap as $r):?>
          <tr>
            <td style="font-weight:500;">
              Week <?=$r['wk']?> — <?=date('d M',strtotime($r['start_date']))?> – <?=date('d M Y',strtotime($r['end_date']))?>
            </td>
            <td><span style="font-weight:600;"><?=$r['total_visits']?></span> visits</td>
            <td><?=$r['total_patients']?> patients</td>
            <td style="font-weight:600;color:#0F6E56;">Rp <?=number_format($r['revenue'],0,',','.')?></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- MONTHLY -->
  <div id="recap-monthly" class="recap-panel" style="display:none;">
    <div class="table-card">
      <div class="table-head"><span class="table-title">Monthly Recap — Last 12 Months</span></div>
      <table class="tbl">
        <thead><tr><th>Month</th><th>Total Visits</th><th>Unique Patients</th><th>Revenue (Paid)</th></tr></thead>
        <tbody>
        <?php if(empty($monthlyRecap)):?>
          <tr><td colspan="4" style="text-align:center;padding:30px;color:#aaa;">No data</td></tr>
        <?php else:foreach($monthlyRecap as $r):?>
          <tr>
            <td style="font-weight:500;"><?=$r['bulan']?></td>
            <td><span style="font-weight:600;"><?=$r['total_visits']?></span> visits</td>
            <td><?=$r['total_patients']?> patients</td>
            <td style="font-weight:600;color:#0F6E56;">Rp <?=number_format($r['revenue'],0,',','.')?></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- YEARLY -->
  <div id="recap-yearly" class="recap-panel" style="display:none;">
    <div class="table-card">
      <div class="table-head"><span class="table-title">Yearly Recap</span></div>
      <table class="tbl">
        <thead><tr><th>Year</th><th>Total Visits</th><th>Unique Patients</th><th>Revenue (Paid)</th></tr></thead>
        <tbody>
        <?php if(empty($yearlyRecap)):?>
          <tr><td colspan="4" style="text-align:center;padding:30px;color:#aaa;">No data</td></tr>
        <?php else:foreach($yearlyRecap as $r):?>
          <tr>
            <td style="font-weight:600;font-size:16px;"><?=$r['tahun']?></td>
            <td><span style="font-weight:600;"><?=$r['total_visits']?></span> visits</td>
            <td><?=$r['total_patients']?> patients</td>
            <td style="font-weight:600;color:#0F6E56;">Rp <?=number_format($r['revenue'],0,',','.')?></td>
          </tr>
        <?php endforeach;endif;?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
function switchRecap(active){
  // Hide all panels
  document.querySelectorAll('.recap-panel').forEach(p=>p.style.display='none');
  document.querySelectorAll('.recap-tab').forEach(t=>{
    t.style.background='#fff';
    t.style.color='#5F5E5A';
  });
  // Show active
  document.getElementById('recap-'+active).style.display='block';
  document.getElementById('tab-'+active).style.background='#1a1a18';
  document.getElementById('tab-'+active).style.color='#fff';
  document.getElementById('tab-'+active).style.border='1px solid #1a1a18';
}
// Init
switchRecap('daily');
</script>

<?php include 'assets/footer.php';?>