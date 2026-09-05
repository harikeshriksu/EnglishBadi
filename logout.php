<?php
require_once __DIR__ . '/includes/config.php';

learner_logout();
flash_set('success', 'You have been logged out.');
redirect(base_url('/'));
