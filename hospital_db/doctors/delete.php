<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
$stmt=$conn->prepare("SELECT doctor_name FROM doctors WHERE doctor_id=?");
$stmt->bind_param('i',$id);$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row){flash('Doctor not found.','danger');redirect('index.php');}
$stmt2=$conn->prepare("DELETE FROM doctors WHERE doctor_id=?");
$stmt2->bind_param('i',$id);$stmt2->execute();
flash("Doctor {$row['doctor_name']} has been deleted.");
redirect('index.php');