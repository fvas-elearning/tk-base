<?php
namespace Bs\Db\Traits;

use Dom\Template;
use Tk\Db\ModelInterface;
use Tk\Db\Tool;
use Bs\Db\Status;
use Tk\ObjectUtil;

/**
 * Any object implementing the Status event system should also use this trait
 * It should also implementStatusChangeInterface if you are not using an external object
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait StatusTrait
{

    /**
     * Set this to true if the status should send the notification message/emails
     * @var bool
     */
    private $_statusNotify = false;

    /**
     * The notification message that should be sent
     * @var string
     */
    private $_statusMessage = '';

    /**
     * The status event name to be used for this status change
     * @var string
     */
    private $_statusEvent = '';

    /**
     * Execute the status event system.
     * This will save the status to the log and execute the status events
     *
     * @var boolean
     */
    private $_statusExecute = true;

    /**
     * @var null|Status
     */
    private $_statusObject = null;


    // NOTE: Override any of these in your object to customise for your application

    /**
     * Must be Called after the status object is saved.
     * Should return true if the status has changed and the statusChange event should be triggered
     *
     * NOTE: You should override this in your own objects to manage when an event is triggered
     *       For example: if a status from completed back to pending should trigger an event?
     *
     * TODO: This should be refactored so that we only need to send the current and prev status names
     *       to the method like bool hasStatusChanged($currStatusName, $prevStatusName);
     *       Then the object only has the values it needs to use directly.
     *
     *
     * @param Status $status
     * @return boolean
     * @throws \Exception
     */
    public function hasStatusChanged(Status $status = null)
    {
        $prevStatusName = $status->getPreviousName();
        if ($status->getName() != $prevStatusName)
            return true;
        return false;
    }

    /**
     * @return string|Template
     */
    public function getPendingIcon()
    {
        return sprintf('<div class="status-icon bg-tertiary" title="Status Pending"><i class="fa fa-hourglass-half"></i></div>');
    }

    /**
     * @return string|Template
     * @throws \Exception
     */
    public function getPendingHtml()
    {
        $user = $this->getCurrentStatus()->getUser();
        $u = '[Unknown]';
        if ($user) $u = $user->getName();
        return sprintf('<em>%s</em> triggered a pending status for %s [ID: %s]',
            $u, $this->getLabel(), $this->getCurrentStatus()->getFid()
        );
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return ucfirst(preg_replace('/[A-Z]/', ' $0', ObjectUtil::basename($this->getCurrentStatus()->getFkey())));
    }

    // ////////////////////////////////  END  ////////////////////////////


    /**
     * Save the status and execute
     * NOTE: This is called by the StatusHandler DB post save event
     *
     * @throws \Exception
     */
    public function saveStatus()
    {
        if (!$this->getStatusEvent()) { // create a default status name
            $this->setStatusEvent($this->makeStatusEventName());
        }
        $this->_statusObj = $this->getConfig()->createStatus($this);
        if ($this->isStatusExecute()) {
            $this->_statusObj->execute();   // <-- Status saved in here!!!
        }
    }

    /**
     * Object may not have a status
     *
     * @return Status|null
     */
    public function getStatusObject()
    {
        if (!$this->_statusObject) {
            $this->_statusObject = $this->getConfig()->getStatusMap()->findFiltered(array(
                'fkey' => get_class($this),
                'fid' => $this->getId(),
                'status' => $this->getStatus()
            ), Tool::create('id DESC'))->current();
        }
        return $this->_statusObject;
    }

    /**
     * @return bool
     */
    public function isStatusNotify(): bool
    {
        return $this->_statusNotify;
    }

    /**
     * Set this to true if the status should send the notification message/emails
     *
     * @param bool $statusNotify
     * @return $this
     */
    public function setStatusNotify(bool $statusNotify)
    {
        $this->_statusNotify = $statusNotify;
        return $this;
    }

    /**
     * Create a default status event name.
     *
     * @return string
     */
    public function makeStatusEventName()
    {
        $objNs = lcfirst(ObjectUtil::getBaseNamespace($this));
        $objStr = lcfirst(ObjectUtil::basename($this));
        //$objStr = strtolower(ObjectUtil::basename($this));
        $objStatus = lcfirst($this->getStatus());
        $str = 'status.' . $objNs . '.' . $objStr . '.' . $objStatus;
        return $str;
    }

    /**
     * @return string
     */
    public function getStatusMessage(): string
    {
        return $this->_statusMessage;
    }

    /**
     * @param string $statusMessage
     * @return $this
     */
    public function setStatusMessage(string $statusMessage)
    {
        $this->_statusMessage = $statusMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusEvent(): string
    {
        return $this->_statusEvent;
    }

    /**
     * @param string $statusEvent
     * @return $this
     */
    public function setStatusEvent(string $statusEvent)
    {
        $this->_statusEvent = $statusEvent;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStatusExecute(): bool
    {
        return $this->_statusExecute;
    }

    /**
     * Execute the status event system.
     * This will save the status to the log and execute status events
     *
     * @param bool $statusExecute
     * @return $this
     */
    public function setStatusExecute(bool $statusExecute)
    {
        $this->_statusExecute = $statusExecute;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Check if this object has a status
     *
     * @param string|array $status
     * @return bool
     */
    public function hasStatus($status)
    {
        if (!is_array($status)) $status = array($status);
        return in_array($this->getStatus(), $status);
    }

    /**
     * return the status list for a select field
     * @param null|string $status
     * @return array
     */
    public static function getStatusList($status = null)
    {
        $arr = \Tk\Form\Field\Select::arrayToSelectList(\Tk\ObjectUtil::getClassConstants(__CLASS__, 'STATUS'));
        if (is_string($status)) {
            $arr2 = array();
            foreach ($arr as $k => $v) {
                if ($v == $status) {
                    $arr2[$k.' (Current)'] = $v;
                } else {
                    $arr2[$k] = $v;
                }
            }
            $arr = $arr2;
        }
        return $arr;
    }

    /**
     * @param array $errors
     * @return array
     */
    public function validateStatus($errors = [])
    {
        if (!$this->getStatus()) {
            $errors['status'] = 'Invalid value: status';
        }
        return $errors;
    }

    /**
     * @return Status|null|ModelInterface
     * @throws /Exception
     */
    public function getCurrentStatus()
    {
        $status = $this->getConfig()->getStatusMap()->findFiltered(
            ['model' => $this],
            Tool::create('id DESC', 1))->current();
        return $status;
    }

}