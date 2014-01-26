<?php
namespace PHPSDK\Response;

/**
 * 音乐响应
 * 
 * @author chenjiahappy
 */
class Music extends AbstractResponse {
	
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
	 * 音乐链接
	 * 
	 * @var string
	 */
	protected $_music_url = '';
	
	/**
	 * 高质量音乐链接，WIFI环境优先使用该链接播放音乐
	 *
	 * @var string
	 */
	protected $_hq_music_url = '';
	
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
<MsgType><![CDATA[music]]></MsgType>
<Music>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<MusicUrl><![CDATA[%s]]></MusicUrl>
<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
</Music>
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
			$this->_title,
			$this->_description,
			$this->_music_url,
			$this->_hq_music_url,
			$this->_media_id
		);
	}
}

// End of the file