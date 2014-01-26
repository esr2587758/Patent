<?php
if(!defined('SEARCH_TYPE_DEFINE')) {
	define('SEARCH_TYPE_DEFINE',true);
	//从solr搜索所有专利
	define('SEARCH_ALL',1);
	//从solr分组搜索专利并取其代表专利
	define('SEARCH_REPR',2);
	//从solr分组搜索专利，并返回每组的指定条专利
	define('SEARCH_GROUP',3);
	//从solr同时对所有符合条件的专利的多个字段排名
	define('FACET_ONE_DIMENSIONAL',4);
	//从solr对所有符合条件的专利的多个字段做多维分析
	define('FACET_MULTI_DIMENSIONAL',5);
	//从solr分组后的专利代表做多个字段的排名分析
	define('FACET_ONE_DIMENSIONAL_REPR',6);
	//从solr对分组后的代表专利的多个字段做多维分析
	define('FACET_MULTI_DIMENSIONAL_REPR',7);
}