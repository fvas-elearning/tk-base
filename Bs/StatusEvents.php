<?php
namespace Bs;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class StatusEvents
{

    /**
     * called when a status object change its current status
     *
     * @event \App\Event\StatusEvent
     */
    const STATUS_CHANGE = 'status.message';

    /**
     * Called after the status events have been called and all messages are ready for sending.
     *
     * @event \App\Event\StatusEvent
     */
    const STATUS_SEND_MESSAGES = 'status.send.messages';

}