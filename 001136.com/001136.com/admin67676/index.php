<?php
// 设置时区为太平洋时间
date_default_timezone_set('America/Los_Angeles');

// Version
define('VERSION', '4.1.0.3');

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Installs
if (!defined('DIR_APPLICATION')) {
	header('Location: ../install/index.php');
	exit();
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Framework
require_once(DIR_SYSTEM . 'framework.php');
