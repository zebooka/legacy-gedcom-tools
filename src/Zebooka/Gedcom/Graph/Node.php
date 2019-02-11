<?php

namespace Zebooka\Gedcom\Graph;

class Node
{
    public $level;
    public $element;

    public $father;
    public $mother;
    public $spouses = [];
    public $children = [];

    public function __construct(\DOMElement $element)
    {
        $this->element = $element;
    }
}
