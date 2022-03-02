<?php
namespace Bs\Controller;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class UserIface extends Iface
{
    use ActionPanelTrait;


    /**
     * @param \Bs\Db\User $user
     * @return bool
     */
    public function hasAccess($user = null)
    {
        if ($user) {
            return ($user->isMember());
        }
        return true;
    }

    /**
     * @return string
     */
    public function getPageTemplatePath()
    {
        return $this->getConfig()->getSitePath() . $this->getConfig()->get('template.admin');
    }


}