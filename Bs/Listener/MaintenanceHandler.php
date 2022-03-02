<?php
namespace Bs\Listener;

use Bs\Db\User;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MaintenanceHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * kernel.controller
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     * @throws \Exception
     */
    public function onController($event)
    {
        /** @var \Tk\Controller\Iface $controller */
        $controller = \Tk\Event\Event::findControllerObject($event);
        if (\Tk\Uri::create()->basename() != 'login.html' && !$controller instanceof \Bs\Controller\Login && !$controller instanceof \Bs\Controller\Logout && !$controller instanceof \Bs\Controller\Maintenance && $this->getConfig()->get('site.maintenance.enabled')) {
            if ($this->getConfig()->getAuthUser()) {
                if ($this->getConfig()->getAuthUser()->hasType(User::TYPE_ADMIN)) return;
                if ($this->getConfig()->getMasqueradeHandler()->getMasqueradingUser() &&
                    $this->getConfig()->getMasqueradeHandler()->getMasqueradingUser()->hasType(User::TYPE_ADMIN))
                    return;
            }
            $maintController = new \Bs\Controller\Maintenance();
            $event->setController(array($maintController, 'doDefault'));
        }
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function showPage($event)
    {
        if (!$this->getConfig()->get('site.maintenance.enabled')) return;
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \Bs\Controller\Iface && !$controller instanceof \Bs\Controller\Maintenance) {
            $page = $controller->getPage();
            if (!$page) return;
            $template = $page->getTemplate();

            $html = <<<HTML
<div class="tk-ribbon tk-ribbon-danger" style="z-index: 99999"><span>Maintenance</span></div>
HTML;
            $template->prependHtml($template->getBodyElement(), $html);
            $template->addCss($template->getBodyElement() ,'tk-ribbon-box');
        }
    }

    /**
     * Use this to pragmatically enable/disable maintenance
     *
     * @param bool $b
     * @param string $message
     * @throws \Tk\Db\Exception
     */
    public static function enableMaintenanceMode($b = true, $message = '')
    {
        $data = \Tk\Db\Data::create();
        if ($b) {
            $data->set('site.maintenance.enabled', 'site.maintenance.enabled');
            if ($message)
                $data->set('site.maintenance.message', $message);
        } else {
            $data->set('site.maintenance.enabled', '');
        }
        $data->save();
    }


    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER =>  array('onController', 15),
            \Tk\PageEvents::CONTROLLER_SHOW => 'showPage'
        );
    }
}