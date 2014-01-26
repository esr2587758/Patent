<?php
namespace CakeExt\Routing;

use PHPSDK;
use Dispatcher as CakeDispatcher,
	PHPSDK\Request\InterfaceRequest,
	PHPSDK\Request\Request;

/**
 * 微信分发器扩展
 *
 * @package       Cake.Routing
 */
class WeiDispatcher extends CakeDispatcher implements InterfaceRequest {

	/**
	 * CakeRequest
	 * 
	 * @var CakeRequest
	 */
	protected $_cakeRequest = null;
	
	/**
	 * 微信客户端请求的默认路由参数
	 * 
	 * @var unknown
	 */
	protected $_defaultParams = array(
		'plugin'     => 'weixin',
		'controller' => 'weixin',
		'action'     => 'response'
	);
	
	/**
	 * Applies Routing and additionalParameters to the request to be dispatched.
	 * If Routes have not been loaded they will be loaded, and app/Config/routes.php will be run.
	 *
	 * @param CakeEvent $event containing the request, response and additional params
	 * @return void
	 */
	public function parseParams($event) {
		
		parent::parseParams($event);
		
		$this->_cakeRequest = $event->data['request'];
	
		// 消息来自微信客户端？改变路由分发
		if ( isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']) ) {
			$weiRequest = new Request('token', $this, false);
			$weiRequest->parse();
		}
	}
	
	/**
	 * 用户关注时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onSubscribe(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'subscribe_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 用户取消关注时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onUnsubscribe(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'unsubscribe_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 收到文本消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onText(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'text_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
		
	}
	
	/**
	 * 收到图片消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onImage(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'image_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 收到地理位置消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onLocation(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'location_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 收到链接消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onLink(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'link_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
		
	}
	
	/**
	 * 收到自定义菜单消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onClick(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'click_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 收到地理位置事件消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onEventLocation(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'event_location_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
		
	}
	
	/**
	 * 收到语音消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onVoice(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'voice_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
		
	}
	
	/**
	 * 收到视频消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onVideo(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'video_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 扫描二维码时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onScan(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'scan_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
		
	}
	
	/**
	 * 收到未知类型消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	*/
	public function onUnknown(Request $request) {
		
		$params = array_merge($this->_defaultParams, array(
			'controller' => 'unknow_server'
		));
		
		$this->addCakeRequest($params, $request->getInput());
	}
	
	/**
	 * 向CakeRequest追加路由参数和请求数据信息
	 * 
	 * @param  array $params
	 * @param  array $data
	 * @return true
	 */
	public function addCakeRequest($params, $data) {
		
		$this->_cakeRequest->addParams($params);
		$this->_cakeRequest->data('Weixin', $data);
	}
}
