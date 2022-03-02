<?php
namespace Bs\Db\Traits;

use Tk\Db\Map\Model;
use Tk\Db\ModelInterface;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait ForeignKeyTrait
{

    /**
     * @return string
     */
    public function getFkey()
    {
        return $this->fkey;
    }

    /**
     * @param string $fkey
     * @return $this
     */
    public function setFkey($fkey)
    {
        $this->fkey = $fkey;
        return $this;
    }

    /**
     *
     * Note: This is use as an alias in cases where set{Object}()
     *   is already used in the main object for another reason
     *
     * @param Model|ModelInterface $model
     * @return ForeignKeyTrait
     */
    public function setForeignModel($model)
    {
        return $this->setForeignModelObj($model);
    }

    /**
     * The models DB mapper object for querying the DB
     *
     * @return null|\Tk\Db\Map\Mapper
     */
    public function getForeignModelMapper()
    {
        return Model::createMapper($this->getFkey().'Map');
    }

    /**
     * @param int $modelId
     * @return Model
     * @throws \Exception
     */
    public function findForeignModel($modelId)
    {
        $mapper = $this->getForeignModelMapper();
        if (!$mapper) return null;
        return $mapper->find($modelId);
    }

    /**
     * @param array $errors
     * @return array
     */
    public function validateFkey($errors = [])
    {
        if (!$this->getFkey()) {
            $errors['fkey'] = 'Invalid value: fkey';
        }
        return $errors;
    }


}