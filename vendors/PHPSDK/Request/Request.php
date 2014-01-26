<?php
namespace PHPSDK\Request;

use PHPSDK\Response\Text;

class Request {
	
	/**
	 * 消息请求
	 * 
	 * @var InterfaceRequest
	 */
	private $_request = null;
	
	/**
	 * 是否开启调试模式
	 * 
	 * @var bool
	 */
	private $_debug = false;
	
	/**
	 * 请求数据
	 * 
	 * @var array
	 */
	private $_inputData = array();
	
	/**
	 * 构造函数
	 * 
	 * @param string $token
	 * @param InterfaceRequest $request
	 * @param string $debug
	 */
	public function __construct($token, InterfaceRequest $request, $debug = false) {
		
		// 验证签名
// 		if ( !$this->_validateSignature($token) ) {
// 			exit('Validate signature was failed.');
// 		}
		
// 		// 验证参数是否存在
// 		if ( !isset($GLOBALS['HTTP_RAW_POST_DATA']) ) {
// 			exit('No request Data.');
// 		}
		
		// 设置错误处理函数，将错误通过文本消息回复显示
		set_error_handler(array(&$this, 'errorHandler'));
		
		//$xmlData = (array)simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);
		
		$xmlData = array(
			'msgtype' => 'text',
		);
		
		// 统一转换为小写
		$this->_inputData = array_change_key_case($xmlData, CASE_LOWER);
		
		$this->_request = $request;
		
		$this->_debug   = $debug;
	}
	
	/**
	 * 解析消息类型
	 *
	 * @return void
	 */
	public function parse() {
		
		switch ( $this->getInput('msgtype') ) {
			
			case 'event':
				switch ( strtolower($this->getInput('event')) ) {
					case 'subscribe':
						$this->_request->onSubscribe($this);
					break;
	
					case 'unsubscribe':
						$this->_request->onUnsubscribe($this);
					break;
	
					case 'scan':
						$this->_request->onScan();
					break;
	
					case 'location':
						$this->_request->onEventLocation($this);
					break;
	
					case 'click':
						$this->_request->onClick($this);
					break;
	
				}
				break;
	
			case 'text':
				$this->_request->onText($this);
			break;
	
			case 'image':
				$this->_request->onImage($this);
			break;
	
			case 'location':
				$this->_request->onLocation($this);
			break;
	
			case 'link':
				$this->_request->onLink($this);
			break;
	
			case 'voice':
				$this->_request->onVoice($this);
			break;
			
			case 'video':
				$this->_request->onVideo($this);
			break;
	
			default:
				$this->_request->onUnknown($this);
			break;
	
		}
	}
	
	/**
	 * 自定义的错误处理函数，将 PHP 错误通过文本消息回复显示
	 * @param  int $level   错误代码
	 * @param  string $msg  错误内容
	 * @param  string $file 产生错误的文件
	 * @param  int $line    产生错误的行数
	 * @return void
	 */
	public function errorHandler($level, $msg, $file, $line) {
		if ( ! $this->_debug) {
			return;
		}
	
		$error_type = array(
				E_WARNING           => 'Warning',
				E_NOTICE            => 'Notice',
				E_USER_ERROR        => 'User Error',
				E_USER_WARNING      => 'User Warning',
				E_USER_NOTICE       => 'User Notice',
				E_STRICT            => 'Strict',
				E_RECOVERABLE_ERROR => 'Recoverable Error',
				E_DEPRECATED        => 'Deprecated',
				E_USER_DEPRECATED   => 'User Deprecated',
		);
	
		$template = <<<ERR
Error:	
%s: %s
File: %s
Line: %s
ERR;

		$errorContent = sprintf(
			$template,
			$error_type[$level],
			$msg,
			$file,
			$line
		);
		
		$responseText = new Text(array(
			'fromUserName' => $this->getInput('fromusername'),
			'toUserName'   => $this->getInput('tousername'),
			'content'      => $errorContent
		));
		
		exit($responseText->send());
	}
	
	/**
	 * 验证签名信息
	 *
	 * @param  string $token 验证信息
	 * @return boolean
	 */
	private function _validateSignature($token) {
		
		if ( ! (isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']))) {
			return false;
		}
	
		$signature = $_GET['signature'];
		$timestamp = $_GET['timestamp'];
		$nonce     = $_GET['nonce'];
	
		$signatureArray = array($token, $timestamp, $nonce);
		sort($signatureArray);
	
		return sha1(implode($signatureArray)) == $signature;
	}
	
	/**
	 * 获取本次请求中的参数
	 *
	 * @param  string $param 参数名
	 * @return mixed
	 */
	public function getInput($param = false) {
		
		if ($param === false) {
			return $this->_inputData;
		}
	
		$param = strtolower($param);
	
		if ( isset($this->_inputData[$param]) ) {
			return $this->_inputData[$param];
		}
		return null;
	}
}

// End of the file