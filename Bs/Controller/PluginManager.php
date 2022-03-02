<?php
namespace Bs\Controller;

use Bs\Config;
use Dom\Template;
use Tk\Db\Exception;
use Tk\Request;
use Tk\Form;
use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Table\Cell\ButtonCollection;
use Tk\Table\Ui\ActionButton;
use Tk\Table;
use Tk\Table\Cell\Date;
use Tk\Table\Cell\Text;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 *
 * @todo Add a delete data confirmation when uninstalling/disabling the plugin
 */
class PluginManager extends \Bs\Controller\AdminIface
{

    /**
     * @var \Tk\Form
     */
    protected $form = null;

    /**
     * @var \Tk\Table
     */
    protected $table = null;


    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('Plugin Manager');

    }

    /**
     * @param Request $request
     * @throws Form\Exception
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->pluginFactory = $this->getConfig()->getPluginFactory();

        if ($request->has('act')) {
            $this->doActivatePlugin($request);
        } else if ($request->has('del')) {
            $this->doDeletePlugin($request);
        } else if ($request->has('deact')) {
            $this->doDeactivatePlugin($request);
        }

        // Upload plugin
        $this->form = $this->getConfig()->createForm('formEdit');
        $this->form->setRenderer($this->getConfig()->createFormRenderer($this->form));
        $this->form->appendField(new Field\File('package', '', $this->getConfig()->getPluginPath()))->addCss('tk-fileinput');
        $this->form->appendField(new Event\Submit('upload', array($this, 'doUpload')))->addCss('btn-primary');
        $this->form->execute();

        // Plugin manager table
        $this->table = $this->getConfig()->createTable('PluginList');
        $this->table->setRenderer($this->getConfig()->createTableRenderer($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Text('icon'))->setLabel('')
            ->addOnCellHtml(function ($cell, $obj, $html) {
                $pluginName = \Bs\Config::getInstance()->getPluginFactory()->cleanPluginName($obj->name);
                if (is_file(\Tk\Config::getInstance()->getPluginPath().'/'.$pluginName.'/icon.png')) {
                    $url =  \Tk\Config::getInstance()->getPluginUrl() . '/' . $pluginName . '/icon.png';
                    $html = sprintf('<img class="media-object" src="%s" var="icon" style="width: 32px;" choice="icon"/>', $url);
;                }
                return $html;
            });;


        //$this->table->appendCell(new ActionsCell('actions'));
        $actionsCell = ButtonCollection::create('actions')->setAttr('style', 'width: 75px;');

        $actionsCell->append(ActionButton::createBtn('Activate Plugin ', '#', 'fa fa-sign-in'))
            ->addCss('btn-success btn-xs noblock act')
            ->setAttr('data-confirm', 'Are you sure you want to install this plugin?')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) {
                /* @var $obj \stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($pluginFactory->isActive($pluginName)) {
                    $cell->getRow()->addCss('plugin-active');
                    $button->setVisible(false);
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    $button->setUrl(\Tk\Uri::create()->reset()->set('act', $pluginName));
                }
            })->setGroup('group');

        $actionsCell->append(ActionButton::createBtn('Delete Plugin Files ', '#', 'fa fa-trash-o'))
            ->addCss('btn-danger btn-sm noblock del')
            ->setAttr('data-confirm', 'Are you sure you want to delete this plugin?')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) {
                /* @var $obj \stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($pluginFactory->isActive($pluginName)) {
                    $cell->getRow()->addCss('plugin-active');
                    $button->setVisible(false);
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    if (!\Tk\Plugin\Factory::isComposer($pluginName, \Bs\Config::getInstance()->getComposer())) {
                        $button->setUrl(\Tk\Uri::create()->reset()->set('del', $pluginName));
                    } else {
                        $button->setAttr('disabled')->addCss('disabled');
                        $button->setAttr('title', 'Cannot delete a composer plugin. See site administrator.');
                    }
                }
            })->setGroup('group');

        $actionsCell->append(ActionButton::createBtn('Configure Plugin ', '#', 'fa fa-cog'))
            ->addCss('btn-primary btn-sm noblock setup')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) {
                /* @var $obj \stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($pluginFactory->isActive($pluginName)) {
                    $cell->getRow()->addCss('plugin-active');
                    if ($plugin->getSettingsUrl()) {
                        $button->setUrl($plugin->getSettingsUrl());
                    } else {
                        $button->setAttr('title', 'No Configuration Available');
                        $button->addCss('disabled')->setAttr('diasabled');
                    }
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    $button->setVisible(false);
                }
            })->setGroup('group');

        $actionsCell->append(ActionButton::createBtn('Deactivate Plugin ', '#', 'fa fa-power-off'))
            ->addCss('btn-danger btn-sm noblock deact')
            ->setAttr('data-confirm', 'Are you sure you want to uninstall this plugin?\\nThis will delete all data relating to the plugin.')
            ->addOnShow(function (ButtonCollection $cell, $obj, ActionButton $button) {
                /* @var $obj \stdClass */
                $pluginFactory = Config::getInstance()->getPluginFactory();
                $pluginName = $pluginFactory->cleanPluginName($obj->name);
                /** @var \Tk\Plugin\Iface $plugin */
                $plugin = $pluginFactory->getPlugin($pluginName);
                if ($pluginFactory->isActive($pluginName)) {
                    $cell->getRow()->addCss('plugin-active');
                    $button->setUrl(\Tk\Uri::create()->reset()->set('deact', $pluginName));
                } else {
                    $cell->getRow()->addCss('plugin-inactive');
                    $button->setVisible(false);
                }
            })->setGroup('group');


        $this->table->appendCell($actionsCell)->addOnCellHtml(function (\Tk\Table\Cell\Iface $cell, $obj, $html) {
            /** @var $obj \stdClass */
            $template = $cell->getTable()->getRenderer()->getTemplate();

            $css = <<<CSS
#PluginList .plugin-inactive td {
  opacity: 0.5;
}
#PluginList .plugin-inactive td.mActions {
  opacity: 1;  
}
.table > thead > tr > th, 
.table > tbody > tr > th, 
.table > tfoot > tr > th, 
.table > thead > tr > td, 
.table > tbody > tr > td, 
.table > tfoot > tr > td {
  vertical-align: middle;
}

CSS;
            $template->appendCss($css);
            return $html;
        });

        $this->table->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setOrderProperty('');
        $this->table->appendCell(new \Tk\Table\Cell\Text('access'))->setOrderProperty('');
        $this->table->appendCell(new \Tk\Table\Cell\Text('version'))->setOrderProperty('');
        $this->table->appendCell(new \Tk\Table\Cell\Date('time'))->setFormat(\Tk\Date::FORMAT_MED_DATE)->setLabel('Created')->setOrderProperty('');

        $this->table->setList($this->getPluginList());

    }

    /**
     * @return array
     * @throws \Tk\Db\Exception
     * @throws \Tk\Plugin\Exception
     */
    private function getPluginList()
    {
        $pluginFactory = $this->getConfig()->getPluginFactory();
        $list = array();
        $names = $pluginFactory->getAvailablePlugins();
        foreach ($names as $pluginName) {
            $info = $pluginFactory->getPluginInfo($pluginName);
            $info->name = str_replace('ttek-plg/', '', $info->name);
            if (empty($info->access)) {
                $info->access = 'admin';
            }
            $info->time = \Tk\Date::create($info->time);
            $list[$pluginName] = $info;
        }
        return $list;
    }

    /**
     * @param \Tk\Form $form
     */
    public function doUpload($form)
    {
        /* @var Field\File $package */
        $package = $form->getField('package');

        if (!preg_match('/\.(zip|gz|tgz)$/i', $package->getValue())) {
            $form->addFieldError('package', 'Please Select a valid plugin file. (zip/tar.gz/tgz only)');
        }
        $dest = $this->getConfig()->getPluginPath() . $package->getValue();
        if (is_dir(str_replace(array('.zip', '.tgz', '.tar.gz'), '', $dest))) {
            $form->addFieldError('package', 'A plugin with that name already exists');
        }

        if ($form->hasErrors()) {
            return;
        }

        try {
            $package->saveFile();
        } catch (Form\Exception $e) {
            // TODO:
            \Tk\Log::error($e->__toString());
        }

        $cmd = '';
        if (\Tk\File::getExtension($dest) == 'zip') {
            $cmd  = sprintf('cd %s && unzip %s', escapeshellarg(dirname($dest)), escapeshellarg(basename($dest)));
        } else if (\Tk\File::getExtension($dest) == 'gz' || \Tk\File::getExtension($dest) == 'tgz') {
            $cmd  = sprintf('cd %s && tar zxf %s', escapeshellarg(dirname($dest)), escapeshellarg(basename($dest)));
        }
        if ($cmd) {
            exec($cmd, $output);
        }

        // TODO: check the plugin is a valid Tk plugin, if not remove the archive and files and throw an error
        // Look for a Plugin.php file and Class maybe????

        \Tk\Alert::addSuccess('Plugin successfully uploaded.');
        \Tk\Uri::create()->reset()->redirect();
    }

    /**
     * @param Request $request
     */
    protected function doActivatePlugin(Request $request)
    {
        $pluginFactory = \Bs\Config::getInstance()->getPluginFactory();
        $pluginName = strip_tags(trim($request->get('act')));
        if (!$pluginName) {
            \Tk\Alert::addWarning('Cannot locate Plugin: ' . $pluginName);
            return;
        }
        try {
            $pluginFactory->activatePlugin($pluginName);
            \Tk\Alert::addSuccess('Plugin `' . $pluginName . '` activated successfully');
        }catch (\Exception $e) {
            \Tk\Alert::addError('Activate Failed: ' . $e->getMessage());
            \Tk\Log::warning($e->__toString());
        }
        \Tk\Uri::create()->reset()->redirect();
    }

    /**
     * @param Request $request
     */
    protected function doDeactivatePlugin(Request $request)
    {
        $pluginName = strip_tags(trim($request->get('deact')));
        if (!$pluginName) {
            \Tk\Alert::addWarning('Cannot locate Plugin: ' . $pluginName);
            return;
        }
        try {
            \Bs\Config::getInstance()->getPluginFactory()->deactivatePlugin($pluginName);
            \Tk\Alert::addSuccess('Plugin `' . $pluginName . '` deactivated successfully');
        }catch (\Exception $e) {
            \Tk\Alert::addError('Deactivate Failed: ' . $e->getMessage());
            \Tk\Log::warning($e->__toString());
        }
        \Tk\Uri::create()->reset()->redirect();
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    protected function doDeletePlugin(Request $request)
    {
        $pluginName = strip_tags(trim($request->get('del')));
        if (!$pluginName) {
            \Tk\Alert::addWarning('Cannot locate Plugin: ' . $pluginName);
            return;
        }
        $pluginPath = \Bs\Config::getInstance()->getPluginFactory()->getPluginPath($pluginName);

        if (!is_dir($pluginPath)) {
            \Tk\Alert::addWarning('Plugin `' . $pluginName . '` path not found');
            return;
        }

        // So when we install plugins the archive must be left in the main plugin folder
        if ((!is_file($pluginPath.'.zip') && !is_file($pluginPath.'.tar.gz') && !is_file($pluginPath.'.tgz'))) {
            \Tk\Alert::addWarning('Plugin is protected and must be deleted manually.');
            return;
        }

        \Tk\File::rmdir($pluginPath);
        if (is_file($pluginPath.'.zip'))  unlink($pluginPath.'.zip');
        if (is_file($pluginPath.'.tar.gz'))  unlink($pluginPath.'.tar.gz');
        if (is_file($pluginPath.'.tgz'))  unlink($pluginPath.'.tgz');
        \Tk\Alert::addSuccess('Plugin `' . $pluginName . '` deleted successfully');

        \Tk\Uri::create()->reset()->redirect();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('form', $this->form->getRenderer()->show());

        // render Table
        $template->appendTemplate('PluginList', $this->table->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>

  <div class="row">
    <div class="col-md-8 col-sm-12">
      <div class="tk-panel" data-panel-title="Available Plugins" data-panel-icon="fa fa-plug" var="PluginList"></div>
    </div>

    <div class="col-md-4 col-sm-12">
      <div class="tk-panel" data-panel-title="Upload Plugin" data-panel-icon="fa fa-upload" var="form">
        <p>Select A zip/tgz plugin package to upload.</p>
      </div>
    </div>
  </div>

</div>
HTML;
        return \Dom\Loader::load($xhtml);
    }

}
