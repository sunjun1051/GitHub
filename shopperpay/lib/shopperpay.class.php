<?php

/**
 * 支付插件处理类
 * Payment plugin process class
 */
class ShopperPay
{
	static $post_params = null;

	/**
	 * 返回JSON格式数据
	 * Return JSON format of data
	 *
	 * @param mixed $data data to send
	 */
	public function sendJson($data)
	{
// 		header('Content-Type: application/json');
// 		print json_encode($data);
        header('Content-Type:text/html; charset=utf-8');
        foreach ($data as $key => $value) {
            echo '{'.$key.':'.$value.'}'; 
        }
		die;
	}

	/**
	 * 发送成功的数据
	 * send data when success
	 *
	 * @param mixed $data data to send
	 */
	public function sendSuccess($data)
	{
		$this->sendJson(array(
				"isSuccess" => "1",
				"errorCode" => "",
				"errorMessage" => ""
			) + $data);
	}

	/**
	 * 发送失败的数据
	 * send data when error occurred
	 *
	 * @param mixed $data data to send
	 */
	public function sendError($errorCode, $errorMessage)
	{
		$this->sendJson(array(
			"isSuccess" => "0",
			"errorCode" => $errorCode,
			"errorMessage" => $errorMessage
		));
	}

	/**
	 * 解析前端提交的POST数据
	 * Parse the POST data submitted by frontend
	 */
	public function getPostParams()
	{
		if (null !== self::$post_params) {
			return self::$post_params;
		}
		$params = $_POST;	
		if (!empty($params)) {
			$data = json_decode($params, true);
			if (NULL !== $data) {
				self::$post_params = $data;
				return $data;
			}
		}
		self::$post_params = false;
		return false;
	}

	/**
	 * 从Session中获取订单信息
	 * Get order information from session
	 */
	public function getOrderInfo()
	{
		global $shopperpay_config;
		// 如果没有开启会话则开启会话
		session_id() or session_start();
		// TODO: 校验订单数据格式是否正确？
		$order_info = empty($_SESSION['SHOPPER_PAY_ORDER']) ? array() : $_SESSION['SHOPPER_PAY_ORDER'];
		//加入货币代码$shopperpay_config是config里面设置的
		$order_info and $order_info['CuryId'] = $shopperpay_config['CuryId']; 
		
		return $order_info;
	}

	/**
	 * 从前端查询中获取收件人地址
	 * get consignee address info from fronted query
	 */
	public function getDestAddress()
	{
		$post_params = $this->getPostParams();
		if (empty($post_params['addressInfo']) or empty($post_params['addressInfo']['destAddress'])) {
			return false;
		}
		//获得收获地址里的所有信息，姓名，电话，等等
		$dest_address = $post_params['addressInfo']['destAddress'];
		//获得地址信息里面的所有key值。
		$dest_address_params = array_keys($dest_address);
		$address_params_names = array(
			'contactName' => '联系人姓名',
			'contactPhone' => '联系人电话',
			'areaCode' => '区号',
			'fixedPhone' => '固话',
			'IDNumber' => '身份证号',
			'zipCode' => '邮政编码',
			'email' => '邮箱',
			'countryId' => '国家代码',
			'countryName' => '国家名称',
			'provinceId' => '省或州代码',
			'provinceName' => '省或州名称',
			'cityId' => '城市代码',
			'cityName' => '城市名称',
			'districtId' => '区代码',
			'districtName' => '区名称',
			'detailAddress1' => '详细地址1',
			'detailAddress2' => '详细地址1',
		);
		
		foreach (array_keys($address_params_names) as $address_param) {
			// TODO: 检查必填项
			if (!in_array($address_param, $dest_address_params)) {
				// 补全未提供的空字段
				// complete the empty fields
				$dest_address[$address_param] = '';
			}
		}
		return $dest_address;
	}

	/**
	 * 从前端查询中获取用户登录信息
	 * get user login information from frontend query
	 */
	public function getLoginUser()
	{
		$post_params = $this->getPostParams();
		if (empty($post_params['userNo']) or empty($post_params['token'])) {
			return false;
		}
		return array(
			"userNo" => $post_params['userNo'],
			"token" => $post_params['token']
		);
	}
	
	/**
	 * 获得商户订单数据，转换成标准格式
	 * @return Array
	 */
	public function getOrderForm() {
	    if ($_POST['china_pay']){
	        $data = $_POST;
	        foreach ($data as $value) {
	            for ($i=0; $i<count($value);$i++) {
	                $product_info[$i] = array(
	                    // 可以是多条商品数据
	                    // 商品名称
	                    'productName' => $_POST['productName'][$i],
	                    // 商品属性，包含name和value的json数组字符串格式
	                    'productAttr' => $_POST['productAttr'][$i],
	                    // 商品图片链接地址
	                    'imageUrl' => $_POST['imageUrl'][$i],
	                    // 商品类别
	                    'categoryName' => $_POST['categoryName'][$i],
	                    // 商品单价
	                    'perPrice' => $_POST['perPrice'][$i],
	                    // 货币代码, 例如，人民币取值为"156"，日元取值为“JPY”,美元取值为“USD”
	                    'curyId' => $_POST['perPrice'][$i],
	                    // 商品数量
	                    'quantity' => $_POST['quantity'][$i],
	                    // 单件商品重量，包括小数点和小数位（4位）一共18位
	                    'perWeight' => $_POST['perWeight'][$i],
	                    // 单件商品体积，包括小数点和小数位（4位）一共18位
	                    'perVolume' => $_POST['perVolume'][$i],
	                    // 单件商品小计
	                    'perTotalAmt' => $_POST['perTotalAmt'][$i],
	                    // 商品备案号
	                    'filingNumber' => $_POST['filingNumber'][$i],
	                    // 商品SKU
	                    'SKU' => $_POST['SKU'][$i],
	                );
	            }
	        }
	        return $product_info;
	    }
	}
	
	/**
	 * 订单相关数据加密
	 * @return array
	 */
	public function get_signed_data(array $sign_data) {
	    $sign_data = implode('', $sign_data);
	    require_once 'shopperapi.class.php';
	    $shopper_api = new ShopperAPI();
	    return $shopper_api->sign($sign_data);
	}



	
	
}