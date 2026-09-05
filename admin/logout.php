<?php
require_once __DIR__ . '/../includes/config.php';

admin_logout();
redirect(base_url('/admin/index.php'));
