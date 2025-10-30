<?php
// 设置时区为太平洋时间
date_default_timezone_set('America/Los_Angeles');

// APPLICATION
define('APPLICATION', 'Admin');

// HTTP
define('HTTP_SERVER', 'https://001136.com/admin67676/');
define('HTTP_CATALOG', 'https://001136.com/');

// DIR
define('DIR_OPENCART', '/www/wwwroot/001136.com/');
define('DIR_APPLICATION', DIR_OPENCART . 'admin67676/');
define('DIR_EXTENSION', DIR_OPENCART . 'extension/');
define('DIR_IMAGE', DIR_OPENCART . 'image/');
define('DIR_SYSTEM', DIR_OPENCART . 'system/');
define('DIR_CATALOG', DIR_OPENCART . 'catalog/');
define('DIR_STORAGE', '/storage_oc/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'opencart');
define('DB_PASSWORD', 'epSNAcH2n3sBfhGb');
define('DB_DATABASE', 'opencart');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');

define('DB_SSL_KEY', '');
define('DB_SSL_CERT', '');
define('DB_SSL_CA', '');

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
