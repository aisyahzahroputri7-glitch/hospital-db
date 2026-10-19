<?php
require_once '../config.php';
$id=intval($_GET['id']??0);
if(!$id){flash('Invalid ID.','danger');redirect('index.php');}
$conn->query("UPDATE examination_schedules SET schedule_status='Completed' WHERE schedule_id=$id");
flash("Schedule has been marked as completed.");
redirect('index.php');
