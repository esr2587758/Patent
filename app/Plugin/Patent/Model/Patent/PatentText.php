<?php

/**
 * 获取专利文本信息
 * 
 * @author jqj
 *        
 */
class PatentText extends PatentAppModel {

	public $name = 'PatentText';

	public $useDbConfig = FALSE;

	public $useTable = FALSE;

	/**
	 * 每次从mongodb拿取的专利最大条数
	 */
	private $pageset = 50;

	private $patent_models;

	private $fieldMapping = false;

	/**
	 *
	 * @param array $pns        	
	 * @param array $field        	
	 */
	function getPatentInfoByPn(array $pns, array $fields) {
		$patents = array();
		if (count($pns) >= $this->pageset) {
			$pns = array_chunk($pns, 50);
		} else {
			$pns = array(
				$pns
			);
		}
		foreach ($pns as $list) {
			$conditions = array(
				'pn' => array(
					'$in' => $list
				)
			);
			$currentPatents = $this->_findPatents($conditions, $fields);
			if ($currentPatents !== false) {
				$patents = array_merge($patents, $currentPatents);
			}
		}
		return $patents;
	}

	/**
	 * 根据给定条件获取专利
	 *
	 * @param array $conditions        	
	 * @param unknown $fields        	
	 */
	private function _findPatents(array $conditions, $fields, $limit = null) {
		$options = array(
			'conditions' => $conditions
		);
		if (! empty($limit) && is_numeric($limit)) {
			$options['limit'] = $limit;
		}
		if (! in_array('PN', $fields)) {
			$fields[] = 'PN';
		}
		$patentModels = $this->getModelFields($fields);
		$basicModel = ClassRegistry::init('mongoPatentBasic');
		$options['fields'] = $patentModels['mongoPatentBasic'];
		unset($patentModels['mongoPatentBasic']);
		$patents = $basicModel->getPatentBasic($options);
		if (empty($patents)) {
			return false;
		}
		$patents = Hash::combine($patents, '{n}.mongoPatentBasic.id', '{n}');
		if (! empty($patentModels)) {
			$poids = array_keys($patents);
			foreach ($patentModels as $modelName => $modelFields) {
				$currentModel = ClassRegistry::init($modelName);
				$currentPatentInfo = $currentModel->getInfos($poids, $modelFields);
				if (! empty($currentPatentInfo)) {
					$patents = Hash::merge($patents, $currentPatentInfo);
				}
			}
		}
		$patents = $this->_formatPatents($patents, $fields);
		return $patents;
	}

	/**
	 * 将mongoDb的专利数据格式化成php用的标准字段
	 *
	 * @param array $patents        	
	 */
	private function _formatPatents(array $patents, array $fields) {
		$tmpPatents = array();
		foreach ($patents as $patent) {
			$patent = $this->_formatPatent($patent, $fields);
			$tmpPatents[$patent['PN']] = $patent;
		}
		return $tmpPatents;
	}

	/**
	 * 格式化单个专利的字段
	 *
	 * @param array $patent        	
	 */
	private function _formatPatent(array $patent, array $fields) {
		$formatFields = array();
		foreach ($this->fieldMapping as $standardField => $mongoKey) {
			if (! in_array($standardField, $fields)) {
				continue;
			}
			$formatFields[$standardField] = Hash::get($patent, $mongoKey);
		}
		return $formatFields;
	}

	private function getModelFields($fields) {
		$mongoFields = $this->syntaxField($fields);
		$patentModels = array();
		foreach ($mongoFields as $field) {
			$explodField = explode('.', $field);
			$modelName = array_shift($explodField);
			$field = implode('.', $explodField);
			if (! isset($patentModels[$modelName])) {
				$patentModels[$modelName] = array();
			}
			$patentModels[$modelName][] = $field;
		}
		$modelNames = array_keys($patentModels);
		$hasBasic = array_search('mongoPatentBasic', $modelNames);
		if ($hasBasic !== false) {
			unset($modelNames[$hasBasic]);
		}
		if (count($modelNames) > 0) {
			if ($hasBasic !== false) {
				$patentModels['mongoPatentBasic'][] = 'id';
			} else {
				$patentModels['mongoPatentBasic'] = array(
					'id'
				);
			}
		}
		if (! in_array('pn', $patentModels['mongoPatentBasic'])) {
			$patentModels['mongoPatentBasic'][] = 'pn';
		}
		return $patentModels;
	}

	/**
	 * 将标准字段切换为mongo字段
	 *
	 * @param array $fields        	
	 */
	function syntaxField(array $standardFields) {
		$mappingMongoFields = array();
		foreach ($this->mongostuct as $mongoField => $standardField) {
			if (is_string($standardField)) {
				if (in_array($standardField, $standardFields)) {
					$mappingMongoFields[] = $mongoField;
				}
				if (! isset($this->fieldMapping[$standardField])) {
					$this->fieldMapping[$standardField] = $mongoField;
				}
			} elseif (is_array($standardField)) {
				$intersect = array_intersect($standardFields, $standardField);
				if ($intersect) {
					$mappingMongoFields[] = $mongoField;
					foreach ($intersect as $standardField) {
						if (! isset($this->fieldMapping[$standardField])) {
							$this->fieldMapping[$standardField] = $mongoField;
						}
					}
				}
			}
		}
		return $mappingMongoFields;
	}

	/**
	 * 标准字段与mongo字段的相比对。
	 * key => mongo字段
	 * value => 标准字段
	 * 如果标准字段在下面的数组里没有，则标准字段与mongo中的字段忽略大小写后值相同
	 *
	 * 如果一个标准字段由mongo的多个字段计算得来，把最终存储的字段写在前面，例如下面的personAn.original.name写在personAn.original.lang前面
	 */
	private $mongostuct = array(
		'mongoPatentBasic.pn' => 'PN',
		'mongoPatentBasic.type' => 'DOCUMENT_TYPE',
		'mongoPatentBasic.family.original' => 'FAMILY_ID',
		'mongoPatentBasic.family.INPADOC' => 'INPADOC_FAMILY_ID',
		'mongoPatentBasic.apdt' => 'APD',
		'mongoPatentBasic.pbdt' => 'PBDT',
		'mongoPatentBasic.apno.original.number' => 'APNO',
		'mongoPatentBasic.class.ipc.code.full' => 'IPC',
		'mongoPatentBasic.class.ipcr.code.full' => 'IPCR',
		'mongoPatentBasic.class.upc.code.full' => 'CCL',
		'mongoPatentBasic.class.loc.code.full' => 'LOC',
		'mongoPatentBasic.class.ecla.code.full' => 'ECLA',
		'mongoPatentBasic.class.fi.code.full' => 'FI',
		'mongoPatentBasic.class.fterm.code.full' => 'FTERM',
		'mongoPatentBasic.personAn.original.name' => 'AN',
		'mongoPatentBasic.personAn.original.nname' => 'AN_ST',
		'mongoPatentBasic.personAn.original.address.address' => 'AN_ADD',
		'mongoPatentBasic.personAn.docdb.name' => 'AN_DOCDB',
		'mongoPatentBasic.personAn.original.lang' => array(
			'AN',
			'AN_ST',
			'AN_ADD'
		),
		'mongoPatentBasic.personIn.original.name' => 'IN',
		'mongoPatentBasic.personIn.original.nname' => 'IN_ST',
		'mongoPatentBasic.personIn.original.address.address' => 'IN_ADD',
		'mongoPatentBasic.personIn.docdb.name' => 'IN_DOCDB',
		'mongoPatentBasic.personIn.original.lang' => array(
			'IN',
			'IN_ST',
			'IN_ADD'
		),
		'mongoPatentBasic.personAt.original.name' => 'AT',
		'mongoPatentBasic.personAt.original.lang' => 'AT',
		'mongoPatentBasic.personAtc.original.name' => 'ATC',
		'mongoPatentBasic.personAtc.original.lang' => 'ATC',
		'mongoPatentBasic.personPe.original.name' => 'PE',
		'mongoPatentBasic.personPe.original.lang' => 'PE',
		'mongoPatentBasic.personAe.original.name' => 'AE',
		'mongoPatentBasic.prio.number' => 'PRIORITY_NUM',
		'mongoPatentBasic.prio.country' => 'PRIORITY_COUNTRY',
		'mongoPatentBasic.prio.date' => 'PRIORITY_DATE',
		'mongoPatentBasic.prio' => 'PRIORITY',
		'mongoPatentBasic.legal' => 'LEGAL',
		'mongoPatentBasic.citation.pn' => 'CITE',
		'mongoPatentTitle.text' => 'TTL',
		'mongoPatentDesc.text' => 'DESC',
		'mongoPatentAbst.text' => 'ABST',
		'mongoPatentClaim.text' => 'CLMS',
		'mongoPatentRefer.text' => 'OREF'
	);
}

/**
 * 专利基本信息
 *
 * @author jqj
 *        
 */
class mongoPatentBasic extends AppModel {

	public $name = 'MongoPatentBasic';

	public $useDbConfig = 'patentText';

	public $primaryKey = 'id';

	public $useTable = 'patent';

	/**
	 * 该模型使用的数据库名
	 * 目前仅支持mongo
	 */
	public $databaseType = 'mongo';

	public $mongoSchema = array(
		'id' => array(
			'type' => 'objectId',
			'key' => 'primary'
		),
		'pn' => array(
			'type' => 'string'
		),
		'apno' => array(
			'type' => 'string'
		),
		'kd' => array(
			'type' => 'string'
		),
		'pbdt' => array(
			'type' => 'date'
		),
		'apdt' => array(
			'type' => 'date'
		),
		'family' => array(
			'type' => 'array'
		),
		'type' => array(
			'type' => 'array'
		),
		'class' => array(
			'type' => 'array'
		),
		'personAn' => array(
			'type' => 'array'
		),
		'personIn' => array(
			'type' => 'array'
		),
		'personPe' => array(
			'type' => 'array'
		),
		'personAe' => array(
			'type' => 'array'
		),
		'personAt' => array(
			'type' => 'array'
		),
		'personAtc' => array(
			'type' => 'array'
		),
		'personEcla' => array(
			'type' => 'array'
		),
		'prio' => array(
			'type' => 'array'
		),
		'prio.date' => array(
			'type' => 'date'
		),
		'citation' => array(
			'type' => 'array'
		),
		'legal' => array(
			'type' => 'array'
		)
	);

	/**
	 * 获取专利基本文本信息
	 */
	function getPatentBasic($options) {
		$results = $this->find('all', $options);
		return $results;
	}

	function afterFind($results, $primary = false) {
		$results = $this->format($results);
		return $results;
	}

	/**
	 * 对专利信息做初步格式化，
	 *
	 * 将文本格式的数组转换为适当分组后的信息
	 *
	 * @param array $results        	
	 */
	function format($results) {
		$tmpResult = array();
		foreach ($results as $i => $result) {
			$result = $result[$this->alias];
			foreach ($result as $field => $value) {
				if (strpos($field, 'person') === 0) {
					$tmpResult[$field] = $this->_person($value);
				} elseif ($field == 'class') {
					$classes = $this->_class($value);
					$tmpResult[$field] = $classes;
				} elseif ($field == 'citation') {
					$tmpResult[$field] = array(
						'pn' => array_keys(Hash::combine($value, '{n}.pn'))
					);
				} elseif ($field == 'prio') {
					foreach ($value as $prio) {
						$tmpResult[$field]['number'][] = $prio['number'];
						$tmpResult[$field]['country'][] = $prio['country'];
						$tmpResult[$field]['date'][] = $prio['date'];
					}
				} else {
					$schema = $this->schema();
					if (! empty($schema[$field]) && $schema[$field]['type'] === 'date') {
						$value = date(DATE_TYPE, $value);
					}
					$tmpResult[$field] = $value;
				}
			}
			$results[$i][$this->alias] = $tmpResult;
		}
		return $results;
	}

	/**
	 * 格式化人的信息
	 * 将同一语言的人放到一个数组里
	 * 将人与地址的信息放到不同的数组里，键值保持相同
	 */
	private function _person($persons) {
		$tmpPersons = array();
		if (! empty($persons['original'])) {
			$tmpPersons['original'] = array(
				'name' => array(),
				'nname' => array(),
				'address' => array()
			);
			foreach ($persons['original'] as $person) {
				$lang = 'UNKNOWN';
				if (! empty($person['lang'])) {
					$lang = $person['lang'];
				}
				if (isset($tmpPersons['original']['name'][$lang])) {
					$tmpPersons['original']['name'][$lang] = array();
					$tmpPersons['original']['nname'][$lang] = array();
					$tmpPersons['original']['address']['address'][$lang] = array();
				}
				if (! empty($person['name'])) {
					$tmpPersons['original']['name'][$lang][] = $person['name'];
				} else {
					$tmpPersons['original']['name'][$lang][] = '';
				}
				if (! empty($person['nname'])) {
					$tmpPersons['original']['nname'][$lang][] = $person['nname'];
				} else {
					$tmpPersons['original']['nname'][$lang][] = '';
				}
				if (! empty($person['address'])) {
					$tmpPersons['original']['address']['address'][$lang][] = $person['address']['address'];
				} else {
					$tmpPersons['original']['address']['address'][$lang][] = '';
				}
			}
		}
		if (! empty($persons['docdb'])) {
			$tmpPersons['docdb'] = array(
				'name' => array()
			);
			foreach ($persons['docdb'] as $person) {
				$name = '';
				if (! empty($person['name'])) {
					$name = $person['name'];
				}
				$tmpPersons['docdb']['name'][] = $name;
			}
		}
		return $tmpPersons;
	}

	/**
	 * 格式化class数据
	 * 
	 * @param array $classes        	
	 * @return multitype:
	 */
	private function _class($classes) {
		foreach ($classes as $field => $fieldValue) {
			$mergeDetail = array();
			foreach ($fieldValue as $i => $detail) {
				$arr = $this->_arrKeyFlip($detail);
				$mergeDetail = array_merge_recursive($mergeDetail, $arr);
			}
			$classes[$field] = $mergeDetail;
		}
		return $classes;
	}

	/**
	 * 将数组里最后一级类型变为数组
	 * 
	 * @param array $arr
	 *        	<code>
	 *        	<?php
	 *        	array(
	 *        	array(
	 *        	'ipc' => array(
	 *        	'code' => 123,
	 *        	'full' => 123/B1
	 *        	)
	 *        	),
	 *        	array(
	 *        	'ipc' => array(
	 *        	'code' => 456,
	 *        	'full' => 455/B1
	 *        	)
	 *        	)
	 *        	)
	 *        	?>
	 *        	</code>
	 * @return array <code>
	 *         <?php
	 *         array(
	 *         'ipc' => array(
	 *         'code' => array(
	 *         '123',
	 *         '456'
	 *         ),
	 *         'full' => array(
	 *         '123/B1',
	 *         '456/B1'
	 *         )
	 *         )
	 *         )
	 *         ?>
	 *         </code>
	 */
	private function _arrKeyFlip($arr) {
		foreach ($arr as $key => $value) {
			if (! empty($value) && is_array($value)) {
				$value = $this->_arrKeyFlip($value);
			} else {
				$value = (array) $value;
			}
			$arr[$key] = $value;
		}
		return $arr;
	}
}

/**
 * 除专利基本信息外的专利表
 *
 * @author jqj
 *        
 */
class mongoPatentMoreInfo extends AppModel {

	public $useDbConfig = 'patentText';

	/**
	 * 该模型使用的数据库名
	 * 目前仅支持mongo
	 */
	public $databaseType = 'mongo';

	/**
	 * 数据库schema
	 *
	 * @var array
	 */
	public $mongoSchema = array(
		'id' => array(
			'type' => 'objectId',
			'key' => 'primary'
		),
		'poid' => array(
			'type' => 'objectId'
		),
		'text' => array(
			'type' => 'string'
		),
		'lang' => array(
			'type' => 'string'
		)
	);

	/**
	 * 根据专利关联id获取专利信息
	 *
	 * @param array $prod        	
	 * @param unknown $fields        	
	 */
	public function getInfos(array $poids, $fields) {
		if (! in_array('poid', $fields)) {
			$fields[] = 'poid';
		}
		if (! in_array('lang', $fields)) {
			$fields[] = 'lang';
		}
		$patents = $this->find('all', array(
			'conditions' => array(
				'poid' => array(
					'$in' => $poids
				)
			),
			'fields' => $fields
		));
		if (empty($patents)) {
			return false;
		}
		$tmpPatents = array();
		foreach ($patents as $patent) {
			$patent = $patent[$this->name];
			$poid = $patent['poid'];
			$lang = $patent['lang'];
			$text = $patent['text'];
			$tmpPatents[$poid][$this->alias]['text'][$lang] = $text;
		}
		return $tmpPatents;
	}
}

/**
 * 专利标题
 *
 * @author jqj
 *        
 */
class mongoPatentTitle extends mongoPatentMoreInfo {

	public $name = 'mongoPatentTitle';

	public $useTable = 'title';
}

/**
 * 专利摘要
 *
 * @author jqj
 *        
 */
class mongoPatentAbst extends mongoPatentMoreInfo {

	public $name = 'mongoPatentAbst';

	public $useTable = 'abstraction';

	public $keyField = 'abst';
}

/**
 * 专利引用文本信息
 *
 * @author jqj
 *        
 */
class mongoPatentRefer extends mongoPatentMoreInfo {

	public $name = 'mongoPatentRefer';

	public $useTable = 'reference';

	public $keyField = 'reference';
}

/**
 * 专利描述
 *
 * @author jqj
 *        
 */
class mongoPatentDesc extends mongoPatentMoreInfo {

	public $name = 'mongoPatentDesc';

	public $useDbConfig = 'patentText2';

	public $useTable = 'description';
}

/**
 * 专利claim信息
 *
 * @author jqj
 *        
 */
class mongoPatentClaim extends mongoPatentMoreInfo {

	public $name = 'mongoPatentClaim';

	public $useDbConfig = 'patentText2';

	public $useTable = 'description';
}
?>