<?php

namespace Zebooka\Gedcom;

// setup errors handling
error_reporting(-1);
set_error_handler(
    function ($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
);
set_exception_handler(
    function (\Throwable $e) {
        if (isset($GLOBALS['logger']) && $GLOBALS['logger'] instanceof \Monolog\Logger) {
            $GLOBALS['logger']->addCritical($e);
        } else {
            error_log($e);
        }
        exit(1);
    }
);
mb_internal_encoding('UTF-8');

// autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// get locale
$locale = 'en';
foreach ([LC_ALL, LC_COLLATE, LC_CTYPE, LC_MESSAGES] as $lc) {
    if (preg_match('/^([a-z]{2})(_|$)/i', setlocale($lc, 0))) {
        $locale = setlocale($lc, 0);
        break;
    }
}
setlocale(LC_ALL, $locale);

define('RES_DIR', __DIR__ . '/../res');
define('FULL_VERSION', \VERSION . ' (' . trim(file_get_contents(RES_DIR . '/VERSION')) . ')');

// translations
//$translator = \Zebooka\Translator\TranslatorFactory::translator(__DIR__ . '/../res', $locale);

$a = \Zebooka\Gedcom\ApplicationFactory::getConsoleApplication();
$a->run();
