<?php
namespace Bs;


use Bs\Db\User;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Page extends \Tk\Controller\Page
{

    /**
     * @param string $templatePath
     */
    public function __construct($templatePath = '')
    {
        if (!$templatePath)
            $templatePath = $this->makeDefaultTemplatePath();
        parent::__construct($templatePath);
    }

    /**
     * Create the default template path using the url role if available (see Config)
     *
     * @return string
     * @todo Refactor this method to not use the public type name
     */
    protected function makeDefaultTemplatePath()
    {
        $urlType = \Bs\Uri::create()->getRoleType($this->getConfig()->getUserTypeList(true));
        if (!$urlType) $urlType = 'public';     // todo rename this to User::TYPE_GUEST
        return $this->getConfig()->getSitePath() . $this->getConfig()->get('template.'.$urlType);
    }


}
