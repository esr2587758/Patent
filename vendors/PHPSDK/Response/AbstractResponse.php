<?php
namespace PHPSDK\Response;

abstract class AbstractResponse {
	
	/**
	 * 接收方帐号
	 * 
	 * @var string
	 */
	protected $_toUserName = '';
	
	/**
	 * 开发者微信号
	 * 
	 * @var string
	 */
	protected $_fromUserName = '';
	
	/**
	 * 消息文本模版内容
	 * 
	 * @var string
	 */
	protected $_template = '';
	
	/**
	 * 构造函数
	 * 
	 * @param  array $params
	 * @return void
	 */
	public function __construct($params = array()) {
	
		foreach ($params as $param => $val) {
			$param = '_'.$param;
			if ( isset($this->$param) ) {
				$this->$param = $val;
			}
		}
	}
	
	/**
	 * 向客户端发送消息
	 * 
	 * @return string
	 */
	abstract public function send();
}

// End of the file