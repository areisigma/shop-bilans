<?php
session_start();
session_destroy();

// Usuń cookie
setcookie('shop_bilans_user', '', time() - 3600, '/');

header('Location: login.php');
exit;
?>
