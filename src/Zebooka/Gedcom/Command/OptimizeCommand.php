<?php

namespace Zebooka\Gedcom\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Controller\OptimizeController;

class OptimizeCommand extends AbstractCommand
{
    const OPTION_DRY_RUN = 'dry-run';

    protected static $defaultName = 'optimize';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Optimize GEDCOM')
            ->setHelp('Optimize GEDCOM by dropping some of empty tags and adding some default values.');

        $this->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Perform optimization, but do not save anything to file.');
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
        $c->fixSpaceAroundFamilyName($gedcom);

        if (!$input->getOption(self::OPTION_DRY_RUN)) {
            $this->putGedcom($gedcom, $input, $output);
        }
    }
}
