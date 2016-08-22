<?php
/**
 * 退款接口
 * Refund Interface
 */


// 载入配置文件
// Load configuration file
require 'init.php';

// 载入海淘天下接口类
// Load GlobalShopper Interface class
require 'lib/shopperapi.class.php';

// 载入插件处理类
// Load GlobalShopper process class
require 'lib/shopperpay.class.php';

// 载入ChinaPay接口类
// Load ChinaPay Interface class
require 'lib/chinapayapi.class.php';

// 载入商户接口类
// Load merchant interface class
require 'lib/sellerapi.class.php';

$chinapay_api = new ChinaPayAPI();
$shopper_api = new ShopperAPI();
$seller_api = new SellerAPI();
$sp = new ShopperPay();

$_POST or $sp->sendError('920', '非法访问！');

$gsOrdId = filter_input(INPUT_POST, 'GSOrdId', FILTER_VALIDATE_STRING);
$merOrdId = filter_input(INPUT_POST, 'MerOrdId', FILTER_VALIDATE_STRING); 
$order_date = filter_input(INPUT_POST, 'order_date', FILTER_VALIDATE_INT);
$refund_amount = filter_input(INPUT_POST, 'refund_amount', FILTER_VALIDATE_FLOAT);
$priv1 = filter_input(INPUT_POST, 'priv1', FILTER_SANITIZE_STRING);

// Get GlobalShopper Order ID By Merchant Order ID
$order_id_query_data = array(
    'gsMerId' => $shopperpay_config['GSMerId'],
	'merOrdId' => $merOrdId,
	'gsOrdId' => $gsOrdId,
);
//GS密钥签名
$order_id_query_data['gsChkValue'] = $sp->get_signed_data($order_id_query_data);
$order_id_query_data['pluginVersion'] = $shopperpay_config['plugin_version'];

$order_id_query_result = $shopper_api->call('pay_plugin/gs_mer_order.jhtml', $order_id_query_data);

$sign_data = $order_id_query_result['merOrdId'].$order_id_query_result['gsOrdId'];
$shopper_api->verify($order_id_query_result['gsChkValue'], $sign_data) or $sp->sendError('910', '验证签名失败！');

!empty($order_id_query_result['isSuccess']) and $order_id_query_result['isSuccess'] == '1' or $sp->sendError('929', '转换订单ID失败！');
$order_id_origin = $merOrdId;
$gsOrderId = $order_id_query_result['gsOrdId'];


// 组合退款参数
// combine refund parameters
$order_refund_data = array(
	'MerID' => $shopperpay_config['MerId'],
	'TransType' => '0002',
	'OrderId' => $gsOrdId,    // 原始支付订单号，16位长度
	'RefundAmount' => $chinapay_api->formatAmt($shopperpay_config['CuryId'], $refund_amount),    // 原始支付订单号，16位长度
	'TransDate' => $order_date,    // 订单支付日期，YYYYMMDD，8位
	'Version' => '20070129',
	'ReturnURL ' => REFUND_NOTIFY_URL,
	'Priv1' => $priv1,
);

// // 发起退款请求
// // send refund request
$refund_result = $chinapay_api->refund($order_refund_data);
$refund_result or $sp->sendError('921', "退款请求失败！");


// 退款失败
// refund failure
isset($refund_result['ResponseCode']) or $sp->sendError('922', "服务器返回错误！");
if ($refund_result['ResponseCode'] !== '0') {
	$refund_gs_notify_data = array(
		"merId" => $shopperpay_config['MerId'],
	    'gsMerId' => $shopperpay_config['GSMerId'],
		"ordId" => $gsOrderId,
		"transtype" => '0002',
	);
	
	//GS密钥签名
	$refund_gs_notify_data['gsChkValue'] = $sp->get_signed_data($refund_gs_notify_data);
	$refund_gs_notify_data['pluginVersion'] = $shopperpay_config['plugin_version'];
	$refund_gs_notify_data["responseCode"] = $refund_result['ResponseCode'];
	$refund_gs_notify_data["message"] = $refund_result['Message'];
	
	$notify_result = $shopper_api->call('pay_plugin/refund_result_notification.jhtml', $refund_gs_notify_data);
	
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
	"ordId" => $gsOrderId,
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

$notify_result = $shopper_api->call('pay_plugin/refund_result_notification.jhtml', $refund_gs_notify_data);

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
	'MerOrdId' => $order_id_origin,
    'GSOrdId' => $gsOrderId,
	'OrderInfo' => $refund_seller_notify_order_data
);

echo json_encode($refund_seller_notify_data);