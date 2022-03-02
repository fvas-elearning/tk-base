<?php
namespace Bs\Db;

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class UserMap extends Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setMarkDeleted('del');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Text('uid'));
            $this->dbMap->addPropertyMap(new Db\Text('type'));
            $this->dbMap->addPropertyMap(new Db\Text('username'));
            $this->dbMap->addPropertyMap(new Db\Text('title'));
            $this->dbMap->addPropertyMap(new Db\Text('nameFirst', 'name_first'));
            $this->dbMap->addPropertyMap(new Db\Text('nameLast', 'name_last'));
            $this->dbMap->addPropertyMap(new Db\Text('email'));
            $this->dbMap->addPropertyMap(new Db\Text('phone'));
            $this->dbMap->addPropertyMap(new Db\Text('credentials'));
            $this->dbMap->addPropertyMap(new Db\Text('position'));
            $this->dbMap->addPropertyMap(new Db\Text('image'));
            $this->dbMap->addPropertyMap(new Db\Text('notes'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Date('lastLogin', 'last_login'));
            $this->dbMap->addPropertyMap(new Db\Text('sessionId', 'session_id'));
            $this->dbMap->addPropertyMap(new Db\Text('password'));
            $this->dbMap->addPropertyMap(new Db\Text('hash'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));

        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Text('uid'));
            $this->formMap->addPropertyMap(new Form\Text('type'));
            $this->formMap->addPropertyMap(new Form\Text('username'));
            $this->formMap->addPropertyMap(new Form\Text('title'));
            $this->formMap->addPropertyMap(new Form\Text('nameFirst'));
            $this->formMap->addPropertyMap(new Form\Text('nameLast'));
            $this->formMap->addPropertyMap(new Form\Text('email'));
            $this->formMap->addPropertyMap(new Form\Text('phone'));
            $this->formMap->addPropertyMap(new Form\Text('credentials'));
            $this->formMap->addPropertyMap(new Form\Text('position'));
            $this->formMap->addPropertyMap(new Form\Text('image'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
            $this->formMap->addPropertyMap(new Form\Text('password'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
        }
        return $this->formMap;
    }

    /**
     * @param string|int $identity
     * @return null|\Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByAuthIdentity($identity)
    {
        return $this->findByUsername($identity);
    }

    /**
     * @param string $username
     * @return null|\Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByUsername($username)
    {
        return $this->findFiltered(array('username' => $username))->current();
    }

    /**
     * @param string $email
     * @return null|\Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByEmail($email)
    {
        return $this->findFiltered(array('email' => $email))->current();
    }

    /**
     * @param string $uid
     * @return null|\Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByUid($uid)
    {
        return $this->findFiltered(array('uid' => $uid))->current();
    }

    /**
     * @param $hash
     * @return \Tk\Db\Map\Model|User
     * @throws \Exception
     */
    public function findByHash($hash)
    {
        return $this->findFiltered(array('hash' => $hash))->current();
    }

    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|User[]|UserIface[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        $r = $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
        //vd($this->getDb()->getLastQuery());
        return $r;
    }

    /**
     * @param \Tk\Db\Filter $filter
     * @return \Tk\Db\Filter
     */
    public function makeQuery(\Tk\Db\Filter $filter)
    {
        $filter->appendFrom('%s a', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.uid LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.name_first LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.name_last LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.username LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.email LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.phone LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.title LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.credentials LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.position LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['type'])) {
            $w = $this->makeMultiQuery($filter['type'], 'a.type');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = %s AND ', $this->getDb()->quote($filter['uid']));
        }

        if (!empty($filter['username'])) {
            $filter->appendWhere('a.username = %s AND ', $this->getDb()->quote($filter['username']));
            if (!empty($filter['password'])) { // ??? Is this insecure ???
                $filter->appendWhere('a.password = %s AND ', $this->getDb()->quote($filter['password']));
            }
        }

        if (!empty($filter['email'])) {
            $filter->appendWhere('a.email = %s AND ', $this->quote($filter['email']));
        }

        if (!empty($filter['nameFirst'])) {
            $filter->appendWhere('a.name_first = %s AND ', $this->quote($filter['nameFirst']));
        }

        if (!empty($filter['nameLast'])) {
            $filter->appendWhere('a.name_last = %s AND ', $this->quote($filter['nameLast']));
        }

        if (!empty($filter['phone'])) {
            $filter->appendWhere('a.phone = %s AND ', $this->getDb()->quote($filter['phone']));
        }

        if (!empty($filter['hash'])) {
            $filter->appendWhere('a.hash = %s AND ', $this->getDb()->quote($filter['hash']));
        }

        if (isset($filter['active']) && $filter['active'] !== '' && $filter['active'] !== null) {
            $filter->appendWhere('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['hasSession'])) {
            $filter->appendWhere('a.session_id != "" AND a.session_id IS NOT NULL AND ');
        }

        if (!empty($filter['permission'])) {
            $filter->appendFrom(', user_permission e');
            $filter->appendWhere('a.id = e.user_id AND ');
            $w = $this->makeMultiQuery($filter['permission'], 'e.name');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }


    // --------------------------------------------


    /**
     * Note: Be sure to check the active status of this role
     *       and return false if this is a non active role.
     *
     * @param int $userId
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    public function hasPermission($userId, $name)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user_permission WHERE user_id = ? AND name = ?');
        $stm->execute(array($userId, $name));
        return ($stm->rowCount() > 0);
    }

    /**
     * @param int $userId
     * @param string $name
     * @throws \Exception
     */
    public function addPermission($userId, $name)
    {
        if ($name && !$this->hasPermission($userId, $name)) {
            $stm = $this->getDb()->prepare('INSERT INTO user_permission (user_id, name)  VALUES (?, ?)');
            $stm->execute(array($userId, $name));
        }
    }

    /**
     * @param int $userId
     * @param string $name
     * @throws \Exception
     */
    public function removePermission($userId, $name = null)
    {
        if ($name !== null) {
            if ($this->hasPermission($userId, $name)) {
                $stm = $this->getDb()->prepare('DELETE FROM user_permission WHERE user_id = ? AND name = ?');
                $stm->execute(array($userId, $name));
            }
        } else {
            $stm = $this->getDb()->prepare('DELETE FROM user_permission WHERE user_id = ?');
            $stm->execute(array($userId));
        }
    }

    /**
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function getPermissions($userId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM user_permission a WHERE a.user_id = ?');
        $stm->execute(array($userId));
        $arr = array();
        foreach ($stm as $row) {
            $arr[] = $row->name;
        }
        return $arr;
    }

}
