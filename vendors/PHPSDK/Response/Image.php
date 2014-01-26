<?php
namespace PHPSDK\Response;

/**
 * 图片响应
 * 
 * @author chenjiahappy
 */
class Image extends AbstractResponse {
	
	/**
	 * 上传的媒体id
	 * 
	 * @var string
	 */
	protected $_media_id = '';
	
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
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[%s]]></MediaId>
</Image>
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
			$this->_media_id
		);
	}
}

// End of the file