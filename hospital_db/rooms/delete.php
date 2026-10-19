<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
$stmt=$conn->prepare("SELECT room_name FROM rooms WHERE room_id=?");
$stmt->bind_param('i',$id);$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();
if(!$row){flash('Room not found.','danger');redirect('index.php');}
$stmt2=$conn->prepare("DELETE FROM rooms WHERE room_id=?");
$stmt2->bind_param('i',$id);$stmt2->execute();
flash("Room {$row['room_name']} has been deleted.");
redirect('index.php');