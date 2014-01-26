<?php
App::uses('DboSource', 'Model/Datasource');

/**
 *
 * @author jqj
 *        
 */
class SolrSource extends DboSource {

	protected $_baseConfig = array(
		'host' => '',
		'login' => '',
		'password' => '',
		'post' => false,
		'timeout' => 10
	);

	public $error;

	public $operators = array(
		'AND',
		'OR',
		'NOT'
	);

	function __construct($config = array(), $autoConnect = false) {
		parent::__construct($config, false);
	}

	/**
	 * Destruct
	 *
	 * @access public
	 */
	public function __destruct() {}

	public function connect() {
		return true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DboSource::isConnected()
	 */
	public function isConnected() {
		return true;
	}

	/**
	 * commit method
	 *
	 * Solr doesn't support transactions
	 *
	 * @return void
	 * @access public
	 */
	public function commit() {
		return false;
	}

	/**
	 * begin method
	 *
	 * Solr doesn't support transactions
	 *
	 * @return void
	 * @access public
	 */
	public function begin() {
		return false;
	}

	/**
	 * rollback method
	 *
	 * Solr doesn't support transactions
	 *
	 * @return void
	 * @access public
	 */
	public function rollback() {
		return false;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DboSource::read()
	 */
	public function read(Model $model, $params = array(), $recursive = null) {
		$url = $this->buildUrl($model, $params);
		$solrParams = $this->buildParams($params);
		if ($this->config['post'] === false) {
			$url .= '?' . $this->_http_build_query($solrParams);
			$solrParams = null;
		}
		$data = $this->_send($model, $url, $solrParams);
		if ($data === false) {
			return false;
		}
		// solr会返回这种奇怪的格式
		if (! empty($data['grouped'])) {
			if (isset($data['grouped'])) {
				$groupNum = count($data['grouped']);
				$grouped = array();
				for ($i = 0; $i < $groupNum; $i ++) {
					$grouped[$data['grouped'][$i]] = $data['grouped'][++ $i];
				}
				$data['grouped'] = $grouped;
			}
		}
		unset($data['responseHeader']);
		if ($model->findQueryType == 'group') {
			if (! empty($data['grouped'])) {
				$groups = $data['grouped'][$params['group']['field']];
				$data['count'] = $groups['matches'];
				$data['groupNum'] = $groups['ngroups'];
				$docs = array();
				foreach ($groups['groups'] as $group) {
					$docs[] = $group['doclist']['docs'][0];
				}
				$data['docs'] = $docs;
			} else {
				$data['count'] = 0;
			}
			unset($data['grouped']);
		} elseif ($model->findQueryType == 'all') {
			$data['count'] = $data['response']['numFound'];
			if (! empty($data['response']['docs'])) {
				$docs = $data['response']['docs'];
				foreach ($docs as $doc) {
					$data['docs'][] = $doc;
				}
			}
			unset($data['response']);
		} else {
			if (isset($data['response'])) {
				unset($data['response']);
			}
		}
		if (! empty($data['facet_counts'])) {
			foreach ($data['facet_counts'] as $type => $value) {
				if (! empty($value)) {
					if ($type == 'facet_fields') {
						$tmpValue = array();
						foreach ($value as $field => $tops) {
							$tmpTop = array();
							$count = count($tops);
							for ($i = 0; $i < $count; $i ++) {
								$tmpTop[$tops[$i]] = $tops[++ $i];
							}
							$value[$field] = $tmpTop;
						}
					} elseif ($type == 'facet_dates') {
						$tmpValue = array();
						foreach ($value as $field => $facetData) {
							if (isset($facetData['gap'])) {
								unset($facetData['gap']);
								unset($facetData['start']);
								unset($facetData['end']);
							}
							$value[$field] = $facetData;
						}
					} elseif ($type == 'facet_pivot') {
						$tmpValue = array();
						foreach ($value as $fields => $details) {
							foreach ($details as $detail) {
								if (! empty($detail['pivot'])) {
									$tmpValue[$fields][$detail['value']] = Hash::combine($detail['pivot'], '{n}.value', '{n}.count');
								}
							}
						}
						$value = $tmpValue;
					}
					$data[$type] = $value;
				}
			}
			unset($data['facet_counts']);
		}
		return $data;
	}

	/**
	 * ÂèëÈÄÅcurlËØ∑Ê±Ç
	 */
	private function _send(Model $model, $url, array $params = null) {
		$header[] = 'Content-type: application/x-www-form-urlencoded;charset=utf-8';
		$header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "Accept-Charset: utf-8;q=0.7,*;q=0.7";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, 0);
		if (! empty($params)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			$url .= '?' . $this->_http_build_query($params);
		}
		if (! empty($this->config['login'])) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->config['login'] . ':' . $this->config['password']);
		}
		$startTime = microtime(true);
		$data = curl_exec($ch);
		$this->took = round((microtime(true) - $startTime) * 1000, 0);
		$this->affected = null;
		$this->numRows = 10;
		$errorNo = curl_errno($ch);
		$params = array();
		if ($errorNo != 0) {
			$this->error = array(
				'code' => $errorNo,
				'msg' => curl_error($ch),
				'url' => $url
			);
			$model->onError();
			$data = false;
		} else {
			$data = json_decode($data, true);
			if ($data !== false && isset($data['error'])) {
				$this->error = $tmpData['error'];
				$this->error['url'] = $url;
				$model->onError();
				$data = false;
			}
		}
		curl_close($ch);
		$this->logQuery($url);
		return $data;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DboSource::logQuery()
	 */
	public function logQuery($sql, $params = array()) {
		$params['error'] = $this->error;
		parent::logQuery(urldecode($sql), $params);
	}

	/**
	 * ÁîüÊàêsolrËØ∑Ê±ÇÁöÑurl
	 *
	 * @param Model $model        	
	 * @param array $params        	
	 * @return string
	 */
	public function generateUrl(Model $model, array $params) {
		$url = $this->buildUrl($model);
		$solrParams = $this->buildParams($params);
		return $url . '?' . $this->_http_build_query($solrParams);
	}

	/**
	 * ÊãºÊé•solr url
	 *
	 * @param Model $model        	
	 * @return string
	 */
	public function buildUrl(Model $model) {
		$url = $this->config['host'] . '/' . $model->table;
		return $url;
	}

	/**
	 * Â§ÑÁêÜsolrÂèÇÊï∞
	 *
	 * @param array $params        	
	 * @return multitype:string
	 */
	public function buildParams(array $params) {
		$solrParams = array(
			'wt' => 'json'
		);
		if (! empty($params['fields'])) {
			$solrParams['fl'] = implode(',', $params['fields']);
		}
		if (! empty($params['conditions'])) {
			$solrParams['q'] = $this->_query($params['conditions']);
		} else {
			$solrParams['q'] = '*.*';
		}
		if (! empty($params['filter'])) {
			$solrParams['fq'] = $this->_query($params['filter']);
		}
		if (! empty($params['hl'])) {
			$this->_hl($params['hl'], $solrParams);
		}
		if (! empty($params['facet'])) {
			$this->_facet($params['facet'], $solrParams);
		}
		if (! empty($params['group'])) {
			$this->_group($params['group'], $solrParams);
		}
		
		$rows = 0;
		if (! empty($params['limit'])) {
			$rows = $params['limit'];
		}
		$solrParams['rows'] = $rows;
		if (! empty($params['offset'])) {
			$solrParams['start'] = $params['offset'];
		} else {
			if ($rows > 0) {
				$page = 1;
				if (! empty($params['page'])) {
					$page = $params['page'];
				}
				$solrParams['start'] = ($page - 1) * $rows;
			}
		}
		return $solrParams;
	}

	/**
	 * ÊãºÊé•solrÁöÑqÂíåfqÂèÇÊï∞
	 *
	 * @param array $query        	
	 * @return string
	 */
	private function _query(array $query) {
		if (is_array($query)) {
			// debug($query);
			$query = $this->_queryArrToStr($query);
		} else {
			trigger_error("Query($query) is string.");
		}
		return $query;
	}

	/**
	 * conditionÊï∞ÁªÑËΩ¨Êç¢ÊàêsolrÁöÑq‰∏éfqÊ†ºÂºè
	 *
	 * @param array $query        	
	 * @return string
	 */
	private function _queryArrToStr(array $query, $defaultOperator = 'AND') {
		$processedQuery = array();
		$multiCondition = (bool) count($query);
		foreach ($query as $key => $value) {
			$key = trim($key);
			$operator = $field = '';
			// Â¶ÇÊûúkeyÂÄº‰∏∫Êï∞Â≠ó
			if (is_numeric($key)) {
				// Â¶ÇÊûú‰∏çÊòØÊï∞ÁªÑÁöÑÁ¨¨‰∏Ä‰∏™ÂÄºÔºåÂ∞ÜÊìç‰ΩúÁ¨¶ÁΩÆ‰∏∫ÈªòËÆ§Êìç‰ΩúÁ¨¶
				$operator = $defaultOperator;
			} else {
				if (in_array(strtoupper($key), $this->operators)) {
					$operator = strtoupper($key);
				} else {
					$field = $key;
					$operator = $defaultOperator;
				}
			}
			// Â¶ÇÊûú‰∏çÊòØÁ¨¨‰∏ÄÁªÑÊï∞ÊçÆÔºåÂàôÂ∞ÜÊìç‰ΩúÁ¨¶Âä†ÂÖ•Âà∞Êï∞ÁªÑ
			if (! empty($processedQuery)) {
				$processedQuery[] = $operator;
			}
			// Â¶ÇÊûúÊòØÊï∞ÁªÑÂàôÂ∞ÜÂÖ∂ÊãºÊé•‰∏∫Â≠óÁ¨¶‰∏≤
			if (is_array($value)) {
				$value = $this->_queryArrToStr($value, $operator);
			}
			if ($field) {
				// Â¶ÇÊûúÂ≠óÁ¨¶‰∏≤ÈáåÊúâÁ©∫Ê†º‰∏îÂÄºÊ≤°ÊúâË¢´‚ÄòÔºà‚ÄôÂåÖË£πÔºåÂàôÂ∞ÜÂÄºÁî®‚ÄòÔºàÔºâ‚ÄôÂåÖË£π
				if (strpos($value, ' ') !== false) {
					$value = "($value)";
				}
				$value = "$field:$value";
			} else {
				// Â¶ÇÊûúÊ≤°ÊúâfieldÔºåÂÄºÈáåÊúâÁ©∫Ê†º‰ª•ÂèäÊúâÂ§öÁªÑÊù°‰ª∂ÔºåÂàôÈªòËÆ§Áî®()Âè∑Â∞ÜÂÄºÂåÖË£π
				if (strpos($value, ' ') !== false && $multiCondition) {
					$value = "($value)";
				}
			}
			$processedQuery[] = $value;
		}
		return implode(' ', $processedQuery);
	}

	/**
	 * ÊãºÊé•solrÁöÑÈ´ò‰∫ÆÂèÇÊï∞
	 *
	 * @param array $hl        	
	 * @param array $solrParams        	
	 */
	private function _hl(array $hl, array &$solrParams) {
		$solrParams['hl'] = 'true';
		$solrParams['hl.fl'] = $hl['field'];
		$solrParams['hl.fragsize'] = 10000;
		$solrParams['hl.snippets'] = 3;
		$solrParams['hl.simple.pre'] = urlencode("<span class='highlight'>");
		$solrParams['hl.simple.post'] = urlencode('</span>');
	}

	/**
	 * ÊãºÊé•solrÁöÑfacetÂèÇÊï∞
	 *
	 * @param array $query        	
	 */
	private function _facet(array $filter, array &$solrParams) {
		$hasFacet = false;
		if (! empty($filter['field'])) {
			$hasFacet = $this->_facetField($filter['field'], $solrParams) || $hasFacet;
		}
		if (! empty($filter['pivot'])) {
			$hasFacet = $this->_facetPivot($filter['pivot'], $solrParams) || $hasFacet;
		}
		if (! empty($filter['date'])) {
			$hasFacet = $this->_facetDate($filter['date'], $solrParams) || $hasFacet;
		}
		if (! empty($filter['query'])) {
			$hasFacet = $this->_facetQuery($filter['query'], $solrParams) || $hasFacet;
		}
		if ($hasFacet) {
			$solrParams['facet'] = 'true';
			$solrParams['facet.limit'] = empty($filter['limit']) ? 10 : $filter['limit'];
			$solrParams['facet.mincount'] = empty($filter['mincount']) ? 1 : $filter['mincount'];
			if (isset($filter['group'])) {
				$solrParams['facet.group'] = $filter['group'];
			}
		}
	}

	/**
	 * Â§ÑÁêÜÁΩëÁ´ôÁöÑfacet.field
	 *
	 * @param array $filter
	 *        	$filter = array(
	 *        	'field' => array('APD','PBDT'),
	 *        	'limit' => array('APD' => 20)	ËøôÊó∂APDÁöÑlimit‰∏∫ 20,PBDTÁöÑ‰∏∫ÈªòËÆ§ÂÄº
	 *        	'mincount' => array('PBDT'=>1)
	 *        	)
	 */
	private function _facetField($filter, array &$solrParams) {
		if (! is_array($filter) || ! isset($filter['field'])) {
			return false;
		}
		$solrParams['facet.field'] = $filter['field'];
		if (! empty($filter['limit']) && is_array($filter['limit'])) {
			foreach ($filter['limit'] as $field => $limit) {
				if (in_array($field, $filter['limit'])) {
					$solrParams["f.$field.facet.limit"] = min(array(
						1,
						$filter['limit']
					));
				}
			}
		}
		if (! empty($filter['mincount']) && is_array($filter['mincount'])) {
			foreach ($filter['mincount'] as $field => $limit) {
				if (in_array($field, $filter['mincount'])) {
					$solrParams["f.$field.facet.mincount"] = min(array(
						1,
						$filter['mincount']
					));
				}
			}
		}
		return true;
	}

	/**
	 * Â§ÑÁêÜÁΩëÁ´ôÁöÑfacet.pivot
	 *
	 * @param $filter ÂèÇÊï∞Êúâ‰∏âÁßçÊ†ºÂºè
	 *        	$filter = array(
	 *        	'field' => array(
	 *        	'APD,DOCUMENT_TYPE',					ÂØπ‰∫éÁ¨¨‰∏Ä‰∏™Â≠óÊÆµPBDTÈôêÂà∂30Êù°ËÆ∞ÂΩïÔºàÁõÆÂâçÁöÑsolrÂØπÁ¨¨‰∫å‰∏™Â≠óÊÆµÊó†Ê≥ïÈôêÂà∂ÔºåÊ∞∏Ëøú‰∏∫10Ôºâ
	 *        	'PBDT,ICL_FACET',
	 *        	'DOCUMENT_TYPE,IPC_FACET',
	 *        	),
	 *        	'limit' => array(
	 *        	'APD' => 10
	 *        	),
	 *        	'mincount' => array(
	 *        	'APD' => 10
	 *        	)
	 *        	)
	 *        	
	 */
	private function _facetPivot($filter, array &$solrParams) {
		if (! is_array($filter) || ! isset($filter['field']) || ! is_array($filter['field'])) {
			return false;
		}
		$solrParams['facet.pivot'] = $filter['field'];
		if (! empty($filter['limit']) && is_array($filter['limit'])) {
			foreach ($filter['limit'] as $field => $limit) {
				if (in_array($field, $filter['limit'])) {
					$solrParams["f.$field.facet.limit"] = min(array(
						1,
						$filter['limit']
					));
				}
			}
		}
		if (! empty($filter['mincount']) && is_array($filter['mincount'])) {
			foreach ($filter['mincount'] as $field => $limit) {
				if (in_array($field, $filter['mincount'])) {
					$solrParams["f.$field.facet.mincount"] = min(array(
						1,
						$filter['mincount']
					));
				}
			}
		}
		return true;
	}

	/**
	 * Â§ÑÁêÜÁΩëÁ´ôÂØπÊó∂Èó¥ÁöÑÂàÜÊûê
	 *
	 * @param $filter $filter
	 *        	= array(
	 *        	'field' => array('APD','PBDT'),
	 *        	'start'	=> '2009-01-01T00:00:00Z',			//ÂºÄÂßãÊó∂Èó¥
	 *        	'gap'	=> '+1MONTH/+1YEAR/+1DAY'			//Êó∂Èó¥Èó¥Èöî.Â¶ÇÊûústart‰∏∫2009-1-1,end‰∏∫2010-1-1.gapËÆæÁΩÆ‰∏∫‚Äù+1MONTH‚ÄùË°®Á§∫Èó¥Èöî1‰∏™Êúà,ÈÇ£‰πàÂ∞Ü‰ºöÊääËøôÊÆµÊó∂Èó¥ÂàíÂàÜ‰∏∫12‰∏™Èó¥ÈöîÊÆµ.Ê≥®ÊÑè‚Äù+‚ÄùÂõ†‰∏∫ÊòØÁâπÊÆäÂ≠óÁ¨¶ÊâÄ‰ª•Â∫îËØ•Áî®‚Äù%2B‚Äù‰ª£Êõø.
	 *        	'end'	=> '2012-01-01T00:00:00Z/NOW',		//ÁªìÊùüÊó∂Èó¥
	 *        	'other' => 'before|after|between|none|all'	//ÈªòËÆ§‰∏∫none before‰ºöÂØπstart‰πãÂâçÁöÑÂÄºÂÅöÁªüËÆ°; after‰ºöÂØπend‰πãÂêéÁöÑÂÄºÂÅöÁªüËÆ°; between‰ºöÂØπstartËá≥end‰πãÈó¥ÊâÄÊúâÂÄºÂÅöÁªüËÆ°.Â¶ÇÊûúhardend‰∏∫trueÁöÑËØù,ÈÇ£‰πàËØ•ÂÄºÂ∞±ÊòØÂêÑ‰∏™Êó∂Èó¥ÊÆµÁªüËÆ°ÂÄºÁöÑÂíå; noneË°®Á§∫ËØ•È°πÁ¶ÅÁî®; allË°®Á§∫before,after,allÈÉΩ‰ºöÁªüËÆ°
	 *        	)
	 *        	
	 */
	private function _facetDate($filter, array &$solrParams) {
		if (! is_array($filter) || ! isset($filter['field']) || ! is_array($filter['field']) || ! isset($filter['start']) || ! isset($filter['end']) || ! isset($filter['gap'])) {
			return false;
		}
		$solrParams['facet.date'] = $filter['field'];
		$solrParams['facet.date.start'] = $filter['start'];
		$solrParams['facet.date.end'] = $filter['end'];
		$solrParams['facet.date.gap'] = $filter['gap'];
		// ÂÆÉË°®Á§∫gapËø≠‰ª£Âà∞endÂ§ÑÈááÁî®‰ΩïÁßçÂ§ÑÁêÜ.‰∏æ‰æãËØ¥Êòéstart‰∏∫2009-1-1,end‰∏∫2009-12-25,gap‰∏∫‚Äù+1MONTH‚Äù,hardend‰∏∫falseÁöÑËØùÊúÄÂêé‰∏Ä‰∏™Êó∂Èó¥ÊÆµ‰∏∫2009-12-1Ëá≥2010-1-1;hardend‰∏∫trueÁöÑËØùÊúÄÂêé‰∏Ä‰∏™Êó∂Èó¥ÊÆµ‰∏∫2009-12-1Ëá≥2009-12-25.
		$solrParams['facet.date.hardend'] = 'true';
		if (! empty($filter['other'])) {
			$solrParams['facet.date.other'] = $filter['other'];
		}
		return true;
	}

	/**
	 *
	 * @param array $query        	
	 * @param array $solrParams        	
	 */
	private function _group(array $group, array &$solrParams) {
		if (! isset($group['field'])) {
			trigger_error('Group field is empty.');
			return false;
		}
		$solrParams['group'] = 'true';
		$solrParams['group.field'] = $group['field'];
		$solrParams['group.limit'] = empty($group['limit']) ? 1 : $group['limit'];
		if (isset($group['main'])) {
			$solrParams['group.main'] = $group['main'];
		}
		if (isset($group['sort'])) {
			$solrParams['group.sort'] = $group['sort'];
		}
		if (isset($group['ngroups'])) {
			$solrParams['group.ngroups'] = $group['ngroups'];
		}
	}

	/**
	 * DataSource Query abstraction
	 *
	 * @return resource Result resource identifier.
	 */
	public function query() {
		$args = func_get_args();
		$model = $args[0];
		$params = $args[1];
		$url = $this->buildUrl($model, $params);
		$solrParams = $this->buildParams($params);
		if ($this->config['post'] === false) {
			$url .= '?' . $this->_http_build_query($solrParams);
			$solrParams = null;
		}
		$data = $this->_send($model, $url, $solrParams);
		unset($data['responseHeader']);
		return $data;
	}

	/**
	 * Caches/returns cached results for child instances
	 *
	 * @param mixed $data        	
	 * @return array Array of sources available in this datasource.
	 */
	public function listSources($data = null) {
		return true;
	}

	/**
	 * Â∞ÜÊï∞ÁªÑÊ†ºÂºèÁöÑÂèÇÊï∞Âèò‰∏∫urlÂèÇÊï∞, Â∞Übasekey‰∏éÊï∞ÁªÑÈîÆÂÄºÊãºÊé•ÁöÑÈÉ®ÂàÜÂéªÈô§, ‰ª•ÈÄÇÂ∫îsolr query
	 *
	 * @param
	 *        	$argSep
	 * @param
	 *        	$prefix
	 * @param
	 *        	$baseKey
	 */
	private function _http_build_query($data, $prefix = null, $argSep = null, $baseKey = null) {
		if (empty($argSep)) {
			$argSep = ini_get('arg_separator.output');
		}
		$out = array();
		foreach ((array) $data as $key => $v) {
			if (is_numeric($key) && ! empty($prefix)) {
				$key = $prefix . $key;
			}
			$key = urlencode($key);
			
			if (! empty($baseKey)) {
				$key = $baseKey;
			}
			if (is_array($v) || is_object($v)) {
				$out[] = $this->_http_build_query($v, $prefix, $argSep, $key);
			} elseif (! empty($v) || $v === 0) {
				$out[] = $key . '=' . urlencode($v);
			}
		}
		return implode($argSep, $out);
	}

	/**
	 * Get the query log as an array.
	 *
	 * @param boolean $sorted
	 *        	Get the queries sorted by time taken, defaults to false.
	 * @param boolean $clear
	 *        	If True the existing log will cleared.
	 * @return array Array of queries run as an array
	 */
	public function getLog($sorted = false, $clear = true) {
		if ($sorted) {
			$log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
		} else {
			$log = $this->_queriesLog;
		}
		if ($clear) {
			$this->_queriesLog = array();
		}
		return array(
			'log' => $log,
			'count' => $this->_queriesCnt,
			'time' => $this->_queriesTime
		);
	}
}
?>