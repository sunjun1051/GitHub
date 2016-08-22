<?php
/**
 * 演示订单数据生成
 */
require '../init.php';

$product_info = array(
	// 可以是多条商品数据
	array(
		// 商品名称
		'productName' => 'Anti Aging Eye Cream',
		// 商品属性，包含name和value的json数组字符串格式
		'productAttr' => '',
		// 商品图片链接地址
		'imageUrl' => '',
		// 商品单价
		'perPrice' => '240.00',
		// 商品数量
		'quantity' => '1',
		// 单件商品重量，包括小数点和小数位（4位）一共18位
		'perWeight' => '0.5',
		// 单件商品体积，包括小数点和小数位（4位）一共18位
		'perVolume' => '300',
		// 单件商品小计
		'perTotalAmt' => '240.00',
		// 商品SKU
		'SKU' => '123456789000',
	),
);

$order = array(
	// 订单号，必须为16位
	'MerOrdId' => date('YmdHis') . rand(10, 99),
   
	// 交易金额
	'ProTotalAmt' => '240',

	// 商品信息,
	'ProductInfo' => $product_info,
);

// 开启Session
session_start();
// 将订单数据存入Session
$_SESSION['SHOPPER_PAY_ORDER'] = $order;

// 跳转到插件入口页面
header('Location: ../index.php');
//var_dump($order);