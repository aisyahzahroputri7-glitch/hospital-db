<?php
require_once 'config.php';

$type=$_POST['type']??'';
$email=trim($_POST['email']??'');
$password=$_POST['password']??'';

if($type==='admin'){
    // Validasi domain email
    if(!isAdminEmail($email)){
        redirect('login.php?error=not_admin_email&tab=admin');
    }
    $stmt=$conn->prepare("SELECT * FROM admins WHERE email=?");
    $stmt->bind_param('s',$email);$stmt->execute();
    $admin=$stmt->get_result()->fetch_assoc();
    if(!$admin || !password_verify($password,$admin['password'])){
        redirect('login.php?error=admin_failed&tab=admin');
    }
    if($admin['status']==='pending'){
        redirect('login.php?error=pending&tab=admin');
    }
    if($admin['status']==='rejected'){
        redirect('login.php?error=rejected&tab=admin');
    }
    $_SESSION['admin_id']=$admin['admin_id'];
    $_SESSION['admin_name']=$admin['name'];
    $_SESSION['admin_email']=$admin['email'];
    $_SESSION['admin_status']=$admin['status'];
    $_SESSION['is_super']=$admin['is_super'];
    redirect('index.php');
}

if($type==='patient'){
    $stmt=$conn->prepare("SELECT * FROM patient_accounts WHERE email=?");
    $stmt->bind_param('s',$email);$stmt->execute();
    $account=$stmt->get_result()->fetch_assoc();
    if(!$account || !password_verify($password,$account['password'])){
        redirect('login.php?error=patient_failed&tab=patient');
    }
    $_SESSION['patient_account_id']=$account['account_id'];
    $_SESSION['patient_name']=$account['name'];
    $_SESSION['patient_id']=$account['patient_id'];
    redirect('patient/dashboard.php');
}

redirect('login.php');