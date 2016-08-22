<?php
/**
 * 交易查询接口
 * Trade Query Interface
 */

// 载入配置文件
// Load configuration file
require 'init.php';

// 载入海淘天下接口类
// Load GlobalShopper Interface class
require 'lib/shopperapi.class.php';

// 载入插件处理类
// Load payment process class
require 'lib/shopperpay.class.php';

// 载入ChinaPay接口类
// Load ChinaPay interface class
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
$resv = filter_input(INPUT_POST, 'resv', FILTER_SANITIZE_STRING);

// 从GS商户端取得GS订单号
// Get GlobalShopper Order ID By Merchant Order ID
if (empty($gsOrdId)) {
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
    
    !empty($order_id_query_result['isSuccess']) and $order_id_query_result['isSuccess'] == '1' or $sp->sendError('919', '转换订单ID失败！');
    $order_id_origin = $merOrdId;
    $gsOrdId = $order_id_query_result['gsOrdId'];
}


// 获取交易查询参数
// get order query parameters
$order_query_data = array(
	'MerId' => $shopperpay_config['GSMerId'],
	'TransType' => '0001',
	'OrdId' => $gsOrdId,    // 原始支付订单号，16位长度
	'TransDate' => $order_date,    // 订单支付日期，YYYYMMDD，8位
	'Version' => '20080515',
	'Resv' => $resv,
);

// 发起交易查询请求
// send order query real request
$query_result = $chinapay_api->query($order_query_data);
$query_result or $sp->sendError('911', "查询请求失败！");

// 交易查询失败
// order query failure
isset($query_result['ResponeseCode']) or $sp->sendError('912', "服务器返回错误！");

if ($query_result['ResponeseCode'] !== '0') {
	$query_gs_notify_data = array(
		"merId" => $shopperpay_config['MerId'],
	    'gsMerId'=> $shopperpay_config['GSMerId'],
		"ordId" => $gsOrdId,
		"transtype" => '0001',
	);
	
	//GS密钥签名
	$query_gs_notify_data['gsChkValue'] = $sp->get_signed_data($query_gs_notify_data);
	$query_gs_notify_data['pluginVersion'] = $shopperpay_config['plugin_version'];
	$query_gs_notify_data["responseCode"] = $query_result['ResponeseCode'];
	$query_gs_notify_data["message"] = $query_result['Message'];

	$notify_result = $shopper_api->call('order_inquiry_notification.jhtml', $query_gs_notify_data);
	
	!empty($notify_result['isSuccess']) and $notify_result['isSuccess'] == '1' or $sp->sendError('915', '同步查询失败数据到海淘天下失败！');
	
	$sp->sendError('913', $query_result['Message']);
	
	
	
}

// 校验查询结果数据
// verify query result sign is valid or not
$query_result_verify = $chinapay_api->verifyQueryResultData($query_result);
$query_result_verify or $sp->sendError('914', "查询数据校验错误！");



// 调用海淘天下查询结果通知接口
// Call GlobalShopper query result notification interface
$query_gs_notify_data = array(
	"merId" => $shopperpay_config['MerId'],
    'gsMerId'=> $shopperpay_config['GSMerId'],
	"ordId" => $gsOrdId,
	"amount" => $query_result['amount'],
	"currencycode" => $query_result['currencycode'],
	"transdate" => $query_result['transdate'],
	"transtype" => $query_result['transtype'],
	"status" => $query_result['status'],
	"gateId" => $query_result['GateId'],
	"priv1" => $query_result['Priv1'],
);
    //GS密钥签名
	$query_gs_notify_data['gsChkValue'] = $sp->get_signed_data($query_gs_notify_data);
	$query_gs_notify_data['pluginVersion'] = $shopperpay_config['plugin_version'];
	$query_gs_notify_data["responseCode"] = $query_result['ResponeseCode'];
	$query_gs_notify_data["message"] = '';

    $notify_result = $shopper_api->call('pay_plugin/order_inquiry_notification.jhtml', $query_gs_notify_data);
    
    $sign_data = $notify_result['merOrdId'].$notify_result['gsOrdId'];
    $shopper_api->verify($notify_result['gsChkValue'], $sign_data) or $sp->sendError('910', '验证签名失败！');
    
    !empty($notify_result['isSuccess']) and $notify_result['isSuccess'] == '1' or $sp->sendError('915', '同步订单查询成功数据到海淘天下失败！');


// 调用商户查询结果通知接口
// Call Merchant query result notify interface
$query_seller_notify_order_data = array(
	'merid' => $shopperpay_config['MerId'],
	'orderno' => $query_result['orderno'],
	"transdate" => $query_result['transdate'],
	"amount" => $query_result['amount'],
	"currencycode" => $query_result['currencycode'],
	"transtype" => $query_result['transtype'],
	"status" => $query_result['status'],
	'checkvalue' => $query_result['checkvalue'],
	"GateId" => $query_result['GateId'],
	"Priv1" => $query_result['Priv1'],
);
$query_seller_notify_data = array(
	'MerOrdId' => $order_id_origin,
    'GSOrdId' => $gsOrdId,
	'OrderInfo' => $query_seller_notify_order_data
);

// 返回商户
echo json_encode(($query_seller_notify_data));


//银行返回商户的信息，接口方是商户
// $notify_result = $seller_api->call(SELLER_QUERY_API, $query_seller_notify_data);
// !empty($notify_result['isSuccess']) and $notify_result['isSuccess'] == '1' or $sp->sendError('916', '同步订单查询成功数据到商户失败！');

// Result for query caller
// $sp->sendSuccess($query_seller_notify_data);