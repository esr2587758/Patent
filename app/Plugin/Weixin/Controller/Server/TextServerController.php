<?php
App::uses('WeixinServerController', 'Weixin.Controller');

use PHPSDK\Response\Text;

class TextServerController extends WeixinServerController {
	
	public function response() {
		
		// 微信请求参数
		$requestData = $this->getWeixinRequestData();
		// debug($requestData);
		
		$textResponse = new Text(array(
			'content' => 'TextServer',
			'fromUserName' => $this->getWeixinRequestData('fromusername'),
			'toUserName' => $this->getWeixinRequestData('tousername'),
		));
		
		$this->send($textResponse);
	}
}