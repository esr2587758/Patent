<?php
use PHPSDK\Response\AbstractResponse;

App::uses('WeixinAppController', 'Weixin.Controller');

abstract class WeixinServerController extends WeixinAppController {
	
	abstract public function response();
	
	/**
	 * 获取微信客户端请求的数据
	 * 
	 * @param  string $param
	 * @return mixed
	 */
	public function getWeixinRequestData($param = false) {
		
		if ($param === false) {
			return Hash::get($this->request->data, 'Weixin');
		}
		
		$param = strtolower($param);
		
		return Hash::get($this->request->data, 'Weixin.'.$param);
	}
	
	/**
	 * 返回相应结果
	 * 
	 * @param AbstractResponse $response
	 */
	public function send(AbstractResponse $response) {
		
		$xmlResponse = $response->send();
		
		// 记录相应消息
		CakeLog::write('debug', $xmlResponse);
		
		echo $xmlResponse;
		exit();
	}
}