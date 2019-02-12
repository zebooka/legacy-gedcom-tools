<?php

namespace Zebooka\Gedcom\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Controller\IdsRenameController;

class IdsRenameCommand extends AbstractCommand
{
    const OPTION_DRY_RUN = 'dry-run';

    protected static $defaultName = 'ids';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Make IDs fancy')
            ->setHelp('Transform IDs of INDI and FAM records to better format.');

        $this->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Perform IDs rename, but do not save anything to file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $gedcom = $this->getGedcom($input, $output);

        $err->writeln("--> Making IDs fancy", OutputInterface::VERBOSITY_NORMAL);
        $controller = new IdsRenameController($output);
        $controller->renameIndis($gedcom);
        $controller->renameFams($gedcom);

        if (!$input->getOption(self::OPTION_DRY_RUN)) {
            $this->putGedcom($gedcom, $input, $output);
        }
    }
}
