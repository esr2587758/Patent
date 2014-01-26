<?php
App::uses('Inflector','Utility');

/**
 *
 * @author jqj
 *
 */
class SolrModel extends Model {

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
	 * List of valid finder method options, supplied as the first parameter to find().
	 *
	 * @var array
	 */
	public $findMethods = array(
		'all' => true,
		'first' => true,
		'count' => true,
		'list' => true,
		'group' => true,
		'facet' => true,
		'' => false
	);

	protected $solrSchema = array();

	protected $operator = array(
		'AND',
		'OR',
		'NOT'
	);

	private $_searchField = array();

	private $_displayField = array();

	private $_facetField = array();

	private $_familyField = array();

	public function __construct($id = false,$table = null,$ds = null) {
		return parent::__construct($id,$table,$ds);
	}

	function schema($field = false) {
		if($this->useTable !== false && (!is_array($this->_schema) || $field === true)) {
			if(!empty($this->solrSchema)) {
				$this->_schema = $this->solrSchema;
				foreach($this->solrSchema as $field => $option) {
					if(!empty($option['display'])) {
						$this->_displayField[] = $field;
					}
					if(!empty($option['index'])) {
						$this->_searchField[] = $field;
					}
					if(!empty($option['family'])) {
						$this->_familyField[] = $field;
					}
					if(!empty($option['facet'])) {
						$this->_facetField[] = $field;
					}
				}
			} else {
				trigger_error('Solr schema ' . $this->name . ' is empty.');
			}
		}
		if(!is_string($field)) {
			return $this->_schema;
		}

		if(isset($this->_schema[$field])) {
			return $this->_schema[$field];
		}

		return null;
	}

	/**
	 * 从solr搜索或分析
	 * @param array $option
	 * @param array $types
	 */
	public function searchFacet(array $option,array $types) {
		$option = $this->processParam($option,$types);
		if($option === false) {
			return false;
		}
		if(in_array(SEARCH_GROUP,$types)) {
			return $this->group($option);
		} elseif(array_intersect(array(
			SEARCH_ALL,
			SEARCH_REPR
		),$types)) {
			if(in_array(SEARCH_ALL,$types)) {
				$type = 'all';
			} elseif(in_array(SEARCH_REPR,$types)) {
				$type = 'group';
			}
			return $this->find($type,$option);
		} else {
			return $this->facet($option);
		}

	}

	/**
	 * 处理发向solr的字段，将不相关的条件删除掉
	 *
	 * @param array $types ['search','group','searchRepr','facetSingle','facetPivot']
	 */
	protected function processParam(array $option,array $types = array(SEARCH_ALL)) {
		$this->_cleanError();
		if(empty($option['conditions'])) {
			trigger_error('请输入查询条件');
			$this->setErrorCode(ERROR_MISS_PARAM);
			$this->setErrorMsg(__('Please input search field.'));
			return false;
		}
		if($this->judgeTypeGroup($types) === false) {
			return false;
		}
		$default = array(
			'conditions' => array(),
			'filter' => array(),
		);
		if(array_intersect(array(
			SEARCH_ALL,
			SEARCH_REPR,
			SEARCH_GROUP
		),$types)) {
			$default['fields'] = array(
				'PN',
				'TTL'
			);
			$default['limit'] = 20;
			$default['offset'] = 0;
			$default['page'] = 1;
		}
		if(array_intersect(array(
			SEARCH_REPR,
			SEARCH_GROUP,
			FACET_MULTI_DIMENSIONAL_REPR,
			FACET_ONE_DIMENSIONAL_REPR
		),$types)) {
			//如果没有分组字段返回false
			if(empty($option['group']['field'])) {
				trigger_error('请输入分组字段');
				$this->setErrorCode(ERROR_MISS_PARAM);
				$this->setErrorMsg(__('Please select group field.'));
				return false;
			}
			$default['group'] = array();
			$option['group']['ngroups'] = 'true';
			if(!in_array(SEARCH_GROUP,$types)) {
				$option['group']['limit'] = 1;
			}
		}
		if(array_intersect(array(
			FACET_MULTI_DIMENSIONAL,
			FACET_MULTI_DIMENSIONAL_REPR,
			FACET_ONE_DIMENSIONAL,
			FACET_ONE_DIMENSIONAL_REPR
		),$types)) {
			$default['facet'] = array();
		}

		//判断facet.pivot
		if(array_intersect(array(
			FACET_MULTI_DIMENSIONAL,
			FACET_MULTI_DIMENSIONAL_REPR,
		),$types)) {
			if(empty($option['facet']['pivot']['field']) || !is_array($option['facet']['pivot']['field'])) {
				trigger_error('请输入pivot字段');
				$this->setErrorCode(ERROR_MISS_PARAM);
				$this->setErrorMsg(__('Please select pivot field.'));
				return false;
			}
		}
		if(array_intersect(array(
			FACET_ONE_DIMENSIONAL,
			FACET_ONE_DIMENSIONAL_REPR
		),$types)) {
			if((empty($option['facet']['field']['field']) || !is_array($option['facet']['field']['field'])) && (empty($option['facet']['date']['field']) || !is_array($option['facet']['date']['field'])) && (empty($option['facet']['range']['field']) || !is_array($option['facet']['range']['field']))) {
				trigger_error('请输入facet字段');
				$this->setErrorCode(ERROR_MISS_PARAM);
				$this->setErrorMsg(__('Please select facet field.'));
				return false;
			}
		}
		if(!empty($option['facet'])) {
			foreach($option['facet'] as $facetType => $detail) {
				if(!empty($detail['limit']) && !is_array($detail['limit'])) {
					trigger_error("请输入正确的facet limit字段 on $facetType");
					$this->setErrorCode(ERROR_MISS_PARAM);
					$this->setErrorMsg(__('Please input correct facet limit field.'));
					return false;
				}
			}
		}
		$option = array_merge($default,$option);
		$option = array_intersect_key($option,array_intersect_key($default,$option));
		if(array_intersect(array(
			FACET_MULTI_DIMENSIONAL_REPR,
			FACET_ONE_DIMENSIONAL_REPR
		),$types)) {
			$option['facet']['group'] = 'true';
		}
		return $option;
	}

	/**
	 * 判断输入的组合条件是否合法
	 */
	protected function judgeTypeGroup($types) {
		$invalid = false;
		if((int)in_array(SEARCH_ALL,$types) + (int)in_array(SEARCH_GROUP,$types) + (int)in_array(SEARCH_REPR,$types) > 1) {
			$invalid = 1;
		}
		if(in_array(SEARCH_ALL,$types) && in_array(FACET_ONE_DIMENSIONAL_REPR,$types)) {
			$invalid = 3;
		}
		if($invalid) {
			trigger_error("query组合($invalid)不合法");
			$this->setErrorCode(ERROR_NOT_FOUND);
			$this->setErrorMsg(__('Group not found.'));
			return false;
		}
		return true;
	}

	/**
	 * 拼接solr url
	 * @param array $query
	 * @param string $type
	 */
	function joinUrl($query,$type = '') {
		$query = $this->buildQuery($type,$query);
		return $this->getDataSource()->generateUrl($this,$query);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::find()
	 */
	public function find($type = 'first',$query = array()) {
		$this->findQueryType = $type;
		$this->id = $this->getID();

		$query = $this->buildQuery($type,$query);
		if($query === null) {
			return null;
		}

		return $this->_readDataSource($type,$query);
	}

	/**
	 * 分组返回专利
	 * @param array $query
	 */
	function group($query = array()) {
		$query = $this->buildQuery('',$query);
		if($query === null) {
			return null;
		}
		return $this->_readGroupSource($query);
	}

	protected function _readGroupSource($query) {
		$results = $this->getDataSource()->read($this,$query);
		$groupData = array();
		if(!empty($results['grouped'])) {
			foreach($results['grouped'] as $field => $groups) {
				$tmpGroups['groupNum'] = $groups['ngroups'];
				$results['count'] = $groups['matches'];
				foreach($groups['groups'] as $group) {
					$doclist = $group['doclist']['docs'];
					$tmpGroups['doclist'][] = $doclist;
				}
				if(count($results['grouped'] == 1)) {
					$results['grouped'][$field] = array_merge($groupData,$tmpGroups);
				} else {
					$results['grouped'][$field] = $tmpGroups;
				}

			}
		}
		if($query['callbacks'] === true || $query['callbacks'] === 'after') {
			$results = $this->_filterGroupResults($results);
			$results = $this->_filterFacetResults($results);
		}
		return $results;
	}

	protected function _filterGroupResults($results,$primary = true) {
		$event = new CakeEvent('Model.afterGroup',$this, array(
			$results,
			$primary
		));
		$event->modParams = 0;
		$this->getEventManager()->dispatch($event);
		return $event->result;
	}

	/**
	 * solr的facet请求
	 * Rows会强制限制为0
	 *
	 * @param array  $query
	 * @return NULL|Ambigous <mixed, multitype:unknown >
	 */
	function facet($query = array()) {
		$query = $this->buildQuery('facet',$query);
		if($query === null) {
			return null;
		}
		return $this->_readFacetSource($query);
	}

	/**
	 * 从solr获取facet内容，并做相应格式化
	 * @param array $query
	 * @return Ambigous <mixed, multitype:unknown >
	 */
	protected function _readFacetSource($query) {
		$results = $this->getDataSource()->read($this,$query);
		if($query['callbacks'] === true || $query['callbacks'] === 'after') {
			$results = $this->_filterFacetResults($results);
		}
		return $results;
	}

	/**
	 * 过滤处理solr facet结果
	 * @param array $results
	 * @param string $primary
	 * @return mixed
	 */
	protected function _filterFacetResults($results,$primary = true) {
		$event = new CakeEvent('Model.afterFacet',$this, array(
			$results,
			$primary
		));
		$event->modParams = 0;
		$this->getEventManager()->dispatch($event);
		return $event->result;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::_readDataSource()
	 */
	protected function _readDataSource($type,$query) {
		$results = $this->getDataSource()->read($this,$query);
		if($query['callbacks'] === true || $query['callbacks'] === 'after') {
			$results = $this->_filterResults($results);
			$results = $this->_filterFacetResults($results);
		}

		$this->findQueryType = null;

		if($this->findMethods[$type] === true) {
			return $this->{'_find' . ucfirst($type)}('after',$query,$results);
		}
		return $results;
	}

	/**
	 * 当数据库操作发生错误时
	 *
	 * @see Model::onError()
	 */
	function onError() {
		$error = $this->getDataSource()->error;
		$msg = 'Solr query error: '.$error['code'].': '.$error['msg'].'=>'.urldecode($error['url']);
		trigger_error($msg);
		$this->log($msg,LOG_ERR);
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

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::buildQuery()
	 */
	public function buildQuery($type = 'first',$query = array()) {
		return parent::buildQuery($type,$query);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::_findFirst()
	 */
	protected function _findFirst($state,$query,$results = array()) {
		if($state === 'before') {
			$query['offset'] = 1;
			return $query;
		}

		if(empty($results['docs'][0])) {
			return array();
		}

		return $results['docs'][0];
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::_findCount()
	 */
	protected function _findCount($state,$query,$results = array()) {
		if($state == 'before') {
			return $query;
		} else {
			return $results;
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::_findList()
	 */
	protected function _findList($state,$query,$results = array()) {
		if($state == 'before') {
			return parent::_findList($state,$query,$results);
		} else {
			$results['docs'] = parent::_findList($state,$query,$results['docs']);
			return $results;
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::_findAll()
	 */
	protected function _findAll($state,$query,$results = array()) {
		if($state == 'before') {
			unset($query['group']);
			return $query;
		} else {
			return $results;
		}
	}

	protected function _findFacet($state,$query,$results = array()) {
		if($state == 'before') {
			$query['offset'] = $query['limit'] = 0;

			return $query;
		} else {
			return $results;
		}
	}

	/**
	 * (non-PHPdoc)
	 */
	protected function _findGroup($state,$query,$results = array()) {
		if($state == 'before') {
			$query['group']['limit'] = 1;
			return $query;
		} else {
			return $results;
		}
	}

	public function afterGroup($results,$primary = false) {
		return $results;
	}

	public function afterFacet($results,$primary = false) {
		return $results;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::implementedEvents()
	 */
	public function implementedEvents() {
		return array(
			'Model.beforeFind' => array(
				'callable' => 'beforeFind',
				'passParams' => true
			),
			'Model.afterFind' => array(
				'callable' => 'afterFind',
				'passParams' => true
			),
			'Model.afterGroup' => array(
				'callable' => 'afterGroup',
				'passParams' => true
			),
			'Model.afterFacet' => array(
				'callable' => 'afterFacet',
				'passParams' => true
			),
			'Model.beforeValidate' => array(
				'callable' => 'beforeValidate',
				'passParams' => true
			),
			'Model.afterValidate' => array('callable' => 'afterValidate'),
			'Model.beforeSave' => array(
				'callable' => 'beforeSave',
				'passParams' => true
			),
			'Model.afterSave' => array(
				'callable' => 'afterSave',
				'passParams' => true
			),
			'Model.beforeDelete' => array(
				'callable' => 'beforeDelete',
				'passParams' => true
			),
			'Model.afterDelete' => array('callable' => 'afterDelete')
		);
	}

}
?>