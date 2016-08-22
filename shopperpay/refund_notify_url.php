<?php
/**
 * 退款结果通知地址
 * Refund result notify address
 */

// 载入配置文件
// Load configuration file
require 'init.php';

// 载入海淘天下接口类
// Load GlobalShopper interface class
require 'lib/shopperapi.class.php';

// 载入商户接口类
// Load merchant interface class
require 'lib/sellerapi.class.php';

// 载入ChinaPay接口类
// Load ChinaPay Interface class
require 'lib/chinapayapi.class.php';

// 载入插件处理类
// Load GlobalShopper process class
require 'lib/shopperpay.class.php';

$shopper_api = new ShopperAPI();
$seller_api = new SellerAPI();
$chinapay_api = new ChinaPayAPI();
$sp = new ShopperPay();

// 接收支付结果数据
// get payment result data
$refund_result = $chinapay_api->getRefundResult();
$refund_result or $chinapay_api->logNotifyError('退款数据接收失败', $refund_result);

// 退款失败
// refund failure
isset($refund_result['ResponseCode']) or $sp->sendError('922', "服务器返回错误！");
if ($refund_result['ResponseCode'] !== '0') {
	$refund_gs_notify_data = array(
		"merId" => $shopperpay_config['MerId'],
	    'gsMerId' => $shopperpay_config['GSMerId'],
		"ordId" => $refund_result['OrderId'],
		"transtype" => '0002',
	);
	
	//GS密钥签名
	$refund_gs_notify_data['gsChkValue'] = $sp->get_signed_data($refund_gs_notify_data);
	$refund_gs_notify_data['pluginVersion'] = $shopperpay_config['plugin_version'];
	$refund_gs_notify_data["responseCode"] = $refund_result['ResponseCode'];
	$refund_gs_notify_data["message"] = $refund_result['Message'];
	
	$url = 'http://192.168.0.101:8080/pay_plugin/refund_result_notification.jhtml';
	$notify_result = $shopper_api->call($url, $refund_gs_notify_data);
	
	!empty($notify_result['isSuccess']) and $notify_result['isSuccess'] == '1' or $sp->sendError('925', '同步退款失败数据到海淘天下失败！');
	$sp->sendError('923', $refund_result['Message']);
}

// 校验退款结果数据
// verify refund result sign is valid or not
$refund_result_verify = $chinapay_api->verifyRefundResultData($refund_result);
$refund_result_verify or $sp->sendError('924', "退款数据校验错误！");

// 调用海淘天下退款结果通知接口
// Call GlobalShopper refund result notification interface
$refund_gs_notify_data = array(
	"merId" => $shopperpay_config['MerId'],
    "gsMerId"=> $shopperpay_config['GSMerId'],
	"ordId" => $refund_result['OrderId'],
	"processDate" => $refund_result['ProcessDate'],
	"sendTime" => $refund_result['SendTime'],
	"transtype" => "0002",
	"refundAmount" => $refund_result['RefundAmout'],
	"status" => $refund_result['Status'],
	"priv1" => $refund_result['Priv1'],
);

//GS密钥签名
$refund_gs_notify_data['gsChkValue'] = $sp->get_signed_data($refund_gs_notify_data);
$refund_gs_notify_data['pluginVersion'] = $shopperpay_config['plugin_version'];
$refund_gs_notify_data["responseCode"] = $refund_result['ResponseCode'];
$refund_gs_notify_data["message"] = '';

$url = 'http://192.168.0.101:8080/pay_plugin/refund_result_notification.jhtml';
$notify_result = $shopper_api->call($url, $refund_gs_notify_data);

$sign_data = $notify_result['merOrdId'].$notify_result['gsOrdId'];
$shopper_api->verify($notify_result['gsChkValue'], $sign_data) or $sp->sendError('910', '验证签名失败！');

!empty($notify_result['isSuccess']) and $notify_result['isSuccess'] == '1' or $sp->sendError('925', '同步退款成功数据到海淘天下失败！');

// 调用商户退款结果通知接口
// Call Merchant Refund notify interface
$refund_seller_notify_order_data = array(
	'merid' => $shopperpay_config['MerId'],
	'orderno' => $refund_result['OrderId'],
	'ProcessDate' => $refund_result['ProcessDate'],
	'sendtime' => $refund_result['SendTime'],
	'transtype' => $refund_result['TransType'],
	'refundamount' => $refund_result['RefundAmout'],
	'refundstatus' => $refund_result['Status'],
	'checkvalue' => $refund_result['CheckValue'],
);
$refund_seller_notify_data = array(
	'MerOrdId' => $notify_result['merOrdId'],
    'GSOrdId' => $refund_result['OrderId'],
	'OrderInfo' => $refund_seller_notify_order_data
);

//商户提供接口， 接受数据
$notify_result = $seller_api->call(SELLER_REFUND_API, $refund_seller_notify_data);
!empty($notify_result['isSuccess']) and $notify_result['isSuccess'] == '1' or $sp->sendError('926', '同步退款成功数据到商户失败！');
