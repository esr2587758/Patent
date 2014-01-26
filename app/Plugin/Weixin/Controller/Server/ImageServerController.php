<?php
App::uses('WeixinServerController', 'Weixin.Controller');

use PHPSDK\Response\News;

class ImageServerController extends WeixinServerController {
	
	public function response() {
		
		// 微信请求参数
		$requestData = $this->getWeixinRequestData();
		// debug($requestData);
		
		$textResponse = new News(array(
				'items' => array(
					array(
						'title' => '小样就你能发',
						'description' => '有胆量你点我啊',
						'pic_url' => 'http://img.funshion.com/pictures/536/625/536625.jpg',
						'url' => 'http://www.innosnap.com/'
					)
				),
				'fromUserName' => $this->getWeixinRequestData('tousername'),
				'toUserName' => $this->getWeixinRequestData('fromusername'),
		));
		
		$this->send($textResponse);
		
	}
}