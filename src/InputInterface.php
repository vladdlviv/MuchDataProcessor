<?php

namespace MuchDataProcessor;


interface InputInterface
{

    public function open($file);
    public function getData();
    public function getName();

}