<?php
namespace Bs\Db\Traits;

use Tk\Db\Map\Model;
use Tk\Db\ModelInterface;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait ForeignModelTrait
{
    use ForeignKeyTrait;

    /**
     * @var ModelInterface
     */
    private $_model = null;


    /**
     * @return int
     */
    public function getFid()
    {
        return $this->fid;
    }

    /**
     * @param int $fid
     * @return ForeignKeyTrait
     */
    public function setFid($fid)
    {
        $this->fid = $fid;
        return $this;
    }


    /**
     * Alias to setForeignModel();
     *
     * @param Model|ModelInterface $model
     * @return ForeignKeyTrait
     */
    public function setModel($model)
    {
        return $this->setForeignModel($model);
    }

    /**
     * @param Model|ModelInterface $model
     * @return ForeignKeyTrait
     */
    public function setForeignModel($model)
    {
        $this->setFkey(get_class($model));
        $this->setFid($model->getVolatileId());
        $this->_model = $model;
        return $this;
    }

    /**
     * Alias to getForeignModel();
     *
     * @return null|Model|ModelInterface|StatusTrait
     * @throws \Exception
     */
    public function getModel()
    {
        return $this->getForeignModel();
    }

    /**
     * @return Model|ModelInterface|null
     * @throws \Exception
     */
    public function getForeignModel()
    {
        if (!$this->_model && class_exists($this->getFkey().'Map')) {
            $this->_model = $this->getForeignModelMapper()->find($this->getFid());
        }
        return $this->_model;
    }


    /**
     * @param array $errors
     * @return array
     */
    public function validateModelId($errors = [])
    {
        $errors = $this->validateFkey($errors);
        if ($this->getFid() === '' || $this->getFid() === null) {
            $errors['fid'] = 'Invalid value: fid';
        }
        return $errors;
    }


}