<?php
namespace Bs\Table;

use Bs\Db\Permission;
use Bs\Db\Status as StatusAlias;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;
use Tk\Table\Action\ColumnSelect;
use Tk\Table\Action\Csv;
use Tk\Table\Cell;
use Tk\Table\Cell\Text;
use Bs\Db\StatusMap;

/**
 * Example:
 * <code>
 *   $table = new Status::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-05-23
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Status extends \Bs\TableIface
{
    /**
     * @var bool
     */
    protected $showLogUrl = true;

    /**
     * @var array
     */
    protected $selectedColumns = array('id', 'name', 'userId', 'message');


    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->addCss('tk-status-table');

        if ($this->getAuthUser()->hasPermission(Permission::MANAGE_SITE)) {
            $this->appendCell(new Cell\Checkbox('id'));
        }
        $this->appendCell(new Text('name'))->setLabel('Status')->setUrl($this->getEditUrl());

//        $logUrl = null;
//        if ($this->isShowLogUrl())
//            $logUrl = \Uni\Uri::createSubjectUrl('/mailLogManager.html');


        $this->appendCell(new Text('event'));
//            ->setOnPropertyValue(function ($cell, $obj, $value) {
//                /** @var $cell \Tk\Table\Cell\Text */
//                /** @var $obj \Uni\Db\Status */
//
//                return $value;
//            });
//            ->setOnCellHtml(function ($cell, $obj, $html) {
//                /** @var $cell \Tk\Table\Cell\Text */
//                /** @var $obj \Uni\Db\Status */
//                $value = $propValue = $cell->getPropertyValue($obj);
//                if ($cell->getCharLimit() && strlen($propValue) > $cell->getCharLimit()) {
//                    $propValue = substr($propValue, 0, $cell->getCharLimit()-3) . '...';
//                }
//                $cell->setAttr('title', $value);
//                $html = htmlentities($propValue);
//
////                $url = $cell->getCellUrl($obj);
////                if (!$url && $obj->getEvent()) {
////                    $logList = \App\Db\MailLogMap::create()->findFiltered(array('statusId' => $obj->getId()));
////                    if ($logList->count() && $logUrl) {
////                        $cell->setAttr('title', 'Click to view all email logs for this status change.');
////                        $url = $logUrl->set('statusId', $obj->getId());
////                    }
////                }
//
//                if ($url) {
//                    $html = sprintf('<a href="%s">%s</a>', htmlentities($url->toString()), htmlentities($propValue));
//                }
//                return $html;
//            });

        $this->appendCell(new Text('userId'))->addOnPropertyValue(function ($cell, $obj, $value) {
                /** @var $obj StatusAlias */
                $value = '';
                if ($obj->getUser())
                    $value = $obj->getUser()->getName();
                return $value;
            });

        $this->appendCell(new Text('message'))->addCss('key wrap-normal')
            ->addOnCellHtml(function ($cell, $obj, $html) {
                /** @var $cell Text */
                /** @var $obj StatusAlias */
                $cell->setAttr('title', 'Message');
                return '<small>' . nl2br(\Tk\Str::stripEntities($html)) . '</small>' ;
            });
        $this->appendCell(new Cell\Date('created'));

        // Filters
        //$this->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Search');

        // Actions
        if ($this->getAuthUser()->hasPermission(Permission::MANAGE_SITE)) {
            $this->appendAction(\Tk\Table\Action\Delete::create());
        }
        $this->appendAction(ColumnSelect::create()->setSelected($this->selectedColumns));
        $this->appendAction(Csv::create());

        
        return $this;
    }

    /**
     * @param array $filter
     * @param null|Tool $tool
     * @return ArrayObject|StatusAlias[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('created DESC');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = StatusMap::create()->findFiltered($filter, $tool);
        return $list;
    }

    /**
     * Note only use this if you need to modify the columns before the init() method
     * @return array
     */
    public function getSelectedColumns()
    {
        return $this->selectedColumns;
    }

    /**
     * Note only use this if you need to set the columns before the init() method
     *
     * @param array $selectedColumns
     * @return Status
     */
    public function setSelectedColumns($selectedColumns)
    {
        $this->selectedColumns = $selectedColumns;
        return $this;
    }


}