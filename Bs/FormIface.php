<?php
namespace Bs;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 22/07/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
abstract class FormIface extends \Tk\Form
{
    /**
     * @var null|\Tk\Db\ModelInterface
     */
    protected $model = null;

    /**
     * Set to true on first call to initFields()
     * @var bool
     */
    private $initDone = false;



    /**
     * @param string $formId
     */
    public function __construct($formId = '')
    {
        if (!$formId)
            $formId = trim(strtolower(preg_replace('/[A-Z]/', '_$0', \Tk\ObjectUtil::basename(get_class($this)))), '_');
        parent::__construct($formId);
    }

    /**
     * @param string $formId
     * @return FormIface|\Tk\Form|static
     */
    public static function create($formId = '')
    {
        /** @var FormIface $obj */
        $obj = parent::create($formId);
        $obj->setRenderer(\Bs\Config::getInstance()->createFormRenderer($obj));
        return $obj;
    }

    /**
     * @param null|\Tk\Db\ModelInterface $model
     * @return FormIface|\Tk\Form|static
     */
    public static function createModel($model = null)
    {
        /** @var FormIface $obj */
        $obj = self::create(\Tk\ObjectUtil::basename($model));
        $obj->setModel($model);
        return $obj;
    }


    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        parent::execute($request);
    }

    /**
     * @return null|\Tk\Db\ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param null|\Tk\Db\ModelInterface $model
     * @return static
     */
    public function setModel($model)
    {
        $this->model = $model;

        if (!$this->initDone) {
            $this->init();
            $this->initDone = true;
        }
        return $this;
    }

    /**
     * init all your form fields here
     */
    abstract public function init();


    /**
     * @return Config
     */
    public function getConfig()
    {
        return Config::getInstance();
    }

    /**
     * @return Db\User
     */
    public function getUser()
    {
        return $this->getConfig()->getUser();
    }

    /**
     * @return \Tk\Uri
     * @throws \Exception
     */
    public function getBackUrl()
    {
        return $this->getConfig()->getBackUrl();
    }


}