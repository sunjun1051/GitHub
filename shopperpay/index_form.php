<?php
//载入配置文件
require 'init.php';

// 载入ChinaPay接口类
// Load ChinaPay interface class
require 'lib/shopperapi.class.php';

// 载入插件处理类
// Load payment plugin process class
require 'lib/shopperpay.class.php';

// 载入银联提交处理类
// Load ChinaPay Submit process class
require 'lib/chinapay_submit.class.php';

$shopper_api = new ShopperAPI();
$sp = new ShopperPay();
$cps = new ChinaPaySubmit();

//获取商户订单信息
$product_info = $sp->getOrderForm() or $sp->sendError('920', '非法访问！');

$order = array(
    // GS商户号，必须为15位
    //	'OrdId' => '1512151021123459',
    'GSMerId' => $shopperpay_config['GSMerId'],
    // 商户物流号
    'LogisticsId' => $shopperpay_config['LogisticsId'],
    // 订单号，必须为16位
    //	'OrdId' => '1512151021123459',
    'MerOrdId' => $_POST['merOrdId'],
    // 交易金额
    'ProTotalAmt' => $_POST['proTotalAmt'],
    // 币种，必须为16位
	'CuryId' => $shopperpay_config['CuryId'],
    // 交易日期，电商下单时间，表示格式为：YYYYMMDD
//     'TransDate' => date('Ymd'),
    // 交易类型，取值范围为："0001"和"0002"， 其中"0001"表示消费交易，"0002"表示退货交易。
//     'TransType' => '0001',
    // 商品信息,
//     'ProductInfo' => $product_info,
    'ProductInfo' => json_encode($product_info),
);

// var_dump($order['ProductInfo']);
// return;


$submitUrl = 'http://192.168.0.101:8080/resources/plugin/pre_pay.jsp';
// $submitUrl = 'http://localhost/test/index.php';
//发送订单信息到GS商户
$cps->buildFormSubmit($order, $submitUrl);



// session_start();
// $_SESSION['SHOPPER_PAY_ORDER'] = $order;

/*
 *  调用GS接口传送订单数据
 *  $send_order_info 返回的是个数组
 */
// $send_order_info = $shopper_api->call('api地址', $order);

// var_dump($send_order_info);
// 判断GS返回值，如果错误或者为空， 则提示用户错误。
// $sp->checkReturn($send_order_info);

// var_dump($order);
// $send_order_info or $sp->sendError('908', "订单发送到GS失败");  




