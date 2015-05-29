<?php
ob_start();
require_once("dynamic_settings/config.php");

$routingParams = array();

// Routing
$app['router']->group(['prefix' => '/{lang?}'], function()use($app,$routingParams){
    $app['router']->any('/{page_type?}/{page_id?}/{page_name?}',
    function($lang=null,$page_type=null,$page_id=null,$page_name=null)use($routingParams) {
        global $routingParams;

        $routingParams['lang']          = $lang!==null ? $lang : SITE_DEFAULT_LANGUAGE;
        $routingParams['page_type']     = $page_type;
        $routingParams['page_id']       = $page_id;
        $routingParams['page_name']     = $page_name;
        //return $routingParams;
    });
});

// Send request of routing
$request2 = Illuminate\Http\Request::createFromGlobals();
$response = $app['router']->dispatch($request2);
$response->sendContent();

// Validate Routing Params
$validatorFactory = new ValidatorFactory(new \Symfony\Component\Translation\Translator('en'));
$pageIdValidator = $routingParams['page_type']==='activate' ? 'email' : 'alpha_dash' ;
$validationRules = array(
    'lang' => ['alpha'],
    'page_type' => ['Regex:/[A-Za-z_]+$/'],
    'page_id' => [$pageIdValidator],
    'page_name' => ['Regex:/^[A-Za-z0-9_-\pL\s]+$/u']
);
$validator = $validatorFactory->make( $routingParams, $validationRules );

if ($validator->fails()) { 
    //var_dump( $validator->failed() );
    $routingParams['page_type'] = "404" ;
}

// Set Lang
$translate = langTools::setLanguage($routingParams['lang']);

// Test routing params : var_dump( $routingParams );

// Match current route to available routes
$routingParams['page_type'] = SiteModules::matchSiteRoutes($routingParams['page_type'],$routingParams['page_id']);

// Set last Visited URL
urlTools::setLastUrl($excludedLoginSitePages,$routingParams['page_type']);

// Get Page
include_once SITE_INT_URL."pages/".$routingParams['page_type'].".php";

// Footer
include_once SITE_INT_URL_FOOTER;

ob_end_flush();