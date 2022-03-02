<?php
namespace Bs\Table;

use Bs\Db\UserIface;
use Tk\Form\Field;
use Tk\Table\Cell;

/**
 * Example:
 * <code>
 *   $table = new User::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2018-11-19
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class User extends \Bs\TableIface
{

    /**
     * @var \Tk\Uri
     */
    protected $editUrl = null;

    protected $targetType = '';

    /**
     * @return string
     */
    public function getTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * @param string $targetType
     * @return $this
     */
    public function setTargetType(string $targetType)
    {
        $this->targetType = $targetType;
        return $this;
    }

    /**
     * @return \Tk\Uri
     */
    public function getEditUrl()
    {
        return $this->editUrl;
    }

    /**
     * @param \Tk\Uri $editUrl
     * @return User
     */
    public function setEditUrl($editUrl)
    {
        $this->editUrl = $editUrl;
        return $this;
    }
    
    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $actionsCell = $this->getActionCell();
        $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('Masquerade', \Tk\Uri::create(),
            'fa fa-user-secret', 'tk-masquerade'))->setAttr('data-confirm', 'You are about to masquerade as the selected user?')
            ->addOnShow(function ($cell, $obj, $button) {
                /* @var $obj \Bs\Db\User */
                /* @var $button \Tk\Table\Cell\ActionButton */
                $config = \Bs\Config::getInstance();
                if ($config->getMasqueradeHandler()->canMasqueradeAs($config->getAuthUser(), $obj)) {
                    $button->setUrl(\Tk\Uri::create()->set(\Bs\Listener\MasqueradeHandler::MSQ, $obj->getHash()));
                } else {
                    $button->setAttr('disabled', 'disabled')->addCss('disabled');
                }
            });
    
        $this->appendCell(new Cell\Checkbox('id'));
        $this->appendCell($actionsCell);

        $this->appendCell(new \Tk\Table\Cell\Text('nameFirst'))->setLabel('Name')->addOnPropertyValue(function ($cell, $obj, $value) {
            /** @var UserIface $obj */
            return $obj->getName();
        })->addCss('key')->setUrl($this->getEditUrl());
        $this->appendCell(new \Tk\Table\Cell\Text('username'));
        $this->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID');
        $this->appendCell(new \Tk\Table\Cell\Email('email'));
        $this->appendCell(new \Tk\Table\Cell\Text('phone'));
        //$this->appendCell(new \Tk\Table\Cell\Text('type'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('active'));
        $this->appendCell(new \Tk\Table\Cell\Date('lastLogin'));
        $this->appendCell(new \Tk\Table\Cell\Date('created'));

        // Filters
        $this->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Search');

        $list = ['Yes' => '1', 'No' => '0'];
        $this->appendFilter(Field\Select::createSelect('active', $list)->setValue('1')->prependOption('-- Active --'));


        // Actions
        //$this->appendAction(\Tk\Table\Action\Link::createLink('New User', 'fa fa-plus', \Bs\Uri::createHomeUrl('/userEdit.html')));
        $arr = array('modified', 'created');
        /** @var \Tk\Table\Action\ColumnSelect $cs */
        $this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setUnselected($arr));

        $this->appendAction(\Tk\Table\Action\Delete::create()->setExcludeIdList(array('1')));
        //$this->appendAction(\Tk\Table\Action\Delete::create())->setExcludeIdList(array(1));
        $this->appendAction(\Tk\Table\Action\Csv::create());

        
        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|\Bs\Db\User[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$this->getTargetType() && !empty($filter['type']))
            $this->setTargetType($filter['type']);
        if (!$tool) $tool = $this->getTool('a.name_first');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = $this->getConfig()->getUserMapper()->findFiltered($filter, $tool);
        return $list;
    }

}