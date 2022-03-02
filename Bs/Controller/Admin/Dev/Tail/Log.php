<?php
namespace Bs\Controller\Admin\Dev\Tail;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Log extends \Bs\Controller\AdminIface
{

    /**
     * @var string
     */
    protected $logPath = '';


    /**
     * Log constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->getConfig()->resetCrumbs();
        $this->logPath = \App\Config::getInstance()->getLogPath();
    }

    /**
     * @param \Tk\Request $request
     * @throws \Tk\Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->setPageTitle('Tail Log');
        if ($request->has('seek')) {
            $this->doSeek($request);
        }
        if ($request->has('refresh')) {
            $this->doRefresh($request);
        } else {
            $this->doSeek($request);
        }
    }

    /**
     * @param \Tk\Request $request
     * @throws \Tk\Exception
     */
    public function doRefresh(\Tk\Request $request)
    {
        if (!is_readable($this->logPath)) {
            echo sprintf('Cannot read log file: ' . $this->logPath . "\n");
            exit;
        }

        $session = \App\Config::getInstance()->getSession();
        $handle = fopen($this->logPath, 'r');
        if ($session->get('tail-offset')) {
            $pos = $session->get('tail-offset');
            $data = stream_get_contents($handle, -1, $pos);
            echo htmlentities($data);
            $pos = ftell($handle);
            $session->set('tail-offset', $pos);
        } else {
            $this->doSeek($request, -1000);
        }
        exit();
    }

    /**
     * @param \Tk\Request $request
     * @param int $seekAdjust
     * @throws \Tk\Exception
     */
    public function doSeek(\Tk\Request $request, $seekAdjust = 0) {
        $session = \App\Config::getInstance()->getSession();
        $handle = fopen($this->logPath, 'r');
        fseek($handle, 0, \SEEK_END);
        $pos = ftell($handle);
        if ($seekAdjust > 0) {
            $pos += $seekAdjust;
        }
        if ($pos < 0) $pos = 0;
        $session->set('tail-offset', $pos);
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $template->setAttr('tail', 'data-src', \Bs\Uri::create()->nolog()->set('refresh'));
        $template->appendJsUrl(\Bs\Uri::create('/vendor/ttek/tk-base/Bs/Controller/Admin/Dev/Tail/jquery.tkTail.js'));
        $js = <<<JS
jQuery(function($) {
  $('#tail').tail({
    height: '600px'
  });
});
JS;
        $template->appendJs($js);

        $css = <<<CSS
.tk-tail {
  position: relative;
}
.tail-out {
  position: relative;
  font-size: 0.9em;
  line-height: 1.2;
  padding-bottom: 15px;
}
CSS;
        $template->appendCss($css);

        return $template;
    }


    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-road" var="panel">
  <div class="tk-tail" id="tail" data-src="" var="tail"></div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}