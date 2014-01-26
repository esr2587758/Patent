<?php
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


define ( 'WEB_DATABASE', 'patsnap_web' );

if (! defined ( 'DATE_TYPE' )) {
	define ( 'DATE_TYPE', 'Y-m-d' );
}
if (! defined ( 'DATE_TYPE_MIN' )) {
	define ( 'DATE_TYPE_MIN', 'Y-m-d H:i' );
}
if (! defined ( 'DATE_H_MIN' )) {
	define ( 'DATE_H_MIN', 'H:i' );
}




// solr of patent basic infomation
if (! defined('PATENT_TEXT_SOLR')) {
	define('PATENT_TEXT_SOLR', 'http://192.168.0.177:8080/patsnap/PATENT');
}
// solr pivot of patent basic infomation
if (! defined('PATENT_TEXT_PIVOT_SOLR')) {
	define('PATENT_TEXT_PIVOT_SOLR', 'http://192.168.3.64:9999/pivot');
}

// IPC definition solr
if (! defined('IPC_DEFINE_SOLR')) {
	define('IPC_DEFINE_SOLR', 'http://192.168.3.62:8080/classification/ipc/');
}

// export service
if (! defined('EXPORT_SERVICE')) {
	define('EXPORT_SERVICE', 'http://192.168.3.64:8000/export');
}

// landscape service
if (! defined('LANDSCAPE_SERVICE')) {
	define('LANDSCAPE_SERVICE', '192.168.0.163:1080');
}



// Mysql account/password
if (! defined ( 'DB_LOGIN' )) {
	define ( 'DB_HOST', '192.168.3.62' );
	define ( 'DB_LOGIN', 'patsnap_w' );
	define ( 'DB_PASSWORD', 'patsnap123' );
	define ( 'DB_PORT', '3306' );
}
// patsnap mongo account/password
if (! defined ( 'PATSNAP_MB_LOGIN' )) {
	define ( 'PATSNAP_MB_HOST', '192.168.3.62' );
	define ( 'PATSNAP_MB_PORT', 27017 );
	define ( 'PATSNAP_MB_LOGIN', 'patsnap_w' );
	define ( 'PATSNAP_MB_PASSWORD', 'patsnappatsnap' );
}
// $mongo_gridfs_export account/password
if (! defined ( 'MONGO_EXPORT_LOGIN' )) {
	define ( 'MONGO_EXPORT_HOST', '192.168.3.60' );
	define ( 'MONGO_EXPORT_PORT', 27017 );
	define ( 'MONGO_EXPORT_LOGIN', 'patsnap_r' );
	define ( 'MONGO_EXPORT_PASSWORD', 'patsnap123' );
}

if (! defined('MONGO_TEXT_LOGIN')) {
	define('MONGO_TEXT_HOST', '192.168.3.66');
	define('MONGO_TEXT_PORT', 27017);
	//define('MONGO_TEXT_HOST', '192.168.1.112');
	//define('MONGO_TEXT_PORT', 27118);
	define('MONGO_TEXT_LOGIN', 'patsnap_r');
	define('MONGO_TEXT_PASSWORD', 'patsnap123');
}


// mongo account/password family
if (! defined ( 'MONGO_TEXT_LOGIN_FAMILY' )) {
	define ( 'MONGO_TEXT_HOST_FAMILY', '192.168.3.62' );
	define ( 'MONGO_TEXT_PORT_FAMILY', 27017 );
	define ( 'MONGO_TEXT_LOGIN_FAMILY', 'patsnap_r' );
	define ( 'MONGO_TEXT_PASSWORD_FAMILY', 'patsnap123' );
}

// mongo image
if (! defined ( 'MONGO_IMG_LOGIN' )) {
	define ( 'MONGO_IMG_HOST', '' );
	define ( 'MONGO_IMG_LOGIN', '' );
	define ( 'MONGO_IMG_PORT', 27017 );
	define ( 'MONGO_IMG_PASSWORD', '' );
	define ( 'MONGO_IMG_REPLI_HOST', 'mongodb://patsnap_r:patsnap123@192.168.0.142:27017,192.168.0.143:27017/patsnapdata' );
	define ( 'MONGO_IMG_REPLI_STATUS', false );
}