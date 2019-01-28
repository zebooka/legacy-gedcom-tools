<?php

namespace Zebooka\Gedcom\Controller;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Gedcom5Document;

class IdsRenameController
{
    /** @var OutputInterface */
    private $err;

    /** @var \Transliterator */
    private $tr;

    public function __construct(OutputInterface $output)
    {
        $this->err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $this->tr = \Transliterator::create('ru-ru_Latn/BGN; Latin; ASCII; UPPER');
    }

    private static function elementXPath(\DOMElement $element)
    {
        return ($element === $element->parentNode || 'GEDCOM' === $element->localName ? ''
            : ($element->parentNode instanceof \DOMElement ? self::elementXPath($element->parentNode) : '')
            . '/' . $element->localName
            . ($element->getAttribute('id') ? "[{$element->getAttribute('id')}]" : ''));
    }

    public function renameIndis(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Renaming IDs of INDIs', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('/GEDCOM/INDI');
        $this->err->writeln("--> Found <info>{$elements->count()}</info> INDIs", OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach ($elements as $element) {
            /** @var \DOMElement $element */
            $name = $gedcom->xpath('./NAME[TYPE[@value="birth"]] | ./NAME[last()]', $element);
            $oldId = $element->getAttribute('id');
            $this->err->write("{$oldId} ", false, OutputInterface::VERBOSITY_VERY_VERBOSE);
            if ($name->length > 0) {
                $name = $name->item(0);
                $surn = trim($gedcom->evaluate('string(./SURN/@value)', $name));
                $givn = trim($gedcom->evaluate('string(./GIVN/@value)', $name));
                $givn = implode('', array_slice(explode(' ', $givn), 0, 1));
                $surngivn = preg_replace('/[^A-Z0-9_]/i', '', $this->tr->transliterate("{$surn} {$givn}"));
                if ('' === $surngivn) {
                    $this->err->write("<error> NO GIVN/SURN </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $this->err->writeln('<fg=red>--x</>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                    continue;
                }

                $birt = $b = $gedcom->evaluate('string(./BIRT/DATE/@value)', $element);
                if (preg_match('/^(?:[0-9]{1,2} (?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC) )?([0-9]{4})$/', $birt, $matches)) {
                    $this->err->write("<fg=red>{$birt}</> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $birt = $matches[1];
                    $this->err->write("= <comment>{$birt}</comment> ", false, OutputInterface::VERBOSITY_DEBUG);
                } elseif (preg_match(
                    '/^(?:FORM|ABT|BEF|AFT|BET|EST|CAL|INT) (?:[0-9]{1,2} (?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC) )?([0-9]{4})( |$)/',
                    $birt,
                    $matches
                )) {
                    $this->err->write("<fg=red>{$birt}</> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $birt = $matches[1];
                    $this->err->write("~ <comment>{$birt}</comment> ", false, OutputInterface::VERBOSITY_DEBUG);
                } elseif ('' !== $birt) {
                    $this->err->write("<error> BIRT '{$birt}' NOT SUPPORTED </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $birt = 'xxxx';
                } else {
                    $this->err->write("<error> NO BIRT DATE </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $birt = '__';
                }


                $newId = "@I{$birt}{$surngivn}@";

                $cnt = 1;
                while (true) {
                    $found = $gedcom->xpath("//*[@id='{$newId}']");
                    if ($found->length == 1) {
                        if ($found->item(0) === $element) {
                            break;
                        }
                        // nothing to do - its not our ID
                        $this->err->write("+", false, OutputInterface::VERBOSITY_DEBUG);
                    } elseif ($found->length == 0) {
                        $element->setAttribute('id', $newId);
                        $links = $gedcom->xpath("//*[@value='{$oldId}']");
                        for ($i = 0; $i < $links->length; $i++) {
                            $links->item($i)->setAttribute('value', $newId);
                        }
                        break;
                    } else {
                        $this->err->writeln(
                            " <error> WARNING! ID {$newId} is used {$found->length} times in GEDCOM! </error> ",
                            OutputInterface::VERBOSITY_VERBOSE
                        );
                        continue 2;
                    }
                    $cnt++;
                    $newId = "@I{$birt}{$surngivn}__{$cnt}@";
                }

                if ($cnt > 1) {
                    $this->err->write(' ', false, OutputInterface::VERBOSITY_DEBUG);
                }

                if ($newId == $oldId) {
                    $this->err->writeln("<fg=cyan><--</>", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    continue;
                }

                $this->err->writeln("--> <info>{$newId}</info>", OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $this->err->write("<error> NO NAME TAG </error>", false, OutputInterface::VERBOSITY_DEBUG);
                $this->err->writeln('<fg=red>--x</>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                continue;
            }
        }
    }

    public function renameFams(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Renaming IDs of FAMs', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('/GEDCOM/FAM');
        $this->err->writeln("--> Found <info>{$elements->count()}</info> FAMs", OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach ($elements as $element) {
            /** @var \DOMElement $element */
            $oldId = $element->getAttribute('id');
            $this->err->write("{$oldId} ", false, OutputInterface::VERBOSITY_VERY_VERBOSE);

            $husb = $gedcom->xpath('./HUSB', $element)->item(0);
            if (!$husb) {
                $this->err->write("<error> NO HUSB </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                $this->err->writeln('<fg=red>--x</>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                continue;
            }
            $wife = $gedcom->xpath('./WIFE', $element)->item(0);
            if (!$wife) {
                $this->err->write("<error> NO WIFE </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                $this->err->writeln('<fg=red>--x</>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                continue;
            }

            $surns = '';
            foreach ([$husb, $wife] as $spouse) {
                /** @var \DOMElement $spouse */
                $indi = $gedcom->xpath("//INDI[@id='{$spouse->getAttribute('value')}']")->item(0);
                $name = $gedcom->xpath("./NAME[TYPE[@value='birth']] | ./NAME[last()]", $indi);
                if (!$name->length) {
                    $this->err->write("<error> {$spouse->nodeName} without NAME </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $this->err->writeln('<fg=red>--x</>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                    continue 2;
                }

                $name = $name->item(0);
                $surn = trim($gedcom->evaluate('string(./SURN/@value)', $name));
                if ('' === $surn) {
                    $this->err->write("<error> NO SURN for {$spouse->nodeName} </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                    $this->err->writeln('<fg=red>--x</>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                    continue 2;
                }

                $surns .= preg_replace('/[^A-Z0-9_]/i', '', $this->tr->transliterate($surn));
            }

            $marr = $b = $gedcom->evaluate('string(./MARR/DATE/@value)', $element);
            if (preg_match('/^(?:[0-9]{1,2} (?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC) )?([0-9]{4})$/', $marr, $matches)) {
                $this->err->write("<fg=red>{$marr}</> ", false, OutputInterface::VERBOSITY_DEBUG);
                $marr = $matches[1];
                $this->err->write("= <comment>{$marr}</comment> ", false, OutputInterface::VERBOSITY_DEBUG);
            } elseif (preg_match(
                '/^(?:FORM|ABT|BEF|AFT|BET|EST|CAL|INT) (?:(?:[0-9]{1,2})? (?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC) )?([0-9]{4})( |$)/',
                $marr,
                $matches
            )) {
                $this->err->write("<fg=red>{$marr}</> ", false, OutputInterface::VERBOSITY_DEBUG);
                $marr = $matches[1];
                $this->err->write("~ <comment>{$marr}</comment> ", false, OutputInterface::VERBOSITY_DEBUG);
            } elseif ('' !== $marr) {
                $this->err->write("<error> MARR '{$marr}' NOT SUPPORTED </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                $marr = 'xxxx';
            } else {
                $this->err->write("<error> NO MARR DATE </error> ", false, OutputInterface::VERBOSITY_DEBUG);
                $marr = '__';
            }

            $newId = "@F{$marr}{$surns}@";

            $cnt = 1;
            while (true) {
                $found = $gedcom->xpath("//*[@id='{$newId}']");
                if ($found->length == 1) {
                    if ($found->item(0) === $element) {
                        break;
                    }
                    // nothing to do - its not our ID
                    $this->err->write("+", false, OutputInterface::VERBOSITY_DEBUG);
                } elseif ($found->length == 0) {
                    $element->setAttribute('id', $newId);
                    $links = $gedcom->xpath("//*[@value='{$oldId}']");
                    for ($i = 0; $i < $links->length; $i++) {
                        $links->item($i)->setAttribute('value', $newId);
                    }
                    break;
                } else {
                    $this->err->writeln(
                        " <error> WARNING! ID {$newId} is used {$found->length} times in GEDCOM! </error> ",
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                    continue 2;
                }
                $cnt++;
                $newId = "@F{$marr}{$surns}__{$cnt}@";
            }

            if ($cnt > 1) {
                $this->err->write(' ', false, OutputInterface::VERBOSITY_DEBUG);
            }

            if ($oldId == $newId) {
                $this->err->writeln("<fg=cyan><--</>", OutputInterface::VERBOSITY_VERY_VERBOSE);
                continue;
            }

            $this->err->writeln("--> <info>{$newId}</info>", OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
    }
}
