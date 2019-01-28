<?php

namespace Zebooka\Gedcom\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Controller\OptimizeController;

class OptimizeCommand extends AbstractCommand
{
    protected static $defaultName = 'optimize';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Optimize GEDCOM')
            ->setHelp('Optimize GEDCOM by dropping some of empty tags and adding some default values.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $gedcom = $this->getGedcom($input, $output);

        $err->writeln("--> Optimizing GEDCOM", OutputInterface::VERBOSITY_NORMAL);
        $c = new OptimizeController($output);
        $c->dropPlacEmptyValues($gedcom);
        $c->setDeatYifDeatDateEmpty($gedcom);
        $c->removeDeatYifDeatDateNotEmpty($gedcom);
        $c->removeEmptyDateFromElementsExceptBirtAndDeat($gedcom);

        $this->putGedcom($gedcom, $input, $output);
    }
}
