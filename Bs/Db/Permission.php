<?php
namespace Bs\Db;


use Tk\ConfigTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Permission
{
    use ConfigTrait;

    /**
     * @var Permission
     */
    public static $instance = null;

    /**
     * Can manage site settings and configuration
     * @target user,admin
     */
    const MANAGE_SITE        = 'perm.manage.site';

    /**
     * Developer User Permissions
     */
    const IS_DEVELOPER = 'perm.developer';

    /**
     * Can masquerade as other lower tier users
     * @target user,admin
     */
    const CAN_MASQUERADE        = 'perm.masquerade';

    /**
     * Manage plugins
     * @target user,admin
     */
    const MANAGE_PLUGINS        = 'perm.manage.plugins';


    /**
     * Get a static instance of this object
     * Call the Config::getPermission() method to get the initial instance of this object
     *
     * @return Permission|\Uni\Db\Permission|\App\Db\Permission
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array|string[]
     */
    public function getAvailablePermissionList($type = '')
    {
        $arr = array();
        switch ($type) {
            case User::TYPE_ADMIN;
                $arr = array(
                    'Manage Site Config' => self::MANAGE_SITE,
                    'Manage Site Plugins' => self::MANAGE_PLUGINS,
                    'Can Masquerade' => self::CAN_MASQUERADE
                );
                break;
            case User::TYPE_MEMBER:
                $arr = array();
                break;
            default:
                $arr = array(
                    'Manage Site Config' => self::MANAGE_SITE,
                    'Manage Site Plugins' => self::MANAGE_PLUGINS,
                    'Can Masquerade' => self::CAN_MASQUERADE
                );
        }
        return $arr;
    }

    /**
     * Return the default user permission when creating a user
     *
     * @param string $type (optional) If set returns only the permissions for that user type otherwise returns all permissions
     * @return array|string[]
     */
    public function getDefaultUserPermissions($type = '')
    {
        $list = array();
        if ($type == User::TYPE_ADMIN) {
            $list = array(
                'Manage Site Config' => self::MANAGE_SITE,
                'Manage Site Plugins' => self::MANAGE_PLUGINS,
                'Can Masquerade' => self::CAN_MASQUERADE
            );
        }
        return $list;
    }



    /**
     * Get all available permissions for a user type
     *
     * @param string $type (optional) The user type (staff, student, user, ...) to return permissions availablefor that user type
     * @return array
     * @deprecated Use getConfig()->getPermission()->getAvailablePermissionList($type);
     */
    public static function getPermissionList($type = '')
    {
        \Tk\Log::warning(self::class . '::getPermissionList() is deprecated');
        return self::getInstance()->getAvailablePermissionList($type);
    }

    /**
     * Return the default permission set for creating new user types.
     *
     * @param string $type
     * @return array
     * @deprecated Use getConfig()->getPermission()->getDefaultUserPermissions($type);
     */
    public static function getDefaultPermissionList($type = '')
    {
        \Tk\Log::warning(self::class . '::getDefaultPermissionList() is deprecated');
        return self::getInstance()->getDefaultUserPermissions($type);
    }


}
