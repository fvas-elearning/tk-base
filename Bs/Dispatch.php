<?php
namespace Bs;


use Bs\Listener\InstallHandler;
use Bs\Listener\StatusHandler;
use Tk\ConfigTrait;
use Bs\Listener\MailHandler;
use Bs\Listener\MaintenanceHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpFoundation\RequestStack;
use Tk\Listener\ActionPanelHandler;
use Tk\Listener\ExceptionEmailListener;
use Tk\Listener\ExceptionListener;
use Tk\Listener\JsonExceptionListener;
use Tk\Listener\LogExceptionListener;
use Tk\Listener\NotFoundLogListener;
use Tk\Listener\PageHandler;
use Tk\Listener\ResponseHandler;
use Tk\Listener\ShutdownHandler;
use Tk\Listener\StartupHandler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Dispatch
{
    use ConfigTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher = null;


    /**
     * @param  EventDispatcherInterface $dispatcher
     */
    public function __construct($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->init();
    }

    /**
     * @param  EventDispatcherInterface $dispatcher
     * @return Dispatch
     */
    public static function create($dispatcher)
    {
        $obj = new static($dispatcher);
        return $obj;
    }

    /**
     * @return  EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     */
    public function initObjects()
    {
        // Init the plugins
        $this->getConfig()->getPluginFactory();
        // Initiate the email gateway
        $this->getConfig()->getEmailGateway();
        // Initiate the Dom\Template loader object
        $this->getConfig()->getDomLoader();

    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        $logger = $this->getConfig()->getLog();
        $request = $this->getConfig()->getRequest();
        $dispatcher = $this->getDispatcher();

        $this->initObjects();

        // TODO: Maybe we no longer need the cli check, have a look
        if (!$this->getConfig()->isCli()) {

            $context = new Routing\RequestContext();
            $matcher = new Routing\Matcher\UrlMatcher($this->getConfig()->getRouteCollection(), $context);
            $requestStack = new RequestStack();
            $dispatcher->addSubscriber(new HttpKernel\EventListener\RouterListener($matcher, $requestStack));
            //$dispatcher->addSubscriber(new \Tk\Listener\RouteListener($matcher));

            $dispatcher->addSubscriber(new PageHandler($dispatcher));
            $dispatcher->addSubscriber(new ResponseHandler($this->getConfig()->getDomModifier()));
        }


        // Tk Listeners
        $dispatcher->addSubscriber(new StartupHandler($logger, $request, $this->getConfig()->getSession()));

        if ($this->getConfig()->get('system.email.exception')) {
            $dispatcher->addSubscriber(new ExceptionEmailListener(
                $this->getConfig()->getEmailGateway(),
                $this->getConfig()->get('system.email.exception'),
                $this->getConfig()->get('site.title')
            ));
        }

        // Exception Handling, log first so we can grab the session log
        $dispatcher->addSubscriber(new LogExceptionListener($logger, true));

        // Log not found URI's for future inspections
        if ($this->getConfig()->getLogPath()) {
            $notFoundLogPath = dirname($this->getConfig()->getLogPath()) . '/404.log';
            if (!is_file($notFoundLogPath)) {
                file_put_contents($notFoundLogPath, '');
            }
            $log = new Logger('notfound');
            $handler = new StreamHandler($notFoundLogPath, Logger::NOTICE);
            $log->pushHandler($handler);
            $dispatcher->addSubscriber(new NotFoundLogListener($log));
        }

        if (preg_match('|^/ajax/.+|', $request->getTkUri()->getRelativePath())) { // If ajax request
            $dispatcher->addSubscriber(new JsonExceptionListener($this->getConfig()->isDebug()));
        } else {
            $dispatcher->addSubscriber(new ExceptionListener($this->getConfig()->isDebug(), 'Bs\Controller\Error'));
        }

        $sh = new ShutdownHandler($logger, $this->getConfig()->getScriptTime());
        $sh->setPageBytes($this->getConfig()->getDomFilterPageBytes());
        $dispatcher->addSubscriber($sh);

        // App Listeners
        $dispatcher->addSubscriber($this->getConfig()->getInstallHandler());
        $dispatcher->addSubscriber(new ActionPanelHandler());
        $dispatcher->addSubscriber(new MailHandler());

        if ($this->getConfig()->getStatusHandler())
            $dispatcher->addSubscriber($this->getConfig()->getStatusHandler());
        if ($this->getConfig()->getAuthHandler())
            $dispatcher->addSubscriber($this->getConfig()->getAuthHandler());
        if ($this->getConfig()->getMasqueradeHandler())
            $dispatcher->addSubscriber($this->getConfig()->getMasqueradeHandler());
        if ($this->getConfig()->getPageTemplateHandler())
            $dispatcher->addSubscriber($this->getConfig()->getPageTemplateHandler());
        if ($this->getConfig()->getCrumbsHandler())
            $dispatcher->addSubscriber($this->getConfig()->getCrumbsHandler());

        $dispatcher->addSubscriber(new MaintenanceHandler());

    }

}