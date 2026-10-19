<?php
if(session_status()===PHP_SESSION_NONE) session_start();

function requireAdmin(){
    if(empty($_SESSION['admin_id'])){
        header('Location: /hospital_db/login.php');
        exit;
    }
    if($_SESSION['admin_status']!=='approved'){
        session_destroy();
        header('Location: /hospital_db/login.php?error=not_approved');
        exit;
    }
}

function requirePatient(){
    if(empty($_SESSION['patient_account_id'])){
        header('Location: /hospital_db/login.php');
        exit;
    }
}

function isAdmin(){
    return !empty($_SESSION['admin_id']) && $_SESSION['admin_status']==='approved';
}

function isPatient(){
    return !empty($_SESSION['patient_account_id']);
}

function isSuperAdmin(){
    return !empty($_SESSION['is_super']) && $_SESSION['is_super']==1;
}