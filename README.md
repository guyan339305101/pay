  需要配合数据库使用
  //v3服务商支付模式
  $data = Db::name('merchant')->where('is_type', 1)->find();
  $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);
  $arr['money'] = '37.80';
  $arr['notify_url'] = '回掉地址';
  $arr['openid'] = 'okjqy5KP6RWj2PRfUV5ZYDqXvoTE';
  //v3服务商支付模式



  //服务商退款
  $mchid=子商户
  $out_trade_no =下单时商户订单
  $url=异步通知url
  $getOrderNo =自生成退款订单号
  $price=退款金额
  $yprice =订单金额
  //服务商退款

