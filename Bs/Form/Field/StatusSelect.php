<?php
namespace Bs\Form\Field;


/**
 * This field is a select with a checkbox.
 * The checkbox state is not saved, and is reset to the default value
 * on each page load. it is meant to be used as a trigger element.
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusSelect extends \Tk\Form\Field\Select
{

    /**
     * @var boolean
     */
    protected $notifyValue = true;

    /**
     * @var string
     */
    protected $messageValue = '';

    /**
     * @var string
     */
    protected $notifyName = 'Notify';

    /**
     * @var string
     */
    protected $messageName = 'Message';


    /**
     * @param string $name
     * @param \Tk\Form\Field\Option\ArrayIterator|array|null $optionIterator
     * @internal param string $checkboxName
     */
    public function __construct($name, $optionIterator = null)
    {
        parent::__construct($name, $optionIterator);
        $this->notifyName = '_' . $name . 'Notify';
        $this->messageName = '_' . $name . 'Message';
    }

    /**
     * @return string
     */
    public function getMessageName()
    {
        return $this->messageName;
    }

    /**
     * @return string
     */
    public function getMessageValue()
    {
        return $this->messageValue;
    }

    /**
     * @return string
     */
    public function getNotifyName()
    {
        return $this->notifyName;
    }

    /**
     * @return bool
     */
    public function isChecked()
    {
        return $this->notifyValue;
    }

    /**
     * @param $b
     * @return $this
     */
    public function setChecked($b)
    {
        $this->notifyValue = $b;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNotifyValue(): bool
    {
        return $this->notifyValue;
    }

    /**
     * @param bool $notifyValue
     * @return StatusSelect
     */
    public function setNotifyValue(bool $notifyValue)
    {
        $this->notifyValue = $notifyValue;
        return $this;
    }

    /**
     * @param array|\ArrayObject $values
     * @return $this|\Tk\Form\Field\Select
     */
    public function load($values)
    {
        parent::load($values);
        $vals = null;
        if ($this->getForm() && $this->getForm()->isSubmitted()) {
            $vals = array();
            if (array_key_exists($this->getName(), $values)) {
                $vals[$this->getName()] = $values[$this->getName()];
            }
            $this->notifyValue = false;
            $vals[$this->getNotifyName()] = false;
            if (isset($values[$this->getNotifyName()]) && $values[$this->getNotifyName()] == $this->getNotifyName()) {
                $this->notifyValue = true;
                $vals[$this->getNotifyName()] = true;
            }
            if (isset($values[$this->getMessageName()]) && $values[$this->getMessageName()]) {
                $this->messageValue = $values[$this->getMessageName()];
                $vals[$this->getMessageName()] = $values[$this->getMessageName()];
            }
            $this->setValue($vals);
        }
        return $this;
    }


    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $t = parent::show();
        $t->appendJsUrl(\Uni\Uri::create('/vendor/uom/tk-base/Bs/Form/Field/jquery.statusSelect.js'));
        $js = <<<JS
jQuery(function ($) {
    $('.tk-status-select').statusSelect();
});
JS;
        $t->appendJs($js);

        $t->setAttr('element', 'data-notify-name', $this->getNotifyName());
        $t->setAttr('element', 'data-message-name', $this->getMessageName());

        $t->setAttr('checkbox', 'name', $this->getNotifyName());
        $t->setAttr('checkbox', 'value', $this->getNotifyName());
        $t->setAttr('checkbox', 'aria-label', $this->getNotifyName());
        if ($this->isChecked()) {
            $t->setAttr('checkbox', 'checked', 'checked');
        }
        $t->setAttr('notes', 'name', $this->getMessageName());
        return $t;
    }

    /**
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-status-select">
  <div class="input-group">
    <div class="input-group-prepend">
      <div class="input-group-text">
        <input type="checkbox" var="checkbox" title="Send Status Change Notification Email" />
      </div>
    </div>
    <select var="element" type="text" aria-label="Status" class="form-control"><option repeat="option" var="option"></option></select>
  </div>
  <div class="" style="margin-top: -2px;position:relative;">
    <textarea name="statusNotes" class="form-control" style="min-height: 80px;" placeholder="Status Update Message." var="notes"></textarea>
  </div>
</div>
HTML;
        
        return \Dom\Loader::load($xhtml);
    }
}