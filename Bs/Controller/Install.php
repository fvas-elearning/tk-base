<?php
namespace Bs\Controller;

use Tk\Form;
use Tk\Form\Field\Input;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Install extends Iface
{
    /**
     * @var null|\Tk\Form
     */
    protected $form = null;

    /**
     * @var null|\Tk\Db\Data
     */
    protected $data = null;

    /**
     * @var null|\Bs\Db\User
     */
    protected $user = null;

    /**
     * Install constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Install');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->data = \Tk\Db\Data::create();
        $this->user = $this->getConfig()->createUser();
        $this->user->setType(\Bs\Db\User::TYPE_ADMIN);
        $this->user->setUsername('admin');
        $this->user->setName('Administrator');

        $this->form = $this->getConfig()->createForm('install');
        $this->form->setDispatcher($this->getConfig()->getEventDispatcher());
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));

        $layout = $this->form->getRenderer()->getLayout();


        $this->form->setFieldset('Site Details');
        $this->form->appendField(new Input('site.title'))->setLabel('Site Title')->setRequired(true);
        $this->form->appendField(new Input('site.short.title'))->setLabel('Short Title')->setRequired(true);
        $this->form->appendField(new Input('site.email'))->setLabel('Site Email')->setRequired(true);

        $this->form->setFieldset('Admin User');
        //$this->form->appendField(new Input('name'))->setRequired(true);
        $this->form->appendField(new Input('username'))->setRequired(true);
        $this->form->appendField(new Form\Field\Password('newPassword'))->setRequired(true);
        $this->form->appendField(new Form\Field\Password('confPassword'))->setRequired(true);

        $this->form->load($this->getConfig()->getUserMapper()->unmapForm($this->user));
        $this->form->load($this->data->all());
        $this->form->appendField(new Form\Event\Submit('save', array($this, 'doSubmit')));

        $this->form->execute($request);

    }

    /**
     * @param Form $form
     * @param Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        $this->getConfig()->getUserMapper()->mapForm($form->getValues(), array());
        $values = $form->getValues('/^site\./');
        $this->data->replace($values);

        if (empty($values['site.title']) || strlen($values['site.title']) < 3) {
            $form->addFieldError('site.title', 'Please enter a valid site name');
        }
        if (empty($values['site.short.title']) || strlen($values['site.title']) < 1) {
            $form->addFieldError('site.short.title', 'Please enter short name for the site');
        }
        if (empty($values['site.email']) || !filter_var($values['site.email'], \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('site.email', 'Please enter a valid site email address');
        }
        $this->user->setEmail($values['site.email']);

        // Password validation needs to be here
        if ($form->getField('newPassword')) {
            if ($form->getFieldValue('newPassword')) {
                if ($form->getFieldValue('newPassword') != $form->getFieldValue('confPassword')) {
                    $form->addFieldError('newPassword', 'Passwords do not match.');
                    $form->addFieldError('confPassword');
                }
            } else {
                $form->addFieldError('newPassword', 'Please enter a password for the administrator user.');
            }
        }

        $form->addFieldErrors($this->user->validate());
        if ($form->hasErrors()) {
            return;
        }


        if ($form->getFieldValue('newPassword')) {
            $this->user->setNewPassword($form->getFieldValue('newPassword'));
        }

        $this->user->save();
        $this->data->save();

        \Tk\Alert::addSuccess('Site Setup Successfully!');
        $event->setRedirect($this->getRedirectUrl());
    }

    /**
     * @return \Tk\Uri
     */
    public function getRedirectUrl()
    {
        return \Tk\Uri::create();
    }


    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $css = <<<CSS
nav.navbar {
  display: none;
}
CSS;
        $template->appendCss($css);

        $js = <<<JS
jQuery(function ($) {
  
  
  
});
JS;
        $template->appendJs($js);

        $template->appendTemplate('form', $this->form->getRenderer()->show());
        
        return $template;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="container">
  
  <h1>Site Setup</h1>
  <div var="form"></div>
  
  <p>&nbsp;</p>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}