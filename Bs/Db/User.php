<?php
namespace Bs\Db;

use Bs\Db\Traits\PermissionTrait;
use Bs\Db\Traits\TimestampTrait;
use Tk\Db\Map\Model;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User extends Model implements UserIface
{
    use PermissionTrait;
    use TimestampTrait;

    /**
     * Default Guest user This type should never be saved to storage
     * It is intended to be the default system user that has not logged in
     * (Access to public pages only)
     */
    const TYPE_GUEST = 'guest';
    /**
     * Administration user (Access to the admin area)
     */
    const TYPE_ADMIN = 'admin';
    /**
     * Base logged in user type (Access to user pages)
     */
    const TYPE_MEMBER = 'member';

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $uid = '';

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var string
     */
    public $username = '';

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $nameFirst = '';

    /**
     * @var string
     */
    public $nameLast = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var string
     */
    public $phone = '';

    /**
     * @var string
     */
    public $credentials = '';

    /**
     * @var string
     */
    public $position = '';

    /**
     * @var string
     */
    public $image = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var \DateTime
     */
    public $lastLogin = null;

    /**
     * @var string
     */
    public $sessionId = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;

    /**
     * @var string
     */
    public $ip = '';

    /**
     * @var \Tk\Db\Data
     */
    private $_data = null;


    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->_TimestampTrait();
        $this->setIp($this->getConfig()->getRequest()->getClientIp());
    }

    /**
     * @return User
     */
    public static function createGuest()
    {
        $user = new self();
        $user->setName('Guest');
        $user->setUsername('guest');
        $user->setType(self::TYPE_GUEST);
        return $user;
    }


    /**
     * Use this to populate the select field for a users title
     *
     * @param string|null $title
     * @return array|string[]
     */
    public static function getTitleList(?string $title = '')
    {
        $arr = array('Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Esq', 'Hon', 'Messrs',
            'Mmes', 'Msgr', 'Rev', 'Jr', 'Sr', 'St');
        $arr = array_combine($arr, $arr);

        $arr2 = array();
        foreach ($arr as $k => $v) {
            if ($v == $title) {
                //$arr2['* ' . $k . ''] = $v;
                $arr2['' . $k . '.'] = $v;
            } else {
                $arr2[$k.'.'] = $v;
            }
        }
        $arr = $arr2;

        return $arr;
    }

    /**
     * @return \Tk\Db\Map\Mapper|UserMap
     */
    public function getMapper()
    {
        return self::createMapper();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if ($this->isGuest()) return;
        $this->getHash();
        $this->getData()->save();
        parent::save();
    }

    /**
     * Get the data object
     *
     * @return \Tk\Db\Data
     */
    public function getData()
    {
        if (!$this->_data)
            $this->_data = \Tk\Db\Data::create(get_class($this), $this->getVolatileId(), 'user_data');
        return $this->_data;
    }

    /**
     * Get the path for all file associated to this object
     *
     * @return string
     */
    public function getDataPath()
    {
        return sprintf('/user/%s', $this->getVolatileId());
    }

    /**
     * @return \Tk\Uri
     * @throws \Exception
     */
    public function getImageUrl()
    {
        $url = \Tk\Uri::create('/html/app/img/user.png');
        if ($this->getImage()) {
            if (filter_var($this->getImage(), \FILTER_SANITIZE_URL, \FILTER_FLAG_PATH_REQUIRED)) {
                $url = \Tk\Uri::create($this->getImage());
            } else if (file_exists($this->getConfig()->getDataPath() . $this->getImage())) {
                $url = \Tk\Uri::create($this->getConfig()->getDataUrl() . $this->getImage());
            }
        } else if (class_exists('\LasseRafn\InitialAvatarGenerator\InitialAvatar')) {
            // TODO: Disabled until the lib works
            // TODO: Currently gets the error:  Uncaught Error: Call to undefined function GuzzleHttp\Psr7\stream_for()
            $color = \Tk\Color::createRandom($this->getVolatileId());
            $avatar = new \LasseRafn\InitialAvatarGenerator\InitialAvatar();
            $img = $avatar->name($this->getName())
                ->length(2)
                ->fontSize(0.5)
                ->size(96)// 48 * 2
                ->background($color->toString(true))
                ->color($color->getTextColor()->toString(true))
                ->generate()
                ->stream('png', 100);
            $b64 = base64_encode($img->getContents());
            $url = \Tk\Uri::create('data:image/png;base64,' . $b64);
        }
        return $url;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return User
     */
    public function setUid(string $uid): User
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @param string|array $type
     * @return bool
     */
    public function hasType($type)
    {
        if (func_num_args() > 1) $type = func_get_args();
        else if (!is_array($type)) $type = array($type);
        foreach ($type as $r) {
            if (trim($r) == trim($this->getType())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return User
     */
    public function setType(string $type): User
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(?string $username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * returns the concatenated first and last name values
     *
     * @return string
     */
    public function getName(): string
    {
        $name = trim($this->getNameFirst() . ' ' . $this->getNameLast());
//        if ($this->getTitle())
//            $name = $this->getTitle() . ' ' . $name;
        if (!trim($name)) $name = $this->getUsername();
        return $name;
    }

    /**
     * @param string|null $name
     * @return User
     */
    public function setName(?string $name): User
    {
        $name = trim($name);
        if ( preg_match('/\s/',$name) ) {
            $this->setNameFirst(substr($name, 0, strpos($name, ' ')));
            $this->setNameLast(substr($name, strpos($name, ' ') + 1));
        } else {
            $this->setNameFirst($name);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return User
     */
    public function setTitle(?string $title): User
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameFirst(): string
    {
        return $this->nameFirst;
    }

    /**
     * @param string $nameFirst
     * @return User
     */
    public function setNameFirst(string $nameFirst): User
    {
        $this->nameFirst = $nameFirst;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameLast(): string
    {
        return $this->nameLast;
    }

    /**
     * @param string $nameLast
     * @return User
     */
    public function setNameLast(string $nameLast): User
    {
        $this->nameLast = $nameLast;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return User
     */
    public function setEmail(?string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return User
     */
    public function setPhone(?string $phone): User
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getCredentials(): string
    {
        return $this->credentials;
    }

    /**
     * @param string|null $credentials
     * @return User
     */
    public function setCredentials(?string $credentials): User
    {
        $this->credentials = $credentials;
        return $this;
    }

    /**
     * @return string
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * @param string|null $position
     * @return User
     */
    public function setPosition(?string $position): User
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     * @return User
     */
    public function setImage(?string $image): User
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     * @return User
     */
    public function setNotes(?string $notes): User
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return User
     */
    public function setActive(bool $active): User
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime|null $lastLogin
     * @return User
     */
    public function setLastLogin(?\DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string|null $sessionId
     * @return User
     */
    public function setSessionId(?string $sessionId): User
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string|null $ip
     * @return User
     */
    public function setIp(?string $ip): User
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Get the user hash or generate one if needed
     *
     * @return string
     */
    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        return $this->hash;
    }

    /**
     * Helper method to generate user hash
     *
     * @param bool $isTemp Set this to true, when generate a temporary hash used for registration
     * @return string
     */
    public function generateHash($isTemp = false)
    {
        $key = sprintf('%s%s', $this->getVolatileId(), $this->getUsername());
        if ($isTemp) {
            $key .= date('-YmdHis');
        }
        return $this->getConfig()->hash($key);
    }

    /**
     * Set the password from a plain string
     *
     * @param string $pwd
     * @return User
     */
    public function setNewPassword($pwd = '')
    {
        if (!$pwd) {
            $pwd = $this->getConfig()->createPassword(10);
        }
        $this->setPassword($this->getConfig()->hashPassword($pwd, $this));
        return $this;
    }

    /**
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->getType() == self::TYPE_ADMIN;
    }

    /**
     * @return boolean
     */
    public function isMember()
    {
        return $this->getType() == self::TYPE_MEMBER;
    }

    /**
     * @return boolean
     */
    public function isGuest()
    {
        return !$this->getType() || $this->getType() == self::TYPE_GUEST;
    }

    /**
     * Return the user types available to the system
     *
     * It is important to order types from most permissions (admin) to least permissions (member/student)
     * this will be used in masquerading log-ins
     *
     * @param bool $valuesOnly (optional) return the type values with no name keys
     * @return array
     * @deprecated use \Bs\Config::getInstance()->getUserTypeList(...)
     */
    public static function getUserTypeList($valuesOnly = false)
    {
        return \Bs\Config::getInstance()->getUserTypeList($valuesOnly);
    }


    /**
     * Validate this object's current state and return an array
     * with error messages. This will be useful for validating
     * objects for use within forms.
     *
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        $errors = array();
        $usermap = $this->getConfig()->getUserMapper();

        if (!$this->getUsername()) {
            $errors['username'] = 'Invalid field username value';
        } else {
            $dup = $usermap->findByUsername($this->getUsername());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['username'] = 'This username is already in use';
            }
        }
        if ($this->getEmail()) {
            if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } else {
                $dup = $usermap->findByEmail($this->getEmail());
                if ($dup && $dup->getId() != $this->getId()) {
                    $errors['email'] = 'This email is already in use';
                }
            }
        }
        if ($this->getUid()) {
            $dup = $usermap->findByUid($this->getUid());
            if ($dup && $dup->getId() != $this->getId()) {
                $errors['uid'] = 'This UID is already in use';
            }
        }
        return $errors;
    }

}
