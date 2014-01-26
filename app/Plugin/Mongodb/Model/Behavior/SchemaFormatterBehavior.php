<?php

class SchemaFormatterBehavior extends ModelBehavior {

	private $model;

	private $mongoType = array(
		'objectId',
		'date',
		'code',
		'string',
		'integer',
		'int',
		'boolean',
		'array',
		'float',
		'double'
	);

	public function beforeFind(Model $model, $query) {
		$this->model = $model;
		if (! empty($query['conditions'])) {
			$schema = $this->_schema($model);
			$query['conditions'] = $this->_formatConditions($query['conditions'], $schema);
		}
		return $query;
	}

	public function afterFind(Model $model, $results, $primary = false) {
		$this->model = $model;
		if (isset($results[0])) {
			foreach ($results as $i => $data) {
				if (isset($data[$model->alias])) {
					$results[$i][$model->alias] = $this->_unFormatDatas($data[$model->alias]);
				}
			}
		} elseif (isset($results[$model->alias])) {
			$results[$model->alias] = $this->_unFormatDatas($results[$model->alias], $schema);
		}
		return $results;
	}

	public function beforeSave(Model $model, $options = array()) {
		$this->model = $model;
		if (isset($model->data[$model->alias])) {
			$schema = $this->_schema($model);
			$data = $this->_formatData($model->data[$model->alias], $schema);
			if ($data === false) {
				return false;
			}
			$model->data[$model->alias] = $data;
		}
		return false;
	}

	/**
	 *
	 * @param array $datas        	
	 */
	private function _unFormatDatas(array $data, $deep = 0) {
		foreach ($data as $field => $value) {
			$field_deep = $deep;
			if (! empty($value)) {
				if (is_object($value)) {
					switch (get_class($value)) {
						case 'MongoDate':
							$value = $value->sec;
							break;
						case 'MongoId':
						case 'MongoCode':
							$value = $value->__toString();
							break;
						default:
							debug(get_class($value));
							break;
					}
				} elseif (is_array($value)) {
					$field_deep ++;
					$value = $this->_unFormatDatas($value, $field_deep);
				}
			}
			if ($field === '_id') {
				unset($data['_id']);
				$data[$this->model->primaryKey] = $value;
			} else {
				if ($value !== $data[$field]) {
					$data[$field] = $value;
				}
			}
		}
		return $data;
	}

	/**
	 * 格式化搜索条件
	 *
	 * @param array $conditions
	 *        	查询条件
	 * @param array $schema
	 *        	表的字段类型
	 * @param array $formatter
	 *        	单个字段的类型
	 * @return array
	 */
	private function _formatConditions(array $conditions, array $schema, array $formatter = array()) {
		foreach ($conditions as $key => $condition) {
			// 如果key值为
			if (isset($schema[$key])) {
				// 获取field的字段类型
				$formatter = array(
					'type' => 'string',
					'length' => 200
				);
				if (! empty($schema[$key])) {
					$formatter = $schema[$key];
				}
				// 如果当前条件是主键，则将key值换为'_id'
				if ($key == $this->model->primaryKey) {
					$key = '_id';
					unset($conditions[$this->model->primaryKey]);
				}
				if (is_array($condition)) {
					$conditions[$key] = $this->_formatConditions($condition, $schema, $formatter);
				} else {
					$conditions[$key] = $this->_formatValue($condition, $formatter);
				}
			} else {
				if (is_array($condition)) {
					$conditions[$key] = $this->_formatConditions($condition, $schema, $formatter);
				} else {
					$conditions[$key] = $this->_formatValue($condition, $formatter);
				}
			}
		}
		return $conditions;
	}

	/**
	 * 格式化数据
	 *
	 * 根据model的schema将数据转变为指定类型
	 *
	 * @param array $datas        	
	 */
	private function _formatData(array $data, array $schema) {
		$this->model->validationErrors = array();
		foreach ($schema as $field => $formatter) {
			$value = Hash::get($data, $field);
			if ($value === null) {
				// 时间类型的数据自动填充
				if (in_array($field, array(
					'created',
					'modified',
					'updated'
				))) {
					if ($field == 'created') {
						if (! isset($data[$this->model->primaryKey]) && ! isset($data['_id'])) {
							$data[$field] = new MongoDate(time());
						}
					} else {
						$data[$field] = new MongoDate(time());
					}
				}
				//主键为空不做处理
				if ($field != $this->model->primaryKey) {
					//如果设置了默认值
					if (! empty($formatter['default'])) {
						$data[$field] = $formatter['default'];
					} else {
						$null = true;
						if (isset($formatter['null'])) {
							$null = $formatter['null'];
							//如果设置了数据不能为空，返回空
							if (! $null) {
								$this->model->validationErrors[$field] = 'Can not empty';
							}
						}
					}
				}
				continue;
			}
			$formatValue = $this->_formatValue($value, $formatter);
			if ($field == $this->model->primaryKey) {
				unset($data[$this->model->primaryKey]);
				$data['_id'] = $formatValue;
			} else {
				if ($formatValue !== $value) {
					$data = Hash::insert($data, $field,$formatValue);
				}
			}
		}
		if (! empty($this->model->validationErrors)) {
			return false;
		}
		return $data;
	}

	/**
	 * 将数据格式化为指定格式
	 *
	 * @param Ambigous $value        	
	 * @param string $type        	
	 * @return Ambigous $type
	 */
	private function _formatValue($value, $formatter) {
		switch ($formatter['type']) {
			case 'objectId':
				if (! is_a($value, 'MongoId')) {
					try {
						$tmpValue = new MongoId($value);
						$value = $tmpValue;
					} catch (MongoException $e) {
						trigger_error("($value)" . $e->getMessage());
					}
				}
				break;
			case 'date':
				if (! is_a($value, 'MongoDate')) {
					$value = new MongoDate($value);
				}
				break;
			case 'code':
				if (! is_a($value, MongoCode)) {
					$value = new MongoCode($value);
				}
				break;
			case 'string':
				if (! is_string($value)) {
					$value = (string) $value;
				}
				if (! isset($formatter['length']) || ! is_numeric($formatter['length'])) {
					$formatter['length'] = 200;
				}
				if (strlen($value) > $formatter['length']) {
					$value = substr($value, 0, $formatter['length']);
				}
				break;
			case 'text':
				if (! is_string($value)) {
					$value = (string) $value;
				}
				break;
			case 'int':
			case 'integer':
				if (is_int($value)) {
					$value = (int) $value;
				}
				break;
			case 'boolean':
				$value = (boolean) $value;
				break;
			case 'float':
				if (! is_float($value)) {
					$value = (float) $value;
				}
				break;
			case 'array':
				if (! is_array($value)) {
					$value = (array) $value;
				}
				break;
			default:
				break;
		}
		return $value;
	}

	/**
	 * 返回Model的schema
	 *
	 * @param Model $model        	
	 */
	private function _schema(Model $model) {
		if (empty($model->_schema)) {
			$model->schema();
		}
		return $model->_schema;
	}
}