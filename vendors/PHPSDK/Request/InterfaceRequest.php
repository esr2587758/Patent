<?php
namespace PHPSDK\Request;

/**
 * request接口
 * 
 * @author chenjiahappy
 */
interface InterfaceRequest {
	
	/**
	 * 用户关注时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onSubscribe(Request $request);
	
	/**
	 * 用户取消关注时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onUnsubscribe(Request $request);
	
	/**
	 * 收到文本消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onText(Request $request);
	
	/**
	 * 收到图片消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onImage(Request $request);
	
	/**
	 * 收到地理位置消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onLocation(Request $request);
	
	/**
	 * 收到链接消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onLink(Request $request);
	
	/**
	 * 收到自定义菜单消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onClick(Request $request);
	
	/**
	 * 收到地理位置事件消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onEventLocation(Request $request);
	
	/**
	 * 收到语音消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onVoice(Request $request);
	
	/**
	 * 收到视频消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onVideo(Request $request);
	
	/**
	 * 扫描二维码时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onScan(Request $request);
	
	/**
	 * 收到未知类型消息时触发，用于实现
	 *
	 * @param  Request $request
	 * @return void
	 */
	public function onUnknown(Request $request);
}

// End of the file