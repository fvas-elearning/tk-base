<?php
namespace Bs\Db\Traits;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2019 Michael Mifsud
 */
trait OrderByTrait
{

    /**
     * @return int
     */
    public function getOrderBy(): int
    {
        return $this->orderBy;
    }

    /**
     * @param int $orderBy
     * @return $this
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    // TODO: add any helper methods that may be needed.



}