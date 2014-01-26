<?php
App::uses ( 'DbBehavior', 'Model/Behavior' );
class MysqlBehavior extends ModelBehavior {
	
	/**
	 * 当发生错误时
	 * 
	 * @param Model $model        	
	 */
	function occurError(Model $model) {
		$model->setErrorCode ( $model->getDataSource ()->errorCode () );
		$this->setErrorMsg ( $model->getDataSource ()->errorInfo () );
	}
}
?>