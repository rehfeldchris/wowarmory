<?php

/*
 * Evaluates an expression of the form cx^e, where c and e are user supplied numbers.
 * x is incremented by 1 each iteration.
 * 
 * Useful to produce linear or exponential sequences.
 */

class MathExpressionSequence implements Iterator
{
    protected $x = 0;
    protected $c = 0;
    protected $e = 0;

    public function __construct($c, $e)
    {
        $this->c = $c;
        $this->e = $e;
    }

    function rewind()
    {
        $this->x = 0;
    }

    function current()
    {
        return $this->evaluate();
    }

    function key()
    {
        return $this->x;
    }

    function next()
    {
        $this->x++;
    }

    function valid()
    {
        //the sign of $c and the result should match, or we overflowed
        return !($c < 0 xor $this->evaluate() < 0);
    }

    protected function evaluate()
    {
        return $this->c * pow($this->x, $this->e);
    }

    function __toString()
    {
        return sprintf("c=%f, x=%f, e=%f, cx^e=%f", $this->c, $this->x, $this->e, $this->current());
    }
}