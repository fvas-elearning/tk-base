<?php
namespace Bs\Listener;

use Bs\Db\User;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PageTemplateHandler implements Subscriber
{
    use ConfigTrait;

    protected function substitution($str)
    {
        $data = array();
        $user = $this->getConfig()->getAuthUser();
        if ($user) {
            $data['user.id'] = $user->getId();
            $data['user.name'] = $user->getName();
            $data['user.email'] = $user->getEmail();
            $data['user.hash'] = $user->getHash();
        }

        $r = preg_replace_callback('/{{([a-zA-Z0-9_\-\.]*)}}/', function ($regs) use ($data) {
            if (isset($data[$regs[1]]))
                return $data[$regs[1]];
            return '';
        }, $str);
        return $r;
    }

    /**
     * @param \Tk\Event\Event $event
     */
    public function showPage(\Tk\Event\Event $event)
    {
        /** @var \Bs\Controller\Iface $controller */
        $controller = \Tk\Event\Event::findControllerObject($event);
        $config = \Bs\Config::getInstance();
        //if (!$controller->getPage()->getTemplatePath()) return;

        $template = $controller->getPage()->getTemplate();

        if (trim($this->getConfig()->get('site.meta.keywords'))) {
            $template->appendMetaTag('keywords', $this->substitution($this->getConfig()->get('site.meta.keywords')));
        }
        if (trim($this->getConfig()->get('site.meta.description'))) {
            $template->appendMetaTag('description', $this->substitution($this->getConfig()->get('site.meta.description')));
        }

        if (trim($this->getConfig()->get('site.global.js'))) {
            $template->appendJs($this->substitution($this->getConfig()->get('site.global.js')), array('data-jsl-priority' => -900));
        }
        if (trim($this->getConfig()->get('site.global.css'))) {
            $template->appendCss($this->substitution($this->getConfig()->get('site.global.css')));
        }

        if ($this->getConfig()->get('site.title')) {
            $v = '';
            if ($config->getVersion()) {
                $v = ' <small>v' . $config->getVersion().'</small>';
            }
            $template->insertHtml($config->get('template.var.page.site-short-title'), $this->getConfig()->get('site.short.title') . $v);
            $template->setAttr($config->get('template.var.page.site-short-title'), 'title', $this->getConfig()->get('site.title'));
            $template->insertText($config->get('template.var.page.site-title'), $this->getConfig()->get('site.title'));
            $template->setTitleText(trim($template->getTitleText() . ' - ' . $this->getConfig()->get('site.title'), '- '));
        }

        $rel = \Tk\Uri::create()->getRelativePath();
        $siteUrl = $this->getConfig()->getSiteUrl();
        $dataUrl = $this->getConfig()->getDataUrl();
        $templateUrl = $this->getConfig()->getTemplateUrl();
        $roleType = '';
        if ($this->getConfig()->getAuthUser() && $this->getConfig()->getAuthUser()->getType() != User::TYPE_GUEST) {
            $roleType = $this->getConfig()->getAuthUser()->getType();
        }
        $fw = $this->getConfig()->get('css.framework');
        $bs4 = $this->isBootstrap4() ? 'true' : 'false';
        $isDebug = $this->getConfig()->isDebug() ? 'true' : 'false';

        $js = <<<JS
var config = {
  relativePath : '$rel',
  siteUrl :      '$siteUrl',
  dataUrl :      '$dataUrl',
  templateUrl:   '$templateUrl',
  cssFramework:  '$fw',
  isBootstrap4:  $bs4,               // deprecated Use 'cssFramework'
  debug:         $isDebug,
  roleType:      '$roleType',
  jquery: {
    dateFormat:  'dd/mm/yy'    
  },
  bootstrap: {
    dateFormat:  'dd/mm/yyyy'    
  }
};
JS;
        $template->appendJs($js, array('data-jsl-priority' => -1000));

        // Add a unique class to the page body tag for page/js based scripts
        //  eg for the relative path "user/dashboard.html" or "user/dashboard" the class would be "user-dashboard"
        $relativePath = trim(\Tk\Uri::create()->getRelativePath(), '/\\');
        if (!$relativePath) $relativePath = trim($config->get('url.auth.home'), '/\\');
        $relativePath = substr($relativePath, 0, strrpos($relativePath, "."));
        $cssClass = preg_replace('/[^0-9a-z_-]/i', '-', $relativePath);
        if ($template->getBodyElement()) {
            $template->addCss($template->getBodyElement(), 'pg-'.$cssClass);
        }


        // Set page title
        if ($controller->getPageTitle() && $controller->isShowTitle()) {
            $template->setTitleText(trim($controller->getPageTitle() . ' - ' . $template->getTitleText(), '- '));
            $template->insertText($config->get('template.var.page.page-header'), $controller->getPageTitle());
            $template->setVisible($config->get('template.var.page.page-header'));
        }

        if ($this->getConfig()->isDebug()) {
            $template->setTitleText(trim('DEBUG: ' . $template->getTitleText(), '- '));
            $template->setVisible('debug');
        }

        // ---- tk-base specific calls ----
        if (\Tk\AlertCollection::hasMessages()) {
            $template->appendTemplate($config->get('template.var.page.alerts'), \Tk\AlertCollection::getInstance()->show());
            $template->setVisible($config->get('template.var.page.alerts'));
        }

        $template->setAttr($config->get('template.var.page.user-url'), 'href', $this->getConfig()->getUserHomeUrl());
        if ($this->hasAuthUser()) {
            $template->insertText($config->get('template.var.page.user-name'), $this->getConfig()->getAuthUser()->getName());
            $template->insertText($config->get('template.var.page.username'), $this->getConfig()->getAuthUser()->getUsername());
            $i = strpos($this->getConfig()->getAuthUser()->getUsername(), '@');
            if ($i > 0) {
                $template->insertText($config->get('template.var.page.username'), substr($this->getConfig()->getAuthUser()->getUsername(), 0, $i));
            }

            $template->setAttr($config->get('template.var.page.user-url'), 'href', $this->getConfig()->getUserHomeUrl());
            $template->setVisible($config->get('template.var.page.logout'));
//            $template->addCss($template->getBodyElement(), $this->getConfig()->getAuthUser()->getType());
        } else {
            $template->setVisible($config->get('template.var.page.login'));
        }

    }


    /**
     * @return bool
     */
    public function isBootstrap4()
    {
        return $this->getConfig()->get('css.framework') == 'bs4';
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_SHOW => array('showPage', 10)
        );
    }

}