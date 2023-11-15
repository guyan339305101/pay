<?php

namespace Guyanpay\phpWxpay;
use think\facade\Db;
use think\Model;
use \Guyanpay\phpWxpay\Wxpay;

// https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/order-shipping/order-shipping.html#%E4%B8%89%E3%80%81%E6%9F%A5%E8%AF%A2%E8%AE%A2%E5%8D%95%E5%8F%91%E8%B4%A7%E7%8A%B6%E6%80%81文档地址
class Deliver {

	public function __construct($data = []) {
		$this->data = $data;
		// $data = Db::name('merchant')->where('is_type', 1)->find();

	}

	/**
	 * Small fish
	 * 项目注释- 获取物流
	$data = merchant();
	$Deliver = new \Guyanpay\phpWxpay\Deliver($data);
	$res = $Deliver->get_company_list();
	 */
	public function get_company_list() {
		$Wxpay = new \Guyanpay\phpWxpay\Wxpay($this->data);
		$token = $Wxpay->getAccessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token=" . $token;
		$res = json_decode($this->http_request($url, ''), true);
		$table = 'xh_admin_delivery';
		$exist = Db::query("show tables like '" . $table . "'");
		$type = 0;
		if (is_array($res['data'])) {
			if (empty($exist)) {
				$c = '';
				$c .= "  name   VARCHAR(255) NOT NULL DEFAULT '' COMMENT '物流名字', ";
				$c .= "  delivery_id  VARCHAR(255) NOT NULL DEFAULT '' COMMENT '物流ID', ";
				$c .= "  service_name  VARCHAR(255) NOT NULL DEFAULT '' COMMENT '服务类型', ";
				$c .= " is_show int(11) NOT NULL DEFAULT '0' COMMENT '是否显示', ";
				$c .= " time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间', ";
				$sql1 = "CREATE TABLE " . $table . "( " . "id INT NOT NULL AUTO_INCREMENT," . $c .
					"PRIMARY KEY ( id )) CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='物流信息'";
				Db::query($sql1);
			}
			Db::table($table)->where('id', '>', 0)->delete();
			$n = [];
			foreach ($res['data'] as $k => $v) {
				$n[$k]['name'] = $v['delivery_name'];
				$n[$k]['delivery_id'] = $v['delivery_id'];
				$service_name = '';
				if (!empty($v['service_type'])) {
					foreach ($v['service_type'] as $f) {
						$service_name .= $f['service_name'] . '-';
					}
				}
				$n[$k]['service_name'] = $service_name;
			}
			if (!empty($n)) {
				Db::table($table)->insertAll($n);
				$type = 1;
			}

		}
		return $type;
	}
	/**
	 * Small fish
	 * 项目注释-
	$data = merchant();
	$Wxpay = new \Guyanpay\phpWxpay\Wxpay($data);
	$token = $Wxpay->getAccessToken();
	order_number_type 订单单号类型，用于确认需要上传详情的订单。枚举值1，使用下单商户号和商户侧单号；枚举值2，使用微信支付单号。
	transaction_id 原支付交易对应的微信订单号
	logistics_type 物流模式，发货方式枚举值：1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
	delivery_mode 货模式，发货模式枚举值：1、UNIFIED_DELIVERY（统一发货）2、SPLIT_DELIVERY（分拆发货） 示例值: UNIFIED_DELIVERY
	is_all_delivered 向用户推送发货完成通知。示例值: true/false
	 */
	public function upload_shipping_info($arr) {
		$rdata = $this->data;
		$Wxpay = new \Guyanpay\phpWxpay\Wxpay($rdata);
		$token = $Wxpay->getAccessToken();
		$url = 'https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info?access_token=' . $token;
		unset($Wxpay);
		$data['order_key'] = $arr['order_key'];
		$data['logistics_type'] = $arr['logistics_type'] ?? 1;
		$data['delivery_mode'] = $arr['delivery_mode'] ?? '1';
		$data['is_all_delivered'] = $arr['is_all_delivered'] ?? true;

		$data['shipping_list'] = $arr['shipping_list'];
		// $data['shipping_list'] = [
		// 	[
		// 		'tracking_no' => $arr['tracking_no'] ?? '',
		// 		'express_company' => $arr['express_company'] ?? '',
		// 		'item_desc' => $arr['item_desc'] ?? '商品信息',
		// 	],

		// ];
		$data['upload_time'] = date("c", strtotime(date('Y-m-d H:i:s', time())));
		$data['payer'] = [
			'openid' => $arr['openid'],
		];
		// p($data);
		$res = json_decode($this->http_request($url, json_encode($data)), true);
		// [errcode] => 10060003
		//  	[errmsg] => 支付单已使用重新发货机会, hint: [a5a7f84f-8591-4ffd-bcaa-fc10fe06fdf8] rid: 65545d8c-4af86682-7e900237
		// p($data1);
		return $res;
	}

	/**
	 * Small fish
	 * 项目注释-
	$data = merchant();
	$Deliver = new \Guyanpay\phpWxpay\Deliver($data);
	$arr['transaction_id'] = '4200002025202311154445649038';
	$res = $Deliver->notify_confirm_receive($arr);
	transaction_id	string	否	原支付交易对应的微信订单号
	merchant_id	string	否	支付下单商户的商户号，由微信支付生成并下发
	sub_merchant_id	string	否	二级商户号
	merchant_trade_no	string	否	商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
	received_time	number	是	快递签收时间，时间戳形式。
	 */
	public function notify_confirm_receive($arr) {
		$rdata = $this->data;
		$Wxpay = new \Guyanpay\phpWxpay\Wxpay($rdata);
		$token = $Wxpay->getAccessToken();
		$url = 'https://api.weixin.qq.com/wxa/sec/order/notify_confirm_receive?access_token=' . $token;
		$res = json_decode($this->http_request($url, json_encode($arr)), true);
		// [errcode] => 10060003
		//  	[errmsg] => 支付单已使用重新发货机会, hint: [a5a7f84f-8591-4ffd-bcaa-fc10fe06fdf8] rid: 65545d8c-4af86682-7e900237
		// p($data1);
		return $res;

	}

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

}