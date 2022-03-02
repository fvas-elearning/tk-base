<?php
namespace Bs\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
interface UserIface extends \Tk\ValidInterface, \Tk\Db\ModelInterface
{

    /**
     * @return \Tk\Db\Data
     */
    public function getData();

    /**
     * @return int
     */
    public function getUid();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * Return the users hashed password
     * @return string
     */
    public function getPassword();

    /**
     * Return the user title. EG: Dr, Ms, Mr, etc
     * @return string
     */
    public function getTitle();

    /**
     * Return the user Credentials. EG: BVSc, MPhil, MANZCVSc, Dip ACVP
     * @return string
     */
    public function getCredentials();

    /**
     * Return the user job position/Department. EG: Senior Lecturer
     * @return string
     */
    public function getPosition();

    /**
     * This should return the concatenated strings:
     * $this->getNameFirst() . ' ' . $this->>getNameLast()
     *
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getNameFirst();

    /**
     * @return string
     */
    public function getNameLast();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getPhone();

    /**
     * @return string
     */
    public function getImage();

    /**
     * @return string
     */
    public function getImageUrl();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @return string
     */
    public function getHash();

    /**
     * @return \DateTime|null
     */
    public function getLastLogin();

    /**
     * @param string|array $type
     * @return bool
     */
    public function hasType($type);

    /**
     * @return array
     */
    public function getPermissions();

    /**
     * @param string $name
     * @return $this
     */
    public function addPermission($name);

    /**
     * @param string $name (optional) If omitted then all permissions are removed
     * @return $this
     */
    public function removePermission($name = null);

    /**
     * Check if this object has the requested permission
     *
     * @param string|string[] $permission
     * @return bool
     */
    public function hasPermission($permission);

}
