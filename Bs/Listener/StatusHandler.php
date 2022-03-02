<?php
namespace Bs\Listener;

use Bs\Db\Traits\StatusTrait;
use Bs\DbEvents;
use Bs\Event\DbEvent;
use Bs\Form\Field\StatusSelect;
use Tk\ConfigTrait;
use Tk\Db\ModelInterface;
use Tk\Event\FormEvent;
use Tk\Event\Subscriber;
use Tk\Form\FormEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * @param DbEvent $event
     * @throws \Exception
     */
    public function onModelSavePost(DbEvent $event)
    {
        /** @var StatusTrait $model */
        $model = $event->getModel();
        if (method_exists($model, 'saveStatus')) {
            $model->saveStatus();
        }
    }

    /**
     * @param DbEvent $event
     * @throws \Exception
     */
    public function onModelDeletePost(DbEvent $event)
    {
        /** @var StatusTrait|ModelInterface $model */
        $model = $event->getModel();
        if (method_exists($model, 'saveStatus')) {
            \Bs\Db\StatusMap::create()->deleteByModel(get_class($model), $model->getId());
        }
    }

    /**
     * This function loads the StatusTrait parameters in the background
     *   do not change this as your status events may stop executing.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
//        // TODO: there should be a better way to check this condition (IE: is a status form)
//        if (!$event->getForm()->getField('status')) return;

        /** @var StatusSelect $field */
        $field = $event->getForm()->getField('status');
        if (!$field || !$field instanceof StatusSelect) return;

        /** @var StatusTrait|ModelInterface $model */
        $model = $event->getForm()->getModel();
        $values = $event->getForm()->getValues();

        $model->setStatusNotify(false);
        if (isset($values[$field->getNotifyName()]) && $values[$field->getNotifyName()] == $field->getNotifyName()) {
            $model->setStatusNotify(true);
        }

        if (isset($values[$field->getMessageName()])) {
            $model->setStatusMessage($values[$field->getMessageName()]);
        }

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            DbEvents::MODEL_SAVE_POST => array('onModelSavePost', 0),
            DbEvents::MODEL_DELETE_POST => array('onModelDeletePost', 0),
            FormEvents::FORM_SUBMIT =>  array('onSubmit', 0)
        );
    }


}
