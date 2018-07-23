<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;
use Tk\Event\ControllerEvent;
use Tk\Event\GetResponseEvent;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{

    /**
     * do any auth init setup
     *
     * @param GetResponseEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Exception
     */
    public function onRequest(GetResponseEvent $event)
    {
        // if a user is in the session add them to the global config
        // Only the identity details should be in the auth session not the full user object, to save space and be secure.
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();
        $user = null;                       // public user

        if ($auth->getIdentity()) {         // Check if user is logged in
            $user = $config->getUserMapper()->findByUsername($auth->getIdentity());
            $config->setUser($user);
        }

        // ---------------- deprecated  ---------------------
        // The following is deprecated in preference of the validatePageAccess() method below
        $role = $event->getRequest()->getAttribute('role');
        // no role means page is publicly accessible
        if (!$role || empty($role)) return;
        if ($user) {
            if (!$user->hasRole($role)) {
                // Could redirect to a authentication error page.
                \Tk\Alert::addWarning('You do not have access to the requested page.');
                $config->getUserHomeUrl($user)->redirect();
            }
        } else {
            \Tk\Uri::create('/login.html')->redirect();
        }
        //-----------------------------------------------------

    }


    /**
     * Use path for permission validation
     *
     * @param ControllerEvent $event
     * @throws \ReflectionException
     */
    public function validatePageAccess(\Tk\Event\ControllerEvent $event)
    {
        $config = \Bs\Config::getInstance();

        // Deprecated remove when role is no longer used as a route attribute
        $role = $event->getRequest()->getAttribute('role');
        if ($role) {
            \Tk\Log::notice('Using legacy page permission system');
            return;
        }
        // --------------------------------------------------------

        $controller = $event->getController();
        if ($controller instanceof \Tk\Controller\Iface) {
            $urlRole = \Bs\Uri::create()->getRole($config->getAvailableUserRoles());
            $role = '';
            if ($config->getUser()) {
                $role = $config->getUser()->getRole();
            }

            // Use path for permission validation
            if ($urlRole) {     // If page requires a valid role for access
                if (!$config->getUser()) {  // if no user and the url has permissions set
                    \Tk\Uri::create('/login.html')->redirect();
                }
                if (!$role == $urlRole) {   // Finally check if the use has access to the url
                    // Could redirect to a authentication error page.
                    \Tk\Alert::addWarning('You do not have access to the requested page.');
                    $config->getUserHomeUrl($config->getUser())->redirect();
                }
            }
        }
    }

    /**
     * @param \Tk\Uri $url
     * @return string
     * @throws \ReflectionException
     * @note Using this page auth method means urls like /admin.html will
     *       not work as expected, keep away from using
     *       roles name as the start of a page name to avoid any bugs
     * @todo: keep an eye on this to be sure we do not get any errors
     */
    protected function getUrlRole($url)
    {
        $config = \Bs\Config::getInstance();
        $roles = $config->getAvailableUserRoles();
        if (preg_match('|^\/([a-z0-9_-]+).*|', $url->getRelativePath(), $regs)) {
            if (!empty($regs[1]) && in_array($regs[1], $roles)) {
                return $regs[1];
            }
        }
        return '';
    }


    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();

        $result = null;
        if (!$event->getAdapter()) {
            $adapterList = $config->get('system.auth.adapters');
            foreach ($adapterList as $name => $class) {
                $event->setAdapter($config->getAuthAdapter($class, $event->all()));
                if (!$event->getAdapter()) continue;
                $result = $auth->authenticate($event->getAdapter());
                $event->setResult($result);
                if ($result && $result->isValid()) {
                    break;
                }
            }
        }

        if (!$result) {
            throw new \Tk\Auth\Exception('Invalid username or password');
        }
        if (!$result->isValid()) {
            return;
        }
        
        $user = $config->getUserMapper()->findByUsername($result->getIdentity());
        if (!$user) {
            throw new \Tk\Auth\Exception('User not found: Contact Your Administrator');
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $result = $event->getResult();
        if (!$result) {
            throw new \Tk\Auth\Exception('Invalid login credentials');
        }
        if (!$result->isValid()) {
            return;
        }

        /* @var \Bs\Db\User $user */
        $user = $config->getUserMapper()->findByUsername($result->getIdentity());
        if (!$user) {
            throw new \Tk\Auth\Exception('Invalid user login credentials');
        }
        if (!$user->isActive()) {
            throw new \Tk\Auth\Exception('Inactive account, please contact your administrator.');
        }

        if($user && $event->getRedirect() == null) {
            $event->setRedirect(\Bs\Config::getInstance()->getUserHomeUrl($user));
        }

        // Update the user record.
        $user->lastLogin = \Tk\Date::create();
        $user->save();

    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        $config = \Bs\Config::getInstance();
        $auth = $config->getAuth();
        $url = $event->getRedirect();
        if (!$url) {
            $event->setRedirect(\Tk\Uri::create('/'));
        }

        $auth->clearIdentity();
        $config->getSession()->destroy();
    }



    // TODO: For all emails lets try to bring it back to the default mail template
    // TODO:   make it configurable so we could add it back in the future????

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRegister(\Tk\Event\Event $event)
    {
        /** @var \Bs\Db\User $user */
        $user = $event->get('user');
        $config = \Bs\Config::getInstance();

        $url = \Tk\Uri::create('/register.html')->set('h', $user->hash);

        $message = $config->createMessage('account.registration');
        $message->setSubject('Account Registration.');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('activate-url', $url->toString());
        \Bs\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRegisterConfirm(\Tk\Event\Event $event)
    {
        /** @var \Bs\Db\User $user */
        $user = $event->get('user');
        $config = \Bs\Config::getInstance();

        // Send an email to confirm account active
        $url = \Tk\Uri::create('/login.html');

        $message = $config->createMessage('account.activated');
        $message->setSubject('Account Activation.');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('login-url', $url->toString());
        \Bs\Config::getInstance()->getEmailGateway()->send($message);

    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onRecover(\Tk\Event\Event $event)
    {
        /** @var \Bs\Db\User $user */
        $user = $event->get('user');
        $pass = $event->get('password');
        $config = \Bs\Config::getInstance();

        $url = \Tk\Uri::create('/login.html');

        $message = $config->createMessage('account.recover');
        $message->setSubject('Password Recovery');
        $message->addTo($user->email);
        $message->set('name', $user->name);
        $message->set('password', $pass);
        $message->set('login-url', $url->toString());
        \Bs\Config::getInstance()->getEmailGateway()->send($message);

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
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::CONTROLLER => array('validatePageAccess', 100),
            AuthEvents::LOGIN => 'onLogin',
            AuthEvents::LOGIN_SUCCESS => 'onLoginSuccess',
            AuthEvents::LOGOUT => 'onLogout',
            AuthEvents::REGISTER => 'onRegister',
            AuthEvents::REGISTER_CONFIRM => 'onRegisterConfirm',
            AuthEvents::RECOVER => 'onRecover'
        );
    }


}