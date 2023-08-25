  需要配合数据库使用
  //v3服务商支付模式
  $data = Db::name('merchant')->where('is_type', 1)->find();
  $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
  $arr['money'] = '37.80';
  $arr['notify_url'] = '回掉地址';
  $arr['openid'] = 'okjqy5KP6RWj2PRfUV5ZYDqXvoTE';
  $res = $TestPackage->getfpay($arr);
  //v3服务商支付模式



  //服务商退款
  $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
  $res = $TestPackage->shoprefund($mchid=子商户,$out_trade_no =下单时商户订单,$url=异步通知url,$getOrderNo =自生成退款订单号,$price=退款金额,$yprice =订单支付金额),
  //服务商退款

  // 直连退款
   $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
  $res = $TestPackage->(refund$mchid=商户号, $transaction_id==微信订单, $url=回掉URL, $getOrderNo=自创建的, $price=退款金额, $yprice = 订单支付金额）
  // 直连退款

// 直连付款
 $arr['gooddesc']='商品'
 $arr['out_trade_no']订单号
 $arr['notify_url']回掉url
 $arr['time_expire'] =支付超时描述 例如：300 代表300秒
 $arr['money']金额
 $arr['openid']下单用户
 $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
 $res = $TestPackage->jsapi($arr);
 // 直连付款
  
 //订单状态查询
  $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
  $res=$TestPackage->getOrder($out_refund_no=支付商户号, $sp_mchid =服务商商户, $sub_mchid = 子商户)
 //订单状态查询


 // 服务商查询单笔退款API
  $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
  $res=$TestPackage->getOrderrefunds($out_refund_no, $sub_mchid = 0)
 // 服务商查询单笔退款API
