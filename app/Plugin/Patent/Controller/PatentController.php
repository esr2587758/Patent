<?php
/**
 * 获取专利的各种信息，基本输入条件为专利号
 */
/**
 * 获取专利的基本信息
 * 
 * @author jqj
 *        
 */
class PatentController extends PatentAppController {
	
	/**
	 * 加载数据库
	 *
	 * @var array
	 */
	public $uses = array (
			// 'Patent.PatentText',
			// 'Patent.PatentSolr',
			'Patent.Patent' 
	);
	function beforeRender() {
		if ($this->request->is ( 'Ajax' )) {
			exit ();
		}
	}
	function basic() {
		$pns = array (
				'EP0867397A1',
				'CN102052911A',
				'US5762374' 
		);
		$this->Patent->setPatentLanguage ( array (
				'DE',
				'FR',
				'CN',
				'EN' 
		) );
		// $this->PatentSolr->setLanguage('ALL');
		// debug($this->Patent->getCited('US5865274'));
		// $result = $this->Patent->getPatents(array('US5865274','US6865274'),array('TTL','APNO'));
		// debug($result);return;
		$this->Patent->setDisplayLanguageNumber ( 0 );
		$result = $this->Patent->search ( array (
				// 'conditions' => "ABST:CAR AND TTL:CA OR (ABST:CUP AND ABST:CU) and cup OR (TTL:((cups tif) AND (CUPS TIF)) OR (ABST: (CUP CAR) AND APD_YEAR:2011))",
				// 'conditions' =>'PN:(EP1055633A1 OR EP0867397B1)',
				'conditions' => 'car',
				// 'conditions' => array(
				// 'car cup',
				// array(
				// 'TTL' => array(
				// 'OR' => array(
				// 'asdf',
				// 'asdf'
				// ),
				// 'TTL'
				// ),
				// 'ABST' => 'CUP'
				// ),
				// 'OR' => array(
				// 'TTL' => array(
				// 'OR' => array(
				// 'cup',
				// 'tissue'
				// )
				// )
				// )
				// ),
				'limit' => 10,
				// 'conditions' => 'car',
				'offset' => 0,
				'fields' => array (
						'PN',
						'APNO',
						'APD',
						'PBDT',
						'AN',
						'TTL',
						'ABST',
						'UPC',
						'IPC',
						'IPCR',
						'LOC',
						'LEGAL',
						'CITE' 
				),
				'facet' => array (
						'field' => array (
								'field' => array (
										'APD_YEAR',
										'IPC_FACET' 
								),
								'limit' => array (
										'IPC_FACET' => 5 
								) 
						),
						'date' => array (
								'field' => array (
										'APD' 
								),
								'start' => '1995-12-31T12:59:59Z',
								'end' => '2012-12-31T12:59:59Z',
								'gap' => '+1YEAR' 
						),
						'pivot' => array (
								'field' => array (
										'DOCUMENT_TYPE,IPC_FACET',
										'DOCUMENT_TYPE,APD_YEAR' 
								) 
						) 
				),
				'group' => array (
						'field' => 'INPADOC_FAMILY_ID',
						'limit' => 5 
				) 
		) );
		debug ( ($result) );
	}
	function legal() {
	}
}
?>