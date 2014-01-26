<?php
App::uses('WeixinServerController', 'Weixin.Controller');

use PHPSDK\Response\Text;

class TextServerController extends WeixinServerController {
	
	public function response() {
		
		$requestData = $this->getWeixinRequestData();
		
		$textResponse = new Text(array(
			'content' => 'TextServer'
		));
		
		$this->send($textResponse);
	}
}