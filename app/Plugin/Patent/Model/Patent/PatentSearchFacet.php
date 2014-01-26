<?php
App::uses('SolrModel', 'Solr.Model');

/**
 * 搜索，过滤专利信息
 * 
 * @author jqj
 *        
 */
class PatentSearchFacet extends SolrModel {

	public $useDbConfig = 'patentSolr';

	public $useTable = 'EN_CN';

	/**
	 * solr的schema
	 * 用来判断条件是否合法
	 * 目前没有使用
	 */
	public $solrSchema = array(
		'PN' => array(
			'type' => 'string',
			'display' => true,
			'index' => true
		),
		'TTL' => array(
			'type' => 'string',
			'index' => true
		),
		'AN' => array(
			'type' => 'array',
			'index' => true
		),
		'IN' => array(
			'type' => 'array',
			'index' => true
		),
		'APD' => array(
			'type' => 'date',
			'index' => true
		),
		'APD_YEAR' => array(
			'type' => 'int',
			'index' => true
		),
		'DESC' => array(
			'type' => 'text',
			'index' => true
		)
	);

	public $language = false;

	/**
	 * 系统支持的有多语言的字段
	 * 
	 * @var array
	 */
	public $languageField = array();

	/**
	 *
	 * @param string $id        	
	 * @param string $table        	
	 * @param string $ds        	
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		$this->languageField = Configure::read('Patent.Language.Field');
		return parent::__construct($id, $table, $ds);
	}

	/**
	 * 设置专利语言
	 *
	 * @param string $language        	
	 * @return boolean
	 */
	public function setLanguage(array $language) {
		$this->language = $language;
		$this->_checkHander();
	}

	/**
	 * 选择solr是用何种hander
	 * enum(select,EN_CN,EN...)
	 */
	private function _checkHander() {
		$this->useTable = 'EN_CN';
	}

	public function beforeFind($query) {
		if (! empty($query['conditions'])) {
			$query['conditions'] = $this->syntaxQuery($query['conditions']);
		}
		
		if (! empty($query['filter'])) {
			$query['filter'] = $this->syntaxQuery($query['filter']);
		}
		if (! empty($query['order'])) {
			$query['order'] = $this->php2solrKey($query['order']);
		} else {
			if (! empty($this->order)) {
				$this->order = $this->php2solrKey($this->order);
			}
		}
		$options['fields'] = Configure::read('Patent.Solr.FIELD.PNDisplay');
		if (! empty($query['facet'])) {
			foreach ($query['facet'] as $facetType => $facet) {
				if ($facetType == 'pivot') {
					foreach ($facet['field'] as $i => $fields) {
						$query['facet'][$facetType]['field'][$i] = implode(',', $this->php2solrValue(explode(',', $fields)));
					}
				} elseif (in_array($facetType, array(
					'field',
					'date'
				))) {
					$query['facet'][$facetType]['field'] = $this->php2solrValue($query['facet'][$facetType]['field']);
				}
				if (! empty($query['facet'][$facetType])) {
					if (! empty($query['facet'][$facetType]['limit'])) {
						$query['facet'][$facetType]['limit'] = $this->php2solrKey($query['facet'][$facetType]['limit']);
					}
				}
			}
		}
		if (! empty($query['fields'])) {
			$query['fields'] = $this->php2solrValue($query['fields']);
		}
		return $query;
	}

	/**
	 * 处理从solr返回的facet内容
	 *
	 * @param $results $this->facet方法返回的内容        	
	 */
	function afterFacet($results, $primary = true) {
		if ($results) {
			foreach ($results as $facetType => $facetValue) {
				if (stripos($facetType, 'facet_') === 0 && ! empty($facetValue)) {
					unset($results[$facetType]);
					if ($facetType !== 'facet_pivot') {
						foreach ($facetValue as $field => $details) {
							$tmpField = $this->solr2phpValue((array) $field);
							$tmpField = $tmpField[0];
							if ($facetType == 'facet_dates') {
								unset($facetValue[$field]);
								$tmpDetails = array();
								foreach ($details as $date => $num) {
									$date = date(DATE_TYPE, strtotime($date));
									$tmpDetails[$date] = $num;
								}
								$details = $tmpDetails;
							} else {
								if ($tmpField != $field) {
									unset($facetValue[$field]);
								}
							}
							$facetValue[$tmpField] = $details;
						}
						$facetType = 'oneDimension';
					} else {
						foreach ($facetValue as $fields => $details) {
							unset($facetValue[$fields]);
							$fields = implode(',', $this->solr2phpValue(explode(',', $fields)));
							$facetValue[$fields] = $details;
						}
						$facetType = 'multiDimension';
					}
					if (! isset($results[$facetType])) {
						$results[$facetType] = array();
					}
					$results[$facetType] = array_merge($results[$facetType], $facetValue);
				}
			}
		}
		return $results;
	}

	/**
	 * 处理查询条件
	 *
	 * @param array $conditions        	
	 * @return Ambigous <boolean, multitype:boolean >
	 */
	public function syntaxQuery($query, $language = false) {
		if (is_string($query)) {
			//将字符串类型的条件分解成数组
			$query = ExplodeQuery::explode($query);
		}
		$this->log($query, LOG_DEBUG);
		$query = $this->addLanguageToQuery($query, $language);
		$query = $this->php2solrKey($query);
		$this->log($query, LOG_DEBUG);
		return $query;
	}

	/**
	 * 将query内容加上语言控制
	 *
	 * @param array $query        	
	 */
	function addLanguageToQuery(array $query, $language = false) {
		if ($language === false) {
			$language = $this->language;
		}
		if ($this->language === false) {
			return $query;
		}
		return $this->_addLanguageToQuery($query, $language);
	}

	/**
	 * 将query内容加上语言控制
	 *
	 * @param array $query        	
	 * @param array $language        	
	 */
	private function _addLanguageToQuery(array $query, $language) {
		$languageQuery = array();
		foreach ($query as $key => $value) {
			if (! is_numeric($key) && in_array($key, $this->languageField)) {
				$tmpFieldQuery = array();
				foreach ($language as $i => $lang) {
					$tmpFieldQuery["{$key}_{$lang}"] = $value;
				}
				if (empty($languageQuery)) {
					$languageQuery['OR'] = $tmpFieldQuery;
				} else {
					$languageQuery[]['OR'] = $tmpFieldQuery;
				}
			} else {
				if (is_array($value)) {
					$value = $this->_addLanguageToQuery($value, $language);
				}
				$languageQuery[$key] = $value;
			}
		}
		return $languageQuery;
	}

	/**
	 * 将数组中key的值由php字段换为solr字段
	 *
	 * @param unknown $data        	
	 * @return boolean multitype:Ambigous multitype:boolean >
	 */
	private function php2solrKey($data) {
		if (empty($data) || is_string($data)) {
			trigger_error("Error format for data");
			return false;
		}
		$processedData = array();
		foreach ($data as $key => $value) {
			$key = SolrFieldMapping::php2solr($key);
			if (! empty($value) && is_array($value)) {
				$value = $this->php2solrKey($value);
			}
			$processedData[$key] = $value;
		}
		return $processedData;
	}

	/**
	 * 将数组中value的值由php字段换为solr字段
	 * 
	 * @param array $data        	
	 * @return array
	 */
	private function php2solrValue($data) {
		if (empty($data) || is_string($data)) {
			trigger_error("Error format for data");
			return false;
		}
		foreach ($data as $key => $value) {
			if (! empty($value)) {
				if (is_array($value)) {
					$value = $this->php2solrValue($value);
				} else {
					$value = SolrFieldMapping::php2solr($value);
				}
				$data[$key] = $value;
			}
		}
		return $data;
	}

	/**
	 * 将数组中key的值由solr字段换为php字段
	 *
	 * @param unknown $data        	
	 * @return boolean multitype:Ambigous multitype:boolean >
	 */
	private function solr2phpKey($data) {
		if (empty($data) || is_string($data)) {
			trigger_error("Error format for data");
			return false;
		}
		$processedData = array();
		foreach ($data as $key => $value) {
			$key = SolrFieldMapping::solr2php($key);
			if (! empty($value) && is_array($value)) {
				$value = $this->solr2phpKey($value);
			}
			$processedData[$key] = $value;
		}
		return $processedData;
	}

	/**
	 * 将数组中value的值由solr字段换为php字段
	 * 
	 * @param array $data        	
	 * @return array
	 */
	private function solr2phpValue($data) {
		if (empty($data) || is_string($data)) {
			trigger_error("Error format for data");
			return false;
		}
		foreach ($data as $key => $value) {
			if (! empty($value)) {
				if (is_array($value)) {
					$value = $this->solr2phpValue($value);
				} else {
					$value = SolrFieldMapping::solr2php($value);
				}
				$data[$key] = $value;
			}
		}
		return $data;
	}

	/**
	 * 判断输入的组合条件是否合法
	 * 
	 * @param array $types        	
	 * @example plugins/solr/Config/bootstrap.php
	 */
	protected function judgeTypeGroup($types) {
		if (parent::judgeTypeGroup($types)) {
			$invalid = false;
			if (((int) in_array(SEARCH_ALL, $types) + (int) in_array(SEARCH_GROUP, $types) + (int) in_array(SEARCH_REPR, $types)) > 0 && ((int) in_array(FACET_MULTI_DIMENSIONAL, $types) + (int) in_array(FACET_MULTI_DIMENSIONAL_REPR, $types)) > 0) {
				$invalid = 2;
			}
			if ($invalid) {
				trigger_error("query组合($invalid)不合法");
				$this->setErrorCode(ERROR_NOT_FOUND);
				$this->setErrorMsg(__('Group not found.'));
				return false;
			}
		} else {
			return false;
		}
		return true;
	}
}

/**
 * solr字段与项目标准字段相互转换
 *
 * @author jqj
 *        
 */
class SolrFieldMapping {

	/**
	 * SOLR与php字段的匹配
	 * key值为php的字段，value为solr的字段
	 *
	 * @var array
	 */
	static $mapping = false;

	/**
	 * 初始化
	 *
	 * @return boolean
	 */
	static private function _init() {
		if (self::$mapping === false) {
			//对solr和php标准字段之间赋值
			self::$mapping = Configure::read('Patent.Solr.Field.Mapping');
		}
		return true;
	}

	/**
	 * 将php的标准字段转为solr字段
	 *
	 * @param string $field
	 *        	转换数组key的内容还是value的内容
	 */
	static function php2solr($field) {
		self::_init();
		if (self::$mapping === false) {
			return $field;
		}
		if (array_key_exists((string) $field, self::$mapping)) {
			$field = self::$mapping[$field];
		}
		return $field;
	}

	/**
	 * 将solr的字段转换为php的字段
	 *
	 * @param string $field        	
	 */
	static function solr2php($field) {
		self::_init();
		if (self::$mapping === false) {
			return $field;
		}
		$phpField = array_search($field, self::$mapping);
		if ($phpField !== false) {
			$field = $phpField;
		}
		return $field;
	}
}

/**
 * 分解query，将字符串类型的字段分解为数组类型
 * 数组的格式与cake Model的conditions结构相同
 *
 * @author jqj
 *        
 */
class ExplodeQuery {

	private static $tag = '|-----------|';

	public static function explode($query) {
		//清洁字符串
		$query = self::_cleanQuery($query);
		return self::_query2Array($query);
	}

	/**
	 * 将字符串类型的条件转换为数组格式
	 * 
	 * @param string $query        	
	 */
	private static function _query2Array($query) {
		//将字符串以AND,OR,NOT截取
		$queryArr = array();
		$query = trim($query);
		//被处理的字符串
		$dealedQuery = $query;
		
		//括号里的内容
		$childQueries = array();
		$deep = 0;
		//获取第一对括号的左右字符位置
		list ($lft, $rht) = self::_getLR($dealedQuery);
		//以第一层括号把query拆分成数组,并把括号及其包裹的内容用$tag替代
		while ($lft !== false && $deep < 1000) {
			if ($rht === false) {
				return false;
			}
			$childQueries[] = substr($dealedQuery, $lft + 1, $rht - $lft - 1);
			$dealedQuery = substr_replace($dealedQuery, self::$tag, $lft, $rht - $lft + 1);
			// debug(compact('query', 'tmp', 'lft', 'rft'));
			list ($lft, $rht) = self::_getLR($dealedQuery);
			$deep ++;
		}
		// debug(compact('dealedQuery','childQueries'));
		$hasOperator = self::_hasOperator($dealedQuery);
		//如果没有操作符
		if (! $hasOperator) {
			if (empty($childQueries)) {
				return array(
					$dealedQuery
				);
			}
			$childOrder = 0;
			return self::_explodeSimpleQuery($dealedQuery, $childQueries, $childOrder);
		}
		$justOneOperator = self::_justOneOperator($dealedQuery);
		//如果字符串中只有一个操作符,直接export
		if ($justOneOperator) {
			$dealQueries = explode(" $justOneOperator ", $dealedQuery);
			$childOrder = 0;
			$processedQueries = array();
			foreach ($dealQueries as $i => $dealedQuery) {
				$currentQuery = self::_explodeSimpleQuery($dealedQuery, $childQueries, $childOrder);
				$processedQueries[] = $currentQuery;
			}
			return array(
				$justOneOperator => $processedQueries
			);
		} else {
			//数组维数
			$deep = 0;
			//操作符的序列
			//括号顺序的序列
			$childOrder = 0;
			//操作符的个数，因为php同一个键值只可以出现一次
			$operatorCount = array(
				'AND' => 0,
				'OR' => 0,
				'NOT' => 0
			);
			//当前分隔符
			$operator = false;
			//下一个分隔符
			$nextOperator = false;
			//将字符串以AND,OR,NOT分组
			do {
				//当前子节点的内容转为数组存放
				$currentQuery = array();
				//第一次出现的操作符号的位置
				$firstPos = array();
				$firstOperatorPos = $nextOperator = false;
				list ($dealedQuery, $nextOperator, $nextDealedQuery) = self::_explodeByFisrstOperator($dealedQuery, $childQueries, $childOrder);
				// debug(compact('dealedQuery', 'nextDealedQuery'));
				//如果字符串的内容为带“括号”的内容，则递归处理
				$currentQuery = self::_explodeSimpleQuery($dealedQuery, $childQueries, $childOrder);
				// debug(compact('currentQuery'));
				//debug(compact('operator','currentQuery'));
				//如果没有操作符，则直接做为数组的第一个元素
				if ($operator === false) {
					$queryArr[] = $currentQuery;
				} else {
					$key = $operator;
					//如果该$operator在数组中已经存在，则在其后加一个空格，以免值被覆盖
					if ($operatorCount[$operator] > 0) {
						for ($operatorI = 0; $operatorI <= $operatorCount[$operator]; $operatorI ++) {
							$key .= ' ';
						}
					}
					$operatorCount[$operator] ++;
					$queryArr[$key] = $currentQuery;
				}
				$dealedQuery = $nextDealedQuery;
				$operator = $nextOperator;
				if ($dealedQuery === self::$tag) {
					$key = $operator;
					//如果该$operator在数组中已经存在，则在其后加一个空格，以免值被覆盖
					if ($operatorCount[$operator] > 0) {
						for ($operatorI = 0; $operatorI <= $operatorCount[$operator]; $operatorI ++) {
							$key .= ' ';
						}
					}
					$operatorCount[$operator] ++;
					$queryArr[$key] = self::_query2Array($childQueries[$childOrder]);
					break;
				}
				//当没有数组或数组维数超过100时，不处理理，以防止用户输入非法的query造成死循环
			} while ($dealedQuery && $deep < 20);
		}
		
		return $queryArr;
	}

	/**
	 * 处理一个单独的字符串，该字符串中没有括号没有操作符
	 */
	private static function _explodeSimpleQuery($simpleQuery, $childQueries, &$order) {
		$currentQuery = '';
		//如果字符串的内容为带“括号”的内容，则递归处理
		if ($simpleQuery === self::$tag) {
			$currentQuery = self::_query2Array($childQueries[$order]);
			$order ++;
		} else {
			//如果左边的内容为一个字段的内容
			if (strpos($simpleQuery, ':') !== false) {
				$arr_tmp = explode(':', $simpleQuery);
				if ((count($arr_tmp) == 2)) {
					$arr_value = explode(':', $simpleQuery);
					$field = strtoupper(trim($arr_value[0]));
					$fieldValue = $arr_value[1];
					//如果值被括号包裹,将该值的结构继续处理
					if ($fieldValue === self::$tag) {
						$fieldValue = $childQueries[$order];
						$order ++;
					}
					$currentQuery[$field] = $fieldValue;
				}
			} else {
				//如果左侧内容直接为一个字符串
				$currentQuery = $simpleQuery;
			}
		}
		return $currentQuery;
	}

	private static function _hasOperator($string) {
		$and = (int) (stripos($string, ' AND ') !== false);
		$or = (int) (stripos($string, ' OR ') !== false);
		$not = (int) (stripos($string, ' NOT ') !== false);
		$kindNum = $and + $or + $not;
		return (bool) $kindNum;
	}

	/**
	 * 判断是否只有一种操作符，如果是则返回操作符
	 */
	private static function _justOneOperator($string) {
		$and = (int) (stripos($string, ' AND ') !== false);
		$or = (int) (stripos($string, ' OR ') !== false);
		$not = (int) (stripos($string, ' NOT ') !== false);
		$kindNum = $and + $or + $not;
		if ($kindNum === 1) {
			if ($and) {
				return 'AND';
			} elseif ($or) {
				return 'OR';
			} else {
				return 'NOT';
			}
		}
		return false;
	}

	private static function _explodeByFisrstOperator($dealedQuery) {
		$operator = $firstPos = false;
		list ($firstPos, $operator) = self::_firstOperator($dealedQuery);
		$nextDealedQuery = substr($dealedQuery, $firstPos + 2 + strlen($operator));
		$dealedQuery = substr($dealedQuery, 0, $firstPos);
		return array(
			$dealedQuery,
			$operator,
			$nextDealedQuery
		);
	}

	/**
	 * 计算第一个操作符的位置以及操作符的内容
	 */
	private static function _firstOperator($dealedQuery) {
		//第一次出现的操作符号的位置
		$firstPos = array();
		$firstAndPos = $firstOrPos = $firstNotPos = false;
		//第一个操作符
		//出现第一个“ AND ”的位置
		$firstAndPos = stripos($dealedQuery, ' AND ');
		//出现第一个“ OR ”的位置
		$firstOrPos = stripos($dealedQuery, ' OR ');
		//出现第一个“ NOT ”的位置
		$firstNotPos = stripos($dealedQuery, ' NOT ');
		
		if ($firstAndPos !== false) {
			$firstPos['AND'] = $firstAndPos;
		}
		if ($firstOrPos !== false) {
			$firstPos['OR'] = $firstOrPos;
		}
		if ($firstNotPos !== false) {
			$firstPos['NOT'] = $firstNotPos;
		}
		//第一个操作符出现的位置
		$firstOperatorPos = $operator = false;
		if (! empty($firstPos)) {
			$firstOperatorPos = min($firstPos);
			$operator = array_search($firstOperatorPos, $firstPos);
		}
		// debug(compact('dealedQuery','firstOperatorPos','operator'));
		return array(
			$firstOperatorPos,
			$operator
		);
	}

	/**
	 * 过滤字符中的无用字符
	 *
	 * @param unknown $query        	
	 * @return mixed
	 */
	private static function _cleanQuery($query) {
		//FIXME 去除连续的空格
		$replace_arr = array(
			")and(" => ") AND (",
			")and " => ") AND ",
			" and(" => " AND (",
			")or(" => ") OR (",
			" or(" => " OR (",
			")not(" => ") NOT (",
			")not " => ") NOT ",
			" not(" => " NOT (",
			//中文标点替代为英文标点
			'“' => '"',
			'”' => '"',
			'［' => '[',
			'【' => '[',
			'］' => ']',
			'】' => ']',
			'‘' => '\'',
			'’' => '\'',
			//"（" => "(",
			//"）" => ")",
			"：" => ":",
			'＆' => '&'
		);
		$query = str_ireplace(array_keys($replace_arr), array_values($replace_arr), trim($query));
		//去除(:)与(双引号,左括号)之间的空格
		$query = preg_replace("/(:[\s]{1,}\()/", ':(', $query);
		$query = preg_replace("/(:[\s]{1,}\")/", ':"', $query);
		$query = preg_replace("/([\"]{2,})/", '"', $query);
		return $query;
	}

	/**
	 * 查找query的第一对左右括号
	 *
	 * @param String $query        	
	 */
	private static function _getLR($query) {
		$lft = strpos($query, '(');
		if ($lft === false) {
			return array(
				false,
				false
			);
		}
		$rht = self::_findRHT($query, $lft);
		// debug(compact('query', 'lft', 'rht'));
		return array(
			$lft,
			$rht
		);
	}

	/**
	 * 查找query与左括号匹配的右括号位置
	 * 1) 指定的左括号的位置为$A
	 * 2) 找出离$A最近的右括号位置$B
	 * 3) 如果$A与$B中间有其它左括号,则找出离$A最近的左括号$C
	 * 4) 计算出与$C最近的右括号位置$D
	 * 5) 将$C与$D替换为"|"
	 * 6) 重新找离$A最近的右括号
	 *
	 * @param String $query        	
	 * @param int $lft        	
	 */
	private static function _findRHT($query, $left_place) {
		// debug($query);
		$first_right_place = strpos($query, ')', $left_place + 1);
		if ($first_right_place === false) {
			return false;
		}
		$N = 0;
		if ($first_right_place - $left_place - 1 > 0) {
			$N = substr_count($query, '(', $left_place + 1, $first_right_place - $left_place - 1);
		}
		if ($N !== 0) {
			$lft_ = strpos($query, '(', $left_place + 1);
			// debug(compact('query','N','lft_','rht_'));
			$rht_ = self::_findRHT($query, $lft_);
			$query = substr_replace($query, '|', $lft_, 1);
			$query = substr_replace($query, '|', $rht_, 1);
			return self::_findRHT($query, $left_place);
		} else {
			return $first_right_place;
		}
	}
}
?>