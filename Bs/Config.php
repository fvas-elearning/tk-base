<?php
namespace Bs;

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
        $this->set('system.lib.base.path', $this['system.vendor.path'] . '/ttek/tk-base');
    }

    /**
     * Load the site route config files
     * @throws \Tk\Db\Exception
     * @throws \Tk\Plugin\Exception
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
     * @throws \Tk\Db\Exception
     * @throws \Tk\Plugin\Exception
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
     * @throws \Tk\Exception
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
     * Hash a string using the system config set algorithm
     *
     * @link http://php.net/manual/en/function.hash.php
     * @param string $str
     * @param string $salt (optional)
     * @param string $algo Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
     *
     * @return string
     */
    public function hash($str, $salt = '', $algo = 'md5')
    {
        if ($salt) $str .= $salt;
        if ($this->get('hash.function'))
            $algo = $this->get('hash.function');
        return hash($algo, $str);
    }

    /**
     * Create a random password
     *
     * @param int $length
     * @return string
     */
    public function generatePassword($length = 8)
    {
        $chars = '234567890abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $i = 0;
        $password = '';
        while ($i <= $length) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
            $i++;
        }
        return $password;
    }

    /**
     * getAuth
     *
     * @return \Tk\Auth
     * @throws \Tk\Db\Exception
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
            $obj = \Tk\Request::create();
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
     * @throws \Tk\Db\Exception
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
     * @throws \Tk\Db\Exception
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
     * @return \Tk\Event\Dispatcher
     */
    public function getEventDispatcher()
    {
        if (!$this->get('event.dispatcher')) {
            $log = new \Psr\Log\NullLogger();
            if ($this->get('event.dispatcher.log')) {
                $log = $this->getLog();
            }
            $obj = new \Tk\Event\Dispatcher($log);
            $this->set('event.dispatcher', $obj);
        }
        return $this->get('event.dispatcher');
    }

    /**
     * @param \Tk\Event\Dispatcher $dispatcher
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function setupDispatcher($dispatcher)
    {
        \Bs\Dispatch::create($dispatcher);
    }

    /**
     * getResolver
     *
     * @return \Tk\Controller\Resolver
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
     * @return \Tk\Routing\RouteCollection
     */
    public function getRouteCollection()
    {
        if (!$this->get('route.collection')) {
            $obj = new \Tk\Routing\RouteCollection();
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
     * @param string $name
     * @return mixed|\Tk\Db\Pdo
     */
    public function getDb($name = 'db')
    {
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
     * @throws \Tk\Exception
     */
    public function getDomModifier()
    {
        if (!$this->get('dom.modifier')) {
            $dm = new \Dom\Modifier\Modifier();
            $dm->add(new \Dom\Modifier\Filter\UrlPath($this->getSiteUrl()));
            $dm->add(new \Dom\Modifier\Filter\JsLast());
            if (class_exists('Dom\Modifier\Filter\Less')) {
                /** @var \Dom\Modifier\Filter\Less $less */
                $less = $dm->add(new \Dom\Modifier\Filter\Less($this->getSitePath(), $this->getSiteUrl(), $this->getCachePath(),
                    array('siteUrl' => $this->getSiteUrl(), 'dataUrl' => $this->getDataUrl(), 'templateUrl' => $this->getTemplateUrl())));
                $less->setCompress(true);
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
            $dl->addAdapter(new \Dom\Loader\Adapter\DefaultLoader());
            $dl->addAdapter(new \Dom\Loader\Adapter\ClassPath($this['template.xtpl.path'], $this['template.xtpl.ext']));
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
     * @throws \Tk\Db\Exception
     */
    public function getBackUrl()
    {
        if ($this->getCrumbs())
            return $this->getCrumbs()->getBackUrl();
        return $this->getSession()->getBackUrl();
    }

    /**
     * @param string $homeTitle
     * @param string $homeUrl
     * @return \Tk\Crumbs
     */
    public function getCrumbs($homeTitle=null, $homeUrl=null)
    {
        if (!$this->get('crumbs')) {
            if ($homeTitle)
                \Tk\Crumbs::$homeTitle = $homeTitle;
            if ($homeUrl)
                \Tk\Crumbs::$homeUrl = $homeUrl;
            $obj = \Tk\Crumbs::getInstance();
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
        $routeName = $this->getRequest()->getAttribute('_route');
        if ($routeName) {
            $routeName = str_replace($replace, '', $routeName);
            return ucwords(trim(str_replace('-', ' ', $routeName)));
        }
        return '';
    }

    /**
     * Get the page role, if multiple roles return the first one.
     *
     * @return string
     */
    public function getPageRole()
    {
        $role = $this->getRequest()->getAttribute('role');
        if (is_array($role)) $role = current($role);
        return $role;
    }

    /**
     * @param string $formId
     * @param string $method
     * @param string|null $action
     * @return \Tk\Form
     */
    public function createForm($formId, $method = \Tk\Form::METHOD_POST, $action = null)
    {
        $form = \Tk\Form::create($formId, $method, $action);
        $form->setEnableRequiredAttr();
        return $form;
    }

    /**
     * @param $form
     * @return \Tk\Form\Renderer\Dom
     */
    public function createFormRenderer($form)
    {
        $obj = new \Tk\Form\Renderer\Dom($form);
        return $obj;
    }

    /**
     * @param string $id
     * @param array $params
     * @param null|\Tk\Request $request
     * @param null|\Tk\Session $session
     * @return \Tk\Table
     */
    public function createTable($id, $params = array(), $request = null, $session = null)
    {
        $form = \Tk\Table::create($id, $params, $request, $session);
        return $form;
    }

    /**
     * @param \Tk\Table $table
     * @return \Tk\Table\Renderer\Dom\Table
     */
    public function createTableRenderer($table)
    {
        $obj = \Tk\Table\Renderer\Dom\Table::create($table);
        return $obj;
    }

    /**
     * @param string $title
     * @param string $icon
     * @param bool $withBack
     * @return \Tk\Ui\Admin\ActionPanel
     * @deprecated No longer used I think
     */
    public function createActionPanel($title = 'Actions', $icon = 'fa fa-cogs', $withBack = true)
    {
        \Tk\Log::notice('\Bs\Config::creteActionPanel() called. Deprecated Function!!!');
        $ap = \Tk\Ui\Admin\ActionPanel::create($title, $icon);
        if ($withBack) {
            $ap->add(\Tk\Ui\Button::create('Back', 'javascript: window.history.back();', 'fa fa-arrow-left'))
                ->addCss('btn-default btn-once back');
        }
        return $ap;
    }

    /**
     * @return mixed
     */
    public function getAdminActionPanel()
    {
        if (!$this->get('admin.action.panel')) {
            $obj = \Tk\Ui\Admin\ActionPanel::create('Actions', 'fa fa-cogs');
            $obj->add(\Tk\Ui\Button::create('Back', 'javascript: window.history.back();', 'fa fa-arrow-left'))
                ->addCss('btn-default btn-once back');
            $this->set('admin.action.panel', $obj);
        }
        return $this->get('admin.action.panel');
    }



    // ------------------------------- Commonly Overridden ---------------------------------------

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
     * @return Db\User
     */
    public function createUser()
    {
        return new Db\User();
    }

    /**
     * @param int $id
     * @return null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Tk\Db\Exception
     * @deprecated use \Bs\Config::getUserMapper()
     */
    public function findUser($id)
    {
        return $this->getUserMapper()->find($id);
    }

    /**
     * @return Db\User
     */
    public function getUser()
    {
        return $this->get('user');
    }

    /**
     * @param Db\User $user
     * @return $this
     */
    public function setUser($user)
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
        if (!$user) $user = $this->getUser();
        if ($user) {
            if ($user->isAdmin())
                return \Tk\Uri::create('/admin/index.html');
            if ($user->isUser())
                return \Tk\Uri::create('/user/index.html');
        }
        return \Tk\Uri::create('/index.html');
    }


    /**
     * getFrontController
     *
     * @return \Tk\Kernel\HttpKernel
     * @throws \Tk\Exception
     */
    public function getFrontController()
    {
        if (!$this->get('front.controller')) {
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
     * @return \Bs\Listener\PageTemplateHandler
     */
    public function getCrumbsHandler()
    {
        if (!$this->get('handler.crumbs')) {
            $this->set('handler.crumbs', new \Bs\Listener\CrumbsHandler());
        }
        return $this->get('handler.crumbs');
    }

    /**
     * @param \Tk\Controller\Iface $controller
     * @return Page
     */
    public function createPage($controller)
    {
        $page = new Page();
        //$page->setController($controller);
//        if (!$controller->getPageTitle()) {     // Set a default page Title for the crumbs
//            $controller->setPageTitle($controller->getDefaultTitle());
//        }
        return $page;
    }


}