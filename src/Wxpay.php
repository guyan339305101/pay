<?php

namespace Guyanpay\phpWxpay;
use think\facade\Db;
use think\Model;

class Wxpay {
	const KEY_LENGTH_BYTE = 32;
	const AUTH_TAG_LENGTH_BYTE = 16;
	public $Db;
	public $pager;
	public $M;
	public $serial_no;
	public $session;
	public $redis;
	public $ount;
	public $forge;
	public $appid;
	public $secret;
	public $key;
	public $mch_id;
	public $apiclient_key;
	public $publicKeyPath;
	public $apiclient_cert;
	public $sp_appid;
	public $sp_mchid;
	public $sub_appid;
	public $sub_mchid;
	public $id;
	public $path;
	public $apiclient_cert_content;
	public $apiclient_key_content;
	protected $autoCheckFields = false;
	public function __construct($data) {
		// $data = Db::name('merchant')->where('is_type', 1)->find();
		$this->id = $data['id'];
		$this->sp_appid = $data['sp_appid'];
		$this->sp_mchid = $data['sp_mchid'];
		$this->sub_appid = $data['sub_appid'];
		$this->sub_mchid = $data['sub_mchid'];
		$this->secret = $data['sub_secret'];
		$this->key = $data['keyd'];
		$this->serial_no = $data['serial_no'];
		$this->apiclient_cert_content = $data['apiclient_cert'];
		$this->apiclient_key_content = $data['apiclient_key'];
		$this->path = app()->getRootPath() . 'vendor/guyanpay/php-wxpay/src/ert/' . $this->id . $this->sub_mchid;
		$this->apiclient_cert = $this->path . '/apiclient_cert.pem';
		$this->apiclient_key = $this->path . '/apiclient_key.pem'; //
		$this->publicKeyPath = $this->path . '/public_key.pem'; //
		// p($this->apiclient_key_content);
	}
	// protected $payApi = [
	// 	'jsapi' => 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi', //APP支付
	// 	'app' => 'https://api.mch.weixin.qq.com/v3/pay/transactions/app', //APP支付
	// 	'h5' => 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5', //H5支付
	// ]; //支付接口列表
	protected $queryApi = 'https://api.mch.weixin.qq.com/v3/pay/transactions/id/{transaction_id}'; //查询订单接口
	protected $refundApi = 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds'; //退单接口
	protected $refundNotify = '******'; //退单回调自定义
	protected $config = [];

	public function index() {

		echo 'pub' . rand(1, 999999);
	}

	//服务商模式支付
	//description商品信息
	//out_trade_no订单号
	//attach附加数据
	//money订单金额
	//time_expire超时时间
	function getfpay($arr = []) {
		$url = 'https://api.mch.weixin.qq.com/v3/pay/partner/transactions/jsapi';
		$body = [
			'sp_appid' => $this->sp_appid,
			'sp_mchid' => $this->sp_mchid,
			'sub_appid' => $this->sub_appid,
			'sub_mchid' => (string) $this->sub_mchid,
			'description' => $arr['description'] ?? '普通商品',
			'out_trade_no' => $arr['out_trade_no'] ?? time() . rand(1111, 9999),
			'notify_url' => $arr['notify_url'] ?? '',
			'attach' => isset($arr['attach']) ? json_encode($arr['attach']) : json_encode(1),
			'settle_info' => [
				'profit_sharing' => $arr['profit_sharing'] ?? false,
			],
			'amount' => [
				'total' => (int) ($arr['money'] * 100),
				'currency' => 'CNY',
			],
			'payer' => [
				'sub_openid' => $arr['openid'],
			],
			'time_expire' => $arr['time_expire'] ?? date("c", strtotime(date('Y-m-d H:i:s', (time() + 300)))),
		];
		$headers = $this->sign('POST', $url, json_encode($body));
		$res = $this->curl_post($url, json_encode($body), $headers);
		$res = json_decode($res, true);
		if (isset($res['code']) && $res['code'] == "NO_AUTH") {
			apijson(0, $res, '系统升级中');
		}
		if (isset($res['code']) && $res['code'] == "PARAM_ERROR") {
			apijson(0, $res, $res['message']);
		}
		if (isset($res['code']) && $res['code'] == "APPID_MCHID_NOT_MATCH") {
			apijson(0, $res, $res['message']);
		}
		if (isset($res['code']) && $res['code'] == "SIGN_ERROR") {
			apijson(0, $res, $res['message']);
		}
		$time = (string) time();
		$str = $this->getRandomStr(32);
		$prepay = "prepay_id=" . $res["prepay_id"]; //数据包
		$message1 = $this->sub_appid . "\n" .
			$time . "\n" .
			$str . "\n" .
			$prepay . "\n";
		openssl_sign($message1, $signature, $this->getMchKey(), "sha256WithRSAEncryption");
		$sign1 = base64_encode($signature);
		// p($sign1);exit();
		$data = array();
		$data['timeStamp'] = $time;
		$data['nonceStr'] = $str;
		$data['signType'] = 'MD5';
		$data['package'] = "prepay_id=" . $res["prepay_id"]; //数据包
		$data['paySign'] = $sign1;
		$data['mch_id'] = $this->sp_mchid;

		return $data;

	}

	/**
	 * Small fish
	 * 项目注释-直连商户支付
	 */
	public function jsapi($arr) {

		$url = "https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi";
		$body = [
			'appid' => $this->sub_appid,
			'mchid' => $this->sub_mchid,
			'description' => $arr['gooddesc'] ?? '商品',
			'out_trade_no' => $arr['out_trade_no'],
			'notify_url' => $arr['notify_url'],
			'time_expire' => $arr['time_expire'] ?? date("c", strtotime(date('Y-m-d H:i:s', (time() + 300)))),
			'amount' => [
				'total' => $arr['money'] * 100,
				'currency' => 'CNY',
			],
			'payer' => [
				'openid' => $arr['openid'],

			],
		];
		$headers = $this->sign('POST', $url, json_encode($body));
		$res = $this->curl_post($url, json_encode($body), $headers);
		$res = json_decode($res, true);
		if (isset($res['code']) && $res['code'] == "NO_AUTH") {
			// apijson('0', $res, $res['message'] ?? '');
			apijson(0, $res, '升级中');
		}
		// p($res);exit();
		if (isset($res['code']) && $res['code'] == "PARAM_ERROR") {
			// apijson('0', $res, $res['message'] ?? '');
			apijson(0, $res, $res['message']);

		}
		if (isset($res['code']) && $res['code'] == "APPID_MCHID_NOT_MATCH") {
			// apijson('0', $res, $res['message'] ?? '');
			apijson(0, $res, $res['message']);
		}
		if (isset($res['code']) && $res['code'] == "SIGN_ERROR") {
			apijson(0, $res, $res['message']);

		}

		$time = (string) time();
		$str = $this->getRandomStr(32);
		$prepay = "prepay_id=" . $res["prepay_id"]; //数据包
		$message1 = $this->appid . "\n" .
			$time . "\n" .
			$str . "\n" .
			$prepay . "\n";

		openssl_sign($message1, $signature, $this->getMchKey(), "sha256WithRSAEncryption");
		$sign1 = base64_encode($signature);
		// p($sign1);exit();
		$data = array();
		$data['timeStamp'] = $time;
		$data['nonceStr'] = $str;
		$data['signType'] = 'MD5';
		$data['package'] = "prepay_id=" . $res["prepay_id"]; //数据包
		$data['paySign'] = $sign1;
		$data['mch_id'] = $this->sp_mchid;

		return $data;

	}

	/**
	 * 直连退款
	 * @param string $transaction_id  平台订单号
	 * @return mixed
	 */
	public function refund($mchid, $transaction_id, $url, $getOrderNo, $price, $yprice = '') {
		if (empty($yprice)) {
			$yprice = $price;
		}
		$body = [
			'transaction_id' => $transaction_id, //平台订单号
			'out_refund_no' => $getOrderNo, //系统退款单号
			'reason' => '退款', //退款原因
			// 			'notify_url' => $url, //退款回调
			'amount' => [
				'refund' => $price * 100, //退款金额
				'total' => $yprice * 100, //原订单金额
				'currency' => 'CNY',
			],
		];
		$headers = $this->sign('POST', $this->refundApi, json_encode($body), $mchid);
		// p($this->refundApi);
		// p($headers);exit();
		return $this->curl_post($this->refundApi, json_encode($body), $headers);
	}

	//服务商退款
	public function shoprefund($mchid, $out_trade_no, $url, $getOrderNo, $price, $yprice = '') {
		if (empty($yprice)) {
			$yprice = $price;
		}
		$body = [
			'sub_mchid' => $mchid,
			'out_trade_no' => $out_trade_no, //平台订单号
			'out_refund_no' => $getOrderNo, //系统退款单号
			'reason' => '退款', //退款原因
			'notify_url' => $url, //退款回调
			'amount' => [
				'refund' => $price * 100, //退款金额
				'total' => $yprice * 100, //原订单金额
				'currency' => 'CNY',
			],
		];
		$headers = $this->sign('POST', $this->refundApi, json_encode($body), $mchid);
		// p($this->refundApi);
		// p($headers);exit();
		$e = $this->curl_post($this->refundApi, json_encode($body), $headers);
		return json_decode($e, true);
	}

	//订单状态查询 服务商
	function getOrder($out_refund_no, $sp_mchid = 0, $sub_mchid = 0) {
		if (empty($sp_mchid)) {
			$sp_mchid = $this->sp_mchid;
		}if (empty($sp_mchid)) {
			$sub_mchid = $this->sub_mchid;
		}
		// $out_refund_no = 'pay2023082164991692578352';
		$url = 'https://api.mch.weixin.qq.com/v3/pay/partner/transactions/out-trade-no/' . $out_refund_no . '?sp_mchid=' . $sp_mchid . '&sub_mchid=' . $sub_mchid;
		$sgin = $this->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$ret = $this->curl_get_https($url, '', $header);
		$ret = json_decode($ret, true);
		// trade_state
		// 交易状态，枚举值：
		// SUCCESS：支付成功
		// REFUND：转入退款
		// NOTPAY：未支付
		// CLOSED：已关闭
		// REVOKED：已撤销（仅付款码支付会返回）
		// USERPAYING：用户支付中（仅付款码支付会返回）
		// PAYERROR：支付失败（仅付款码支付会返回）
		// 示例值：SUCCESS
		return $ret;
	}
	// 服务商查询单笔退款API
	function getOrderrefunds($out_refund_no, $sub_mchid = 0) {
		if (empty($sp_mchid)) {
			$sub_mchid = $this->sub_mchid;
		}
		// p($sub_mchid);exit();
		// $out_refund_no = 'pay2023082164991692578352';
		$url = 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds/' . $out_refund_no . '?sub_mchid=' . $sub_mchid;
		$sgin = $this->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$ret = $this->curl_get_https($url, '', $header);
		$ret = json_decode($ret, true);
		// channel 退款取消
		// 		ORIGINAL：原路退款
		// BALANCE：退回到余额
		// OTHER_BALANCE：原账户异常退到其他余额账户
		// OTHER_BANKCARD：原银行卡异常退到其他银行卡
		return $ret;
	}
	/**
	 * 签名
	 * @param string $http_method    请求方式GET|POST
	 * @param string $url            url
	 * @param string $body           报文主体
	 * @return array
	 */
	public function sign($http_method = 'POST', $url = '', $body = '', $mchid = 0) {
		$mch_private_key = $this->getMchKey($mchid); //私钥
		// exit();
		$timestamp = time(); //时间戳
		$nonce = $this->getRandomStr(32); //随机串
		$url_parts = parse_url($url);
		$canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
		//构造签名串
		$message = $http_method . "\n" .
			$canonical_url . "\n" .
			$timestamp . "\n" .
			$nonce . "\n" .
			$body . "\n"; //报文主体
		//计算签名值
		openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
		$sign = base64_encode($raw_sign);
		//设置HTTP头
		$token = sprintf('WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
			$this->sp_mchid, $nonce, $timestamp, $this->serial_no, $sign);
		$headers = [
			'Accept: application/json',
			'User-Agent: */*',
			'Content-Type: application/json; charset=utf-8',
			'Authorization: ' . $token,
		];
		// p($headers);exit();
		return $headers;
	}

	//私钥
	public function getMchKey($mchid = 0) {
		// exit();
		return openssl_get_privatekey($this->apiclient_key_content);
	}

	//post请求
	public function curl_post($url, $data, $headers = array()) {

		// p($data);exit();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		//设置header头
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// POST数据
		curl_setopt($ch, CURLOPT_POST, 1);
		// 把post的变量加上
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

	//get请求
	public function curl_get($url, $headers = array()) {
		$info = curl_init();
		curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($info, CURLOPT_HEADER, 0);
		curl_setopt($info, CURLOPT_NOBODY, 0);
		curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($info, CURLOPT_SSL_VERIFYHOST, false);
		//设置header头
		curl_setopt($info, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($info, CURLOPT_URL, $url);
		$output = curl_exec($info);

		curl_close($info);
		return $output;
	}
	//get 请求 这个查询订单可以
	function curl_get_https($url, $data, $header) {
		// 模拟提交数据函数
		$curl = curl_init(); // 启动一个CURL会话
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header); // 头部信息
		$tmpInfo = curl_exec($curl); // 执行操作
		if (curl_errno($curl)) {
			echo 'Errno' . curl_error($curl); //捕抓异常
		}
		curl_close($curl); // 关闭CURL会话
		return $tmpInfo; // 返回数据，json格式
	}

	/**
	 * 获得随机字符串
	 * @param $len      integer       需要的长度
	 * @param $special  bool      是否需要特殊符号
	 * @return string       返回随机字符串
	 */
	public function getRandomStr($len, $special = false) {
		$chars = array(
			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
			"l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
			"w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
			"H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
			"S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
			"3", "4", "5", "6", "7", "8", "9",
		);

		if ($special) {
			$chars = array_merge($chars, array(
				"!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
				"%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
				"}", "<", ">", "~", "+", "=", ",", ".",
			));
		}

		$charsLen = count($chars) - 1;
		shuffle($chars); //打乱数组顺序
		$str = '';
		for ($i = 0; $i < $len; $i++) {
			$str .= $chars[mt_rand(0, $charsLen)]; //随机取出一位
		}
		return $str;
	}

	/**
	 * 生成订单号
	 */
	public function getOrderNo() {
		return date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	}

	//作用：生成签名
	private function getSign($Obj) {

		foreach ($Obj as $k => $v) {
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);

		//签名步骤二：在string后加入KEY
		$String = $String . "&key=" . $this()['key'];

		//签名步骤三：MD5加密
		$String = md5($String);

		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		return $result_;
	}

	///作用：格式化参数，签名过程需要使用
	private function formatBizQueryParaMap($paraMap, $urlencode) {
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v) {
			if ($urlencode) {
				$v = urlencode($v);
			}
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff) - 1);
		}
		return $reqPar;
	}
	/*获取access_token,不能用于获取用户信息的token*/
	public function getAccessToken() {

		$value = cache('key');

		$res = cache('getAccessToken');
		if (!empty($res)) {
			return $res;
		} else {
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appid&secret=$this->secret";
			$res = json_decode($this->http_request($url), true);
			cache('getAccessToken', $res['access_token'], 7000);
			return $res['access_token'];

		}

	}

	//HTTP请求（支持HTTP/HTTPS，支持GET/POST）
	private function http_request($url, $data = null) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	//支付回掉解密数据
	public function WxToString($associatedData, $nonceStr, $ciphertext) {
		$ciphertext = \base64_decode($ciphertext);
		if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
			return false;
		}
		// ext-sodium (default installed on >= PHP 7.2)
		if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') &&
			\sodium_crypto_aead_aes256gcm_is_available()) {
			return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->key);
		}
		// ext-libsodium (need install libsodium-php 1.x via pecl)
		if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') &&
			\Sodium\crypto_aead_aes256gcm_is_available()) {
			return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->key);
		}
		// openssl (PHP >= 7.1 support AEAD)
		if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
			$ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
			$authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);
			return \openssl_decrypt($ctext, 'aes-256-gcm', $this->key, \OPENSSL_RAW_DATA, $nonceStr,
				$authTag, $associatedData);
		}

		throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
	}

	// 支付回掉	//支付回掉签名验证
	function verifySigns($http_data) {
		$body = $http_data['body'];
		$wechatpay_timestamp = $http_data['wechatpay-timestamp'];
		$wechatpay_nonce = $http_data['wechatpay-nonce'];
		$wechatpay_signature = $http_data['wechatpay-signature'];
		$signature = base64_decode($wechatpay_signature);
		if (!file_exists($this->publicKeyPath)) {
			$plat = $this->getCert();
			$associatedData = $plat['data'][0]['encrypt_certificate']['associated_data'];
			$nonceStr = $plat['data'][0]['encrypt_certificate']['nonce'];
			$ciphertext = $plat['data'][0]['encrypt_certificate']['ciphertext'];
			$plat_cert_decrtpt = $this->WxToString($associatedData, $nonceStr, $ciphertext);
			$pub_key = trim($plat_cert_decrtpt);
			$sf = $this->publicKeyPath;
			$fp = fopen($sf, "w"); //写方式打开文件
			fwrite($fp, $pub_key); //存入内容
			fclose($fp); //关闭文件
		} else {
			$pub_key = file_get_contents($this->publicKeyPath);
		}
		$body = str_replace('\\', '', $body);
		$message =
			$wechatpay_timestamp . "\n" .
			$wechatpay_nonce . "\n" .
			$body . "\n";
		$res = openssl_verify($message, $signature, $pub_key, OPENSSL_ALGO_SHA256);

		if ($res == 1) {
			return true;
		}

		return false;
	}

	/**
	 * 获取证书
	 * @param $disposeUrl :此处的url是接口地址去除域名后的url
	 * @param $nonce_str :32位随机字符串
	 * @param $requestBody :请求报文的主体
	 * @return string
	 */
	public function getCert() {
		$url = 'https://api.mch.weixin.qq.com/v3/certificates';
		$time = time();
		$nonce_str = $this->getRandomStr(32);
		$data = '';
		$signBody = $this->signBody('/v3/certificates', $time, $nonce_str, $data, 'GET'); //获取参与签名的主体数据
		$header = $this->getHeader($nonce_str, $this->getSignature($signBody));
		$res = $this->curlRequest($url, 'GET', '', $header);
		$res = json_decode($res, true);
		return $res;

	}

	/**
	 * 用于生成签名数据主体
	 * @param $disposeUrl :此处的url是接口地址去除域名后的url
	 * @param $nonce_str :32位随机字符串
	 * @param $requestBody :请求报文的主体
	 * @return string
	 */
	public function signBody($disposeUrl, $time, $nonce_str, $requestBody, $method = 'POST') {
		$data = array(
			$method, $disposeUrl, time(), $nonce_str, $requestBody, '',
		);
		return join("\n", $data);
	}

	/**
	 * 用于生成签名值
	 * @param $data :用于签名的数据
	 * @return string
	 */
	public function getSignature($data) {
		$private = $this->apiclient_key_content;
		// p($private);exit();
		$key = openssl_pkey_get_private($private);
		openssl_sign($data, $signature, $key, 'sha256WithRSAEncryption');
		return base64_encode($signature);
	}
	/**
	 * @param $nonce_str :32位随机字符串,与上方的随机字符串保持一致
	 * @param $signature :上方生成的签名值
	 * @return string[]
	 */
	public function getHeader($nonce_str, $signature) {
		$token = sprintf('mchid="%s",serial_no="%s",nonce_str="%s",timestamp="%s",signature="%s"', $this->sp_mchid, $this->serial_no, $nonce_str, time(), $signature);
		$data = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 Edg/103.0.1264.71',
			"Authorization: WECHATPAY2-SHA256-RSA2048 $token",
		);
		return $data;
	}

	/**
	 * 使用curl完成对接口的请求
	 * @param $url :接口的url地址
	 * @param string $type :传输类型,若为POST需要提供$data与$header
	 * @param string $data :当type = POST时需要提供,为请求主体数据
	 * @param array $header :当type = POST时需要提供,为请求头
	 * @return bool|string
	 */
	public function curlRequest($url, string $type = '', string $data = '', array $header = []) {
		$action = curl_init();
		curl_setopt($action, CURLOPT_URL, $url);
		curl_setopt($action, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($action, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($action, CURLOPT_HEADER, 0);
		curl_setopt($action, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($action, CURLOPT_SSL_VERIFYHOST, 0);
		if ($type == 'POST') {
			curl_setopt($action, CURLOPT_POST, 1);
			curl_setopt($action, CURLOPT_POSTFIELDS, $data);
			curl_setopt($action, CURLOPT_HTTPHEADER, $header);
		} else if (!empty($header)) {
			curl_setopt($action, CURLOPT_HTTPHEADER, $header);
		}
		$result = curl_exec($action);
		curl_close($action);
		return $result;
	}

	//H5测试支付
	//    public function pay(){
	//        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5';
	//        $body = [
	//            'appid' => '******',
	//            'mchid' => '******',
	//            'description' => '******',
	//            'out_trade_no' => date('YmdHis').rand(1000,9999),
	//            'notify_url' => "******",
	//            'amount' => [
	//                'total' => 100,
	//                'currency' => 'CNY'
	//            ],
	//            'scene_info' => [
	//                'payer_client_ip' => Request::instance()->ip(),
	//                'h5_info' => [
	//                    'type' => 'Wap',
	//                ],
	//            ],
	//        ];
	//        $headers = $this->sign('POST',$url,json_encode($body));
	//        $res = $this->curl_post($url,$body,$headers);
	//        print_r($res);
	//    }
}
