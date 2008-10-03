<?php

class X
{
    public function y()
    {
        echo "y\n";
        return $this;
    }
    public function z()
    {
        echo "z\n";
        return $this;
    }
    public function a($n)
    {
        return $n;
    }
}

$x = new X();
echo $x->y()->z()->a(1);


