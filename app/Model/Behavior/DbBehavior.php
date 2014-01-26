<?php
interface DbBehavior {
	
	/**
	 * 当发生错误时
	 * 
	 * @param Model $model        	
	 */
	public function occurError(Model $model);
}