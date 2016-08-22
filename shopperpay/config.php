<?php
/*
 * 商户配置文件
 * Merchant configuration file
 */

// CHINAPAY 环境切换 (true: 生产环境 | false: 测试环境)
define('ENV_SWITCH', false);

// 海淘天下分配的商户号
//Merchant Id, provided by GS, String 7.
$shopperpay_config['GSMerId'] = '5020001';

// 商户号，由ChinaPay分配的15个字节的数字串
//Merchant Id, provided by Chinapay, String 15.
$shopperpay_config['MerId'] = '808080071198021';

// 物流商户号，为海淘天下分配的商户号
//Logistic Merchant Id, provided by Globalshopper.
$shopperpay_config['LogisticsId'] = '808080071198022';

// CHINAPAY公钥配置
//public key configuration
define('CHINAPAY_PUBKEY', dirname(__FILE__) . '/key/thenatural/PgPubk.key');

// CHINAPAY私钥配置
//private key configuration
define('CHINAPAY_PRIVKEY', dirname(__FILE__) . '/key/thenatural/MerPrK_808080071198021_20160711103730.key');

// GS公钥配置
//public key configuration
define('GS_PUBKEY', dirname(__FILE__) . '/key/sign/publicKey.keystore');

// GS私钥配置
//private key configuration
define('GS_PRIVKEY', dirname(__FILE__) . '/key/sign/privateKey_5020001.keystore');

// 设置时区
// Timezone setting
date_default_timezone_set('Asia/Shanghai');

// 时区，东时区表示为正，西时区表示为负，长度3个字节，必填
// time zone, Eastren time zone means '+ ', western time zone means '- '.Less than 3 bytes.
$shopperpay_config['TimeZone'] = '+08';

// 国家代码，4位长度，电话代码编码，必填 (美国=0001，日本=0081， 中国=0086)
// Country Code, length 4, area code phone code.
$shopperpay_config['CountryId'] = '0086';

// 货币代码, 例如，人民币取值为"156"，日元取值为“JPY”,美元取值为“USD”,在商户入网的时候就已经规定,不可修改
// Currency ID, ex:RMB = '156', Japanese yen = 'JPY', Dollar = 'USD'.
$shopperpay_config['CuryId'] = 'USD';

// 夏令时标志，1为夏令时，0不为夏令时，必填[后期可以通过配置项配置]
// tag of summer time. '1'means use summer time. '0'means do not use summer time.
$shopperpay_config['DSTFlag'] = '0';

// 商户相关配置 ========================================================================================================
// Merchant related configuration ======================================================================================

// 商户状态回传API
// Merchant order payment result call back address.
define('SELLER_API', 'http://www.pujiangzhen.cn/shopperpay/demo/seller_api.php');

// 支付成功前端返回到商户的地址
// Merchant order payment result call back interface API private key.
define('SELLER_RETURN_URL', 'http://www.pujiangzhen.cn/shopperpay/demo/return_url.php');

// 商户退款状态回传API
// Merchant refund result call back address.
define('SELLER_REFUND_API', 'http://localhost/order_refund');

