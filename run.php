<?php
require_once("php/other/defines.php");
require_once("php/other/consts.php");
require_once("php/libs/cfunctions.php");
require_once("php/libs/interfaces/IEncryptionUtil.php");
require_once("php/libs/base/EncryptionBase.php");
require_once("php/libs/EncryptionLegacyUtil.php");
require_once("php/libs/EncryptionUtil.php");
require_once("php/libs/DBClass.php");
require_once("php/daemon/jsonRPCClient.php");
require_once("php/daemon/RPC.php");
require_once ('MigrationUtil.php');

(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('cli only');

$util = new MigrationUtil();
$util->run();
