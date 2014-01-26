<?php
//系统可用专利语言
Configure::write('Patent.Language.List', array(
	'EN',
	'CN',
	'JP',
	'FR',
	'DE'
));
//本项目所有标准字段与solr字段的映射
Configure::write('Patent.Solr.Field.Mapping', array(
	'CITE' => 'CITE_FACET',
	'IPC_FACET' => 'ICL_FACET'
));
//有语言区分的专利字段
Configure::write('Patent.Language.Field', array(
	'TTL',
	'ABST',
	'DESC',
	'CLMS',
	'AN',
	'IN',
	'AT',
	'ATC',
	'PE',
	'AE'
));
