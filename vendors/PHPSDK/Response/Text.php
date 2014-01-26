<?php
namespace PHPSDK\Response;

/**
 * 文本响应
 * 
 * @author chenjiahappy
 */
class Text extends AbstractResponse {
	
	/**
	 * 文本内容
	 * 
	 * @var string
	 */
	protected $_content = '';
	
	/**
	 * 构造函数
	 * 
	 * @param array $params
	 */
	public function __construct($params = array()) {
		
		$this->_template = <<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>
XML;
		
		parent::__construct($params);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \PHPSDK\Response\AbstractResponse::send()
	 */
	public function send() {
		
		return sprintf(
			$this->_template,
			$this->_toUserName,
			$this->_fromUserName,
			time(),
			$this->_content
		);
	}
}

// End of the file