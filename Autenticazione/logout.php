<?php
session_start();
session_destroy();
header("Location: Autenticazione/login.php");
exit;
?>
