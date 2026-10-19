<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
$stmt=$conn->prepare("SELECT p.patient_name FROM examination_schedules es JOIN patients p ON es.patient_id=p.patient_id WHERE es.schedule_id=?");
$stmt->bind_param('i',$id);$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row){flash('Schedule not found.','danger');redirect('index.php');}
$stmt2=$conn->prepare("DELETE FROM examination_schedules WHERE schedule_id=?");
$stmt2->bind_param('i',$id);$stmt2->execute();
flash("Schedule for {$row['patient_name']} has been deleted.");
redirect('index.php');