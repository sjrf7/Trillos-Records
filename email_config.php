<?php
// Email Configuration for Password Recovery
// IMPORTANT: Update these settings with your actual SMTP credentials

require_once 'load_env.php';

define('SMTP_HOST', getenv('SMTP_HOST')); 
define('SMTP_PORT', getenv('SMTP_PORT')); 
define('SMTP_USERNAME', getenv('SMTP_USER')); 
define('SMTP_PASSWORD', getenv('SMTP_PASS')); 
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME'));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION'));

// Site URL
define('SITE_URL', getenv('SITE_URL'));
?>
