<?php
namespace Bs;



use Bs\Db\User;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Uri extends \Tk\Uri
{

    /**
     * A static factory method to facilitate inline calls
     *
     * <code>
     *   \Tk\Uri::create('http://example.com/test');
     * </code>
     *
     * @param null|string|\Tk\Uri $spec
     * @param null|\Bs\Db\User|string $user
     * @return string|\Tk\Uri|static
     */
    public static function createHomeUrl($spec = null, $user = null)
    {
        if ($spec instanceof \Tk\Uri)
            return clone $spec;
        $home = '';
        if (is_string($user))
            $home = $user;
        if (!$user)
            $user = Config::getInstance()->getAuthUser();
        if ($user instanceof \Bs\Db\UserIface && $user->getType() && !$user->hasType(User::TYPE_GUEST))
            $home = '/' . $user->getType();
        return new static($home . '/' . trim($spec,'/'));
    }

    /**
     * Call this to ensure the breadcrumb system ignores this URL
     *
     * @param bool $b
     * @return static
     * @deprecated use noCrumb()
     */
    public function ignoreCrumb($b = true)
    {
        return $this->noCrumb($b);
    }

    /**
     * Call this to ensure the breadcrumb system ignores this URL
     *
     * @param bool $b
     * @return static
     */
    public function noCrumb($b = true)
    {
        if ($b)
            $this->set(\Tk\Crumbs::CRUMB_IGNORE);       // 'crumb_ignore'
        else
            $this->remove(\Tk\Crumbs::CRUMB_IGNORE);
        return $this;
    }

    /**
     * Debug Only
     * Call this to enable/disable log entries for this url
     *
     * @param bool $b
     * @return static
     */
    public function noLog($b = true)
    {
        if ($b)
            $this->set('nolog');
        else
            $this->remove('nolog');
        return $this;
    }


    /**
     * Attempts to get a valid user role type from the start of a site path
     *
     *
     * Example uses following roles array:
     *  $roles = array('admin', 'user');
     *
     * URI`s return values:
     *  o /index.html               => ''
     *  o /user/profile.html        => 'user'
     *  o /system/admin/edit.html   => ''
     *  o /admin/settings           => 'admin
     *
     * The script check for a role after the first `/` char and if exists returns that as the found role.
     * This call will be handy for authentication and page template loading
     *
     * @param array $roles  Supply a list of available roles to search for
     * @return string
     * @todo: deprecate this function outta here it should be a helper method in the App/Config if needed???
     * @deprecated Not sure yet!!!!
     */
    public function getRoleType($roles = array())
    {
        if (preg_match('|^\/([a-z0-9_-]+).*|', $this->getRelativePath(), $regs)) {
            if (!empty($regs[1]) && in_array($regs[1], $roles)) {
                return $regs[1];
            }
        }
        return '';
    }

    /**
     * @param array $roles
     * @return string
     * @deprecated use getRoleType()
     */
    public function getRole($roles = array())
    {
        return $this->getRoleType($roles);
    }
}
