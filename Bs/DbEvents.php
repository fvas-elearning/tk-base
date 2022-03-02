<?php
namespace Bs;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class DbEvents
{

    /**
     * Fired when a \Tk\Db\Map\Model object is inserted to the DB.
     * Called just before the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_INSERT = 'db.model.insert';

    /**
     * Fired when a \Tk\Db\Map\Model object is inserted to the DB.
     * Called just after the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_INSERT_POST = 'db.model.insert.post';

    /**
     * Fired when a \Tk\Db\Map\Model object is updated in the DB.
     * Called just before the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_UPDATE = 'db.model.update';

    /**
     * Fired when a \Tk\Db\Map\Model object is updated in the DB.
     * Called just after the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_UPDATE_POST = 'db.model.update.post';

    /**
     * Fired when a \Tk\Db\Map\Model object is saved the DB.
     * Also one th event of INSERT/UPDATE will be fired after the SAVE event
     * Called just before the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_SAVE = 'db.model.save';

    /**
     * Fired when a \Tk\Db\Map\Model object is saved the DB.
     * Also one th event of INSERT/UPDATE will be fired after the SAVE event
     * Called just after the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_SAVE_POST = 'db.model.save.post';

    /**
     * Fired when a \Tk\Db\Map\Model object is deleted from the DB.
     * Called just before the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_DELETE = 'db.model.delete';

    /**
     * Fired when a \Tk\Db\Map\Model object is deleted from the DB.
     * Called just after the DB query
     *
     * @event \Bs\Event\DbEvent
     */
    const MODEL_DELETE_POST = 'db.model.delete.post';
    
}