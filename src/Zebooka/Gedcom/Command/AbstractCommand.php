<?php

namespace Zebooka\Gedcom\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zebooka\Gedcom\Gedcom5Document;

abstract class AbstractCommand extends Command
{
    const ARGUMENT_GEDCOM = 'gedcom';

    protected function configure()
    {
        parent::configure();

        $this->addArgument(self::ARGUMENT_GEDCOM, InputArgument::OPTIONAL, 'GEDCOM file to process', '-');
    }

    protected function getGedcom(InputInterface $input, OutputInterface $output)
    {
        $err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $filename = $input->getArgument(self::ARGUMENT_GEDCOM);
        if (0 === ftell(STDIN)) {
            $err->writeln("--> Reading from STDIN...", OutputInterface::VERBOSITY_VERBOSE);
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } elseif ($filename && '-' !== $filename) {
            $err->writeln("--> Reading from file '<info>$filename</info>'...", OutputInterface::VERBOSITY_VERBOSE);
            if (!is_file($filename) || !is_readable($filename)) {
                throw new \RuntimeException("Unable to read file '{$filename}'.");
            }
            $contents = file_get_contents($filename);
        } else {
            throw new \RuntimeException("Please provide a filename or pipe template content to STDIN.");
        }

    $b = strlen($contents);
        $err->writeln("--> <info>{$b}</info> bytes read", OutputInterface::VERBOSITY_VERY_VERBOSE);

        return new Gedcom5Document($contents);
    }

    protected function putGedcom(Gedcom5Document $gedcom, InputInterface $input, OutputInterface $output)
    {
        $err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $filename = $input->getArgument(self::ARGUMENT_GEDCOM);
        if ($filename && '-' !== $filename) {
            $err->writeln("--> Saving to file '<info>{$filename}</info>'...", OutputInterface::VERBOSITY_NORMAL);
            $b = file_put_contents($filename, $gedcom->toGedcom());
            $err->writeln("--> <info>{$b}</info> bytes written", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $err->writeln("<info>SAVED</info>", OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $err->writeln("--> Sending to STDOUT...", OutputInterface::VERBOSITY_VERBOSE);
            $output->write($gedcom->toGedcom(), OutputInterface::VERBOSITY_QUIET);
        }
    }
}
