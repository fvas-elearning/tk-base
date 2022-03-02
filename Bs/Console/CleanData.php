<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class CleanData extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('clean-data')
            ->setAliases(array('cd'))
            ->setDescription('Clean the data/ folder of empty folders');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->deleteEmptyFolders();
        $this->deleteTempFiles();
        if (!$this->getConfig()->isDebug())
            $this->deleteOldSessions();
    }


    /**
     * @throws \Exception
     */
    protected function deleteEmptyFolders()
    {
        // Recursively Remove any empty folders older than 1 hour
        if (is_dir($this->getConfig()->getDataPath())) {
            $path = rtrim($this->getConfig()->getDataPath(), '/');
            $this->write('   - Removing Empty Folders: ' . $path);
            \Tk\File::removeEmptyFolders($path, function ($path) {
                $date = \Tk\Date::create(filemtime($path));
                $now = \Tk\Date::create()->sub(new \DateInterval('PT1H'));
                if ($date < $now) {
                    //$this->write('     Deleting: [' . $date->format(\Tk\Date::FORMAT_ISO_DATETIME) . '] - ' . $path);
                    return true;
                }
                return false;
            });
        }
    }

    /**
     *
     */
    protected function deleteTempFiles()
    {
        // clean up temp files older than 7 days
        if (is_dir($this->getConfig()->getTempPath())) {
            $dir  = new \RecursiveDirectoryIterator($this->getConfig()->getTempPath(), \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
            $this->write('   - Removing Temp Files: '.$this->getConfig()->getTempPath().'');
            $now = \Tk\Date::create()->sub(new \DateInterval('P7D'));
            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isDir()) continue;
                $date = \Tk\Date::create(filemtime($file));
                if ($date < $now) {
//                    /$this->write('     Deleting: [' . $date->format(\Tk\Date::FORMAT_ISO_DATETIME) . '] ' . $file->__toString());
                    unlink($file);
                }
            }
        }

    }

    /**
     * Clear any sessions in the DB that are outdated
     * This is just to ensure we do not waste space on defunct session data
     * @throws \Tk\Db\Exception
     */
    protected function deleteOldSessions()
    {
        $this->write('   - Cleaning obsolete sessions.');
        $db = \App\Config::getInstance()->getDb();
        $expire = session_cache_expire()*4;
        $stm = $db->prepare('DELETE FROM '.\Tk\Session\Adapter\Database::$DB_TABLE.' WHERE modified < DATE_SUB(NOW(), INTERVAL '.$expire.' MINUTE)');
        $stm->execute();
    }

}
