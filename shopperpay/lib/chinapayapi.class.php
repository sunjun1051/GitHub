<?php
/**
 * ChinaPay 订单查询与退款接口
 */

require 'netpayclient.php';

class ChinaPayAPI
{
	static $refund_result_data = null;

	/*
	 * 发送 HTTP 请求到 API 服务器
	 * Send HTTP request to API Server
	 *
	 * @param string $method request method
	 * @param string $url API URL
	 * @param array $data request data
	 * @return mixed result data received form server
	 */
	public function sendRequest($method, $url, $data = array())
	{
		logResult("ChinaPay Request", array('url' => $url, 'data' => $data));

		# 发送 HTTP 请求并取得返回数据
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('ContentType：application/x-www-form-urlencoded;charset=utf-8'));
		switch ($method) {
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				break;
			case 'GET':
			default:
				if (empty($data)) {
					break;
				}
				$params = array();
				foreach ($data as $k => $v) {
					$params[] = $k . '=' . urlencode($v);
				}
				$url_params = implode('&', $params);
				if (false === strchr($url, '?')) {
					$url .= '?' . $url_params;
				} else {
					$url .= '&' . $url_params;
				}
				break;
		}
		curl_setopt($ch, CURLOPT_URL, $url);  //链接的接口地址
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //不直接输出，转为流文件
		$res = curl_exec($ch);
		curl_close($ch);

		logResult("ChinaPay Response", array('url' => $url, 'data' => $res));
		return $res;
	}

	/**
	 * 创建提交表单
	 * Create the Payment Submit Form
	 *
	 * @param string $action Form submit action url
	 * @param array $params Payment parameters
	 * @return string the payment submit form string
	 */
	public function buildForm($action, $params)
	{
		$sHtml = "<form id='chinapaysubmit' name='chinapaysubmit' action='" . $action . "' method='POST'>";
		while (list ($key, $val) = each($params)) {
			$sHtml .= "<input type='hidden' name='" . $key . "' value='" . htmlspecialchars($val) . "'/>";
		}
		$sHtml .= "</form>";

		$sHtml .= "<script>document.forms['chinapaysubmit'].submit();</script>";

		return $sHtml;
	}

	/**
	 * 解析接口返回的数据
	 * parse return data from ChinaPay API
	 *
	 * @param string $result the origin data returned from api
	 * @return array the parsed result data
	 */
	public function parseResultData($result)
	{
		$is_match = preg_match('/<body>\s*(.*)\s*<\/body>/', $result, $matches);
		if (!$is_match) {
			return array();
		}
		$result_data = $matches[1];
		$result_data = mb_convert_encoding($result_data, 'UTF-8', 'GB2312');
		parse_str($result_data, $result_array);
		return $result_array;
	}

	/**
	 * 订单查询
	 * Order Information Query
	 *
	 * @param array $params order query parameters
	 * @return array order query result data
	 */
	public function query($params)
	{
		if (!defined('CHINAPAY_QUERY_URL')) {
			die('Config error: no CHINAPAY_QUERY_URL');
		}
		$params = $this->signQueryData($params);
//		echo $this->buildForm(CHINAPAY_QUERY_URL, $params);
//		die;
		$result = $this->sendRequest('POST', CHINAPAY_QUERY_URL, $params);
		return $this->parseResultData($result);
	}

	/**
	 * 退款
	 * Refund
	 *
	 * @param array $params refund request parameters
	 * @return array refund result data
	 */
	public function refund($params)
	{
		if (!defined('CHINAPAY_REFUND_URL')) {
			die('Config error: no CHINAPAY_REFUND_URL');
		}
		$params = $this->signRefundData($params);
//		echo $this->buildForm(CHINAPAY_REFUND_URL, $params);
//		die;
		$result = $this->sendRequest('POST', CHINAPAY_REFUND_URL, $params);
		return $this->parseResultData($result);
	}

	/**
	 * 签名查询数据
	 * sign the query data
	 *
	 * @param array $data the unsigned data
	 * @return array the signed data
	 */
	public function signQueryData($data)
	{
		$merid = buildKey(CHINAPAY_PRIVKEY);
		if (!$merid) {
			echo "导入私钥文件失败！";
			exit;
		}
		$data4sign = array(
			$data['MerId'],
			$data['TransDate'],
			$data['OrdId'],
			$data['TransType'],
		);
		$str4sign = implode('', $data4sign);
		$data['ChkValue'] = sign($str4sign);
		return $data;
	}

	/**
	 * 验证交易结果签名
	 * check the signature of payment result data
	 *
	 * @param array $result_data the payment result data
	 * @return bool the signature is valid or not
	 */
	public function verifyQueryResultData($result_data)
	{
		$flag = buildKey(CHINAPAY_PUBKEY);
		if (!$flag) {
			echo "导入公钥文件失败！";
			exit;
		}
		return verifyTransResponse(
			$result_data['merid'],
			$result_data['orderno'],
			$result_data['amount'],
			$result_data['currencycode'],
			$result_data['transdate'],
			$result_data['transtype'],
			$result_data['status'],
			$result_data['checkvalue']
		);
	}

	/**
	 * 签名退款数据
	 * sign the refund request data
	 *
	 * @param array $data the unsigned data
	 * @return array the signed data
	 */
	public function signRefundData($data)
	{
		$merid = buildKey(CHINAPAY_PRIVKEY);
		if (!$merid) {
			echo "导入私钥文件失败！";
			exit;
		}
		$data4sign = array(
			$data['MerID'],
			$data['TransDate'],
			$data['TransType'],
			$data['OrderId'],
			$data['RefundAmount'],
			$data['Priv1'],
		);
		$str4sign = implode('', $data4sign);
		$data['ChkValue'] = sign($str4sign);
		return $data;
	}

	/*
	 * 验证退款结果签名
	 * check the signature of refund result data
	 *
	 * @param array $result_data the refund result data
	 * @return bool the signature is valid or not
	 */
	public function verifyRefundResultData($result_data)
	{
		$flag = buildKey(CHINAPAY_PUBKEY);
		if (!$flag) {
			echo "导入公钥文件失败！";
			exit;
		}
		$data4verify = array(
			$result_data['MerID'],
			$result_data['ProcessDate'],
			$result_data['TransType'],
			$result_data['OrderId'],
			$result_data['RefundAmout'],
			$result_data['Status'],
			$result_data['Priv1'],
		);
		$str4verify = implode('', $data4verify);
		return verify($str4verify, $result_data['CheckValue']);
	}

	/**
	 * 获取退款结果通知数据
	 * get the refund result notify data
	 *
	 * @return array|null
	 */
	public function  getRefundResult()
	{
		if (null !== self::$refund_result_data) {
			return self::$refund_result_data;
		}
		$result_keys = array('ResponseCode', 'MerID', 'ProcessDate', 'SendTime', 'TransType', 'OrderId', 'RefundAmout', 'Status', 'Priv1', 'CheckValue',);
		$refund_result = array();
		foreach ($result_keys as $result_key) {
			$refund_result[$result_key] = empty($_POST[$result_key]) ? '' : $_POST[$result_key];
		}
		self::$refund_result_data = $refund_result;
		logResult('ChinaPay Result Data', array(
			'uri' => $_SERVER["REQUEST_URI"],
			'data' => $this->getRefundResult(),
		));
		return $refund_result;
	}

// =======================================================================

	/**
	 * 金额转分格式
	 * pay amount transform to cent format
	 *
	 * @param string $currencyId the currency code
	 * @param string|float|int $amount pay amount
	 * @return string cent format amount
	 */
	public function formatAmt($currencyId, $amount)
	{
		if ($currencyId == 'JPY') {
			$formatted_amount = (string)intval((int)$amount * 100);
		} else {
			$formatted_amount = (string)intval($amount * 100);

		}
		return str_pad($formatted_amount, 12, '0', STR_PAD_LEFT);
	}

	/**
	 * 分格式转金额
	 * cent format pay amount transform to normal, the reversed to method formatAmt
	 *
	 * @param string $currencyId the currency code
	 * @param string $amount cent format pay amount
	 * @return string normal format amount
	 */
	public function unformatAmt($currencyId, $amount)
	{
		$amount = ltrim($amount, '0');
		if ($currencyId == 'JPY') {
			return sprintf('%d', $amount / 100);
		} else {
			return sprintf('%.2f', $amount / 100);
		}

	}

	/**
	 * 显示返回页错误
	 * show return error info
	 *
	 * @param string $msg the error message
	 * @param mixed $detail the detail data about the error
	 */
	public function showReturnError($msg, $detail = '')
	{
		logResult('ChinaPay Return Error', array(
			'error' => $msg,
			'detail' => $detail,
		));
		die($msg);
	}

	/**
	 * 记录通知页错误
	 * Log the notify error info
	 *
	 * @param string $msg the error message
	 * @param mixed $detail the detail data about the error
	 */
	public function logNotifyError($msg, $detail = '')
	{
		logResult('ChinaPay Notify Error', array(
			'error' => $msg,
			'detail' => $detail,
		));
		die($msg);
	}
}