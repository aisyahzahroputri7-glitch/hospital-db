<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
$stmt=$conn->prepare("SELECT p.patient_name FROM medical_records mr JOIN patients p ON mr.patient_id=p.patient_id WHERE mr.medical_record_id=?");
$stmt->bind_param('i',$id);$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row){flash('Record not found.','danger');redirect('index.php');}
$stmt2=$conn->prepare("DELETE FROM medical_records WHERE medical_record_id=?");
$stmt2->bind_param('i',$id);$stmt2->execute();
flash("Medical record of {$row['patient_name']} has been deleted.");
redirect('index.php');