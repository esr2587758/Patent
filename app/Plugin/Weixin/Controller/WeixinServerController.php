<?php
use PHPSDK\Response\AbstractResponse;

App::uses('WeixinAppController', 'Weixin.Controller');

abstract class WeixinServerController extends WeixinAppController {
	
	/**
	 * 默认的响应数据，主要存储共用数据
	 * 
	 * @var unknown
	 */
	protected $_defaultResponses = array();
	
	/**
	 * (non-PHPdoc)
	 * @see Controller::beforeFilter()
	 */
	public function beforeFilter() {
		
		// 默认响应数据
		$this->_defaultResponses = array(
			'fromUserName' => $this->getWeixinRequestData('tousername'),
			'toUserName' => $this->getWeixinRequestData('fromusername'),
		);
		
		return parent::beforeFilter();
	}
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
	 * 响应XML消息
	 * 
	 * @param  array  $data
	 * @param  string $type
	 * @return
	 */
	public function responseXML($data = array(), $type = 'text') {
	
		$data = array_merge($this->_defaultResponses, $data);
	
		$oResponse = null;
		
		switch ( $type ) {
			case 'text':
				$oResponse = new \PHPSDK\Response\Text($data);
			break;
			
			case 'image':
				$oResponse = new \PHPSDK\Response\Image($data);
			break;
			
			case 'music':
				$oResponse = new \PHPSDK\Response\Music($data);
			break;
			
			case 'news':
				$oResponse = new \PHPSDK\Response\News($data);
			break;
			
			case 'video':
				$oResponse = new \PHPSDK\Response\Video($data);
			break;
			
			case 'voice':
				$oResponse = new \PHPSDK\Response\Voice($data);
			break;
		}
		
		if ( $oResponse instanceof AbstractResponse ) {
			// TODO 以后要做异常处理
			return false;
		}
		
		// 生成xml响应数据
		$xmlResponse = $oResponse->send();
		
		// DEBUG 记录相应消息
		CakeLog::write('debug', $xmlResponse);
		
		echo $xmlResponse;
		
		exit();
	}
	
	/**
	 * 响应客户端消息
	 */
	abstract public function response();
}