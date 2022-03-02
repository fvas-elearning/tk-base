<?php
namespace Bs;

use Bs\Db\Permission;;

use Bs\Db\Status;
use Bs\Db\StatusMap;
use Bs\Db\User;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Tk\Db\ModelInterface;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Config extends \Tk\Config
{

    /**
     * init the default params.
     */
    protected function init()
    {
        parent::init();
        $this->set('system.lib.base.path', $this['system.vendor.path'] . '/uom/tk-base');
    }

    /**
     * Load the site route config files
     */
    public function loadConfig()
    {
        include($this->getLibBasePath() . '/config/application.php');
        parent::loadConfig();
    }

    /**
     * Load the site route config files
     */
    public function loadRoutes()
    {
        include($this->getLibBasePath() . '/config/routes.php');
        $this->loadAppRoutes();
    }


    /**
     * @return string
     */
    public function getLibBaseUrl()
    {
        return $this->getSiteUrl() . rtrim($this->get('system.lib.base.path'), '/');
    }

    /**
     * @return string
     */
    public function getLibBasePath()
    {
        return $this->getSitePath() . rtrim($this->get('system.lib.base.path'), '/');
    }

    /**
     * getPluginFactory
     *
     * @return \Tk\Plugin\Factory
     */
    public function getPluginFactory()
    {
        if (!$this->get('plugin.factory')) {
            $this->set('plugin.factory', \Tk\Plugin\Factory::getInstance($this->getDb(), $this->getPluginPath(), $this->getEventDispatcher()));
        }
        return $this->get('plugin.factory');
    }

    /**
     * A factory method to create an instances of an Auth adapters
     *
     * @param string $class
     * @param array $submittedData
     * @return \Tk\Auth\Adapter\Iface
     * @throws \Tk\Auth\Exception
     */
    public function getAuthAdapter($class, $submittedData = array())
    {
        /** @var \Tk\Auth\Adapter\Iface $adapter */
        $adapter = null;
        switch($class) {
            case '\Tk\Auth\Adapter\Config':
                $adapter = new $class($this['system.auth.username'], $this['system.auth.password']);
                break;
            case '\Tk\Auth\Adapter\Ldap':
                $adapter = new $class($this['system.auth.ldap.host'], $this['system.auth.ldap.baseDn'], $this['system.auth.ldap.filter'],
                    $this['system.auth.ldap.port'], $this['system.auth.ldap.tls']);
                break;
            case '\Tk\Auth\Adapter\DbTable':
                $adapter = new $class($this['db'], $this['system.auth.dbtable.tableName'],
                    $this['system.auth.dbtable.usernameColumn'], $this['system.auth.dbtable.passwordColumn'],
                    $this['system.auth.dbtable.activeColumn']);
                $adapter->setHashCallback(array($this, 'hashPassword'));
                break;
            case '\Tk\Auth\Adapter\Trapdoor':
                $adapter = new $class();
                break;
            default:
                throw new \Tk\Auth\Exception('Cannot locate adapter class: ' . $class);
        }
        // send the user submitted username and password to the adapter
        $adapter->replace($submittedData);
        return $adapter;
    }

    /**
     * @param $pwd
     * @param \Bs\Db\User $user (optional)
     * @return string
     */
    public function hashPassword($pwd, $user = null)
    {
        $salt = '';
        if ($user && $this->get('system.auth.salted')) {
            if (method_exists($user, 'getHash')) {
                $salt = $user->getHash();
            } else if ($user->hash) {
                $salt = $user->hash;
            }
        }
        return $this->hash($pwd, $salt);
    }


    /**
     * getAuth
     *
     * @return \Tk\Auth
     */
    public function getAuth()
    {
        if (!$this->get('auth')) {
            $obj = new \Tk\Auth(new \Tk\Auth\Storage\SessionStorage($this->getSession()));
            $this->set('auth', $obj);
        }
        return $this->get('auth');
    }

    /**
     * getRequest
     *
     * @return \Tk\Request
     */
    public function getRequest()
    {
        if (!parent::getRequest()) {
            if (is_array($this->get('request.trusted.proxies'))) {
                \Tk\Request::setTrustedProxies($this->get('request.trusted.proxies'), Request::HEADER_X_FORWARDED_ALL);
            }
            if (is_array($this->get('request.trusted.hosts'))) {
                \Tk\Request::setTrustedHosts($this->get('request.trusted.hosts'));
            }

            $obj = \Tk\Request::createFromGlobals();
            parent::setRequest($obj);
        }
        return parent::getRequest();
    }

    /**
     * getCookie
     *
     * @return \Tk\Cookie
     */
    public function getCookie()
    {
        if (!parent::getCookie()) {
            $obj = new \Tk\Cookie($this->getSiteUrl());
            parent::setCookie($obj);
        }
        return parent::getCookie();
    }

    /**
     * getSession
     *
     * @return \Tk\Session
     */
    public function getSession()
    {
        if (!parent::getSession()) {
            $adapter = $this->getSessionAdapter();
            $obj = \Tk\Session::getInstance($adapter, $this, $this->getRequest(), $this->getCookie());
            parent::setSession($obj);
        }
        return parent::getSession();
    }

    /**
     * getSessionAdapter
     *
     * @return \Tk\Session\Adapter\Iface|null
     */
    public function getSessionAdapter()
    {
        if (!$this->get('session.adapter')) {
            $adapter = new \Tk\Session\Adapter\Database($this->getDb(), new \Tk\Encrypt());
            $this->set('session.adapter', $adapter);
        }
        return $this->get('session.adapter');
    }

    /**
     * getEventDispatcher
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (!$this->get('event.dispatcher')) {
            $obj = new \Tk\EventDispatcher\EventDispatcher();
            if ($this->get('event.dispatcher.log')) {
                $obj->setLogger($this->getLog());
            }
            $this->set('event.dispatcher', $obj);
        }
        return $this->get('event.dispatcher');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     */
    public function setupDispatcher($dispatcher)
    {
        \Bs\Dispatch::create($dispatcher);
    }

    /**
     * getResolver
     *
     * @return ControllerResolverInterface
     */
    public function getResolver()
    {
        if (!$this->get('resolver')) {
            $obj = new \Tk\Controller\PageResolver($this->getLog());
            $this->set('resolver', $obj);
        }
        return $this->get('resolver');
    }


    /**
     * getSiteRoutes
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection()
    {
        if (!$this->get('route.collection')) {
            $obj = new \Symfony\Component\Routing\RouteCollection();
            $this->set('route.collection', $obj);
        }
        return parent::get('route.collection');
    }

    /**
     * Ways to get the db after calling this method
     *
     *  - \Uni\Config::getInstance()->getDb()       //
     *  - \Tk\Db\Pdo::getInstance()                //
     *
     * Note: If you are creating a base lib then the DB really should be sent in via a param or method.
     *
     * @param ?string|null $name
     * @return mixed|\Tk\Db\Pdo
     */
    public function getDb($name = null)
    {
        if (!$name) $name = 'db';
        if (!$this->get('db') && $this->has($name.'.type')) {
            try {
                $pdo = \Tk\Db\Pdo::getInstance($name, $this->getGroup($name, true));
                $this->set('db', $pdo);
            } catch (\Exception $e) {
                error_log('<p>Config::getDb(): ' . $e->getMessage() . '</p>');
                exit;
            }
        }
        return $this->get('db');
    }

    /**
     * get a dom Modifier object
     *
     * @return \Dom\Modifier\Modifier
     */
    public function getDomModifier()
    {
        if (!$this->get('dom.modifier')) {
            $dm = new \Dom\Modifier\Modifier();
            $dm->add(new \Dom\Modifier\Filter\UrlPath($this->getSiteUrl()));
            $dm->add(new \Dom\Modifier\Filter\JsLast());

            if (class_exists('Dom\Modifier\Filter\Less')) {
                /** @var \Dom\Modifier\Filter\Less $less */
                $vars = array(
                    'siteUrl' => rtrim(\Tk\Uri::create($this->getSiteUrl())->getPath(), '/'),
                    'dataUrl' => rtrim(\Tk\Uri::create($this->getDataUrl())->getPath(), '/'),
                    'templateUrl' => rtrim(\Tk\Uri::create($this->getTemplateUrl())->getPath(), '/') );
                $less = $dm->add(new \Dom\Modifier\Filter\Less($this->getSitePath(), $this->getSiteUrl(), $this->getCachePath(), $vars ));
                $less->setCompress(true);
                $less->setCacheEnabled(!$this->isRefreshCacheRequest());
            }

            if (class_exists('Dom\Modifier\Filter\Scss')) {
                /** @var \Dom\Modifier\Filter\Scss $scss */
                $vars = array(
                    'siteUrl' => rtrim(\Tk\Uri::create($this->getSiteUrl())->getPath(), '/'),
                    'dataUrl' => rtrim(\Tk\Uri::create($this->getDataUrl())->getPath(), '/'),
                    'templateUrl' => rtrim(\Tk\Uri::create($this->getTemplateUrl())->getPath(), '/') );
                $scss = $dm->add(new \Dom\Modifier\Filter\Scss($this->getSitePath(), $this->getSiteUrl(), $this->getCachePath(), $vars));
                $scss->setCompress(true);
                $scss->setCacheEnabled(!$this->isRefreshCacheRequest());
                $scss->setTimeout(\Tk\Date::DAY*14);
            }

            if ($this->isDebug()) {
                $dm->add($this->getDomFilterPageBytes());
            }
            $this->set('dom.modifier', $dm);
        }
        return $this->get('dom.modifier');
    }

    /**
     * @return \Dom\Modifier\Filter\PageBytes
     */
    public function getDomFilterPageBytes()
    {
        if (!$this->get('dom.filter.page.bytes')) {
            $obj = new \Dom\Modifier\Filter\PageBytes($this->getSitePath());
            $this->set('dom.filter.page.bytes', $obj);
        }
        return $this->get('dom.filter.page.bytes');
    }

    /**
     * getDomLoader
     *
     * @return \Dom\Loader
     */
    public function getDomLoader()
    {
        if (!$this->get('dom.loader')) {
            $dl = \Dom\Loader::getInstance()->setParams($this->all());
            $dl->addAdapter(new \Dom\Loader\Adapter\ClassPath($this->getSitePath() . $this['template.xtpl.path'], $this['template.xtpl.ext']));
            $this->set('dom.loader', $dl);
        }
        return $this->get('dom.loader');
    }

    /**
     * getEmailGateway
     *
     * @return \Tk\Mail\Gateway
     */
    public function getEmailGateway()
    {
        if (!$this->get('email.gateway')) {
            $gateway = new \Tk\Mail\Gateway($this);
            $gateway->setDispatcher($this->getEventDispatcher());
            $this->set('email.gateway', $gateway);
        }
        return $this->get('email.gateway');
    }

    /**
     * Return the back URI if available, otherwise it will return the home URI
     *
     * @return \Tk\Uri
     */
    public function getBackUrl()
    {
        if ($this->getCrumbs())
            return $this->getCrumbs()->getBackUrl();
        return $this->getSession()->getBackUrl();
    }

    /**
     * @return \Tk\Crumbs
     */
    public function getCrumbs()
    {
        if (!$this->get('crumbs')) {
            $homeUrl = '/index.html';
            $homeTitle = 'Dashboard';
            if (!$this->getAuthUser() || $this->getAuthUser()->hasType(User::TYPE_GUEST)) {
                $homeTitle = 'Home';
            } else {
                $homeUrl = \Bs\Uri::createHomeUrl($homeUrl)->getRelativePath();
            }
            $obj = \Tk\Crumbs::getInstance($homeUrl, $homeTitle);
            $this->set('crumbs', $obj);
        }
        return $this->get('crumbs');
    }


    //  -----------------------  Create methods  -----------------------


    /**
     * @return string
     */
    public function makePageTitle()
    {
        $replace = array('admin-', 'user-');
        /** @var \Tk\Request $request */
        $routeName = $this->getRequest()->attributes->get('_route');
        if ($routeName) {
            $routeName = str_replace($replace, '', $routeName);
            return ucwords(trim(str_replace('-', ' ', $routeName)));
        }
        return '';
    }

    /**
     * @param string $formId
     * @return \Tk\Form
     */
    public function createForm($formId)
    {
        $form = \Tk\Form::create($formId);
        $form->setDispatcher($this->getEventDispatcher());
        // TODO: check this does not cause issues
        $form->setRenderer($this->createFormRenderer($form));
        return $form;
    }

    /**
     * @param $form
     * @return \Tk\Form\Renderer\Dom
     */
    public function createFormRenderer($form)
    {
        $obj = \Tk\Form\Renderer\DomRenderer::create($form);
        //$obj = \Tk\Form\Renderer\Dom::create($form);
        $obj->setFieldGroupRenderer($this->getFormFieldGroupRenderer($form));
        $obj->getLayout()->setDefaultCol('col');
        return $obj;
    }

    /**
     * @param \Tk\Form $form
     * @return \Tk\Form\Renderer\FieldGroup
     */
    public function getFormFieldGroupRenderer($form)
    {
        return \Tk\Form\Renderer\FieldGroup::create($form);
    }


    /**
     * @param string $id
     * @return \Tk\Table
     */
    public function createTable($id)
    {
        $table = \Tk\Table::create($id);
        $table->setDispatcher($this->getEventDispatcher());
        return $table;
    }

    /**
     * @param \Tk\Table $table
     * @return \Tk\Table\Renderer\Dom\Table|\Tk\Table\Renderer\Iface
     */
    public function createTableRenderer($table)
    {
        $table->addCss('table table-bordered table-striped table-hover');
        $obj = \Tk\Table\Renderer\Dom\Table::create($table);
        return $obj;
    }




    // ------------------------------- Commonly Overridden ---------------------------------------

    /**
     * Return the user types available to the system
     *
     * It is important to order types from most permissions (admin) to least permissions (member/student)
     * this will be used in masquerading log-ins
     *
     * @param bool $valuesOnly (optional) return the type values with no name keys
     * @return array
     */
    public function getUserTypeList($valuesOnly = false)
    {
        $arr = $this->get('user.type.list');
        if (!is_array($arr))
            $arr = array(
                'Administrator' => 'admin',
                'Member' => 'member'
            );
        if ($valuesOnly) {
            $arr = array_values($arr);
        }
        return $arr;
    }

    /**
     * @return Db\UserMap
     */
    public function getUserMapper()
    {
        if (!$this->get('obj.mapper.user')) {
            $this->set('obj.mapper.user', Db\UserMap::create());
        }
        return $this->get('obj.mapper.user');
    }

    /**
     * Get the user for the Auth object getIdentity() method
     * @param Db\UserIface $user
     * @return int|string
     */
    public function getUserIdentity($user)
    {
        return $user->getUsername();
    }

    /**
     * @return Db\UserIface
     */
    public function createUser()
    {
        return new Db\User();
    }

    /**
     * @param int $id
     * @return null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface|Db\UserIface
     * @throws \Exception
     * @deprecated use \Bs\Config::getUserMapper()
     */
    public function findUser($id)
    {
        return $this->getUserMapper()->find($id);
    }

    /**
     * Do we have an authorized user logged in
     * @param null $user
     * @return bool
     */
    public function hasAuthUser($user = null)
    {
        if (!$user) $user = $this->getAuthUser();
        if (!$user || !$user->getType()) return false;
        return !$user->hasType(User::TYPE_GUEST);
    }

    /**
     * @return Db\UserIface|\|App\Db\User
     */
    public function getAuthUser()
    {
        return $this->get('user');
    }

    /**
     * @param Db\UserIface $user
     * @return $this
     */
    public function setAuthUser($user)
    {
        $this->set('user', $user);
        return $this;
    }

    /**
     * Return the users home|dashboard relative url
     *
     * @param \Bs\Db\User|null $user
     * @return \Tk\Uri
     */
    public function getUserHomeUrl($user = null)
    {
        if (!$user) $user = $this->getAuthUser();
        if (!$user) return \Bs\Uri::create('/login.html');
        return \Bs\Uri::createHomeUrl('/index.html', $user);
    }

    /**
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array
     * @deprecated Use getConfig()->getPermission()->getAvailablePermissionList($type);
     */
    public function getPermissionList($type = '')
    {
        return $this->getPermission()->getAvailablePermissionList($type);
    }

    /**
     * @return Permission|null
     */
    public function getPermission()
    {
        return \Bs\Db\Permission::getInstance();
    }


    /**
     *
     * @param ModelInterface $model
     * @return Status
     */
    public function createStatus($model)
    {
        return Status::create($model);
    }

    /**
     * @param ModelInterface $model
     * @return StatusMap
     */
    public function getStatusMap()
    {
        return StatusMap::create();
    }


    /**
     * getFrontController
     *
     * @return \Symfony\Component\HttpKernel\HttpKernel
     * @throws \Exception
     */
    public function getFrontController()
    {
        if (!$this->get('front.controller')) {

            $this->setupDispatcher($this->getEventDispatcher());
            $obj = new \Bs\FrontController($this->getEventDispatcher(), $this->getResolver());
            $this->set('front.controller', $obj);
        }
        return parent::get('front.controller');
    }

    /**
     * @return \Bs\Listener\AuthHandler
     */
    public function getAuthHandler()
    {
        if (!$this->get('auth.handler')) {
            $this->set('auth.handler', new \Bs\Listener\AuthHandler());
        }
        return $this->get('auth.handler');
    }

    /**
     * @return \Bs\Listener\MasqueradeHandler
     */
    public function getMasqueradeHandler()
    {
        if (!$this->get('auth.masquerade.handler')) {
            $this->set('auth.masquerade.handler', new \Bs\Listener\MasqueradeHandler());
        }
        return $this->get('auth.masquerade.handler');
    }

    /**
     * @return \Bs\Listener\PageTemplateHandler
     */
    public function getPageTemplateHandler()
    {
        if (!$this->get('page.template.handler')) {
            $this->set('page.template.handler', new \Bs\Listener\PageTemplateHandler());
        }
        return $this->get('page.template.handler');
    }

    /**
     * @return \Bs\Ui\MenuManager
     */
    public function getMenuManager()
    {
        if (!$this->get('system.menu.manager')) {
            $this->set('system.menu.manager', \Bs\Ui\MenuManager::getInstance());
        }
        return $this->get('system.menu.manager');
    }

    /**
     * @return \Symfony\Component\Console\Application
     */
    public function getConsoleApplication()
    {
        if (!$this->get('system.console')) {
            $app = new \Symfony\Component\Console\Application($this->get('site.title'), $this->get('system.info.version'));

            $dispatcher = $this->getEventDispatcher();
            $this->setupDispatcher($dispatcher);
            $app->setDispatcher($dispatcher);

            // Setup Global Console Commands
            $app->add(new \Bs\Console\Upgrade());
            $app->add(new \Bs\Console\Maintenance());
            $app->add(new \Bs\Console\DbBackup());
            $app->add(new \Bs\Console\UserPass());
            $app->add(new \Bs\Console\Migrate());
            $app->add(new \Bs\Console\CleanData());
            if ($this->isDebug()) {
                $app->add(new \Bs\Console\MakeModel());
                $app->add(new \Bs\Console\MakeTable());
                $app->add(new \Bs\Console\MakeManager());
                $app->add(new \Bs\Console\MakeForm());
                $app->add(new \Bs\Console\MakeEdit());
                $app->add(new \Bs\Console\MakeAll());
                $app->add(new \Bs\Console\Debug());
                $app->add(new \Bs\Console\Mirror());
            }

            $this->set('system.console', $app);
        }
        return $this->get('system.console');
    }

    /**
     * @return \Tk\Listener\CrumbsHandler
     */
    public function getCrumbsHandler()
    {
        if (!$this->get('handler.crumbs')) {
            $this->set('handler.crumbs', new \Tk\Listener\CrumbsHandler());
        }
        return $this->get('handler.crumbs');
    }

    /**
     * @return \Bs\Listener\StatusHandler
     */
    public function getStatusHandler()
    {
        if (!$this->get('handler.status')) {
            $this->set('handler.status', new \Bs\Listener\StatusHandler());
        }
        return $this->get('handler.status');
    }

    /**
     * @return \Bs\Listener\InstallHandler
     */
    public function getInstallHandler()
    {
        if (!$this->get('handler.installer')) {
            $this->set('handler.installer', new \Bs\Listener\InstallHandler());
        }
        return $this->get('handler.installer');
    }

    /**
     * @param string $customDataPath
     * @return array
     */
    public function getElfinderPath($customDataPath = '/media')
    {
        $dataPath = $this->getDataPath() . $customDataPath;
        $dataUrl = $this->getDataUrl() . $customDataPath;
        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0777, true);
        }
        if (!is_dir($dataPath . '/.trash/')) {
            mkdir($dataPath . '/.trash/', 0777, true);
        }
        return array($dataPath, $dataUrl);
    }

    /**
     * @param string $templatePath
     * @return Page
     */
    public function getPage($templatePath = '')
    {
        if (!$this->get('controller.page')) {
            try {
                $page = $this->createPage($templatePath);
            } catch (\Exception $e) { \Tk\Log::error($e->__toString()); }
            // Add a template ClassPath adapter to the DomLoader
            $this->getDomLoader()->addAdapter(new \Dom\Loader\Adapter\ClassPath(dirname($page->getTemplatePath()).'/xtpl',
                $this->get('template.xtpl.ext'), false
            ));
            $this->set('controller.page', $page);
        }
        return $this->get('controller.page');
    }

    /**
     * @param string $templatePath
     * @return Page
     */
    public function createPage($templatePath = '')
    {
        return new Page($templatePath);
    }

    /**
     * validate a filename and see if we think it is a script or a harmful file
     * to upload to the server
     *
     * @param string $filename
     * @return bool
     */
    public static function validateFile($filename)
    {
        $filename = basename($filename);
        $ext = trim(\Tk\File::getExtension($filename), '.');
        // TODO: make these configurable in the config.php
        $exclude = array('exe', 'com', 'php', 'perl', 'php5', 'php4', 'html', 'css', 'js');
        $include = array();

        if (count($exclude)) {
            if (in_array($ext, $exclude)) {
                return false;
            }
        }
        if (count($include)) {
            return in_array($ext, $include);
        }
        return true;
    }
}