// when using minified angular modules, use module('myApp', []).controller('MyController', ['myService', function (myService) { ...
angular.module('AmpersandApp', ['ngResource', 'ngRoute', 'ngSanitize', 'restangular', 'ui.bootstrap', 'uiSwitch', 'cgBusy', 'siTable', 'ngStorage', 'angularFileUpload', 'ui.bootstrap.datetimepicker', 'hc.marked'])
.config(function($routeProvider, $locationProvider) {
    $routeProvider
        // default start page
        .when('/', {
            resolveRedirectTo : ['NavigationBarService', function (NavigationBarService) {
                return NavigationBarService.getRouteForHomePage();
            }]
        })
        .when('/prototype/welcome', { 
            controller : '',
            templateUrl : 'app/src/shared/welcome.html',
            interfaceLabel : 'Welcome'
            })
        // installer page
        .when('/admin/installer', {
            controller : 'InstallerController',
            templateUrl : 'app/src/admin/installer.html',
            interfaceLabel : 'Installer'
            })
        .when('/redirect-after-login', {
            resolveRedirectTo : ['LoginService', function (LoginService) {
                return LoginService.getPageBeforeLogin();
            }]
        }).when('/404', {
            templateUrl: 'app/src/shared/404.html',
            interfaceLabel: '404'
            })
        .otherwise({redirectTo: '/404'});
    
    $locationProvider.hashPrefix(''); // see: https://stackoverflow.com/questions/41211875/angularjs-1-6-0-latest-now-routes-not-working
}).config(function(RestangularProvider) {
    
    RestangularProvider.setBaseUrl('api/v1'); // Generate: path to API folder
    RestangularProvider.setDefaultHeaders({"Content-Type": "application/json"});
    RestangularProvider.setPlainByDefault(true);
    
}).run(function(Restangular, $rootScope, $location, $route, NotificationService, RoleService, NavigationBarService, LoginService, $localStorage){

    // Store previous url in localstorage.
    // This url can be used when returning back to the application after e.g. OAuth login
    // Value is used by fixed route '/redirect-after-login'
    $rootScope.$on('$locationChangeSuccess', function (event, newUrl, previousUrl) {
        const url = new URL(previousUrl);

        // AngularJS is a single page app where routing is done after the hash (#)
        // Regular URL layout doesn't apply here. We only require the 'hash' part of the URL.
        const path = url.hash.substr(1); // strip the hash char

        // Don't store the routes for login page itself and redirect-after-login
        if (path === '/ext/Login' || path === '/redirect-after-login') {
            return;
        }

        $localStorage.login_urlBeforeLogin = path;
    });

    Restangular.addFullRequestInterceptor(function(element, operation, what, url, headers, params){
        //params.navIfc = true;
        //params.metaData = true;
        return params;
    });
    
    Restangular.addResponseInterceptor(function(data, operation, what, url, response, deferred){
        if(operation != 'get' && operation != 'getList' && data.sessionRefreshAdvice) NavigationBarService.refreshNavBar();
		if((data || {}).navTo != null) $location.url(data.navTo);
        
        return data;
    });
    
    Restangular.setErrorInterceptor(function(response, deferred, responseHandler) {
        var message;
        var details;
        if(typeof response.data === 'object' && response.data !== null){
            if(response.data.error == 404) { // 404: Not found
                NotificationService.addInfo(response.data.msg || 'Resource not found');
            
            } else if(response.status == 401){ // 401: Unauthorized
                if(response.data.loginPage) {
                    LoginService.setLoginPage(response.data.loginPage);
                }
                LoginService.setSessionIsLoggedIn(false);
                NavigationBarService.refreshNavBar();
                LoginService.gotoLoginPage();
                NotificationService.addInfo(response.data.msg || 'Login required to access this page');
            
            } else {
                message = response.data.msg || response.statusText; // if empty response message, take statusText
                NotificationService.addError(message, response.status, true, response.data.html);
            }
            
            if(response.data.notifications !== undefined) NotificationService.updateNotifications(response.data.notifications);
            if (response.data.navTo != null) {
                $location.url(response.data.navTo);
            }
        // network error
        } else if (response.status === -1) {
            NotificationService.addError('Connection error. Please check your internet connection and try again', null, false);
        }else{
            message = response.status + ' ' + response.statusText;
            details = response.data; // html content is excepted
            NotificationService.addError(message, response.status, true, details);
        }
        
        return true; // proceed with success or error hooks of promise
    });
    
    $rootScope.getCurrentDateTime = function (){
        return new Date();
    };
    
    // Add feature to $location.url() function to be able to prevent reloading page (set reload param to false)
    var original = $location.url;
    $location.url = function (url, reload) {
        if (reload === false) {
            var lastRoute = $route.current;
            var un = $rootScope.$on('$locationChangeSuccess', function () {
                $route.current = lastRoute;
                un();
            });
        }
        return original.apply($location, [url]);
    };
}).value('cgBusyDefaults',{
    message:'Loading...',
    backdrop: true,
    //templateUrl: 'my_custom_template.html',
    //delay: 500, // in ms
    minDuration: 500, // in ms
    // wrapperClass: 'my-class my-class2'
});
