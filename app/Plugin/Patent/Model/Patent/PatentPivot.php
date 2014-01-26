<?php
if(!class_exists('PatentSearchFacet')){
	if(!include(dirname(__File__).DS.'PatentSearchFacet.php')){
		trigger_error('不能加载文件');
	}
}

class PatentPivot extends PatentSearchFacet {
	public $useDbConfig = 'patentPivot';

}
