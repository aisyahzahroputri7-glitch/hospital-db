<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
if(!$id){flash('Invalid ID.','danger');redirect('index.php');}
$stmt=$conn->prepare("SELECT patient_name FROM patients WHERE patient_id=?");
$stmt->bind_param('i',$id);$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row){flash('Patient not found.','danger');redirect('index.php');}
$stmt2=$conn->prepare("DELETE FROM patients WHERE patient_id=?");
$stmt2->bind_param('i',$id);$stmt2->execute();
flash("Patient {$row['patient_name']} has been deleted.");
redirect('index.php');