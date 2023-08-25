需要配合数据库使用
  //v3服务商支付模式
  $data = Db::name('merchant')->where('is_type', 1)->find();
  $TestPackage = new \Guyanpay\phpWxpay\Wxpay($data);


  $arr['money'] = '37.80';
  $arr['notify_url'] = '回掉地址';
  $arr['openid'] = 'okjqy5KP6RWj2PRfUV5ZYDqXvoTE';

