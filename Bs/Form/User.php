<?php
namespace Bs\Form;

use Bs\Db\Permission;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   $form = new User::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 *
 * @author Mick Mifsud
 * @created 2018-11-19
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class User extends \Bs\FormIface
{
    /**
     * Setup the controller to work with users of this role
     * @var string
     */
    protected $targetType = '';

    /**
     * @throws \Exception
     */
    public function init()
    {
        $layout = $this->getRenderer()->getLayout();
        $layout->removeRow('title', 'col-1');
        $layout->removeRow('nameFirst', 'col');
        $layout->removeRow('nameLast', 'col');
        $layout->removeRow('phone', 'col');
        $layout->removeRow('position', 'col');


        $tab = 'Details';
//        if ($this->getAuthUser()->isAdmin()) {
//            $list = $this->getConfig()->getUserTypeList();
//            $this->appendField(new Field\Select('type', $list))->prependOption('-- Select --', '')->setTabGroup($tab)->setRequired(true);
//        }

        $this->appendField(Field\Select::createSelect('title', \Bs\Db\User::getTitleList($this->getUser()->getTitle()))->prependOption('-- Select --') )->setTabGroup($tab);
        $this->appendField(new Field\Input('nameFirst'))->setLabel('First Name')->setTabGroup($tab)->setRequired(true);
        $this->appendField(new Field\Input('nameLast'))->setLabel('Last Name(s)')->setTabGroup($tab)->setRequired(true);
        $f = $this->appendField(new Field\Input('username'))->setTabGroup($tab)->setRequired(true);
        if ($this->getUser()->getId())
            $f->addCss('tk-input-lock');
        $f = $this->appendField(new Field\Input('email'))->setTabGroup($tab)->setRequired(true);
        if ($this->getUser()->getId())
            $f->addCss('tk-input-lock');
        $this->appendField(new Field\Input('phone'))->setTabGroup($tab)->setNotes('Enter a phone number that you can be contacted on directly.');
        $this->appendField(new Field\Input('credentials'))->setTabGroup($tab)->setNotes('Enter your professional credentials. EG: BVSc, MPhil, MANZCVSc, Dip ACV');
        $this->appendField(new Field\Input('position'))->setTabGroup($tab)->setNotes('Enter your work position/Department. EG: Senior Lecturer');
        $this->appendField(Field\Checkbox::create('active')->setCheckboxLabel('Enable User Login'))->setTabGroup($tab);


        $tab = 'Password';

        $this->setAttr('autocomplete', 'off');
        $f = $this->appendField(new Field\Password('newPassword'))->setAttr('placeholder', 'Click to edit')
            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
            ->setTabGroup($tab);
        if (!$this->getUser()->getId())
            $f->setRequired(true);

        $f = $this->appendField(new Field\Password('confPassword'))->setAttr('placeholder', 'Click to edit')
            ->setAttr('readonly')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")
            ->setNotes('Change this users password.')->setTabGroup($tab);
        if (!$this->getUser()->getId())
            $f->setRequired(true);

        if ($this->getUser()->getId() == $this->getConfig()->getAuthUser()->getId()) {
            $this->remove('active');
        }

        $tab = 'Permissions';
        $list = $this->getConfig()->getPermission()->getAvailablePermissionList($this->getUser()->getType());
        if (count($list)) {
            $this->appendField(new Field\CheckboxGroup('permission', $list))->setLabel('Permission List')->setTabGroup($tab);
        }

        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load($this->getConfig()->getUserMapper()->unmapForm($this->getUser()));
        if ($this->getUser()->getId() && $this->getField('permission')) {
            $this->load(array('permission' => $this->getUser()->getPermissions()));
        }
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        $this->getConfig()->getUserMapper()->mapForm($form->getValues(), $this->getUser());

        // Password validation needs to be here
        if ($form->getField('newPassword')) {
            if ($form->getFieldValue('newPassword')) {
                if ($form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
                    $form->addFieldError('newPassword', 'Passwords do not match.');
                    $form->addFieldError('confPassword');
                }
            } else {
                if (!$this->getUser()->getId()) {
                    $form->addFieldError('newPassword', 'Please enter a password for this new user.');
                }
            }
        }

        // Just a small check to ensure the user down not change their own role
        if ($this->getUser()->getId() == $this->getConfig()->getAuthUser()->getId() && $this->getUser()->getType() != $this->getConfig()->getAuthUser()->getType()) {
            $form->addError('You cannot change your own user type information.');
        }
        if ($this->getUser()->getId() == $this->getConfig()->getAuthUser()->getId() && !$this->getUser()->isActive()) {
            $form->addError('You cannot change your own active status.');
        }

        $form->addFieldErrors($this->getUser()->validate());
        if ($form->hasErrors()) {
            return;
        }

        if ($form->getFieldValue('newPassword')) {
            $this->getUser()->setNewPassword($form->getFieldValue('newPassword'));
        }

        $this->getUser()->save();

        if ($form->getField('permission')) {
            $this->getUser()->removePermission();
            $this->getUser()->addPermission($form->getFieldValue('permission'));
        }

        // Keep the admin account available and working. (hack for basic sites)
        if ($this->getUser()->getId() == 1 && $this->getUser()->getUsername() == 'admin') {
            $this->getUser()->setActive(true);
            $this->getUser()->setUsername('admin');
            $this->getUser()->setType(\Bs\Db\User::TYPE_ADMIN);
        }


        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('userId', $this->getUser()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Bs\Db\User
     */
    public function getUser()
    {
        return $this->getModel();
    }

    /**
     * @param \Bs\Db\User $user
     * @return $this
     */
    public function setUser($user)
    {
        return $this->setModel($user);
    }

    /**
     * @return string
     */
    public function getTargetType()
    {
        return $this->targetType;
    }

    /**
     * @param string $targetType
     * @return User
     */
    public function setTargetType($targetType)
    {
        $this->targetType = $targetType;
        return $this;
    }

}
