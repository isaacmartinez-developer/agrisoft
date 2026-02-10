<?php
define('DB_HOST','127.0.0.1');
define('DB_NAME','agrisoft');
define('DB_USER','root');
define('DB_PASS','');
define('APP_NAME','AGRISOFT');
define('BASE_URL','/agrisoft/public');

define('ALERT_EMAIL_ENABLED', false);
define('ALERT_EMAIL_TO','');

if (session_status()===PHP_SESSION_NONE) session_start();
