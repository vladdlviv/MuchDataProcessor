<?php

namespace MuchDataProcessor;


class InputCsv implements \MuchDataProcessor\InputInterface
{

    private $_delimiter;
    private $_maxLineLength;
    private $_file = null;
    private $_data = [];

    public function __construct($delimiter = ',', $maxLineLength = 4096)
    {
        $this->_delimiter = $delimiter;
        $this->_maxLineLength = $maxLineLength;
    }

    public function open($file)
    {
        $this->_file = $file;

        //try to avoid line endings issue
        ini_set('auto_detect_line_endings', true);

        if (($handle = fopen($file, "r")) !== false) {

            while (($data = fgetcsv($handle, $this->_maxLineLength, $this->_delimiter)) !== false) {
                $this->_data[] = $data;
            }

            fclose($handle);

            // "<= 1" because we need to have header + at least one row of data
            if(count($this->_data) <= 1) {
                throw new \Exception('Input is empty or something.');
            }

        } else {
            throw new \Exception('Can not read input file.');
        }

        ini_set('auto_detect_line_endings', false);
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getName()
    {
        return $this->_file;
    }

}