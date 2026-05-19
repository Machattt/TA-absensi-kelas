<?php
session_start();
session_unset();
session_destroy();
header("Location: /absensi kelas/auth/login.php");
exit();
?>
