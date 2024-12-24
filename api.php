<?php
// **
// USED TO DEFINE CUSTOM API ROUTES
// **
$app->get('/plugin/cmdb/settings', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess('ADMIN-CONFIG')) {
		$cmdbPlugin->api->setAPIResponseData($cmdbPlugin->_pluginGetSettings());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugin/cmdb/records', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess('CMDB-READ')) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getAllRecords());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});