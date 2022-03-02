<?php
/*
 * NOTE: Be sure to add routes in correct order as the first match will win
 * 
 * Route Structure
 * $route = new Route(
 *     '/archive/{month}',              // path
 *     '\Namespace\Class::method',      // Callable or class::method string
 *     array('month' => 'Jan'),         // Params and defaults to path params... all will be sent to the request object.
 *     array('GET', 'POST', 'HEAD')     // methods
 * );
 */
use Tk\Routing\Route;

$config = \Bs\Config::getInstance();
$routes = $config->getRouteCollection();
if (!$routes) return;


// Used to redirect index.php request back home in rarer instances
$routes->add('public-index-php-fix', Route::create('/index.php', function ($request) {
    \Tk\Uri::create('/')->redirect();
}));
$routes->add('home', Route::create('/index.html', 'Bs\Controller\Index::doDefault'));
$routes->add('home-base', Route::create('/', 'Bs\Controller\Index::doDefault'));

$routes->add('login', Route::create('/login.html', 'Bs\Controller\Login::doDefault'));
$routes->add('register', Route::create('/register.html', 'Bs\Controller\Register::doDefault'));
$routes->add('recover', Route::create('/recover.html', 'Bs\Controller\Recover::doDefault'));
$routes->add('logout', Route::create('/logout.html', 'Bs\Controller\Logout::doDefault'));

$routes->add('install', Route::create('/install.html', 'Bs\Controller\Install::doDefault'));
$routes->add('maintenance', Route::create('/maintenance.html', 'Bs\Controller\Maintenance::doDefault'));


// Admin Pages
//$routes->add('admin-dashboard', Route::create('/admin/index.html', 'Bs\Controller\Admin\Dashboard::doDefault'));
//$routes->add('admin-dashboard-base', Route::create('/admin/', 'Bs\Controller\Admin\Dashboard::doDefault'));
$routes->add('admin-user-profile', Route::create('/admin/profile.html', 'Bs\Controller\User\Profile::doDefault'));

$routes->add('admin-admin-manager', Route::create('/admin/adminManager.html', 'Bs\Controller\User\Manager::doDefaultType',
    array('targetType' => \Bs\Db\User::TYPE_ADMIN)));
$routes->add('admin-admin-edit', Route::create('/admin/adminEdit.html', 'Bs\Controller\User\Edit::doDefaultType',
    array('targetType' => \Bs\Db\User::TYPE_ADMIN)));

$routes->add('admin-user-manager', Route::create('/admin/memberManager.html', 'Bs\Controller\User\Manager::doDefaultType',
    array('targetType' => \Bs\Db\User::TYPE_MEMBER)));
$routes->add('admin-user-edit', Route::create('/admin/memberEdit.html', 'Bs\Controller\User\Edit::doDefaultType',
    array('targetType' => \Bs\Db\User::TYPE_MEMBER)));

$routes->add('admin-settings', Route::create('/admin/settings.html', 'Bs\Controller\Admin\Settings::doDefault'));
$routes->add('admin-plugin-manager', Route::create('/admin/plugins.html', 'Bs\Controller\PluginManager::doDefault'));


// Member Pages
//$routes->add('member-dashboard', Route::create('/member/index.html', 'Bs\Controller\Member\Dashboard::doDefault'));
//$routes->add('member-dashboard-base', Route::create('/member/', 'Bs\Controller\Member\Dashboard::doDefault'));
$routes->add('member-profile', Route::create('/member/profile.html', 'Bs\Controller\User\Profile::doDefault'));


// Admin Dev Pages
$routes->add('admin-dev-events',
    Route::create('/admin/dev/dispatcherEvents.html', 'Bs\Controller\Admin\Dev\Events::doDefault'));
$routes->add('admin-tail-log', Route::create('/admin/dev/tailLog.html', 'Bs\Controller\Admin\Dev\Tail\Log::doDefault'));
$routes->add('admin-forms', Route::create('/admin/dev/forms.html', 'Bs\Controller\Admin\Dev\Forms::doDefault'));


// Examples

// Ajax Routes
//$routes->add('ajax-find-user', Route::create('/api/1.0/findUser', 'App\Ajax\User::doFindUser', array('POST')));

// Example: How to do a simple controller/route all-in-one
//$routes->add('simpleTest', Route::create('/test.html', function ($request) use ($config) {
//    vd($config->toString());
//    return '<p>This is a simple test</p>';
//}));

