<?php

/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 *
 * Write logs, for Debuging easily (can be log into database etc.)
 * NOTICE: the server need to enable fopen configuration
 */
function logResult($type, $data = '')
{
	if (!defined('LOGS_ENABLED') or !LOGS_ENABLED) {
		return;
	}
	$fp = fopen(dirname(dirname(__FILE__)) . "/logs/logs-" . date('Ymd') . ".txt", "a");
	flock($fp, LOCK_EX);
	fwrite($fp, '==== ' . $type . ': ' . date("Y-m-d H:i:s") . " ===========\n");
	fwrite($fp, '==== IP: ' . $_SERVER["REMOTE_ADDR"] . "\n");
	fwrite($fp, var_export($data, true) . "\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}