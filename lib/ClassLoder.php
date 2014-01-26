<?php

/**
 * class加载类
 * 
 * 支持命名空间加载及
 * 类似ZF1.x的路径分割命名的长类名加载
 * 
 */
class ClassLoder {
	/**
	 * 类-路径映射数组 
	 * 
	 * @var array
	 */
	protected static $_class_map    = array();
	
	/**
	 * include_path 路径字典
	 * 
	 * @var array
	 */
	protected static $_include_path  = array();
	
	/**
	 * 命名空间-路径映射数组 
	 * 
	 * @var array
	 */
	protected static $_namespaces = array();
	
	/**
	 * 是否已经注册类自动装载器 
	 * 
	 * @var bool
	 */
	private static $_registerd = false;
	
	/**
	 * 注册类自动装载器 
	 * 
	 * @return bool true
	 */
	public static function register()
	{
		if ( self::$_registerd === false ) {
			
			spl_autoload_register(array('ClassLoder', 'load'));
			self::$_registerd = true;
		}
		return true;
	}
	
	/**
	 * 加载类
	 *
	 * @param string $class
	 * @return  bool   true
	 */
	public static function load($class)
	{
		if ( isset(static::$_class_map[$class]) ) {
			return require static::$_class_map[$class];
		}

		foreach (static::$_namespaces as $namespace => $directory) {
			
			if (strpos($class, $namespace) !== false) {
				return static::_load_namespaced($class, $namespace, $directory);
			}
		}

		static::_load_file($class);
	}
	
	/**
	 * 注册命名空间映射
	 *
	 * @param  array   $map       映射数组信息
	 * @param  string  $append 追加符(用于准确查找命名空间映射信息)
	 * @return void
	 */
	public static function namespaces($map, $append = '\\')
	{
		$map = static::_format_map($map, $append);
		
		static::$_namespaces = array_merge($map, static::$_namespaces);
	}
	
	/**
	 * 注册类名-路径映射信息
	 *
	 * @param  array  $map 映射信息
	 * @return void
	 */
	public static function class_map($map)
	{
		static::$_class_map = array_merge(static::$_class_map, $map);
	}
	
	/**
	 * 注册include_path字典路径
	 *
	 * @param  string|array  $directory
	 * @return   void
	 */
	public static function include_paths($directory)
	{
		$directories = static::_format($directory);
		
		static::$_include_path = array_unique(
			array_merge(static::$_include_path, $directories)
		);
	}

	/**
	 * 注册以下划线分割模拟的命名空间
	 *
	 * @param  array  $map
	 * @return void
	 */
	public static function underscored($map)
	{
		static::namespaces($map, '_');
	}
	
	/**
	 * 根据命名空间映射关系加载类
	 *
	 * @param  string  $class            类名
	 * @param  string  $namespace 命名空间映射键值
	 * @param  string  $directory       字典目录
	 * @return void
	 */
	protected static function _load_namespaced($class, $namespace, $directory)
	{
		return static::_load_file(substr($class, strlen($namespace)), $directory);
	}

	/**
	 * 加载类文件
	 *
	 * @param  string  $class       类名
	 * @param  string  $directory 字典目录
	 * @return bool
	 */
	protected static function _load_file($class, $directories = null)
	{
		$file        = str_replace(array('\\', '_'), '/', $class);
		// 路径为空时使用默认路径字典
		$directories = ( $directories  ? $directories : static::$_include_path);
		$lower       = strtolower($file);
		
		foreach ((array) $directories as $directory) {
			if (file_exists($path = $directory.$lower.EXT)) {
				return require $path;
			} else if (file_exists($path = $directory.$file.EXT)) {
				return require $path;
			}
		}
		return false;
	}

	/**
	 * 命名空间处理，主要是处理路径
	 * 
	 * @param array  $map
	 * @param string $append
	 * @return  array
	 */
	protected static function _format_map($map, $append)
	{
		$namespaces = array();
		
		foreach ($map as $namespace => $directory) {
			
			$namespace = trim($namespace, $append).$append;
			unset(static::$_namespaces[$namespace]);
			
			$namespaces[$namespace] = static::_format($directory);
		}

		return $namespaces;
	}

	/**
	 * 路径处理，以路径分割符结尾
	 *
	 * @param  array  $directories 路径数组
	 * @return   array
	 */
	protected static function _format($directories)
	{
		return array_map(function($directory)
		{
			return rtrim($directory, DS).DS;
		
		}, (array) $directories);
	}
}
?>