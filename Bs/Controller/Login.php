<?php
namespace Bs\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Auth\AuthEvents;
use Tk\Event\AuthEvent;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Login extends Iface
{

    /**
     * @var Form
     */
    protected $form = null;




    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Login');
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
     */
    public function doDefault(Request $request)
    {
        $this->init();

        $this->form->execute();

    }

    /**
     * @throws \Exception
     */
    protected function init()
    {
        if (!$this->form) {
            $this->form = $this->getConfig()->createForm('login-form');
        }

        $this->form->appendField(new Field\Input('username'));
        $this->form->appendField(new Field\Password('password'));
        $this->form->appendField(new Event\Submit('login', array($this, 'doLogin')))->removeCss('btn-default')->addCss('btn btn-lg btn-primary btn-ss');
        $this->form->appendField(new Event\Link('forgotPassword', \Tk\Uri::create($this->getConfig()->get('url.auth.recover')), ''))
            ->removeCss('btn btn-sm btn-default btn-once')->addCss('tk-recover-url');

        if ($this->getConfig()->get('site.client.registration')) {
            $this->form->appendField(new \Tk\Form\Event\Link('register', \Tk\Uri::create($this->getConfig()->get('url.auth.register')), ''))
                ->removeCss('btn btn-sm btn-default btn-once')->addCss('tk-register-url');
        }
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     */
    public function doLogin($form, $event)
    {
        if ($form->hasErrors()) {
            $form->addError('Invalid username or password');
            return;
        }

        try {
            // Fire the login event to allow developing of misc auth plugins
            $e = new AuthEvent();
            $e->replace($form->getValues());
            $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGIN, $e);

            // Use the event to process the login like below....
            $result = $e->getResult();
            if (!$result) {
                $form->addError('Invalid username or password');
                return;
            }
            if (!$result->isValid()) {
                $form->addError( implode("<br/>\n", $result->getMessages()) );
                return;
            }

            // Copy the event to avoid propagation issues
            $e2 = new AuthEvent($e->getAdapter());
            $e2->replace($e->all());
            $e2->setResult($e->getResult());
            $e2->setRedirect($e->getRedirect());
            $this->getConfig()->getEventDispatcher()->dispatch(AuthEvents::LOGIN_SUCCESS, $e2);
            if ($e2->getRedirect())
                $e2->getRedirect()->redirect();

        } catch (\Exception $e) {
            $form->addError($e->getMessage());
            $form->addError('Login Error: ' . $e->getMessage());
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        if ($this->form) {
            $template->appendTemplate('form', $this->form->getRenderer()->show());
        }

        if ($this->getConfig()->get('site.client.registration')) {
            $template->setVisible('register');
            $this->getPage()->getTemplate()->setVisible('register');
        }

        $js = <<<JS
jQuery(function ($) {
  
  $('#login-form').on('keypress', function (e) {
    if (e.which === 13) {
      $(this).find('#login-form_login').trigger('click');
    }
  });
  
});
JS;
        $template->appendJs($js);

        return $template;
    }


    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-login-panel tk-login">

  <div var="form"></div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
}