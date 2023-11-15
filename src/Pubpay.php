<?php

namespace Guyanpay\phpWxpay;
use think\facade\Db;
use think\Model;
use \Guyanpay\phpWxpay\Wxpay;

class Pubpay {

	public function __construct($data = []) {
		$this->data = $data;
		// $data = Db::name('merchant')->where('is_type', 1)->find();

	}

	// 接口说明 请求分账API
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/profitsharing/orders
	// 请求方式：POST
	// 频率限制：300/s
	public function profitsharing_orders($arr) {
		$NewPay = new Wxpay($this->data);
		// p($NewPay);exit();
		$url = 'https://api.mch.weixin.qq.com/v3/profitsharing/orders';
		$data['sub_mchid'] = (string) $NewPay->sub_mchid;
		$data['appid'] = $NewPay->appid;
		$data['sub_appid'] = $this->sub_appid ?? ''; //body微信分配的子商户公众账号ID，分账接收方类型包含PERSONAL_SUB_OPENID时必填。
		$data['out_order_no'] = $arr['out_order_no'] ?? time() . rand(11111, 9999); //服务商系统内部的分账单号，在服务商系统内部唯一，同一分账单号多次请求等同一次。只能是数字、大小写字母_-|*@
		$data['receivers'] = [
			'type' => $arr['type'] ?? 'PERSONAL_OPENID', //1、MERCHANT_ID：商户号2、PERSONAL_OPENID：个人openid（由父商户APPID转换得到）3、PERSONAL_SUB_OPENID: 个人sub_openid（由子商户APPID转换得到）
			'account' => $arr['openid'], //1、分账接收方类型为MERCHANT_ID时，分账接收方账号为商户号2、分账接收方类型为PERSONAL_OPENID时，分账接收方账号为个人openid3、分账接收方类型为PERSONAL_SUB_OPENID时，分账接收方账号为个人sub_openid
		];
		$data['amount'] = $arr['money'] * 100;
		$data['description'] = $arr['description'] ?? '商品分账';
		$data['unfreeze_unsplit'] = $arr['unfreeze_unsplit']; //、如果为true，该笔订单剩余未分账的金额会解冻回分账方商户；2、如果为false，该笔订单剩余未分账的金额不会解冻回分账方商户，可以对该笔订单再次进行分账。true

		$headers = $NewPay->sign('POST', $url, json_encode($data), 0);
		$e = $NewPay->curl_post($url, json_encode($data), $headers);
		return json_decode($e, true);
	}

	//接口说明 查询分账结果API
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/profitsharing/orders/{out_order_no}
	// 请求方式：POST
	// 频率限制：300/s
	public function ordersresult($out_refund_no, $transaction_id) {
		$NewPay = new Wxpay($this->data);

		if (empty($sp_mchid)) {
			$sp_mchid = $NewPay->sp_mchid;
		}if (empty($sp_mchid)) {
			$sub_mchid = $NewPay->sub_mchid;
		}
		// $out_refund_no = 'pay2023082164991692578352';
		$url = 'https://api.mch.weixin.qq.com/v3/profitsharing/orders/' . $out_refund_no . '?transaction_id=' . $transaction_id . '&sub_mchid=' . $sub_mchid;
		$sgin = $NewPay->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$ret = $NewPay->curl_get_https($url, '', $header);
		$ret = json_decode($ret, true);
		return $ret;
	}

	//接口说明 查询剩余待分金额API
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/profitsharing/transactions/{transaction_id}/amounts
	// 请求方式：POST
	// 频率限制：300/s
	public function transactions($transaction_id) {
		$NewPay = new Wxpay($this->data);
		// $out_refund_no = 'pay2023082164991692578352';
		$url = 'https://api.mch.weixin.qq.com/v3/profitsharing/transactions/' . $transaction_id . '/amounts';
		$sgin = $NewPay->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$ret = $NewPay->curl_get_https($url, '', $header);
		$ret = json_decode($ret, true);
		return $ret;
	}

	// 接口说明 添加分账接收方API
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/profitsharing/receivers/add
	// 请求方式：POST
	// 频率限制：300/s
	public function receiversadd($arr) {
		$NewPay = new Wxpay($this->data);
		$url = 'https://api.mch.weixin.qq.com/v3/profitsharing/receivers/add';
		$data['sub_mchid'] = (string) $NewPay->sub_mchid;
		$data['appid'] = $NewPay->sp_appid;
		$data['sub_appid'] = $NewPay->sub_appid ?? ''; //body微信分配的子商户公众账号ID，分账接收方类型包含PERSONAL_SUB_OPENID时必填。
		$data['type'] = $arr['type'] ?? 'PERSONAL_OPENID'; //1、MERCHANT_ID：商户号2、PERSONAL_OPENID：个人openid（由父商户APPID转换得到）3、PERSONAL_SUB_OPENID: 个人sub_openid（由子商户APPID转换得到）
		$data['account'] = $arr['openid']; //类型是MERCHANT_ID时，是商户号 类型是PERSONAL_OPENID时，是个人openid 类型是PERSONAL_SUB_OPENID时，是个人sub_openid
		$data['relation_type'] = $arr['relation_type'] ?? 'USER'; //子商户与接收方的关系。 本字段值为枚举：SERVICE_PROVIDER：服务商STORE：门店STAFF：员工STORE_OWNER：店主PARTNER：合作伙伴HEADQUARTER：总部BRAND：品牌方DISTRIBUTOR：分销商USER：用户SUPPLIER： 供应商CUSTOM：自定义

		$headers = $NewPay->sign('POST', $url, json_encode($data), 0);
		$e = $NewPay->curl_post($url, json_encode($data), $headers);
		return json_decode($e, true);
	}

	// 接口说明 删除分账接收方API
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/profitsharing/receivers/delete
	// 请求方式：POST
	// 频率限制：300/s
	public function receiversdelete($arr) {
		$NewPay = new Wxpay($this->data);
		$url = 'https://api.mch.weixin.qq.com/v3/profitsharing/receivers/delete';
		$data['sub_mchid'] = (string) $NewPay->sub_mchid;
		$data['appid'] = $NewPay->appid;
		$data['sub_appid'] = $this->sub_appid ?? ''; //body微信分配的子商户公众账号ID，分账接收方类型包含PERSONAL_SUB_OPENID时必填。
		$data['out_order_no'] = $arr['out_order_no'] ?? time() . rand(11111, 9999); //服务商系统内部的分账单号，在服务商系统内部唯一，同一分账单号多次请求等同一次。只能是数字、大小写字母_-|*@
		$data['type'] = $arr['type'] ?? 'PERSONAL_OPENID'; //1、MERCHANT_ID：商户号2、PERSONAL_OPENID：个人openid（由父商户APPID转换得到）3、PERSONAL_SUB_OPENID: 个人sub_openid（由子商户APPID转换得到）
		$data['account'] = $arr['openid']; //类型是MERCHANT_ID时，是商户号 类型是PERSONAL_OPENID时，是个人openid 类型是PERSONAL_SUB_OPENID时，是个人sub_openid

		$headers = $NewPay->sign('POST', $url, json_encode($data), 0);
		$e = $NewPay->curl_post($url, json_encode($data), $headers);
		return json_decode($e, true);
	}

	// 接口说明 请求分账API
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/profitsharing/orders/unfreeze
	// 请求方式：POST
	// 频率限制：300/s
	public function ordersunfreeze($arr) {
		$NewPay = new Wxpay($this->data);
		$url = 'https://api.mch.weixin.qq.com/v3/profitsharing/orders/unfreeze';
		$data['sub_mchid'] = (string) $NewPay->sub_mchid;
		$data['appid'] = $NewPay->appid;
		$data['sub_appid'] = $this->sub_appid ?? ''; //body微信分配的子商户公众账号ID，分账接收方类型包含PERSONAL_SUB_OPENID时必填。
		$data['out_order_no'] = $arr['out_order_no'] ?? time() . rand(11111, 9999); //服务商系统内部的分账单号，在服务商系统内部唯一，同一分账单号多次请求等同一次。只能是数字、大小写字母_-|*@
		$data['description'] = $arr['description'] ?? '解冻全部剩余资金'; //分账的原因描述，分账账单中需要体现
		$headers = $NewPay->sign('POST', $url, json_encode($data), 0);
		$e = $NewPay->curl_post($url, json_encode($data), $headers);
		return json_decode($e, true);
	}
}
