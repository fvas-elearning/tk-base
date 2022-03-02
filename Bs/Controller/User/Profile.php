<?php
namespace Bs\Controller\User;

use Bs\Db\Permission;
use Bs\Db\User;
use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Profile extends \Bs\Controller\AdminEditIface
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('My Profile');
        //$this->getCrumbs()->reset();
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->setForm($this->createForm());

        if ($this->getForm()->getField('active'))
            $this->getForm()->removeField('active');
        if ($this->getForm()->getField('username'))
            $this->getForm()->getField('username')->setAttr('disabled')->addCss('form-control disabled')->removeCss('tk-input-lock');
        if ($this->getForm()->getField('uid'))
            $this->getForm()->getField('uid')->setAttr('disabled')->addCss('form-control disabled')->removeCss('tk-input-lock');
        if ($this->getForm()->getField('email'))
            $this->getForm()->getField('email')->setAttr('disabled')->addCss('form-control disabled')->removeCss('tk-input-lock');
        if ($this->getForm()->getField('permission')) {
            if (!$this->getAuthUser()->hasPermission(Permission::MANAGE_SITE) && $this->getAuthUser()->getId() != 1)
                $this->getForm()->getField('permission')->setAttr('readonly')->setAttr('disabled');
        }

        $this->getForm()->execute();
    }
    /**
     * @return \Bs\Form\User
     */
    protected function createForm()
    {
        return \Bs\Form\User::create()->setModel($this->getConfig()->getAuthUser());
    }

    /**
     * @return Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->form->show());
        if ($this->getConfig()->getAuthUser()->getId())
            $template->setAttr('panel', 'data-panel-title', $this->getConfig()->getAuthUser()->getName() . ' - [ID ' . $this->getConfig()->getAuthUser()->getId() . ']');

        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-user" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}
