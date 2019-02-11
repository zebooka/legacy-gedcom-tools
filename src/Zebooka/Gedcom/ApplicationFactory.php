<?php

namespace Zebooka\Gedcom;

use Symfony\Component\Console\Application;
use Zebooka\Gedcom\Command\LeafsCommand;
use Zebooka\Gedcom\Command\OptimizeCommand;
use Zebooka\Gedcom\Command\IdsRenameCommand;
use Zebooka\Gedcom\Command\RenderCommand;

class ApplicationFactory
{
    public static function getConsoleApplication()
    {
        $a = new Application(basename($_SERVER['argv'][0]), FULL_VERSION);
        $a->add(new OptimizeCommand());
        $a->add(new IdsRenameCommand());
        $a->add(new LeafsCommand());
        $a->add(new RenderCommand());
        return $a;
    }
}
