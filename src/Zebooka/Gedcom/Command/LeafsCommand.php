<?php

namespace Zebooka\Gedcom\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Controller\LeafsController;

class LeafsCommand extends AbstractCommand
{
    const OPTION_REVERSE = 'reverse';

    protected static $defaultName = 'leafs';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Display leafs')
            ->setHelp('Search and display leafs from GEDCOM suitable for rendering tree. Leafs are ending descendants on family tree.');

        $this->addOption(self::OPTION_REVERSE, 'r', InputOption::VALUE_NONE, 'Reverse order of leafs (first with higher ranking, least with lower).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $gedcom = $this->getGedcom($input, $output);
        $rankingPrecision = $output->isVeryVerbose() ? 7 : 0;

        $c = new LeafsController($output);
        $leafs = $c->gedcomToLeafs($gedcom);
        $end = end($leafs);

        if ($input->getOption(self::OPTION_REVERSE)) {
            $leafs = array_reverse($leafs, true);
            $err->writeln("--> <fg=red>Output in reverse order</>", OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        $rankingLength = strlen('' . number_format($end[0], $rankingPrecision));

        foreach ($leafs as $id => $leaf) {
            $ranking = str_pad(number_format($leaf[0], $rankingPrecision), $rankingLength, ' ', STR_PAD_LEFT);
            $isLeaf = $leaf[2];
            /** @var \DOMElement $element */
            $element = $leaf[1];
            $name = $gedcom->evaluate('string(./NAME/@value)', $element);
            $birthday = $gedcom->evaluate('string(./BIRT/DATE/@value)', $element);
            $deathday = $gedcom->evaluate('string(./DEAT/DATE/@value|./DEAT/@value)', $element);
            $dates = ($birthday ? $birthday : 'Y') . ($deathday ? "<fg=red> .. {$deathday}</>" : '');

            if ($output->isQuiet() && $isLeaf) {
                $output->writeln($id, OutputInterface::VERBOSITY_QUIET);
            } elseif ($output->isVerbose()) {
                $highlight = $isLeaf ? 'red' : 'white';
                $output->writeln("<fg={$highlight}>{$ranking} --></> <fg=cyan>{$id}</> <fg=default>-- {$name}</> <fg=green>-- {$dates}</>", OutputInterface::VERBOSITY_NORMAL);
            } else {
                $highlight = $isLeaf ? 'red' : 'white';
                $output->writeln("<fg={$highlight}>--></> <fg=cyan>{$id}</> <fg=default>-- {$name}</> <fg=green>-- {$dates}</>", OutputInterface::VERBOSITY_NORMAL);
            }
        }
    }
}
