<?php

namespace Bs\Db;

use DateTime;
use Exception;
use Tk\DataMap\DataMap;
use Tk\DataMap\Form;
use Tk\DataMap\Db;
use Tk\Date;
use Tk\Db\Filter;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Map\Model;
use Tk\Db\Pdo;
use Tk\Db\Tool;
use Tk\ObjectUtil;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusMap extends Mapper
{

    /**
     * @param Pdo|null $db
     * @throws Exception
     */
    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->initTable($this->getDb());
    }

    /**
     * init table
     * @param Pdo|null $db
     * @throws Exception
     */
    public function initTable($db)
    {

        if (!$db->hasTable('status')) {
            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `status` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,              -- The user who performed the activity
  `msq_user_id` INT UNSIGNED NOT NULL DEFAULT 0,          -- If the user was masquerading who was the root masquerading user
  
  -- For tkuni lib only
  `institution_id` INTEGER NOT NULL DEFAULT 0,            
  `course_id` INTEGER NOT NULL DEFAULT 0,
  `subject_id` INTEGER NOT NULL DEFAULT 0,
  
  `fkey` VARCHAR(64) NOT NULL DEFAULT '',                 -- A foreign key as a string (usually the object name)
  `fid` INTEGER NOT NULL DEFAULT 0,                       -- foreign_id
  `name` VARCHAR(32) NOT NULL DEFAULT '',                 -- pending|approved|not_approved
  `event` VARCHAR(128) NOT NULL DEFAULT '',               -- the name of the event triggered if any (link status_event.name)
  `notify` BOOL NOT NULL DEFAULT 1,                       -- Was the message email sent
  `message` TEXT,                                         -- A status update log message
  `serial_data` TEXT,                                     -- json/serialized data of any related objects pertaining to this activity
  `del` BOOL NOT NULL DEFAULT 0,                          -- This value should mirror its model `del` value
  `created` DATETIME NOT NULL,
  KEY (`user_id`),
  KEY (`msq_user_id`),
  KEY (`fid`),
  KEY (`fkey`),
  KEY (`fid`, `id`)
) ENGINE = InnoDB;
SQL;
            $db->query($sql);
        } else {
/*
    //TODO: Need to update all site using status if the following does not work
    alter table status
        add institution_id INT UNSIGNED default 0 not null after msq_user_id;
    alter table status
        add course_id INT UNSIGNED default 0 not null after institution_id;
    alter table status
        add subject_id INT UNSIGNED default 0 not null after course_id;
*/
            $statusTbl = $db->getTableInfo('status');
            if (!array_key_exists('institution_id', $statusTbl)) {
                $db->query('alter table status add institution_id INT UNSIGNED default 0 not null after msq_user_id;');
            }
            if (!array_key_exists('course_id', $statusTbl)) {
                $db->query('alter table status add course_id INT UNSIGNED default 0 not null after institution_id;');
            }
            if (!array_key_exists('subject_id', $statusTbl)) {
                $db->query('alter table status add subject_id INT UNSIGNED default 0 not null after course_id;');
            }
        }
    }

    /**
     *
     * @return DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->dbMap = new DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('userId', 'user_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('msqUserId', 'msq_user_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('institutionId', 'institution_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('courseId', 'course_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('subjectId', 'subject_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('fid'));
            $this->dbMap->addPropertyMap(new Db\Text('fkey'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('event'));
            $this->dbMap->addPropertyMap(new Db\Boolean('notify'));
            $this->dbMap->addPropertyMap(new Db\Text('message'));
            $this->dbMap->addPropertyMap(new Db\Json('serialData', 'serial_data'));
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
            $this->formMap->addPropertyMap(new Form\Integer('userId'));
            $this->formMap->addPropertyMap(new Form\Integer('msqUserId'));
            $this->formMap->addPropertyMap(new Form\Integer('institutionId'));
            $this->formMap->addPropertyMap(new Form\Integer('courseId'));
            $this->formMap->addPropertyMap(new Form\Integer('subjectId'));
            $this->formMap->addPropertyMap(new Form\Integer('fid'));
            $this->formMap->addPropertyMap(new Form\Text('fkey'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('event'));
            $this->formMap->addPropertyMap(new Form\Text('message'));
            $this->formMap->addPropertyMap(new Form\Boolean('notify'));
            $this->formMap->addPropertyMap(new Form\Date('created'));
        }
        return $this->formMap;
    }

    /**
     * @param array|Filter $filter
     * @param Tool|null $tool
     * @return ArrayObject|Status[]
     * @throws Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        $r = $this->selectFromFilter($this->makeQuery(Filter::create($filter)), $tool);
        //vd($this->getDb()->getLastQuery());
        return $r;
    }

    /**
     * @param Filter $filter
     * @return Filter
     */
    public function makeQuery(Filter $filter)
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.message LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
            }
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('a.user_id = %d AND ', (int)$filter['userId']);
        }

        if (!empty($filter['msqUserId'])) {
            $filter->appendWhere('a.msq_user_id = %d AND ', (int)$filter['msqUserId']);
        }
        if (!empty($filter['model']) && $filter['model'] instanceof Model) {
            $filter['fid'] = $filter['model']->getId();
            $filter['fkey'] = get_class($filter['model']);
        }
        if (!empty($filter['fid'])) {
            $filter->appendWhere('a.fid = %d AND ', (int)$filter['fid']);
        }
        if (!empty($filter['fkey'])) {
            $filter->appendWhere('a.fkey = %s AND ', $this->quote($filter['fkey']));
        }

        if (!empty($filter['before']) && $filter['before'] instanceof DateTime) {
            $filter->appendWhere('a.created < %s AND ', $this->quote($filter['before']->format(Date::FORMAT_ISO_DATETIME)));
        }
        if (!empty($filter['after']) && $filter['after'] instanceof DateTime) {
            $filter->appendWhere('a.created > %s AND ', $this->quote($filter['after']->format(Date::FORMAT_ISO_DATETIME)));
        }

        if (!empty($filter['monthFrom']) && $filter['monthFrom'] instanceof DateTime) {
            $filter->appendWhere('DATE_FORMAT(a.created, "%%Y-%%m") >= %s AND ', $this->quote($filter['monthFrom']->format('Y-m')));
        }
        if (!empty($filter['monthTo']) && $filter['monthTo'] instanceof DateTime) {
            $filter->appendWhere('DATE_FORMAT(a.created, "%%Y-%%m") <= %s AND ', $this->quote($filter['monthTo']->format('Y-m')));
        }

        if (!empty($filter['event'])) {
            $w = $this->makeMultiQuery($filter['event'], 'a.event');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['name'])) {
            $w = $this->makeMultiQuery($filter['name'], 'a.name');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['institutionId'])) {
            $filter->appendWhere('a.institution_id = %d AND ', (int)$filter['institutionId']);
        }

        if (!empty($filter['courseId'])) {
            $filter->appendWhere('a.course_id = %d AND ', (int)$filter['courseId']);
        }

        if (!empty($filter['subjectId'])) {
            if (empty($filter['courseId'])) {
                $filter->appendWhere('a.subject_id = %d AND ', (int)$filter['subjectId']);
            } else {
                $filter->appendWhere('(a.subject_id = %d OR a.subject_id = 0) AND ', (int)$filter['subjectId']);
            }
        }


        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }
//vd($filter);
        return $filter;
    }

    /**
     * @param string $fkey
     * @param int $fid
     */
    public function deleteByModel($fkey, $fid)
    {
        try {
            $stm = $this->getDb()->prepare('UPDATE status SET del = 1 WHERE fkey = ? AND fid = ?');
            $stm->execute(array($fkey, $fid));
        } catch (\Tk\Db\Exception $e) { }
    }

    /**
     * @param array|Filter $filter
     * @return array
     * @throws Exception
     */
    public function findKeys($filter)
    {
        $filter = $this->makeQuery(Filter::create($filter));
        $sql = sprintf('SELECT DISTINCT a.fkey FROM %s WHERE %s ', $filter->getFrom(), $filter->getWhere());
        $r = $this->getDb()->query($sql);
        $a = array();
        foreach ($r as $obj) {
            $a[ObjectUtil::basename($obj->fkey)] = $obj->fkey;
        }
        return $a;
    }

    /**
     * @param array|Filter $filter
     * @param Tool $tool
     * @return ArrayObject
     * @throws Exception
     */
    public function findCurrentStatus($filter, $tool = null)
    {
        $filter = $this->makeQuery(Filter::create($filter));
        $filter->appendFrom(', (
  SELECT y.id
  FROM `status` y
  WHERE y.`id` = (
    SELECT MAX(z.`id`)
    FROM `status` z
    WHERE z.`fid` = y.`fid` AND z.`fkey` = y.`fkey`)
  ) y');
        $filter->prependWhere('a.id = y.id AND ');
        $r = $this->selectFromFilter($filter, $tool);
        return $r;
    }

    /**
     * @param array|Filter $filter
     * @return array
     */
    public function findMonthlyTotals($filter)
    {
        $a = array();
        try {
            $filter = $this->makeQuery(Filter::create($filter));
            $sql = sprintf('SELECT DATE_FORMAT(a.created, "%%Y-%%m") AS month, COUNT(*) as total
FROM %s
WHERE %s
GROUP BY DATE_FORMAT(a.created, "%%Y-%%m")', $filter->getFrom(), $filter->getWhere());
            $stm = $this->getDb()->prepare($sql);
            $stm->execute();
            foreach ($stm as $row) {
                $a[$row->month] = $row->total;
            }
        } catch (\Tk\Db\Exception $e) { }
        return $a;
    }

}