<?php
/**
 * 插件入口地址
 * Plugin entry address
 */
session_start();
// 载入配置文件
// Load configuration file
require 'init.php';

// 载入银联提交处理类
// Load ChinaPay Submit process class
require 'lib/chinapay_submit.class.php';

// 载入插件处理类
// Load payment plugin process class
require 'lib/shopperpay.class.php';

$cps = new ChinaPaySubmit();
$sp = new ShopperPay();

$order_session = $_SESSION['SHOPPER_PAY_ORDER'] ?: $sp->sendError('101', '订单未找到');

// 接受并处理session数据
$order_session ['ProductInfo'] = json_encode($order_session['ProductInfo']);

// 添加config内的必要支付数据
$order_data = array(
    'GSMerId' => $shopperpay_config['GSMerId'],
    'LogisticsId' => $shopperpay_config['LogisticsId'],
    'PluginVersion' => $shopperpay_config['plugin_version'],
    'CuryId' => $shopperpay_config['CuryId'],
    'PayInfoUrl' => dirname($self_url).'/pay.php',
);

// 对相关数据进行签名
$sign_data = array(
              $order_session['MerOrdId'],
              $order_data['GSMerId'],
              $order_data['LogisticsId'],
              $order_session['ProTotalAmt'],
              $order_data['CuryId'],
              $order_data['PayInfoUrl']
    );

// var_dump($sign_data);


$order_data['GSChkValue'] = $sp->get_signed_data($sign_data);

// 合并数据， 准备提交
$shopper_pay_order = $order_session + $order_data;

// var_dump($shopper_pay_order);return;

// Form表单提交到GS商城
$cps->buildFormSubmit($shopper_pay_order, GS_API.'pay_plugin/validate_merchant.jhtml');
