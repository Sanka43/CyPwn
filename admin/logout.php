<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/auth.php';

admin_logout();
header('Location: login.php');
exit;
