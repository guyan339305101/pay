<?php

namespace Guyanpay\phpWxpay;
use think\facade\Db;
use think\Model;

//https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter11_1_2.shtml 文档地址
class PlatformPay {

// 接口说明
	// 适用对象：电商平台https://pay.weixin.qq.com/wiki/doc/apiv3_partner/apis/chapter7_1_1.shtml
	// 请求URL：https://api.mch.weixin.qq.com/v3/ecommerce/applyments/
	// 请求方式：POST
	// 接口频率：15QPS(商户号维度)
	// 是否支持幂等：是
	public function applyments($Wxpay, $arr) {

		$data['out_request_no'] = $arr['out_request_no'] ?? time();
		$data['organization_type'] = $arr['organization_type'] ?? '2401';
		$data['authorize_letter_copy'] = $arr['authorize_letter_copy'] ?? '';
		$data['id_card_info'] = [
			'id_card_copy' => $arr['id_card_copy'] ?? '', //身份证正面
			'id_card_national' => $arr['id_card_national'] ?? '', //身份证背面
			'id_card_address' => $arr['id_card_address'] ?? '', //身份证地址
			'id_card_valid_time_begin' => $arr['id_card_valid_time_begin'] ?? '', //身份证开始时间
			'id_card_valid_time' => $arr['id_card_valid_time'] ?? '', //身份证结束时间
		];
		$data['owner'] = $arr['owner'] ?? true;
		//结算信
		$data['account_info'] = [
			'bank_account_type' => $arr['bank_account_type'] ?? '75', //账户类型75-对私账户。74-对公账若主体为个体工商户，可填写：74-对公账户、75-对私账户。
			'account_bank' => $arr['account_bank'] ?? '', //开户银行示例值：工商银行
			'account_name' => $arr['account_name'] ?? '', //敏感信息加密说明。
			'bank_address_code' => $arr['bank_address_code'] ?? '', //至少精确到市，详细参见省市区编号对照表。
			'bank_name' => $arr['bank_name'] ?? '', //开户银行全称 （含支行） 填写这个不用写联行号
			'account_number' => $arr['account_number'] ?? '', //银行卡号
		];
		$data['contact_info'] = [
			'contact_type' => $arr['contact_type'] ?? '', //1、主体为“小微/个人卖家 ”，可选择：65-经营者/法人。2、主体为“个体工商户/企业/政府机关/事业单位/社会组织”，可选择：65-经营者/法人、66- 经办人。 （经办人：经商户授权办理微信支付业务的人员）。示例值：65
			'contact_name' => $arr['contact_name'] ?? '', //超级管理员姓名
			'contact_email' => $arr['contact_email'] ?? '', //超级管理员邮箱//个人小微企业必须填
			'mobile_phone' => $arr['mobile_phone'] ?? '', //手机管理手机
		];
		$data['sales_scene_info'] = [
			'store_name' => $arr['store_name'] ?? '', //店铺名称示例值：爱烧烤
			'store_url' => $arr['store_url'] ?? '', //示例值：http://www.qq.com官网
			'store_qr_code' => $arr['store_qr_code'] ?? '', //1、店铺二维码 or 店铺链接二选一必填。2、若为电商小程序，可上传店铺页面的小程序二维码。
		];
		$data['merchant_shortname'] = $arr['merchant_shortname'] ?? ''; //商户简称即最多21个汉字长度示例值：腾讯
		//企业需要填写
		$data['business_license_info'] = [
			'business_license_copy' => $arr['business_license_copy'] ?? '', //营业执照图片
			'business_license_number' => $arr['business_license_number'] ?? '', //营业执照注册号
			'merchant_name' => $arr['merchant_name'] ?? '', //营业执照名称示例值：腾讯科技有限公司
			'legal_person' => $arr['legal_person'] ?? '', //法人姓名示例值：张三
			'company_address' => $arr['company_address'] ?? '', //注册地址
			'business_time' => $arr['business_time'] ?? '', //营业期限"2014-01-01","长期"
		];
		if (isset($arr['id_doc_info']) && !empty($arr['id_doc_info'])) {
			// 当证件持有人类型为经营者/法人且证件类型不为“身份证”时填写。
			$data['id_doc_info'] = $arr['id_doc_info'];
		}if (isset($arr['ubo_info_list']) && !empty($arr['ubo_info_list'])) {
			// 仅企业需要填写。
			$data['ubo_info_list'] = $arr['ubo_info_list'];
		}
		$url = 'https://api.mch.weixin.qq.com/v3/ecommerce/applyments/';

		// p($data);exit();
		$WxpayModel = new \Guyanpay\phpWxpay\Wxpay($Wxpay);
		$headers = $WxpayModel->sign('POST', $url, json_encode($data));
		$headers[] = "Wechatpay-Serial:" . $WxpayModel->serial_no . "";
		p($headers);
		// exit();
		$res = $WxpayModel->curl_post($url, json_encode($data), $headers);
		p($res);
	}

	//    适用对象： 服务商 获取对私帐户银行
	// 请求URL：https://api.mch.weixin.qq.com/v3/capital/capitallhh/banks/personal-banking
	// 请求方式：GET
	// $arr = Db::name('merchant')->find(1);
	// 	$TestPackage = new \Guyanpay\phpWxpay\PlatformPay();
	// 	$res = $TestPackage->personalbanking($arr, $page = 0, $limit = 200, $n = []);

	public function personalbanking($arr, $page = 0, $limit = 200, $n = [], $table = 'xh_banking_ds') {
		// $arr = [];
		$get = new \Guyanpay\phpWxpay\Wxpay($arr);
		$url = 'https://api.mch.weixin.qq.com/v3/capital/capitallhh/banks/personal-banking?offset=' . $page . '&limit=' . $limit;
		$sgin = $get->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$res = json_decode($get->curl_get_https($url, [], $header), true);
		// p($res);exit();
		if (isset($res['links']['next']) && !empty($res['links']['next'])) {
			$n = array_merge($n, $res['data']);
			$this->personalbanking($arr, $page + 200, $limit = 200, $n, $table);
			unset($res);
		} else {
			$n = array_merge($n, $res['data']);
			$exist = Db::query("show tables like '" . $table . "'");
			if (!empty($exist)) {
				Db::table($table)->where('id', '>', 0)->delete();
				Db::table($table)->strict(false)->insertAll($n);
			} else {
				if (isset($n[0]) && !empty(isset($n[0]))) {
					$c = '';
					foreach ($n[0] as $k => $v) {
						$c .= " " . $k . " VARCHAR(150) NOT NULL DEFAULT '0' COMMENT '', ";
					}
				}
				$sql1 = "CREATE TABLE " . $table . "( " . "id INT NOT NULL AUTO_INCREMENT," . $c .
					"PRIMARY KEY ( id )) CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='银行列表对私'";
				Db::query($sql1);
				Db::table($table)->strict(false)->insertAll($n);
			}

			return ['code' => 1, '操作完成'];
		}
		// p($res);

	}
	// 适用对象： 服务商 获取对公账户银行
	// 请求URL：https://api.mch.weixin.qq.com/v3/capital/capitallhh/banks/personal-banking
	// 请求方式：GET
	// $arr = Db::name('merchant')->find(1);
	// 	$TestPackage = new \Guyanpay\phpWxpay\PlatformPay();
	// 	$res = $TestPackage->corporatebanking($arr, $page = 0, $limit = 200, $n = []);

	public function corporatebanking($arr, $page = 0, $limit = 200, $n = [], $table = 'xh_banking_dz') {
		// $arr = [];
		$get = new \Guyanpay\phpWxpay\Wxpay($arr);
		$url = 'https://api.mch.weixin.qq.com/v3/capital/capitallhh/banks/personal-banking?offset=' . $page . '&limit=' . $limit;
		$sgin = $get->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$res = json_decode($get->curl_get_https($url, [], $header), true);
		// p($res);exit();
		if (isset($res['links']['next']) && !empty($res['links']['next'])) {
			$n = array_merge($n, $res['data']);
			$this->personalbanking($arr, $page + 200, $limit = 200, $n, $table);
			unset($res);
		} else {
			$n = array_merge($n, $res['data']);
			$exist = Db::query("show tables like '" . $table . "'");
			if (!empty($exist)) {
				Db::table($table)->where('id', '>', 0)->delete();
				Db::table($table)->strict(false)->insertAll($n);
			} else {
				if (isset($n[0]) && !empty(isset($n[0]))) {
					$c = '';
					foreach ($n[0] as $k => $v) {
						$c .= " " . $k . " VARCHAR(150) NOT NULL DEFAULT '0' COMMENT '', ";
					}
				}
				$sql1 = "CREATE TABLE " . $table . "( " . "id INT NOT NULL AUTO_INCREMENT," . $c .
					"PRIMARY KEY ( id )) CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='银行列表对公'";
				Db::query($sql1);
				Db::table($table)->strict(false)->insertAll($n);
			}
			return ['code' => 1, '操作完成'];
		}
	}
	// 查询省份列表API
	// 适用对象： 服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/capital/capitallhh/areas/provinces
	// 请求方式：GET
	public function provinces($arr, $table = 'xh_wx_city') {
		$url = 'https://api.mch.weixin.qq.com/v3/capital/capitallhh/areas/provinces';
		$get = new \Guyanpay\phpWxpay\Wxpay($arr);
		$sgin = $get->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$res = json_decode($get->curl_get_https($url, [], $header), true);
		$exist = Db::query("show tables like '" . $table . "'");
		if (empty($exist)) {
			$c = '';
			$c .= "city_code VARCHAR(150) NOT NULL DEFAULT '0' COMMENT '', ";
			$c .= "city_name VARCHAR(150) NOT NULL DEFAULT '0' COMMENT '', ";
			$c .= "pid int(11) NOT NULL DEFAULT '0' COMMENT '', ";
			$sql1 = "CREATE TABLE " . $table . "( " . "id INT NOT NULL AUTO_INCREMENT," . $c .
				"PRIMARY KEY ( id )) CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='银行列表对公'";
			Db::query($sql1);
		}
		foreach ($res['data'] as $v) {
			$provinces = Db::table($table)->where('pid', 0)->where('city_code', $v['province_code'])->find();
			if (!empty($provinces)) {
				$b = $provinces['id'];
				Db::table($table)->where('id', $provinces['id'])->update(['city_name' => $v['province_name']]);
			} else {
				$b = Db::table($table)->strict(false)->insertGetId(['city_name' => $v['province_name'], 'city_code' => $v['province_code']]);
			}
			$son = $this->cities($get, $arr, $v['province_code']);
			if (isset($son['data']) && !empty($son['data'])) {
				foreach ($son['data'] as $vd) {
					$vson = Db::table($table)->where('pid', '>', $b)->where('city_code', $vd['city_code'])->find();
					if (!empty($vson)) {
						Db::table($table)->where('id', $vson['id'])->update(['city_name' => $vd['city_name']]);
					} else {
						$b = Db::table($table)->strict(false)->insertGetId(['city_name' => $vd['city_name'], 'city_code' => $vd['city_code'], 'pid' => $b]);
					}
				}
			}
		}
	}
	// 查询城市列表API
	// 适用对象： 服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/capital/capitallhh/areas/provinces/{province_code}/cities
	// 请求方式：GET
	public function cities($get, $arr, $province_code) {
		$url = 'https://api.mch.weixin.qq.com/v3/capital/capitallhh/areas/provinces/' . $province_code . '/cities';
		$sgin = $get->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$res = json_decode($get->curl_get_https($url, [], $header), true);
		return $res;
	}

	// 适用对象： 服务商 查询支持银行
	// 请求URL：https://api.mch.weixin.qq.com/v3/capital/capitallhh/banks/{bank_alias_code}/branches
	// 请求方式：GET
	// $arr = Db::name('merchant')->find(1);
	// $TestPackage = new \Guyanpay\phpWxpay\PlatformPay();
	// $res = $TestPackage->branches($arr, $brank_code = '1000009561', $city_code = '10', 0, 200);
	// [bank_branch_id] => 308100005019
	// [bank_branch_name] => 招商银行股份有限公司北京分行
	public function branches($arr, $brank_code, $city_code, $page = 0, $limit = 200) {
		// $arr = [];
		$get = new \Guyanpay\phpWxpay\Wxpay($arr);
		$url = 'https://api.mch.weixin.qq.com/v3/capital/capitallhh/banks/' . $brank_code . '/branches?city_code=' . $city_code . '&offset=' . $page . '&limit=' . $limit;
		$sgin = $get->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$res = json_decode($get->curl_get_https($url, [], $header), true);
		return $res;
	}

	// 接口说明
	// 适用对象：服务商
	// 请求URL：https://api.mch.weixin.qq.com/v3/applyment4sub/applyment/business_code/{business_code}
	// 请求方式：GET
	// 业务申请编号	business_cod
	public function business_code($business_code) {
		// $arr = [];
		$get = new \Guyanpay\phpWxpay\Wxpay($arr);
		$url = 'https://api.mch.weixin.qq.com/v3/applyment4sub/applyment/business_code/' . $business_code;
		$sgin = $get->sign($http_method = 'GET', $url = $url, $body = '');
		$header = $sgin;
		$res = json_decode($get->curl_get_https($url, [], $header), true);
		return $res;
	}
	// 接口说明
	// 支持商户：【普通服务商】【电商平台】
	// 请求方式：【POST】/v3/apply4sub/sub_merchants/{sub_mchid}/modify-settlement
	// 请求域名：【主域名】https://api.mch.weixin.qq.com 使用该域名将访问就近的接入点【备域名】
	// https://api2.mch.weixin.qq.com
	// 使用该域名将访问异地的接入点 ，指引点击查看
	public function settlement($Wxpay, $arr) {

		$url = 'https://api.mch.weixin.qq.com/v3/apply4sub/sub_merchants/{sub_mchid}/modify-settlement';
		$WxpayModel = new \Guyanpay\phpWxpay\Wxpay($Wxpay);
		$url = $url . '/' . $WxpayModel->sub_mchid;
		$headers = $WxpayModel->sign('POST', $url, json_encode($data));
		$headers[] = "Wechatpay-Serial:" . $WxpayModel->serial_no . "";
		$data['modify_mode'] = 'MODIFY_MODE_ASYNC';
		$data['account_type'] = $arr['account_type'] ?? 1; //1、小微主体：经营者个人银行卡2、个体工商户主体：经营者个人银行卡/ 对公银行账户3、企业主体：对公银行账户4、党政、机关及事业单位主体：对公银行账户5、其他组织主体：对公银行账户可选取值：ACCOUNT_TYPE_BUSINESS: 对公银行账户ACCOUNT_TYPE_PRIVATE: 经营者个人银行卡
		$data['account_bank'] = $arr['account_bank'] ?? ''; //【开户银行】 请填写开户银行名称。对私银行调用：查询支持个人业务的银行列表API对公银行调用：查询支持对公业务的银行列表API
		$data['bank_address_code'] = $arr['bank_address_code']; //【开户银行省市编码】 需至少精确到市，详细参见省市区编号对照表。
		$data['bank_name'] = $arr['bank_name']; //【开户银行全称（含支行）】 1、根据开户银行查询接口中的“是否需要填写支行”判断是否需要填写。如为其他银行，开户银行全称（含支行）和开户银行联行号二选一。2、详细需调用查询支行列表API查看查询结果
		$data['bank_branch_id'] = $arr['bank_branch_id']; //【开户银行联行号】 1、根据开户银行查询接口中的“是否需要填写支行”判断是否需要填写。如为其他银行，开户银行全称（含支行）和开户银行联行号二选一。2、详细需调用查询支行列表API查看查询结果。
		$data['account_number'] = $arr['account_number']; //【银行账号】 1、数字，长度遵循系统支持的开户银行对照表中对公/对私卡号长度要求2、该字段需进行加密处理，加密方法详见敏感信息加密说明。(提醒：必须在HTTP头中上送Wechatpay-Serial)
		// exit();
		$res = $WxpayModel->curl_post($url, json_encode($data), $headers);
		p($res);
	}

	// 查询结算账户 接口说明服务商/电商平台（不包括支付机构、银行），可使用本接口，查询其进件且已签约特约商户/二级商户当前的结算账户信息（敏感信息掩码）和验证结果。
	// 支持商户：【普通服务商】【电商平台】
	// 请求方式：【POST】/v3/apply4sub/sub_merchants/{sub_mchid}/settlement
	// 请求域名：【主域名】】https://api.mch.weixin.qq.com 使用该域名将访问就近的接入点【备域名】
	// https://api2.mch.weixin.qq.com
	// 使用该域名将访问异地的接入点 ，指引点击查看
	public function settlementquery($Wxpay, $arr) {

		$url = 'https://api.mch.weixin.qq.com//v3/apply4sub/sub_merchants/' . $sub_mchid . '/settlement';
		$WxpayModel = new \Guyanpay\phpWxpay\Wxpay($Wxpay);
		$url = $url . '/' . $WxpayModel->sub_mchid;
		$headers = $WxpayModel->sign('GET', $url, json_encode($data));
		$data = '';
		// exit();
		$res = $WxpayModel->curl_get_https($url, json_encode($data), $headers);
		p($res);
	}

	// 查询结算账户修改申请状态 接口说明服务商/电商平台（不包括支付机构、银行），可使用本接口，查询其进件且已签约特约商户/二级商户当前的结算账户信息（敏感信息掩码）和验证结果。
	// 支持商户：【普通服务商】【电商平台】
	// 请求方式：【POST】/v3/apply4sub/sub_merchants/{sub_mchid}/application/{application_no}
	// 请求域名：【主域名】】https://api.mch.weixin.qq.com 使用该域名将访问就近的接入点【备域名】
	// https://api2.mch.weixin.qq.com
	// 使用该域名将访问异地的接入点 ，指引点击查看
	public function application($Wxpay, $arr) {

		$url = '/v3/apply4sub/sub_merchants/' . $application_no . '/application/' . $application_no;
		$WxpayModel = new \Guyanpay\phpWxpay\Wxpay($Wxpay);
		$url = $url . '/' . $WxpayModel->sub_mchid;
		$headers = $WxpayModel->sign(' GET', $url, json_encode($data));
		$data = '';

		// exit();
		$res = $WxpayModel->curl_get_https($url, json_encode($data), $headers);
		p($res);
	}

}
