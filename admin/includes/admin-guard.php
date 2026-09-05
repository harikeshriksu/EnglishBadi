<?php
/**
 * Include this AFTER includes/config.php at the top of every admin page
 * (except admin/index.php itself and setup.php). Enforces login and the
 * 8-hour idle timeout, and exposes the logged-in admin as $currentAdmin.
 */
require_admin();
$currentAdmin = current_admin();
