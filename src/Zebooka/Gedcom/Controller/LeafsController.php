<?php

namespace Zebooka\Gedcom\Controller;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Gedcom5Document;

class LeafsController
{
    /** @var OutputInterface */
    private $err;

    public function __construct(OutputInterface $output)
    {
        $this->err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }

    public function gedcomToLeafs(Gedcom5Document $gedcom)
    {
        // add all nodes to heap
        $list = [];
        $indis = $gedcom->xpath('/GEDCOM/INDI');
        $this->err->writeln("--> Found <info>{$indis->count()}</info> INDIs", OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach ($indis as $indi) {
            /** @var \DOMElement $indi */
            $list[$indi->getAttribute('id')] = [$this->calculateRanking($indi, $gedcom), $indi, $this->checkIsLeaf($indi, $gedcom)];
        }

        uasort(
            $list,
            function ($a, $b) {
                return $a[0] <=> $b[0];
            }
        );

        return $list;
    }

    private function checkIsLeaf(\DOMElement $indi, Gedcom5Document $gedcom)
    {
        $id = $indi->getAttribute('id');

        // not dead
        $notDead = !$gedcom->xpath('./DEAT', $indi)->count();

        // less than 30 years since birth
        $youngerThan30 = false;
        if ($birt = $gedcom->evaluate('string(./BIRT/DATE/@value)', $indi)) {
            $timestamp = strtotime('+ 30 years', Gedcom5Document::gedcomDateToTimestamp($birt));
            $youngerThan30 = $timestamp > time();
        }

        // no children
        $noChildren = true;
        $fams = $gedcom->xpath("/GEDCOM/FAM[HUSB/@value='{$id}']|/GEDCOM/FAM[WIFE/@value='{$id}']");
        if ($fams) {
            foreach ($fams as $fam) {
                if ($gedcom->xpath('./CHIL', $fam)->count()) {
                    $noChildren = false;
                    break;
                }
            }
        }

        return $notDead && $youngerThan30 && $noChildren;
    }

    private function calculateRanking(\DOMElement $indi, Gedcom5Document $gedcom)
    {
        $id = $indi->getAttribute('id');

        $basic = 1;
        $sublings = 1;
        $father = $mother = 0;
        $cp = 1; // coefficient for parents weight

        // get family where indi is child
        $fam = $gedcom->xpath("/GEDCOM/FAM[CHIL/@value='{$id}']")->item(0);

        if ($fam) {
            $sublings = $gedcom->xpath('./CHIL', $fam)->count();

            // calculate parents and their rankings
            if ($f = $gedcom->evaluate('string(./HUSB/@value)', $fam)) {
                if ($f = $gedcom->xpath("/GEDCOM/INDI[@id='{$f}']")->item(0)) {
                    $father = $this->calculateRanking($f, $gedcom);
                }
            }

            if ($m = $gedcom->evaluate('string(./WIFE/@value)', $fam)) {
                if ($m = $gedcom->xpath("/GEDCOM/INDI[@id='{$m}']")->item(0)) {
                    $mother = $this->calculateRanking($m, $gedcom);
                }
            }
        }

        return $basic + sqrt($father * $father + $mother * $mother) * $cp + log($sublings);
    }
}
