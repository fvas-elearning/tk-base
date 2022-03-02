<?php
namespace Bs\Controller;

use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Request;
use Tk\Auth\AuthEvents;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Register extends Iface
{
    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \Bs\Db\User
     */
    private $user = null;



    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Register New Account');
    }

    /**
     * @return \Tk\Controller\Page
     */
    public function getPage()
    {
        if (!$this->page) {
            $templatePath = '';
            if ($this->getConfig()->get('template.login')) {
                $templatePath = $this->getConfig()->getSitePath() . $this->getConfig()->get('template.login');
            }
            $this->page = $this->getConfig()->getPage($templatePath);
        }
        return parent::getPage();
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        if (!$this->getConfig()->get('site.client.registration')) {
            \Tk\Alert::addError('User registration has been disabled on this site.');
            \Tk\Uri::create('/')->redirect();
        }
        if ($request->has('h')) {
            $this->doConfirmation($request);
        }

        $this->user = $this->getConfig()->createUser();
        $this->user->roleId = \Bs\Db\ROLE::DEFAULT_TYPE_USER;

        $this->init();

        $this->form->load($this->getConfig()->getUserMapper()->unmapForm($this->user));
        $this->form->execute();
    }

    /**
     * @throws \Exception
     */
    protected function init()
    {
        if (!$this->form) {
            $this->form = $this->getConfig()->createForm('register-account');
            $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));
        }

        $this->form->appendField(new Field\Input('name'));
        $this->form->appendField(new Field\Input('email'));
        $this->form->appendField(new Field\Input('username'));
        $this->form->appendField(new Field\Password('password'));
        $this->form->appendField(new Field\Password('passwordConf'))->setLabel('Password Confirm');
        $this->form->appendField(new Event\Submit('register', array($this, 'doRegister')))->removeCss('btn-default')->addCss('btn btn-lg btn-primary btn-ss');
        $this->form->appendField(new Event\Link('forgotPassword', \Tk\Uri::create($this->getConfig()->get('url.auth.recover')), ''))
            ->removeCss('btn btn-sm btn-default btn-once')->addCss('tk-recover-url');
    }


    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doRegister($form, $event)
    {
        $this->getConfig()->getUserMapper()->mapForm($form->getValues(), $this->user);

        if (!$this->form->getFieldValue('password')) {
            $form->addFieldError('password', 'Please enter a password');
            $form->addFieldError('passwordConf');
        }
        // Check the password strength, etc....
        if (!preg_match('/.{6,32}/', $this->form->getFieldValue('password'))) {
            $form->addFieldError('password', 'Please enter a valid password');
            $form->addFieldError('passwordConf');
        }
        // Password validation needs to be here
        if ($this->form->getFieldValue('password') != $this->form->getFieldValue('passwordConf')) {
            $form->addFieldError('password', 'Passwords do not match.');
            $form->addFieldError('passwordConf');
        }

        $form->addFieldErrors($this->user->validate());

        if ($form->hasErrors()) {
            return;
        }

        // Create a user and make a temp hash until the user activates the account
        $hash = $this->user->generateHash(true);
        $this->user->hash = $hash;
        $this->user->active = false;
        $this->user->setNewPassword($this->user->password);
        $this->user->save();

        // Fire the login event to allow developing of misc auth plugins
        $e = new \Tk\Event\Event();
        $e->set('form', $form);
        $e->set('user', $this->user);
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::REGISTER, $e);


        // Redirect with message to check their email
        \Tk\Alert::addSuccess('Your New Account Has Been Created.');
        \Tk\Config::getInstance()->getSession()->set('h', $this->user->hash);
        $event->setRedirect(\Tk\Uri::create());
    }

    /**
     * Activate the user account if not activated already, then trash the request hash....
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doConfirmation($request)
    {
        // Receive a users on confirmation and activate the user account.
        $hash = $request->get('h');
        if (!$hash) {
            throw new \InvalidArgumentException('Cannot locate user. Please contact administrator.');
        }
        /** @var \Bs\Db\User $user */
        $user = $this->getConfig()->getUserMapper()->findByHash($hash);
        if (!$user) {
            throw new \InvalidArgumentException('Cannot locate user. Please contact administrator.');
        }
        $user->hash = $user->generateHash();
        $user->active = true;
        $user->save();

        $event = new \Tk\Event\Event();
        $event->set('user', $user);
        $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::REGISTER_CONFIRM, $event);

        \Tk\Alert::addSuccess('Account Activation Successful.');
        \Tk\Uri::create($this->getConfig()->get('url.auth.login'))->redirect();

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        if ($this->getConfig()->get('site.client.registration')) {
            $template->setVisible('register');
        }

        if ($this->getConfig()->getSession()->getOnce('h')) {
            $template->setVisible('success');

        } else {
            $template->setVisible('form');
            // Render the form
            $template->insertTemplate('form', $this->form->getRenderer()->show());
        }

        return $template;
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-login-panel tk-register">

  <div choice="success">
    <h2>Success</h2>
    <p>
      An confirmation email has been sent to <span var="email">example@example.com</span> please
      follow the link in that email to activate your new account.
    </p>
    <p>Thank You!</p>
  </div>
  <div var="form" choice="form"></div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}