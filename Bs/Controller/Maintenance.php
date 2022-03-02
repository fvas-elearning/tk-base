<?php
namespace Bs\Controller;

use Tk\Request;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Maintenance extends \Bs\Controller\Iface
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Maintenance');
    }


    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        if (!$this->getConfig()->get('site.maintenance.enabled')) {
            if ($this->getConfig()->getAuthUser()) {
                $this->getConfig()->getUserHomeUrl()->redirect();
            } else {
                \Tk\Uri::create($this->getConfig()->get('url.auth.home'))->redirect();
            }
        }
    }

    /**
     * For compatibility
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $tpl = $this->getPage()->getTemplate();

        if ($this->getConfig()->get('site.maintenance.message')) {
            $tpl->appendHtml('message', $this->getConfig()->get('site.maintenance.message'));
            $tpl->setVisible('message');
        } else {
            $tpl->setVisible('default-message');
        }
        return $template;
    }


    /**
     * @return \Tk\Controller\Page|\Bs\Page
     */
    public function getPage()
    {
        if (!$this->page) {
            $this->page = $this->getConfig()->getPage($this->getConfig()->getSitePath() . $this->getConfig()->get('template.maintenance'));
        }
        return parent::getPage();
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
    
}