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


// 载入商户接口类
// Load merchant interface class
require 'lib/sellerapi.class.php';


$shopper_api = new ShopperAPI();
$seller_api = new SellerAPI();
$sp = new ShopperPay();


$_POST or $sp->sendError('920', '非法访问！');

$gsOrdId = filter_input(INPUT_POST, 'GSOrdId', FILTER_VALIDATE_STRING);
$merOrdId = filter_input(INPUT_POST, 'MerOrdId', FILTER_VALIDATE_STRING);


// 向GS发起订单查看请求
$order_gs_query_data = array(
	'gsMerId' => $shopperpay_config['GSMerId'],    //GS商户号
    'gsOrdId' => $gsOrdId,                          //GS订单号
	'merOrdId' => $merOrdId,                       //商户订单号
);
//GS密钥签名
$order_gs_query_data['gsChkValue'] = $sp->get_signed_data($order_gs_query_data);
$order_gs_query_data['pluginVersion'] = $shopperpay_config['plugin_version'];

$order_gs_query_result = $shopper_api->call('pay_plugin/gsorder_detail.jhtml', $order_gs_query_data);

$sign_data = $order_gs_query_result['merOrdId'].$order_gs_query_result['gsOrdId'].$order_gs_query_result['gsOrdStatus'].json_encode($order_gs_query_result['ordPackageInfo']).json_encode($order_gs_query_result['consigneeInfo']);
$shopper_api->verify($order_gs_query_result['gsChkValue'], $sign_data) or $sp->sendError('910', '验证签名失败！');
$order_gs_query_result and $order_gs_query_result['isSuccess'] == '1' or $sp->sendError('919', '查询GS订单失败！');

// 返回商户;
unset($order_gs_query_result['gsChkValue']);
echo json_encode(($order_gs_query_result));




