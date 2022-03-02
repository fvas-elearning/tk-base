<?php
namespace Bs\Controller\Member;

use Tk\Request;

/**
 * Class Index
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Dashboard extends \Bs\Controller\UserIface
{

    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->setPageTitle('My Account');
        
    }

    public function show()
    {
        $template = parent::show();
        
        $template->insertText('name', $this->getAuthUser()->getName());
        
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
<div>

    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-users fa-fw"></i> Welcome <span var="name"></span>
      </div>
      <div class="panel-body ">

        <p>Something spiffy.....</p>
        <p><a href="/logout.html">Logout ;-)</a></p>
        
      </div>
    </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}