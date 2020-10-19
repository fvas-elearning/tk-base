<?php
namespace Bs\Db;


use Bs\Db\Traits\ForeignModelTrait;
use Exception;
use Tk\Db\Map\Model;
use Tk\Db\ModelInterface;
use Tk\Db\Tool;
use Tk\Form\Field\Iface;
use Tk\Log;
use Tk\ObjectUtil;
use Bs\Db\Traits\CreatedTrait;
use Bs\Db\Traits\UserTrait;
use DateTime;
use Bs\Db\Traits\StatusTrait;
use Bs\Event\StatusEvent;
use Bs\Form\Field\StatusSelect;
use Bs\StatusEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Status extends Model
{
    use UserTrait;
    use ForeignModelTrait;
    use CreatedTrait;

    // Status type templates (use these in your own objects)
    // const STATUS_PENDING = Status::STATUS_PENDING;       <---- This is valid syntax in you objects.
    const STATUS_PENDING = 'pending';
    const STATUS_AMEND = 'amend';
    const STATUS_APPROVED = 'approved';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_NOT_APPROVED = 'not approved';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * @var int
     */
    public $id = 0;

    /**
     * The user who performed the activity
     * @var int
     */
    public $userId = 0;

    /**
     * If the user was masquerading who was the root masquerading user
     * @var int
     */
    public $msqUserId = 0;

    /**
     * The id of the subject of the activity
     * @var int
     */
    public $fid = 0;

    /**
     * The object class/key the foreign_id relates to
     * @var string
     */
    public $fkey = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * The name of the event if triggered, '' for none
     * @var string
     */
    public $event = '';

    /**
     * Should this status trigger the mail notification handler
     * @var bool
     */
    public $notify = true;

    /**
     * @var string
     */
    public $message = '';

    /**
     * objects or array of objects
     * @var mixed
     */
    public $serialData = null;

    /**
     * @var DateTime
     */
    public $created = null;


    /**
     * @var ModelInterface|StatusTrait
     */
    private $_modelStrategy = null;

    /**
     * @var Status
     */
    private $_previous = null;


    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->_CreatedTrait();
    }

    /**
     * @param ModelInterface|StatusTrait $model
     * @return Status
     * @throws Exception
     */
    public static function create($model)
    {
        $obj = new static();
        $obj->setForeignModel($model);
        $obj->setName($model->getStatus());
        $obj->setNotify($model->isStatusNotify());
        $obj->setMessage($model->getStatusMessage());
        $obj->setEvent($model->getStatusEvent());

        $config = $obj->getConfig();
        if ($config->getAuthUser()) {
            $obj->setUserId($config->getAuthUser()->getId());
            if ($config->getMasqueradeHandler()->isMasquerading()) {
                $msqUser = $config->getMasqueradeHandler()->getMasqueradingUser();
                if ($msqUser) {
                    $obj->setMsqUserId($msqUser->getId());
                }
            }
        }

        return $obj;
    }

    /**
     * Trigger status change events and save the status object.
     * Call this after you create the status object
     *
     * @throws Exception
     */
    public function execute()
    {
        if (!$this->getName() || $this->getName() == $this->getPreviousName()) {
            Log::debug('Status skipped');
            return;
        }
        $this->save();

        /** @var StatusTrait $model */
        $model = $this->getModel();
        if (!\Tk\ObjectUtil::classUses($model, StatusTrait::class)) {
            Log::error(get_class($this->getModel()) . ' does not use StatusTrait. Please update your class definition.');
            return;
        }

        // Trigger mail event depending on the model
        if ($model->hasStatusChanged($this)) {
            // Trigger Event
            $e = new StatusEvent($this);
            if ($this->getConfig()->getEventDispatcher()) {
                // Fire event to setup status mail messages
                $this->getConfig()->getEventDispatcher()->dispatch(StatusEvents::STATUS_CHANGE, $e);
                if ($this->getEvent()) {
                    // Trigger status events for system wide processing. EG: 'status.placement.not approved', status.placementrequest.pending'
                    $this->getConfig()->getEventDispatcher()->dispatch($this->getEvent(), $e);
                }
                // Fire the event to send those messages
                $this->getConfig()->getEventDispatcher()->dispatch(StatusEvents::STATUS_SEND_MESSAGES, $e);
            }
        } else {
            // Set the notify flag to false as there was no event triggering
            $this->setNotify(false);
            $this->save();
        }
    }

    /**
     * @return Status|null|ModelInterface|StatusTrait
     * @throws Exception
     */
    public function getPrevious()
    {
        if (!$this->_previous) {
            $filter = array(
                'before' => $this->getCreated(),
                'fid' => $this->getFid(),
                'fkey' => $this->getFkey()
            );
            $this->_previous = StatusMap::create()->findFiltered($filter, Tool::create('created DESC', 1))->current();
        }
        return $this->_previous;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPreviousName()
    {
        if ($this->getPrevious())
            return $this->getPrevious()->getName();
        return '';
    }

    /**
     * @param Status|null $status
     * @param string $userType
     * @return Status|null
     * @throws Exception
     */
    public static function findLastByUserType($status, $userType)
    {
        if (!$status) return $status;
        if ($status->getUser() && $status->getUser()->hasType($userType)) {
            return $status;
        }
        return self::findLastByUserType($status->getPrevious(), $userType);
    }

    /**
     * Return a unique list of users that have changed a status for this object
     *
     * @param string|null $type
     * @return array|User[]
     * @throws Exception
     */
    public function findUsersByType($type = '')
    {
        $userList = array();
        $statusList = StatusMap::create()->findFiltered(array('model' => $this->getModel()));
        foreach ($statusList as $status) {
            if (!$status->getUser()) continue;
            if ($type && $status->getUser()->getType() == $type) {
                $userList[$status->getUserId()] = $status->getUser();
            }
        }
        return $userList;
    }

    /**
     * @return int
     */
    public function getMsqUserId(): int
    {
        return $this->msqUserId;
    }

    /**
     * @param int $msqUserId
     * @return Status
     */
    public function setMsqUserId(int $msqUserId): Status
    {
        $this->msqUserId = $msqUserId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Status
     */
    public function setName(string $name): Status
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @param string $event
     * @return Status
     */
    public function setEvent(string $event): Status
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNotify()
    {
        return $this->notify;
    }

    /**
     * @param bool $notify
     * @return Status
     */
    public function setNotify($notify): Status
    {
        $this->notify = $notify;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Status
     */
    public function setMessage(string $message): Status
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSerialData()
    {
        return $this->serialData;
    }

    /**
     * @param mixed $serialData
     * @return Status
     */
    public function setSerialData($serialData)
    {
        $this->serialData = $serialData;
        return $this;
    }

}