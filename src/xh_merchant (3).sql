-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2023-08-25 13:42:55
-- 服务器版本： 5.6.50-log
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tpswolle`
--

-- --------------------------------------------------------

--
-- 表的结构 `xh_merchant`
--

CREATE TABLE IF NOT EXISTS `xh_merchant` (
  `id` int(11) NOT NULL,
  `is_show` int(11) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `is_type` int(11) DEFAULT '2' COMMENT '状态/使用/停用',
  `keyd` varchar(50) NOT NULL DEFAULT '' COMMENT 'V3-key(32位)',
  `serial_no` varchar(100) NOT NULL DEFAULT '' COMMENT '证书编号:',
  `apiclient_cert` text NOT NULL COMMENT '支付证书apiclient_cert',
  `apiclient_key` text NOT NULL COMMENT '支付证书apiclient_key',
  `public_key` text NOT NULL COMMENT '支付证书public_key',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '商户名称',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `sub_secret` varchar(222) DEFAULT NULL COMMENT '小程序Appsecret',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家',
  `sp_appid` varchar(222) NOT NULL COMMENT '服务商appid',
  `sp_mchid` varchar(222) NOT NULL COMMENT '服务商商户号',
  `sub_appid` varchar(222) NOT NULL COMMENT '小程序appid',
  `sub_mchid` int(11) NOT NULL COMMENT '子商户'
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='商户管理';

--
-- 转存表中的数据 `xh_merchant`
--

INSERT INTO `xh_merchant` (`id`, `is_show`, `is_type`, `keyd`, `serial_no`, `apiclient_cert`, `apiclient_key`, `public_key`, `name`, `time`, `sub_secret`, `shop_id`, `sp_appid`, `sp_mchid`, `sub_appid`, `sub_mchid`) VALUES
(11, 0, 1, '3213213', '321321', '+ewqweqw', '7RLo', '---3123', '32131', '2023-08-11 14:08:42', '321312', 0, '32321', '321321', '321321', 32332);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `xh_merchant`
--
ALTER TABLE `xh_merchant`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_show_name` (`is_show`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `xh_merchant`
--
ALTER TABLE `xh_merchant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
