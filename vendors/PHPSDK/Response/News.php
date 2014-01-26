<?php
namespace PHPSDK\Response;

/**
 * 图文响应
 * 
 * @author chenjiahappy
 */
class News extends AbstractResponse {
	
	/**
	 * 图文信息数组
	 * 
	 * @var unknown
	 */
	protected $_items = array();
	
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
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>
%s
</Articles>
</xml>
XML;
		
		parent::__construct($params);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \PHPSDK\Response\AbstractResponse::send()
	 */
	public function send() {
		
		$itemTemplate = <<<XML
<item>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url>
</item>
XML;
		$strArticles = '';
		foreach ($this->_items as $item) {
			
			$strTitle = ( isset($item['title']) ? $item['title'] : '' );
			$strDescription = ( isset($item['description']) ? $item['description'] : '' );
			$strPicUrl = ( isset($item['pic_url']) ? $item['pic_url'] : '' );
			$strUrl = ( isset($item['url']) ? $item['url'] : '' );
			
			$strArticles .= sprintf(
				$itemTemplate,
				$strTitle,
				$strDescription,
				$strPicUrl,
				$strUrl
			);
		}
		
		return sprintf(
			$this->_template,
			$this->_toUserName,
			$this->_fromUserName,
			time(),
			count($this->_items),
			$strArticles
		);
	}
}

// End of the file