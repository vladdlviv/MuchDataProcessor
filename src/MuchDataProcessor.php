<?php

namespace MuchDataProcessor;


class MuchDataProcessor
{

    public function __construct($inputFile, InputInterface $input, OutputInterface $output)
    {
        $input->open($inputFile);
        $output->store($input);
    }

}