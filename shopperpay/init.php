<?php
/*
 * 插件配置文件
 * Payment plugin configuration file
 */

//引入用户配置文件
require 'config.php';

$china_pay_url = ENV_SWITCH ? 'https://payment.chinapay.com/pay/TransGet' : 'http://payment-test.chinapay.com/pay/TransGet';
$china_query_url = ENV_SWITCH ? 'http://control.chinapay.com/QueryWeb/processQuery.jsp' : 'http://payment-test.chinapay.com/QueryWeb/processQuery.jsp';
$chinapay_refund_url = ENV_SWITCH ? 'http://console.chinapay.com/refund/SingleRefund.jsp' : 'http://payment-test.chinapay.com/refund1/SingleRefund.jsp';

// 测试环境ChinaPay支付接口地址
// Test development environment, ChinaPay payment interface address.
define('CHINAPAY_PAY_URL', $china_pay_url);

// 生产环境ChinaPay支付接口地址
// Production running environment, ChinaPay payment interface address.
// define('CHINAPAY_PAY_URL', 'https://payment.chinapay.com/pay/TransGet');

// 查询与退款接口新增配置项 ============================================================================================
// The new additional configuration items about order query and refund APIs
//
// 测试环境ChinaPay查询接口地址
// ChinaPay order query API url in TEST environment
define('CHINAPAY_QUERY_URL', $china_query_url);
// 生产环境ChinaPay查询接口地址
// ChinaPay order query API url in PRODUCT environment
// define('CHINAPAY_QUERY_URL', 'http://control.chinapay.com/QueryWeb/processQuery.jsp');

// 测试环境ChinaPay退款接口地址
// ChinaPay refund API url in TEST environment
define('CHINAPAY_REFUND_URL', $chinapay_refund_url);
// 生产环境ChinaPay退款接口地址
// ChinaPay refund API url in PRODUCT environment
// define('CHINAPAY_REFUND_URL', 'http://console.chinapay.com/refund/SingleRefund.jsp');

//插件版本号：
$shopperpay_config['plugin_version'] = 'v2.0.1';

// PHP 5.5 关闭已废弃提示
// PHP 5.5 Shutdown the Deprecated warning
error_reporting(error_reporting() ^ E_DEPRECATED ^ E_NOTICE);

// 交易回调地址
$self_url = ((!empty($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$self_url = str_replace('localhost', '192.168.0.105', $self_url);
define('NOTIFY_URL', dirname($self_url) . '/notify_url.php');
define('RETURN_URL', dirname($self_url) . '/return_url.php');

// 订单FORM提交GS返回的地址
define('PAY_INFO_URL', dirname($self_url).'/pay.php');

// 退款通知回调地址
// Refund notification callback url, change this if internal access address this not the same as external one.
define('REFUND_NOTIFY_URL', dirname($self_url) . '/refund_notify_url.php');
// =====================================================================================================================

// 海淘天下接口地址
// Test development environment,Globalshopper API interface address.
// define('GS_API', 'http://test.globalshopper.com.cn/');
define('GS_API', 'http://192.168.0.100:8080/');


// 海淘天下订单页
define('GS_ORDER_LIST', 'http://test.globalshopper.com.cn/member/order/list.jhtml');

// 查询与退款接口新增配置项 ============================================================================================
// The new additional configuration items about order query and refund APIs
//
// =====================================================================================================================


// 银联相关配置 ========================================================================================================
// Configurations about ChinaPay

// 支付接入版本号，如“20080515”
// payment integration version number.
$shopperpay_config['Version'] = '20080515';

// 后台交易接收URL，长度不要超过80个字节，必填，建议使用IP代替域名
// Background server transaction handle URL, less than 80 byte.Better to use IP than domain.
$shopperpay_config['BgRetUrl'] = NOTIFY_URL;

// 页面交易接收URL，长度不要超过80个字节，必填
// page transaction handle URL, less than 80 byte.
$shopperpay_config['PageRetUrl'] = RETURN_URL;

// 支付网关号，当GateId值为空时，浏览器会自动跳转至ChinaPay银行列表选择页面；
// 当GateId为具体网关号时，则浏览器直接跳转至网关号对应的页面。
// payment gate number , browser will redirect to ChinaPay bank list choose page when GateId is empty.
// browser will redirect to specific page when GateId is assigned to other specified values.
$shopperpay_config['GateId'] = '8613';

// 境外商户标识，默认为00，必填
// oversea merchant tag , default = '00'
$shopperpay_config['ExtFlag'] = '00';

// 启用日志
// Log switch
define('LOGS_ENABLED', true);
