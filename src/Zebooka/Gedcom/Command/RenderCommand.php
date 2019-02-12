<?php

namespace Zebooka\Gedcom\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RenderCommand extends AbstractCommand
{
    const OPTION_FORMAT = 'format';

    protected static $defaultName = 'render';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Render GEDCOM')
            ->setHelp('Render GEDCOM file uzing Graphviz tool to PNG/SVG/DOT format.');

        $this->addOption(self::OPTION_FORMAT, 'o', InputOption::VALUE_REQUIRED, 'Output format', 'png');
    }
}
