<?php
session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect ke halaman login
header('Location: login.php');
exit();
?>