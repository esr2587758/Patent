<?php
/**
* Innosnap Service组件
*/
namespace Innosnap\Service;

use Zend\ServiceManager\ServiceManager;

class ServiceListener
{
	/**
	 * 实例化服务
	 *
	 * @var array
	 */
	protected $invokables = array();

	/**
	 * 工厂服务
	 *
	 * @var array
	 */
	protected $factories = array();

	/**
	 * 抽象工厂
	 *
	 * @var array
	 */
	protected $abstractFactories = array();

	/**
	 * 别名
	 *
	 * @var array
	 */
	protected $aliases = array();

	/**
	* 共享服务
	*
	* @var array
	*/
	protected $shared = array();

	/**
	 * 构造函数
	 *
	 *
	 * @param  array $configuration
	 */
	public function __construct(array $configuration = array())
	{
		if(isset($configuration['invokables'])){
			$this->invokables = array_merge($this->invokables, $configuration['invokables']);
		}

		if(isset($configuration['factories'])){
			$this->factories = array_merge($this->factories, $configuration['factories']);
		}

		if(isset($configuration['abstract_factories'])){
			$this->abstractFactories = array_merge($this->abstractFactories, $configuration['abstract_factories']);
		}

		if(isset($configuration['aliases'])){
			$this->aliases = array_merge($this->aliases, $configuration['aliases']);
		}

		if(isset($configuration['shared'])){
			$this->shared = array_merge($this->shared, $configuration['shared']);
		}

	}

	/**
	 * 初始化ServiceManager
	 * 
	 * @param  ServiceManager $serviceManager
	 * @return void
	 */
	public function initServiceManager(ServiceManager $serviceManager){
		foreach($this->invokables as $name => $class){
			$serviceManager->setInvokableClass($name, $class);
		}

		foreach($this->factories as $name => $factoryClass) {
			$serviceManager->setFactory($name, $factoryClass);
		}

		foreach($this->abstractFactories as $factoryClass){
			$serviceManager->addAbstractFactory($factoryClass);
		}

		foreach($this->aliases as $name => $service){
			$serviceManager->setAlias($name, $service);
		}

		foreach($this->shared as $name => $value){
			$serviceManager->setShared($name, $value);
		}

		$serviceManager->setService('ServiceManager', $serviceManager);
		$serviceManager->setAlias('Zend\ServiceManager\ServiceLocatorInterface', 'ServiceManager');
		$serviceManager->setAlias('Zend\ServiceManager\ServiceManager', 'ServiceManager');
	}
}
