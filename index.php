<?php
require __DIR__ . '/config.php';

header('Location: ' . (currentUser() ? 'pages/inventory.php' : 'pages/register.php'));
exit;