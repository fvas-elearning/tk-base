<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;
use Symfony\Component\HttpKernel\KernelEvents;
use Bs\Db\User;
use Bs\Db\Role;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MasqueradeHandler implements Subscriber
{
    /**
     * Session ID
     */
    const SID = '__masquerade__';

    /**
     * The query string for the msq user
     * Eg: `index.html?msq=23`
     */
    const MSQ = 'msq';

    /**
     * The order of role permissions
     * @var array
     */
    public static $roleOrder = array(
        Role::TYPE_ADMIN,        // Highest
        Role::TYPE_USER          // Lowest
    );

    /**
     * Add any headers to the final response.
     *
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     */
    public function onMasquerade($event)
    {
        $request = $event->getRequest();
        $config = \Bs\Config::getInstance();
        if (!$request->request->has(static::MSQ)) return;

        try {
            /** @var User $user */
            $user = $config->getUser();
            if (!$user) throw new \Tk\Exception('Invalid User');
            /** @var User $msqUser */
            $msqUser = $config->getUserMapper()->findByHash($request->get(static::MSQ));
            if (!$msqUser) throw new \Tk\Exception('Invalid User');
            $this->masqueradeLogin($user, $msqUser);

        } catch (\Exception $e) {
            \Tk\Alert::addWarning($e->getMessage());
        }
    }


    // -------------------  Masquerade functions  -------------------

    /**
     * Check if this user can masquerade as the supplied msqUser
     *
     * @param User $user The current User
     * @param User $msqUser
     * @return bool
     */
    public function canMasqueradeAs($user, $msqUser)
    {
        $config = $this->getConfig();
        if (!$msqUser || !$user || !$msqUser->active) return false;
        if ($user->id == $msqUser->id) return false;

        $msqArr = $config->getSession()->get(static::SID);

        if (is_array($msqArr)) {    // Check if we are already masquerading as this user in the queue
            foreach ($msqArr as $data) {
                if ($data['userId'] == $msqUser->id) return false;
            }
        }

        // If not admin their role must be higher in precedence see \Uni\Db\User::$roleOrder
        if ($user->isAdmin() || $this->hasPrecedence($user, $msqUser)) {
            return true;
        }
        return false;
    }

    /**
     * @param User $user The current User
     * @param User $msqUser
     * @return bool
     */
    protected function hasPrecedence($user, $msqUser)
    {
        // Get the users role precedence order index
        $userRoleIdx = $this->getRolePrecedenceIdx($user);
        $msqRoleIdx = $this->getRolePrecedenceIdx($msqUser);
        return ($userRoleIdx < $msqRoleIdx);
    }

    /**
     * @param \Bs\Db\UserIface $user
     * @return int
     */
    public function getRolePrecedenceIdx($user)
    {
        return array_search($user->getRoleType(), static::$roleOrder);
    }


    /**
     *
     * @param User $user
     * @param User $msqUser
     * @return bool|void
     * @throws \Exception
     */
    public function masqueradeLogin($user, $msqUser)
    {
        $config = $this->getConfig();
        if (!$msqUser || !$user) return;
        if ($user->id == $msqUser->id) return;

        // Get the masquerade queue from the session
        $msqArr = $config->getSession()->get(static::SID);
        if (!is_array($msqArr)) $msqArr = array();

        if (!$this->canMasqueradeAs($user, $msqUser)) {
            return;
        }

        // Save the current user and url to the session, to allow logout
        $userData = array(
            'userId' => $user->id,
            'url' => \Tk\Uri::create()->remove(static::MSQ)->toString()
        );
        array_push($msqArr, $userData);
        // Save the updated masquerade queue
        $config->getSession()->set(static::SID, $msqArr);
        // Simulates an AuthAdapter authenticate() method
        $config->getAuth()->getStorage()->write($config->getUserIdentity($msqUser));

        // Trigger the login success event for correct redirect
        $url = $config->getUserHomeUrl($msqUser);
        $e = new AuthEvent();
        $result = new \Tk\Auth\Result(\Tk\Auth\Result::SUCCESS, $config->getUserIdentity($msqUser));
        $e->setResult($result);
        $e->setRedirect($url);
        $config->getEventDispatcher()->dispatch(AuthEvents::LOGIN_SUCCESS, $e);
        if ($e->getRedirect())
            $e->getRedirect()->redirect();

    }


    /**
     * masqueradeLogout
     *
     * @throws \Exception
     */
    public function masqueradeLogout()
    {
        $config = $this->getConfig();
        if (!$this->isMasquerading()) return;
        if (!$config->getAuth()->hasIdentity()) return;
        $msqArr = $config->getSession()->get(static::SID);
        if (!is_array($msqArr) || !count($msqArr)) return;

        $userData = array_pop($msqArr);
        if (empty($userData['userId']) || empty($userData['url']))
            throw new \Tk\Exception('Session data corrupt. Clear session data and try again.');

        // Save the updated masquerade queue
        $config->getSession()->set(static::SID, $msqArr);

        /** @var User $user */
        $user = $config->getUserMapper()->find($userData['userId']);
        $config->getAuth()->getStorage()->write($config->getUserIdentity($user));

        \Tk\Uri::create($userData['url'])->redirect();
    }

    /**
     * If this user is masquerading
     *
     * 0 if not masquerading
     * >0 The masquerading total (for nested masquerading)
     *
     * @return int
     * @throws \Exception
     */
    public function isMasquerading()
    {
        $config = $this->getConfig();
        if (!$config->getSession()->has(static::SID)) return 0;
        $msqArr = $config->getSession()->get(static::SID);
        return count($msqArr);
    }

    /**
     * Get the user who is masquerading, ignoring any nested masqueraded users
     *
     * @return \Bs\Db\User|\Bs\Db\UserIface|null
     * @throws \Exception
     */
    public function getMasqueradingUser()
    {
        $config = $this->getConfig();
        $user = null;
        if ($config->getSession()->has(static::SID)) {
            $msqArr = current($config->getSession()->get(static::SID));
            /** @var \Bs\Db\User $user */
            $user = $config->getUserMapper()->find($msqArr['userId']);
        }
        return $user;
    }

    /**
     * masqueradeLogout
     */
    public function masqueradeClear()
    {
        $this->getConfig()->getSession()->remove(static::SID);
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        if ($this->isMasquerading()) {   // stop masquerading
            $this->masqueradeLogout();
            //$event->stopPropagation();
        }
    }

    /**
     * @return \Bs\Config|\Tk\Config
     */
    public function getConfig()
    {
        return \Bs\Config::getInstance();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onMasquerade',
            AuthEvents::LOGOUT => array('onLogout', 10)
        );
    }
}