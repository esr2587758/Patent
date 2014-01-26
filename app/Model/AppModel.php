<?php
/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link http://cakephp.org CakePHP(tm) Project
 * @package app.Model
 * @since CakePHP(tm) v 0.2.9
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses ( 'Model', 'Model' );

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package app.Model
 */
class AppModel extends Model {

	/**
	 * 错误编码
	 *
	 * @see app/Lib/errorDefine.php
	 * @var int
	 */
	private $errorCode = NULL;

	/**
	 * 错误描述
	 *
	 * @var String
	 */
	private $errorMsg = NULL;

	/**
	 * 数据库类型
	 *
	 * @var enum(mysql,mongo)
	 */
	protected $databaseType = 'mysql';

	/**
	 *
	 * @param string $id        	
	 * @param string $table        	
	 * @param string $ds        	
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		if ($this->databaseType == 'mongo') {
			if(CakePlugin::loaded('Mongodb')){
				$this->actsAs [] = 'Mongodb';
				$this->actsAs [] = 'Mongodb.SchemaFormatter';
			}
		} elseif ($this->databaseType == 'mysql') {
			$this->actsAs [] = 'Mysql';
		}
		return parent::__construct ( $id, $table, $ds );
	}
	/**
	 * 当数据库操作发生错误时
	 * 
	 * @see Model::onError()
	 */
	function onError() {
		 $this->occurError ();
		 $this->log($this->errorMsg,LOG_ERR);
	}
	
	/**
	 * 获取模型的error code
	 * 
	 * @return integer
	 */
	function getErrorCode() {
		return $this->errorCode;
	}
	
	/**
	 * 获取模型的错误信息
	 * 
	 * @return NULL
	 */
	function getErrorMsg() {
		return $this->errorMsg;
	}
	function setErrorCode($code) {
		$this->errorCode = $code;
	}
	function setErrorMsg($msg) {
		$this->errorMsg = $msg;
	}
	
	/**
	 * 清除错误消息
	 */
	protected function _cleanError() {
		$this->errorCode = null;
		$this->errorMsg = null;
	}
}
