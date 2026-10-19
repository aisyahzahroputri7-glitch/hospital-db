<?php
session_start();
session_destroy();
header('Location: /hospital_db/login.php');
exit;