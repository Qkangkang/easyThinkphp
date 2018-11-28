<?php 
!defined('APP_DEBUG') and exit('Access Denied.');

include CORE_DIR . 'functions.php';
include CORE_DIR . 'vendor/log.php';
include CORE_DIR . 'vendor/debug.php';
include CORE_DIR . 'vendor/request.php';

include CORE_DIR . 'lib/Controller.class.php';
include CORE_DIR . 'lib/Model.class.php';
include CORE_DIR . 'lib/View.class.php';
include CORE_DIR . 'lib/Sql.class.php';
include CORE_DIR . 'lib/Route.class.php';