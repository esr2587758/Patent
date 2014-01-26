<?php
namespace PHPSDK\Response;

/**
 * 视频响应
 * 
 * @author chenjiahappy
 */
class Video extends AbstractResponse {
	
	/**
	 * 音乐标题
	 * 
	 * @var string
	 */
	protected $_title = '';
	
	/**
	 * 音乐描述
	 * 
	 * @var string
	 */
	protected $_description = '';
	
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
<MsgType><![CDATA[video]]></MsgType>
<Video>
<MediaId><![CDATA[%s]]></MediaId>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
</Video> 
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
			$this->_media_id,
			$this->_title,
			$this->_description
		);
	}
}

// End of the file