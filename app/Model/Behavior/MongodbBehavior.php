<?php
App::uses('DbBehavior', 'Model/Behavior');

class MongodbBehavior extends ModelBehavior implements DbBehavior {

	/**
	 *
	 * @param Model $Model        	
	 * @param array $keys        	
	 * @param array $params        	
	 */
	public function distinct($Model, $key, $params = array()) {
		$result = $Model->getDataSource()->distinct($Model, $key, $params);
		foreach ($result as $i => $value) {
			$result[$i] = array(
				$Model->name => array(
					$key => $value
				)
			);
		}
		$event = new CakeEvent('Model.afterFind', $Model, array(
			$result,
			true
		));
		$event->modParams = 0;
		$Model->getEventManager()->dispatch($event);
		$result = $event->result;
		foreach ($result as $i => $value){
			$result[$i] = $value[$Model->name][$key];
		}
		return $result;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DbBehavior::occurError()
	 */
	function occurError(Model $model) {
		$error = $model->getDataSource()
			->getMongoDb()
			->lastError();
		if (! empty($error['err'])) {
			$model->setErrorCode($error['code']);
			$model->setErrorMsg($error['err']);
		}
	}
}