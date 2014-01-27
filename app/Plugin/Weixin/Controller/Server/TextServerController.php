<?php
App::uses('WeixinServerController', 'Weixin.Controller');

/**
 * 文本请求服务
 * 
 * @author chenjiahappy
 */
class TextServerController extends WeixinServerController {
	
	/**
	 * 文本命令规则
	 * 
	 * @var string
	 */
	private $_regCommand = '/(s|a)([\S]*)[\s]+(.*)/i';
	
	/**
	 * 字段映射消息
	 * 
	 * @var array
	 */
	private $_fieldMaps = array();
	
	/**
	 * (non-PHPdoc)
	 * @see WeixinServerController::response()
	 */
	public function response() {
		
		$this->_fieldMaps = Configure::read('command.field.mappers');
		
		// 解析文本命令
		$content = $this->getWeixinRequestData('content');
		preg_match($this->_regCommand, $content, $matches);
		
		if ( !$matches || count($matches) != 4 ) {
			$this->_help();
		}
		
		// 解析参数
		list(, $type, $field, $val) = $matches;
		
		// 是否是有效字段
		if ( !array_key_exists($field, $this->_fieldMaps) ) {
			$this->_help();
		}
		
		switch ($type) {
			case 's':
				$this->_search($field, $val);
			break;
			
			case 'a':
				$this->_analyze($field, $val);
			break;
			
			default:
				$this->_help();
			break;
		}
	}
	
	/**
	 * 搜索
	 * 
	 * @param  string $field
	 * @param  string $val
	 * @return void
	 */
	private function _search($field, $val = '') {
		$this->responseXML(array(
			'content' => 'Type:search Field:'.$field. ' Value:' . $val
		));
	}
	
	/**
	 * 分析
	 *
	 * @param  string $field
	 * @param  string $val
	 * @return void
	 */
	private function _analyze($field, $val = '') {
		
		$this->responseXML(array(
			'content' => 'Type:analyze Field:'.$field. ' Value:' . $val
		));
	}
	
	/**
	 * 提示消息
	 * 
	 * @return void
	 */
	private function _help() {
		
		$this->responseXML(array(
			'content' => 'ABSTTTLCLMS	Abstract &amp; Title &amp; Claims
		TTLABST	Title &amp; Abstract
		PN	Publication Number
		APNO	Application Number
		APD	Application Date
		PBDT	Publication Date
		PRIORITY_DATE	Priority Date
		ABST	Abstract
		TTL	Title
		CLMS	Claims
		DESC	Description
		CCL	US Classification
		ICL	International Classification
		LOC	Locarno Classification
		IN	Inventor Name
		IN_ADDRESS	Inventor Address
		AN	Assignee Name
		AN_ADDRESS	Assignee Address
		PE	Primary Examiner
		AE	Assistant Examiner
		AT	Attorney Name'
		));
	}
}