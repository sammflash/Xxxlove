<?php
/**
 * The canonical "/admin" entry point. Sends an already-signed-in admin
 * straight to the dashboard, everyone else to the login screen.
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

redirect(current_admin() ? '/admin/dashboard.php' : '/admin/login.php');
