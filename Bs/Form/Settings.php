<?php
namespace Bs\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   $form = new User::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2018-11-19
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class Settings extends \Bs\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $tab = 'Site';
        $this->appendField(new Field\Input('site.title'))->setTabGroup($tab)->setLabel('Site Title')->setRequired(true);
        $this->appendField(new Field\Input('site.short.title'))->setTabGroup($tab)->setLabel('Site Short Title')->setRequired(true);
        $this->appendField(new Field\Input('site.email'))->setTabGroup($tab)->setLabel('Site Email')->setRequired(true)
            ->setNotes('The default email address the system will use to send contact requests and system messages.');

        $this->appendField(new Field\Input('google.map.apikey'))->setTabGroup($tab)->setLabel('Google API Key')
            ->setNotes('<a href="https://cloud.google.com/maps-platform/" target="_blank">Get Google Maps Api Key</a> And be sure to enable `Maps Javascript API`, `Maps Embed API` and `Places API for Web` for this site.');
        $this->appendField(new Field\Checkbox('site.client.registration'))->setTabGroup($tab)->setLabel('Client Registration')
            ->setCheckboxLabel('Enable user site registration.');
        $this->appendField(new Field\Checkbox('site.client.activation'))->setTabGroup($tab)->setLabel('Account Activation')
            ->setCheckboxLabel('Enable automatic activation of registered user accounts.')->setNotes('If not checked you must manually activate new user accounts.');

        $tab = 'Email';
        $this->appendField(new Field\Textarea('site.email.sig'))->setTabGroup($tab)->setLabel('Email Signature')
            ->setNotes('Set the email signature to appear at the foot of all system emails.')->addCss('mce-min');

        $tab = 'Metadata';
        $this->appendField(new Field\Input('site.meta.keywords'))->setTabGroup($tab)->setLabel('Metadata Keywords');
        $this->appendField(new Field\Input('site.meta.description'))->setTabGroup($tab)->setLabel('Metadata Description');

        $this->appendField(new Field\Textarea('site.global.js'))->setAttr('id', 'site-global-js')->setTabGroup($tab)->setLabel('Custom JS')
            ->setNotes('You can omit the &lt;script&gt; tags here')->addCss('code')->setAttr('data-mode', 'javascript');
        $this->appendField(new Field\Textarea('site.global.css'))->setAttr('id', 'site-global-css')->setTabGroup($tab)->setLabel('Custom CSS Styles')
            ->setNotes('You can omit the &lt;style&gt; tags here')->addCss('code')->setAttr('data-mode', 'css');

        $tab = 'Maintenance';
        $this->appendField(new Field\Checkbox('site.maintenance.enabled'))->addCss('check-enable')->setLabel('')->setTabGroup($tab)->setCheckboxLabel('Maintenance Mode Enabled');
        $this->appendField(new Field\Textarea('site.maintenance.message'))->addCss('mce-min')->setTabGroup($tab)->setLabel('Message');


        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\LinkButton('cancel', $this->getBackUrl()));

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load($this->getModel()->toArray());
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();
        $this->getData()->replace($values);

        if (empty($values['site.title']) || strlen($values['site.title']) < 3) {
            $form->addFieldError('site.title', 'Please enter your name');
        }
        if (empty($values['site.email']) || !filter_var($values['site.email'], \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('site.email', 'Please enter a valid email address');
        }

        if ($form->hasErrors()) {
            return;
        }

        $this->getData()->save();

        \Tk\Alert::addSuccess('Site settings saved.');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create());
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Tk\Db\Data
     */
    public function getData()
    {
        return $this->getModel();
    }

    /**
     * @param \Tk\Db\Data $data
     * @return $this
     */
    public function setData($data)
    {
        return $this->setModel($data);
    }
    
}