<?php

namespace Zebooka\Gedcom\Controller;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Gedcom\Gedcom5Document;

class OptimizeController
{
    /** @var OutputInterface */
    private $err;

    public function __construct(OutputInterface $output)
    {
        $this->err = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }

    private static function elementXPath(\DOMElement $element)
    {
        return ($element === $element->parentNode || 'GEDCOM' === $element->localName ? ''
            : ($element->parentNode instanceof \DOMElement ? self::elementXPath($element->parentNode) : '')
            . '/' . $element->localName
            . ($element->getAttribute('id') ? "[{$element->getAttribute('id')}]" : ''));
    }

    private function debugElement(\DOMElement $element)
    {
        $this->err->writeln(self::elementXPath($element), OutputInterface::VERBOSITY_VERY_VERBOSE);

        $this->err->writeln(
            '<comment>' . Gedcom5Document::composeLinesFromElementWithRoot($element) . '</comment>',
            OutputInterface::VERBOSITY_DEBUG
        );
    }

    public function dropPlacEmptyValues(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Drop PLAC empty values', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('//PLAC[not(@value) and not(child::*)]');
        foreach ($elements as $element) {
            /** @var \DOMElement $element */
            $this->debugElement($element);
            $element->parentNode->removeChild($element);
        }
    }

    public function setDeatYifDeatDateEmpty(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Set DEAT Y if DEAT/DATE is empty', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('//DEAT[not(@value) and DATE[not(@value)]]');
        foreach ($elements as $element) {
            /** @var \DOMElement $element */
            $this->debugElement($element);
            $element->setAttribute('value', 'Y');
        }
    }

    public function removeDeatYifDeatDateNotEmpty(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Remove DEAT Y if DEAT/DATE is not empty', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('//DEAT[@value and DATE[@value]]');
        foreach ($elements as $element) {
            /** @var \DOMElement $element */
            $this->debugElement($element);
            $element->removeAttribute('value');
        }
    }

    public function removeEmptyDateFromElementsExceptBirtAndDeat(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Remove empty DATE from elements except BIRT and DEAT', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('//*[local-name() != "BIRT" and local-name() != "DEAT"]/DATE[not(@value) and not(child::*)]');
        foreach ($elements as $element) {
            /** @var \DOMElement $element */
            $this->debugElement($element);
            $element->parentNode->removeChild($element);
        }
    }

    public function fixSpaceAroundFamilyName(Gedcom5Document $gedcom)
    {
        $this->err->writeln('--> Fix spaces around family name', OutputInterface::VERBOSITY_VERBOSE);
        $elements = $gedcom->xpath('//NAME');
        foreach ($elements as $element) {
            /** @var \DOMElement $element */

            $old = $element->getAttribute('value');
            $parts = array_map('trim', explode('/', $old));
            if (3 == count($parts)) {
                $new = trim("{$parts[0]} /{$parts[1]}/ {$parts[2]}");
            } else {
                $new = $old;
            }

            if ($new !== $old) {
                $element->setAttribute('value', $new);
                $this->debugElement($element);
            }
        }
    }
}
