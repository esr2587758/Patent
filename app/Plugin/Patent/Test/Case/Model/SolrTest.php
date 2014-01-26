<?php
App::uses('Solr', 'Patent.Model');
/**
 *
 * @author jqj
 *        
 */
class SolrTest extends CakeTestCase {
	public $fixtures = array('Patent.Solr');
	
	public function setUp() {
		parent::setUp();
		$this->Article = ClassRegistry::init('Solr');
	}
	
	public function testg(){
		
	}
}

?>