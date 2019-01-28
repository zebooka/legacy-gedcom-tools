<?php

namespace Zebooka\Gedcom;

class Gedcom5Document
{
    /** @var \DOMDocument */
    public $dom;

    public function __construct($source)
    {
        $this->dom = new \DOMDocument('1.0', 'utf-8');
        $this->dom->loadXML('<GEDCOM/>');
        $stack = array($this->dom->documentElement);
        $lines = explode("\n", $source);

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");
            if ('' == $line) {
                continue;
            }
            $currentLevel = count($stack) - 2;
            $elementLevel = 0;
            $element = $this->createElementFromLine($line, $elementLevel);

            while ($currentLevel >= $elementLevel) {
                array_pop($stack);
                $currentLevel--;
            }

            /** @var \DOMElement $parentNode */
            $parentNode = end($stack);
            $element = $parentNode->appendChild($element);
            array_push($stack, $element);
        }
    }

    private function createElementFromLine($line, &$level)
    {
        list($level, $tag, $value) = array_pad(explode(' ', $line, 3), 3, null);

        if (0 == $level && preg_match('/^@.+@$/', $tag)) {
            $id = $tag;
            list ($tag, $value) = array_pad(explode(' ', $value, 2), 2, null);
        }

        $element = $this->dom->createElement($tag);
        isset($id) && $element->setAttribute('id', $id);
        isset($value) && $element->setAttribute('value', $value);

        return $element;
    }

    public function toGedcom()
    {
        $gedcom = '';
        foreach ($this->dom->documentElement->childNodes as $childNode) {
            $gedcom .= $this->composeLinesFromElement($childNode, 0) . PHP_EOL;
        }
        return $gedcom;
    }

    public static function composeLinesFromElement(\DOMElement $element, $level)
    {
        $gedcom = "{$level} " .
            ($element->getAttribute('id') ? "{$element->getAttribute('id')} " : '') .
            $element->nodeName .
            (strlen('' . $element->getAttribute('value')) ? " {$element->getAttribute('value')}" : '');

        foreach ($element->childNodes as $childNode) {
            $gedcom .= PHP_EOL . self::composeLinesFromElement($childNode, $level + 1);
        }

        return $gedcom;
    }

    public static function composeLinesFromElementWithRoot(\DOMElement $element)
    {
        $lines = [];
        $parent = $element->parentNode;
        while ($parent->parentNode instanceof \DOMElement) {
            $line = ($parent->getAttribute('id') ? "{$parent->getAttribute('id')} " : '') .
                $parent->nodeName .
                (strlen('' . $parent->getAttribute('value')) ? " {$parent->getAttribute('value')}" : '');
            array_unshift($lines, $line);
            $parent = $parent->parentNode;
        }
        foreach ($lines as $i => $line) {
            $lines[$i] = "{$i} {$line}";
        }
        return implode(PHP_EOL, $lines) . PHP_EOL . self::composeLinesFromElement($element, count($lines));
    }

    public function xpath($expression, $contextnode = null)
    {
        return (new \DOMXPath($this->dom))->query($expression, $contextnode);
    }

    public function evaluate($expression, $contextnode = null)
    {
        return (new \DOMXPath($this->dom))->evaluate($expression, $contextnode);
    }

    public static function log(\DOMElement $element)
    {
        $level = -3;
        $parentNode = $element;
        while ($parentNode && $parentNode !== $parentNode->parentNode) {
            $level++;
            $parentNode = $parentNode->parentNode;
        }
        return "{$element->getNodePath()}:\n" . Gedcom5Document::composeLinesFromElement($element, $level) . "\n\n";
    }
}
