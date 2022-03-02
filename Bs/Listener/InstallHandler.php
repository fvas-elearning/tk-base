<?php
namespace Bs\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class InstallHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws \Exception
     */
    public function onSystemInit($event)
    {
        $list = $this->getConfig()->getUserMapper()->findFiltered(array());
        if (!$list->count()) {
            if (\Tk\Uri::create()->getRelativePath() != '/install.html') {
                // clear all sessions
                $query = sprintf('DELETE FROM %s WHERE 1', \Tk\Session\Adapter\Database::$DB_TABLE);
                $this->getDb()->query($query);

                \Tk\Uri::create('/install.html')->redirect();
            }
        } else {
            if (\Tk\Uri::create()->getRelativePath() == '/install.html') {
                \Tk\Uri::create('/index.html')->redirect();
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onSystemInit', 50)
        );
    }

}