<?php
/**
 * 获取专利文本信息
 * 可以搜索，分析，查看专利信息，本类只是最基本的操作，不做任何业务层的处理
 */

if (! defined('PATENT_SOURCE_TYPE')) {
	define('PATENT_SOURCE_TYPE', true);
	define('PATENT_SOURCE_SEARCH_FACET_ONE', 1);
	define('PATENT_SOURCE_FACET_MULTI', 3);
	define('PATENT_SOURCE_TEXT_INFO', 4);
	define('PATENT_SOURCE_LEGAL', 5);
	define('PATENT_SOURCE_STANDARD_NAME', 6);
}

/**
 * 专利文本信息处理
 * 
 * @author jqj
 *        
 */
class Patent extends PatentAppModel {

	public $name = 'PatentInfo';

	public $useDbConfig = false;

	public $useTable = false;

	public $allFields = array(
		'PN',
		'TTL',
		'AN',
		'AN_ADD',
		'AN_ST',
		'AN_DOCDB',
		'IN',
		'IN_ADD',
		'IN_ST',
		'IN_DOCDB',
		'AT',
		'ATC',
		'PE',
		'AE',
		'ABST',
		'DESC',
		'CLMS',
		'IPC',
		'IPCR',
		'IPC_FACET',
		'UPC',
		'LOC',
		'LOC_FACET',
		'FI',
		'FI_FACET',
		'FTERM',
		'FTERM_FACT',
		'UPC_FACET',
		'PRIORITY',
		'CITE'
	);

	/**
	 * 专利优先显示的语言
	 */
	private $patentLanguage = array(
		'EN'
	);

	/**
	 * 系统支持的专利语言
	 * 
	 * @var array
	 */
	private $systemLanguage = false;

	/**
	 * 返回的专利字段的语言个数，如果为0显示所有内容
	 * 
	 * @var int
	 */
	private $displayLanguageNumber = 1;

	function __construct($id = false, $table = null, $ds = null) {
		$this->systemLanguage = Configure::read('Patent.Language.List');
	}

	/**
	 * 设置专利语言，搜索时会从设定的语言中搜索，当返回多语言时，其语言顺序与本设置相同
	 */
	public function setPatentLanguage($language = 'ALL') {
		$this->sortLanguage = $this->_getLanguage($language);
		if ($this->sortLanguage === false) {
			return false;
		}
		return true;
	}
	

	/**
	 * 设置专利显示语言个数，当设置为0时会返回所有语言，当设置为1时，会取消语言key字段
	 *
	 * @param int $number        	
	 * @return boolean
	 */
	public function setDisplayLanguageNumber($number = 1) {
		if (! is_numeric($number) && $number < 0) {
			trigger_error('参数输入错误');
			return false;
		}
		$this->displayLanguageNumber = $number;
		return true;
	}

	/**
	 * 根据专利号获取指定字段的专利信息
	 * 
	 * @param array $pns        	
	 * @param array $fields        	
	 */
	public function getPatents(array $pns, array $fields) {
		$patentModel = $this->getPatentSource(PATENT_SOURCE_TEXT_INFO);
		$patents = $patentModel->getPatentInfoByPn($pns, $fields);
		if ($patents) {
			foreach ($patents as $pn => $patent) {
				$patents[$pn] = $this->_formatePatent($patent);
			}
		}
		return $patents;
	}

	/**
	 * 获取指定专利的专利信息
	 */
	public function getPatent($pn, array $fields) {
		$patents = $this->getPatents((array) $pn, $fields);
		if (isset($patents[$pn])) {
			return $patents[$pn];
		}
		return false;
	}

	/**
	 * 从solr搜索专利，不分组，没有facet
	 *
	 * 其它参数会被过滤掉
	 *
	 * @see Patent::_searchFacet()
	 */
	public function search(array $option) {
		$searchResult = $this->_searchFacet($option, (array) SEARCH_ALL);
		if ($searchResult === false) {
			return false;
		}
		$searchResult = $this->_fillPatentTextToDocs($searchResult, $option['fields']);
		return $searchResult;
	}

	/**
	 * 从sor分组搜索专利，获取每组专利的第一组信息，没有facet
	 *
	 * @see Patent::_searchFacet()
	 */
	public function searchRepr(array $option) {
		$searchResult = $this->_searchFacet($option, (array) SEARCH_REPR);
		if ($searchResult === false) {
			return false;
		}
		$searchResult = $this->_fillPatentTextToDocs($searchResult, $option['fields']);
		return $searchResult;
	}

	/**
	 * 从solr分组搜索专利，返回符合条件的所有专利，没有facet
	 *
	 * @see Patent::_searchFacet()
	 */
	public function searchGroup(array $option) {
		$searchResult = $this->_searchFacet($option, (array) SEARCH_GROUP);
		if ($searchResult === false) {
			return false;
		}
		$searchResult = $this->_fillPatentTextToGrouped($searchResult, $option['fields']);
		return $searchResult;
	}

	/**
	 * 根据不同的solr请求组合从solr获取数据
	 * 
	 * @param array $option
	 *        	<code>
	 *        	<?php
	 *        	array(
	 *        	'conditions' => array(), //array//string.建议使用数组，如果使用string后续还要分解为数组
	 *        	'filter' => array(), //array/string 过滤条件
	 *        	'fields' => array(),
	 *        	'limit' => 20,//int
	 *        	'offset' => 0，//偏移量
	 *        	'page' => 1,
	 *        	'facet' => array(
	 *        	'mincount' => 20,
	 *        	'group' => true, // 分析时，是否只统计分组的代表专利
	 *        	'field' => array(
	 *        	'field' => array('APD_YEAR','DOCUMENT_TYPE'),
	 *        	'limit' => array('APD_YEAR'=>50)
	 *        	),
	 *        	'date' => array(
	 *        	'field' => array('APD','PBDT'),
	 *        	'limit' => array(),
	 *        	'start' => '1995-12-31T12:59:59Z',
	 *        	'end' => '2012-12-31T12:59:59Z',
	 *        	'gap' => '+1MONTH'
	 *        	),
	 *        	'pivot' => array(
	 *        	'field' => array('DOCUMENT_TYPE,IPC_FACET'),
	 *        	'limit' => array()
	 *        	)
	 *        	)
	 *        	)
	 *        	
	 *        	?>
	 *        	</code>
	 */
	private function _searchFacet(array $option, array $types) {
		$this->_cleanError();
		if (array_intersect(array(
			FACET_MULTI_DIMENSIONAL,
			FACET_MULTI_DIMENSIONAL_REPR
		), $types)) {
			$sourceModel = $this->getPatentSource(PATENT_SOURCE_FACET_MULTI);
		} else {
			$sourceModel = $this->getPatentSource(PATENT_SOURCE_SEARCH_FACET_ONE);
		}
		$sourceModel->setLanguage($this->patentLanguage);
		$searchResult = $sourceModel->searchFacet($option, $types);
		if ($searchResult === false) {
			$this->setErrorCode($sourceModel->getErrorCode());
			$this->setErrorMsg($sourceModel->getErrorMsg());
			return false;
		}
		return $searchResult;
	}

	/**
	 * 将solr返回结果里的docs的专利信息填充
	 *
	 * @param array $searchResult
	 *        	PatentSearchFacet 返回的数据
	 * @param array $fields
	 *        	需要显示的字段
	 */
	private function _fillPatentTextToDocs($searchResult, $fields) {
		if ($searchResult['count'] > 0 && ! empty($searchResult['docs'])) {
			$pns = array_keys(Hash::combine($searchResult['docs'], '{n}.PN'));
			$patents = $this->getPatents($pns, $fields);
			foreach ($searchResult['docs'] as $i => $patent) {
				if (isset($patents[$patent['PN']])) {
					$searchResult['docs'][$i] = $patents[$patent['PN']];
				}
			}
		}
		return $searchResult;
	}

	/**
	 * 将solr返回结果里的grouped的专利信息填充
	 *
	 * @param array $searchResult
	 *        	PatentSearchFacet 返回的数据
	 * @param array $fields
	 *        	需要显示的字段
	 */
	private function _fillPatentTextToGrouped($searchResult, array $fields) {
		if ($searchResult['count'] > 0 && ! empty($searchResult['grouped'])) {
			$groups = $searchResult['grouped'];
			$pns = array();
			foreach ($groups as $field => $group) {
				$pns = array_merge($pns, Hash::combine($group['doclist'], '{n}.{n}.PN'));
			}
			$pns = array_keys($pns);
			$patents = $this->getPatents($pns, $fields);
			foreach ($groups as $field => $group) {
				foreach ($group['doclist'] as $i => $patentGroup) {
					foreach ($patentGroup as $groupKey => $patent) {
						if (isset($patents[$patent['PN']])) {
							$groups[$field]['doclist'][$i][$groupKey] = $patents[$patent['PN']];
						}
					}
				}
			}
			$searchResult['grouped'] = $groups;
		}
		
		return $searchResult;
	}

	/**
	 * 搜索以及对所有搜索结果做facet
	 *
	 * @see Patent::_searchFacet()
	 */
	public function searchAndOneDimensional(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			SEARCH_ALL,
			FACET_ONE_DIMENSIONAL
		));
		if ($searchResult === false) {
			return false;
		}
		$searchResult = $this->_fillPatentTextToDocs($searchResult, $option['fields']);
		return $searchResult;
	}

	/**
	 * 搜索专利代表，对符合条件的所有专利做facet
	 *
	 * @see Patent::_searchFacet()
	 */
	public function searchReprAndOneDimensional(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			SEARCH_REPR,
			FACET_ONE_DIMENSIONAL
		));
		if ($searchResult === false) {
			return false;
		}
		$searchResult = $this->_fillPatentTextToDocs($searchResult, $option['fields']);
		return $searchResult;
	}

	/**
	 *  搜索专利代表，对专利代表做facet
	 *
	 * @see Patent::_searchFacet()
	 */
	public function searchReprAndOneDimensionalRepr(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			SEARCH_REPR,
			FACET_ONE_DIMENSIONAL_REPR
		));
		if ($searchResult === false) {
			return false;
		}
		$searchResult = $this->_fillPatentTextToDocs($searchResult, $option['fields']);
		return $searchResult;
	}

	/**
	 * 对所有内容的一个或多个字段分析
	 *
	 * @see Patent::_searchFacet()
	 */
	public function oneDimensional(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			FACET_ONE_DIMENSIONAL
		));
		if ($searchResult === false) {
			return false;
		}
		return $searchResult;
	}

	/**
	 * 对专利代表的一个或多个字段分析
	 */
	public function oneDimensionalRepr(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			FACET_ONE_DIMENSIONAL_REPR
		));
		if ($searchResult === false) {
			return false;
		}
		return $searchResult;
	}

	/**
	 * 对所有内容多个字段做交叉分析
	 *
	 * @see Patent::_searchFacet()
	 */
	public function multDimensional(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			FACET_MULTI_DIMENSIONAL
		));
		if ($searchResult === false) {
			return false;
		}
		return $searchResult;
	}

	/**
	 * 对专利代表的多个字段做交叉分析
	 *
	 * @see Patent::_searchFacet()
	 */
	public function multDimensionalRepr(array $option) {
		$searchResult = $this->_searchFacet($option, array(
			FACET_MULTI_DIMENSIONAL_REPR
		));
		if ($searchResult === false) {
			return false;
		}
		return $searchResult;
	}

	/**
	 * 法律状态的历史变更信息
	 */
	public function legalHistory(array $pns) {
		$sourceModel = $this->getPatentSource(PATENT_SOURCE_LEGAL);
	}

	/**
	 * 根据专利号获取引用专利的专利号列表
	 */
	public function getCite($pn) {
		$patent = $this->getPatent($pn, array(
			'CITE'
		));
		if (isset($patent['CITE'])) {
			return $patent['CITE'];
		}
		return false;
	}

	/**
	 * 根据专利号获取被引用专利的专利号列表
	 */
	public function getCited($pn) {
		$patentSourceModel = $this->getPatentSource(PATENT_SOURCE_SEARCH_FACET_ONE);
		$firstLimit = 50;
		$option = array(
			'conditions' => array(
				'CITE' => $pn
			),
			'fields' => array(
				'PN'
			),
			'limit' => $firstLimit
		);
		$searchResult = $this->_searchFacet($option, (array) SEARCH_ALL);
		if ($searchResult === false) {
			return false;
		}
		$citedCount = $searchResult['count'];
		$citedPatents = array();
		if ($citedCount > 0) {
			$citedPatents = Hash::combine($searchResult['docs'], '{n}.PN');
			if ($citedCount > $firstLimit) {
				$offset = $firstLimit;
				$limit = $citedCount - $firstLimit;
				$option['limit'] = $limit;
				$option['offset'] = $offset;
				$searchResult = $this->_searchFacet($option, (array) SEARCH_ALL);
				$citedPatents = array_merge($citedPatents, Hash::combine($searchResult['docs'], '{n}.PN'));
			}
			$citedPatents = array_keys($citedPatents);
		}
		return $citedPatents;
	}

	/**
	 * 根据不同的类型获取数据模型
	 */
	public function getPatentSource($type = false) {
		if ($type === false) {
			return $this->patentSource;
		}
		$patentSource = false;
		$type = strtolower($type);
		switch ($type) {
			case PATENT_SOURCE_FACET_MULTI:
				if(!class_exists('PatentPivot')){
					include dirname(__File__).DS.'Patent'.DS.'PatentPivot.php';
				}
				$patentSource = ClassRegistry::init('Patent.PatentPivot');
				break;
			case PATENT_SOURCE_SEARCH_FACET_ONE:
				if(!class_exists('PatentSearchFacet')){
					if(!include(dirname(__File__).DS.'Patent'.DS.'PatentSearchFacet.php')){
						trigger_error('不能加载文件');
					}
				}
				$patentSource = ClassRegistry::init('Patent.PatentSearchFacet');
				break;
			case PATENT_SOURCE_LEGAL:
				if(!class_exists('PatentLegal')){
					if(!include(dirname(__File__).DS.'Patent'.DS.'PatentLegal.php')){
						trigger_error('不能加载文件');
					}
				}
				$patentSource = ClassRegistry::init('Patent.PatentLegal');
				break;
			case PATENT_SOURCE_TEXT_INFO:
				if(!class_exists('PatentText')){
					if(!include(dirname(__File__).DS.'Patent'.DS.'PatentText.php')){
						trigger_error('不能加载文件');
					}
				}
				$patentSource = ClassRegistry::init('Patent.PatentText');
				break;
			case PATENT_SOURCE_STANDARD_NAME:
				if(!class_exists('PatentStandardName')){
					if(!include(dirname(__File__).DS.'Patent'.DS.'PatentStandardName.php')){
						trigger_error('不能加载文件');
					}
				}
				$patentSource = ClassRegistry::init('Patent.PatentStandardName');
			default:
				trigger_error("$type 不存在");
				return false;
				break;
		}
		return $patentSource;
	}

	/**
	 * 格式化返回值的格式
	 * 如果是多语言的，刚将其按指定语言排序
	 * 如果设置了返回语言个数，为0时返回所有语言，为1时将取消返回语言Key
	 */
	private function _formatePatent($patent) {
		foreach ($patent as $field => $value) {
			if (is_array($value) && $this->_judgeIsMultiLan($value)) {
				$value = $this->_sortByLanguage($value);
				if ($this->displayLanguageNumber === 1) {
					$value = array_shift($value);
				} elseif ($this->displayLanguageNumber > 1) {
					$value = array_slice($value, 0, $this->displayLanguageNumber, true);
				}
				$patent[$field] = $value;
			}
		}
		return $patent;
	}

	/**
	 * 判断该值的字段是否是多语言字段
	 */
	private function _judgeIsMultiLan(array $data) {
		$language = $this->systemLanguage;
		$language[] = 'UNKNOWN';
		return (boolean) array_intersect($language, array_keys($data));
	}

	/**
	 * 将专利内容按指定的语言顺序排列
	 * 
	 * @param array $fieldData        	
	 */
	private function _sortByLanguage(array $data) {
		return array_merge(array_intersect_key(array_flip($this->patentLanguage), $data), $data);
	}

	/**
	 * 根据用户传入的参数，处理成网站最终支持的语言
	 * 
	 * @param string|array $language        	
	 * @return boolean array
	 */
	private function _getLanguage($language = 'ALL') {
		if (empty($language)) {
			trigger_error("错误的语言设置");
			return false;
		}
		$formatLanguage = false;
		if (is_string($language)) {
			$language = strtoupper($language);
			if ($language == 'ALL') {
				$formatLanguage = $this->systemLanguage;
			} elseif (in_array($language, $this->systemLanguage)) {
				$formatLanguage = (array) $language;
			} else {
				trigger_error("错误的语言设置($language)");
			}
		} else {
			$inValidLanguage = false;
			$validLanguage = array_intersect($language, array_intersect($this->systemLanguage, $language));
			if ($validLanguage) {
				$formatLanguage = $validLanguage;
				$inValidLanguage = array_diff($language, $this->systemLanguage);
			} else {
				$inValidLanguage = $language;
			}
			if ($inValidLanguage) {
				trigger_error("错误的语言设置（" . implode(', ', $inValidLanguage) . "）");
			}
		}
		return $formatLanguage;
	}
}
?>