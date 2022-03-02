<?php
namespace Bs;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Bootstrap
{
    /**
     * @return \Bs\Config
     * @throws \Exception
     */
    public static function execute()
    {
        $obj = new static();
        return $obj->init();
    }

    /**
     * This will also load dependant objects into the config, so this is the DI object for now.
     *
     * @return \Bs\Config
     * @throws \Exception
     */
    public function init()
    {

        $config = \Bs\Config::getInstance();

        // This maybe should be created in a Factory or DI Container....
        if (is_writable($config->getLogPath())) {
            $sessionLog = $config->get('log.session');

            if (!$config->getRequest()->has('nolog')) {
                $processors = array();
                if (is_writable(dirname($sessionLog))) {
                    if (!is_file($sessionLog) || is_writable($sessionLog))
                        file_put_contents($sessionLog, ''); // Refresh log for this session

                    $processors[] = function ($record) use ($sessionLog) {
                        // create a session logger file
                        if (isset($record['message'])) {
                            $str = $record['message'] . "\n";
                            if (is_writable($sessionLog)) {
                                file_put_contents($sessionLog, $str, FILE_APPEND | LOCK_EX);
                            }
                        }
                        return $record;
                    };
                }
                //$logger = new Logger('system');
                $logger = new Logger('system', array(), $processors);
                $handler = new StreamHandler($config->getLogPath(), $config->getLogLevel());
                $formatter = new \Tk\Log\MonologLineFormatter();
                $formatter->setScriptTime($config->getScriptTime());
                $handler->setFormatter($formatter);
                $logger->pushHandler($handler);
                $config->setLog($logger);
                \Tk\Log::getInstance($logger);
                \Dom\Template::$logger = $logger;
            }
        } else {
            error_log('Log Path not readable: ' . $config->getLogPath());
        }

        if (!$config->isDebug()) {
            ini_set('display_errors', 'Off');
            error_reporting(0);
        } else {
            \Dom\Template::$enableTracer = true;

            // Allow self-signed certs in file_get_contents in debug mode only
            $context = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            stream_context_set_default($context);
        }
        // Init framework error handler
        \Tk\ErrorHandler::getInstance($config->getLog());

        // Initiate the default database connection
        $config->getDb();

        // Load Config with db data
        $config->replace(\Tk\Db\Data::create()->all());

        // Return if using cli (Command Line)
        if ($config->isCli()) return $config;

        // --- HTTP only bootstrapping from here ---

        // Include all URL routes
        $config->loadRoutes();

        // * Session
        $config->getSession();

        return $config;
    }


}

