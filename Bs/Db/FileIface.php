<?php
namespace Bs\Db;

use Tk\Db\Tool;

/**
 * Use this interface if you are planing on using the file upload field
 *
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
interface FileIface
{

    /**
     * @param Tool $tool
     * @return array|File[]
     */
    public function getFileList(Tool $tool);


}
