<?php

use Ampersand\Config;
use Ampersand\Core\Concept;
use Ampersand\Session;
use Ampersand\Core\Atom;
use Ampersand\Interfacing\Resource;
use Ampersand\Log\Logger;
use Ampersand\Log\Notifications;
use Ampersand\Interfacing\InterfaceObject;
use Ampersand\Interfacing\Transaction;

global $app;

/**************************************************************************************************
 *
 * resource calls WITHOUT interfaces
 *
 *************************************************************************************************/

$app->get('/resources', function() use ($app) {
	if(Config::get('productionEnv')) throw new Exception ("List of all resource types is not available in production environment", 403);
	
    $content = array_values(
        array_map(function($cpt){
            return $cpt->label; // only show label of resource types
        }, array_filter(Concept::getAllConcepts(), function($cpt){
            return $cpt->isObject(); // filter concepts without a representation (i.e. resource types)
    })));
	
	print json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

$app->get('/resources/:resourceType', function ($resourceType) use ($app) {
	$session = Session::singleton();
	
	$roleIds = $app->request->params('roleIds');
	$session->activateRoles($roleIds);
	
	$concept = Concept::getConcept($resourceType);
	
	// Checks
    if(!$concept->isObject()) throw new Exception ("Resource type not found", 404);
	if(!$session->isEditableConcept($concept)) throw new Exception ("You do not have access for this call", 403);
	
    print json_encode($concept->getAllAtomObjects(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});


$app->get('/resources/:resourceType/:resourceId', function ($resourceType, $resourceId) use ($app) {
	$session = Session::singleton();

	$roleIds = $app->request->params('roleIds');
	$session->activateRoles($roleIds);
    
	$resource = new Resource($resourceId, $resourceType);
	
	// Checks
	if(!$session->isEditableConcept($resource->concept)) throw new Exception ("You do not have access for this call", 403);
	if(!$resource->exists()) throw new Exception("Resource '{$resource}' not found", 404);

	print json_encode($resource, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});


/**************************************************************************************************
 *
 * resource calls WITH interfaces
 *
 *************************************************************************************************/

$app->get('/resources/:resourceType/:resourceId/:ifcPath+', function ($resourceType, $resourceId, $ifcPath) use ($app) {
	$session = Session::singleton();

	$roleIds = $app->request->params('roleIds');
	$session->activateRoles($roleIds);
    
    // Options
    $rcOptions = $ifcOptions = 0;
	if (filter_var($app->request->params('metaData'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_META_DATA | Resource::INCLUDE_SORT_DATA;
    if (filter_var($app->request->params('navIfc'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_NAV_IFCS;
    if (filter_var($app->request->params('inclLinktoData'), FILTER_VALIDATE_BOOLEAN)) $ifcOptions = $ifcOptions | InterfaceObject::INCLUDE_LINKTO_IFCS;
    $depth = $app->request->params('depth');

    // Get content
    $content = (new Resource($resourceId, $resourceType))->walkPath($ifcPath)->get($rcOptions, $ifcOptions);

	print json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

});

$app->put('/resources/:resourceType/:resourceId/:ifcPath+', function ($resourceType, $resourceId, $ifcPath) use ($app) {
    $session = Session::singleton();
    $transaction = Transaction::getCurrentTransaction();
    
    $roleIds = $app->request->params('roleIds');
    $options = $app->request->params();
    
    $session->activateRoles($roleIds);
    
    // Options
    $rcOptions = $ifcOptions = 0;
	if (filter_var($app->request->params('metaData'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_META_DATA | Resource::INCLUDE_SORT_DATA;
    if (filter_var($app->request->params('navIfc'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_NAV_IFCS;
    if (filter_var($app->request->params('inclLinktoData'), FILTER_VALIDATE_BOOLEAN)) $ifcOptions = $ifcOptions | InterfaceObject::INCLUDE_LINKTO_IFCS;
    $depth = $app->request->params('depth');
    
    // Perform put
    $resource = (new Resource($resourceId, $resourceType))->walkPath($ifcPath, 'Ampersand\Interfacing\Resource')->put((object) $app->request->getBody())->get($rcOptions, $ifcOptions);
    
    // Close transaction
    $transaction->close();
    if($transaction->isCommitted()) Logger::getUserLogger()->notice($resource->getLabel() . " updated");
    
    // Return result
    $result =   [ 'content'             => $resource
                , 'notifications'       => Notifications::getAll()
                , 'invariantRulesHold'  => $transaction->invariantRulesHold()
                ];
    print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

$app->patch('/resources/:resourceType/:resourceId(/:ifcPath+)', function ($resourceType, $resourceId, $ifcPath = array()) use ($app) {
	$session = Session::singleton();
    $transaction = Transaction::getCurrentTransaction();
	
	$roleIds = $app->request->params('roleIds');
	$options = $app->request->params();
	
	$session->activateRoles($roleIds);
	
    // Options
    $rcOptions = $ifcOptions = 0;
	if (filter_var($app->request->params('metaData'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_META_DATA | Resource::INCLUDE_SORT_DATA;
    if (filter_var($app->request->params('navIfc'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_NAV_IFCS;
    if (filter_var($app->request->params('inclLinktoData'), FILTER_VALIDATE_BOOLEAN)) $ifcOptions = $ifcOptions | InterfaceObject::INCLUDE_LINKTO_IFCS;
    $depth = $app->request->params('depth');
	
	// Perform patch(es)
    $resource = (new Resource($resourceId, $resourceType))->walkPath($ifcPath, 'Ampersand\Interfacing\Resource')->patch($app->request->getBody())->get($rcOptions, $ifcOptions);
	
    // Close transaction
    $transaction->close();
    if($transaction->isCommitted()) Logger::getUserLogger()->notice($resource->getLabel() . " updated");
    
	// Return result
	$result = array ( 'patches'				=> $app->request->getBody()
					, 'content' 			=> $resource
					, 'notifications' 		=> Notifications::getAll()
					, 'invariantRulesHold'	=> $transaction->invariantRulesHold()
					, 'sessionRefreshAdvice' => $session->getSessionRefreshAdvice()
					);
	
	print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
});

$app->post('/resources/:resourceType/:resourceId/:ifcPath+', function ($resourceType, $resourceId, $ifcPath) use ($app) {
	$session = Session::singleton();
    $transaction = Transaction::getCurrentTransaction();

	$roleIds = $app->request->params('roleIds');
	$session->activateRoles($roleIds);

	$options = $app->request->params();
    
    // Options
    $rcOptions = $ifcOptions = 0;
	if (filter_var($app->request->params('metaData'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_META_DATA | Resource::INCLUDE_SORT_DATA;
    if (filter_var($app->request->params('navIfc'), FILTER_VALIDATE_BOOLEAN)) $rcOptions = $rcOptions | Resource::INCLUDE_NAV_IFCS;
    if (filter_var($app->request->params('inclLinktoData'), FILTER_VALIDATE_BOOLEAN)) $ifcOptions = $ifcOptions | InterfaceObject::INCLUDE_LINKTO_IFCS;
    $depth = $app->request->params('depth');
    
    // Perform create
    $resource = (new Resource($resourceId, $resourceType))->walkPath($ifcPath, 'Ampersand\Interfacing\ResourceList')->post($app->request->getBody())->get($rcOptions, $ifcOptions);
    
    // Close transaction
    $transaction->close();
    if($transaction->isCommitted()) Logger::getUserLogger()->notice($resource->getLabel() . " created");
    
	// Return result
	$result = array ( 'content' 			=> $resource
					, 'notifications' 		=> Notifications::getAll()
					, 'invariantRulesHold'	=> $transaction->invariantRulesHold()
					, 'sessionRefreshAdvice' => $session->getSessionRefreshAdvice()
					);

	print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

$app->delete('/resources/:resourceType/:resourceId/:ifcPath+', function ($resourceType, $resourceId, $ifcPath) use ($app) {
	$session = Session::singleton();
    $transaction = Transaction::getCurrentTransaction();

	$roleIds = $app->request->params('roleIds');
	$session->activateRoles($roleIds);

	$options = $app->request->params();
    
	// Perform delete
    $resource = (new Resource($resourceId, $resourceType))->walkPath($ifcPath, 'Ampersand\Interfacing\Resource')->delete();
    
    // Close transaction
    $transaction->close();
    if($transaction->isCommitted()) Logger::getUserLogger()->notice("Resource deleted");
    
	// Return result
	$result = array ( 'notifications' 		=> Notifications::getAll()
					, 'invariantRulesHold'	=> $transaction->invariantRulesHold()
					, 'sessionRefreshAdvice' => $session->getSessionRefreshAdvice()
					);

	print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

});

?>