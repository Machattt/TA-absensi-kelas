<?php
session_start(); // Mulai sesi buat dihapus
session_unset(); // Bersihin semua variabel sesi
session_destroy(); // Hancurkan sesinya sekalian

// Lempar balik ke halaman login
header("Location: ../auth/login.php");
exit();
?>
