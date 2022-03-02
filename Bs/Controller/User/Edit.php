<?php
namespace Bs\Controller\User;

use Bs\Db\User;
use Tk\Request;
use Dom\Template;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \Bs\Controller\AdminEditIface
{
    /**
     * Setup the controller to work with users of this role
     * @var string
     */
    protected $targetType = User::TYPE_MEMBER;

    /**
     * @var \Bs\Db\User
     */
    protected $user = null;


    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Member Edit');
    }

    /**
     * @return \Bs\Db\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param \Tk\Request $request
     * @param string $targetType
     * @throws \Exception
     */
    public function doDefaultType(\Tk\Request $request, $targetType)
    {
        $this->targetType = $targetType;
        switch($targetType) {
            case \Bs\Db\User::TYPE_ADMIN:
                $this->setPageTitle('Admin Edit');
                break;
        }
        $this->doDefault($request);
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->user = $this->getConfig()->createUser();
        $this->user->setType($this->targetType);
        if ($request->get('userId')) {
            $this->user = $this->getConfig()->getUserMapper()->find($request->get('userId'));
        }

        $this->setForm(\Bs\Form\User::create()->setModel($this->user));
        $this->initForm($request);
        $this->getForm()->execute($request);
    }

    public function initForm(\Tk\Request $request)
    {
        if ($this->user->getId() == 1 || !$this->getConfig()->getAuthUser()->isAdmin()) {
            $this->getForm()->appendField(new \Tk\Form\Field\Html('type', $this->user->getType()))
                ->setAttr('disabled')->addCss('form-control disabled')->setAttr('disabled')
                ->addCss('form-control disabled')->setTabGroup('Details');

            $this->getForm()->appendField(new \Tk\Form\Field\Html('username'))->setAttr('disabled')
                ->addCss('form-control disabled')->setTabGroup('Details');
        }

    }

    /**
     * @throws \Exception
     */
    public function initActionPanel()
    {
        if ($this->user->getId() && $this->getConfig()->getMasqueradeHandler()->canMasqueradeAs($this->getAuthUser(), $this->user)) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Masquerade',
                \Bs\Uri::create()->reset()->set(\Bs\Listener\MasqueradeHandler::MSQ, $this->user->getHash()), 'fa fa-user-secret'))
                ->setAttr('data-confirm', 'You are about to masquerade as the selected user?')->addCss('tk-masquerade');
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();
        
        // Render the form
        $template->appendTemplate('form', $this->form->show());
        
        if ($this->user->getId())
            $template->setAttr('form', 'data-panel-title', $this->user->getName() . ' - [ID ' . $this->user->getId() . ']');
        
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
<div class="tk-panel" data-panel-icon="fa fa-user" var="form"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}