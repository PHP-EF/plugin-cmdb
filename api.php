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
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-WRITE'])) {
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
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-WRITE'])) {
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
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-WRITE'])) {
        // Delete the CMDB Record
        $cmdbPlugin->deleteRecord($args['id']);
    }

	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Get CMDB Layout (Used to build the tables & form)
$app->get('/plugin/cmdb/layout', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-READ'])) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getColumnsAndSections());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Get CMDB Sections
$app->get('/plugin/cmdb/sections', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getSections());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Create New CMDB Section
$app->post('/plugin/cmdb/sections', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB Section with the submitted data
		if (isset($data["name"])) {
			$cmdbPlugin->addSection($data["name"]);
		}
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Section
$app->patch('/plugin/cmdb/section/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB Section with the submitted data
		if (isset($data["name"])) {
			$cmdbPlugin->updateSection($args['id'],$data["name"]);
		}
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Delete CMDB Section
$app->delete('/plugin/cmdb/section/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
		$cmdbPlugin->removeSection($args["id"]);
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Get CMDB Columns
$app->get('/plugin/cmdb/columns', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
		$data = $request->getQueryParams();
		if (isset($data['section'])) {
			$cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getColumnDefinitionBySectionId($data['section']));
		} else {
			$cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getColumnDefinitions());
		}
        
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Column Weight
$app->patch('/plugin/cmdb/column/{id}/weight', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
		$data = $cmdbPlugin->api->getAPIRequestData($request);
		if (isset($data['weight'])) {
			if ($cmdbPlugin->updateColumnWeight($args['id'],$data['weight'])) {
				$cmdbPlugin->api->setAPIResponseMessage('Successfully updated column position');
			} else {
				$cmdbPlugin->api->setAPIResponse('Error','Failed to update column position');
			}
		} else {
			$cmdbPlugin->api->setAPIResponse('Error','Weight missing from request');
		}        
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Section Weight
$app->patch('/plugin/cmdb/section/{id}/weight', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-ADMIN'])) {
		$data = $cmdbPlugin->api->getAPIRequestData($request);
		if (isset($data['weight'])) {
			if ($cmdbPlugin->updateSectionWeight($args['id'],$data['weight'])) {
				$cmdbPlugin->api->setAPIResponseMessage('Successfully updated section position');
			} else {
				$cmdbPlugin->api->setAPIResponse('Error','Failed to update section position');
			}
		} else {
			$cmdbPlugin->api->setAPIResponse('Error','Weight missing from request');
		}        
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});