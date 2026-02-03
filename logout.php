<?php
/**
 * Çıkış İşlemi
 */

require_once 'includes/auth.php';

startSecureSession();
logoutUser();

header('Location: index.php');
exit;
