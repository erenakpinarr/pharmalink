<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../includes/auth.php';
session_unset();
session_destroy();
header('Location: ' . sayf('index.php'));
exit;
