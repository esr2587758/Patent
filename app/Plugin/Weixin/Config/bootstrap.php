<?php
App::build(
	array(
		'Controller' => array(
			'%s' . 'Controller' . DS . 'Server' . DS
		)
	),
	App::REGISTER
);

Configure::write('command.field.mappers', array(
	
	'ABSTTTLCLMS' => array(
		'solr_field' => array(

		),
		'label' => 'Abstract & Title & Claims'
	),
	'TTLABST' => array(
		'solr_field' => array(
					
		),
		'label' => 'Title & Abstract'
	),
	'PN' => array(
		'solr_field' => array(
					
		),
		'label' => 'Publication Number'
	),
	'APNO' => array(
		'solr_field' => array(
					
		),
		'label' => 'Application Number'
	),
	'APD' => array(
		'solr_field' => array(
					
		),
		'label' => 'Application Date'
	),
	'PBDT' => array(
		'solr_field' => array(
					
		),
		'label' => 'Publication Date'
	),
	'PRIORITY_DATE' => array(
		'solr_field' => array(
					
		),
		'label' => 'Priority Date'
	),
	'ABST' => array(
		'solr_field' => array(
					
		),
		'label' => 'Abstract'
	),
	'TTL' => array(
		'solr_field' => array(
					
		),
		'label' => 'Title'
	),
	'CLMS' => array(
		'solr_field' => array(
					
		),
		'label' => 'Claims'
	),
	'DESC' => array(
		'solr_field' => array(
					
		),
		'label' => 'Description'
	),
	'CCL' => array(
		'solr_field' => array(
					
		),
		'label' => 'US Classification'
	),
	'ICL' => array(
		'solr_field' => array(
					
		),
		'label' => 'International Classification'
	),
	'LOC' => array(
		'solr_field' => array(
					
		),
		'label' => 'Locarno Classification'
	),
	'IN' => array(
		'solr_field' => array(
					
		),
		'label' => 'Inventor Name'
	),
	'IN_ADDRESS' => array(
		'solr_field' => array(
					
		),
		'label' => 'Inventor Address'
	),
	'AN' => array(
		'solr_field' => array(
					
		),
		'label' => 'Assignee Name'
	),
	'AN_ADDRESS' => array(
		'solr_field' => array(
					
		),
		'label' => 'Assignee Address'
	),
	'PE' => array(
		'solr_field' => array(
					
		),
		'label' => 'Primary Examiner'
	),
	'AE' => array(
		'solr_field' => array(
					
		),
		'label' => 'Assistant Examiner'
	),
	'AT' => array(
		'solr_field' => array(
					
		),
		'label' => 'Attorney Name'
	),
));