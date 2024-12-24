<?php
// Get CMDB Plugin Settings
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

// Get CMDB Records
$app->get('/plugin/cmdb/records', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-READ'])) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getAllRecords());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Create New CMDB Record
$app->post('/plugin/cmdb/record', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess("ADMIN-CONFIG")) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB record with the submitted data
        $cmdbPlugin->newRecord($data);
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Record
$app->patch('/plugin/cmdb/record/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess("ADMIN-CONFIG")) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Update the CMDB record values with the submitted data
        $cmdbPlugin->updateRecord($args['id'], $data);
    }

	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Delete CMDB Record
$app->delete('/plugin/cmdb/record/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess("ADMIN-CONFIG")) {
        // Delete the CMDB Record
        $cmdbPlugin->deleteRecord($args['id']);
    }

	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});