<?php
App::uses('WeixinServerController', 'Weixin.Controller');

class ImageServerController extends WeixinServerController {
	
	public function response() {
		
		$this->responseXML(array(
			'items' => array(
				array(
					'title' => '小样就你能发',
					'description' => '有胆量你点我啊',
					'pic_url' => 'http://img.funshion.com/pictures/536/625/536625.jpg',
					'url' => 'http://www.innosnap.com/'
				),
				array(
					'title' => '再看',
					'description' => '把你吃掉',
					'pic_url' => 'http://img.funshion.com/pictures/536/625/536625.jpg',
					'url' => 'http://www.innosnap.com/'
				),
			),
		), 'news');
	}
}