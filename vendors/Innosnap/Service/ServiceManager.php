<?php
/**
 * Innosnap Service组件
 */
namespace Innosnap\Service;

use Zend\ServiceManager\ServiceManager as ZendServiceManager;

class ServiceManager {
	
	private static $_instance = null;
	
	private $_serviceLocator = null;

	private function __construct() {}
	
	/**
	 * 获取单列对象
	 * 
	 * @return ServiceManager
	 */
	public static function getInstance() {
		
		if ( self::$_instance == null ) {
			
			self::$_instance = new self();
			self::$_instance->setServiceLocator(new ZendServiceManager());
		}
		return self::$_instance;
	}
	
	public function setServiceLocator(ZendServiceManager $serviceLocator) {
		
		$this->_serviceLocator = $serviceLocator;
	}
	
	public function getServiceLocator() {
	
		return $this->_serviceLocator;
	}
	
	public function register($listeners) {
		
		$serviceListener = new ServiceListener($listeners);
		$serviceListener->initServiceManager($this->_serviceLocator);
	}
}