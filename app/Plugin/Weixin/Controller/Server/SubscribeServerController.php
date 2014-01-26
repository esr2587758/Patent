<?php
App::uses('WeixinServerController', 'Weixin.Controller');

use PHPSDK\Response\Text;

class SubscribeServerController extends WeixinServerController {
	
	public function response() {
		
		$textResponse = new Text(array(
			'content' => 'SubscribeServer'
		));
		
		$this->send($textResponse);
	}
}