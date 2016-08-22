<?php
/**
 * ChinaPay 订单提交接口
 * ChinaPay Order Submit Interface
 */

if (!defined('CHINAPAY_PAY_URL')) {
	die('Config error: no CHINAPAY_PAY_URL');
}

require 'netpayclient.php';

class ChinaPaySubmit
{
	static $pay_result_data = null;

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

	/*
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
	 * 生成交易数据签名
	 * generate the sign of payment data
	 *
	 * @param string $pay_data the data to be signed
	 * @return string the sign of payment data
	 */
	public function signPayData($pay_data)
	{
		$merid = buildKey(CHINAPAY_PRIVKEY);
		if (!$merid) {
			echo "导入私钥文件失败！";
			exit;
		}
		$data4sign = array(
			$pay_data['MerId'],  //商户号
			$pay_data['OrdId'],  //GS订单号
			$pay_data['TransAmt'],  //订单交易金额
			$pay_data['CuryId'],     //币种
			$pay_data['TransDate'],  //交易日期
			$pay_data['TransTime'],  //交易时间
			$pay_data['TransType'],  //交易种类
			$pay_data['CountryId'],  //国际id
			$pay_data['TimeZone'],   //时区
			$pay_data['DSTFlag'],    //夏令时标志
			$pay_data['ExtFlag'],        //境外商户标志
			$pay_data['Priv1'],      //商户私有域段1
			$pay_data['Priv2'],      //商户私有域段2
		);
		$str4sign = implode('', $data4sign);
		return sign($str4sign);   //生成签名值
	}

	/**
	 * 获取支付结果数据
	 * get the payment result data
	 *
	 * @return array|null
	 */
	public function  getPayResult()
	{
		if (null !== self::$pay_result_data) {
			return self::$pay_result_data;
		}
		$result_keys = array('merid', 'orderno', 'transdate', 'amount', 'currencycode', 'transtype', 'status', 'checkvalue', 'GateId', 'Priv1',);
		$pay_result = array();
		foreach ($result_keys as $result_key) {
			$pay_result[$result_key] = empty($_POST[$result_key]) ? '' : $_POST[$result_key];
		}
		self::$pay_result_data = $pay_result;
		logResult('ChinaPay Result Data', array(
			'uri' => $_SERVER["REQUEST_URI"],
			'data' => $this->getPayResult(),
		));
		return $pay_result;
	}

	/**
	 * 验证交易结果签名
	 * verify the sign information about Payment Result data
	 *
	 * @param array $pay_result_data payment result data
	 * @return bool the sign information is valid or not
	 */
	public function verifyPayResultData($pay_result_data)
	{
		$flag = buildKey(CHINAPAY_PUBKEY);
		if (!$flag) {
			echo "导入公钥文件失败！";
			exit;
		}
		return verifyTransResponse(
			$pay_result_data['merid'],
			$pay_result_data['orderno'],
			$pay_result_data['amount'],
			$pay_result_data['currencycode'],
			$pay_result_data['transdate'],
			$pay_result_data['transtype'],
			$pay_result_data['status'],
			$pay_result_data['checkvalue']
		);
	}

	/**
	 * 创建提交表单
	 * Create the Payment Submit Form
	 *
	 * @param array $params Payment parameters
	 * @return string the payment submit form string
	 */
	public function buildFormSubmit($params, $url = CHINAPAY_PAY_URL)
	{
	    $sHtml = "<form id='submit' name='submit' action='" . $url . "' method='POST'>";
        if (is_array($params)) {
            while (!!list($key, $val) = each($params)) {
                $sHtml .= "<input type='hidden' name='" . $key . "' value='" . htmlspecialchars($val) . "'/>";
            }
        }else {
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . htmlspecialchars($params) . "'/>";
        }
		$sHtml .= "</form>";
		$sHtml .= "<script>document.forms['submit'].submit();</script>";
		echo $sHtml;
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
		header('Content-Type:text/html; charset=utf-8');
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