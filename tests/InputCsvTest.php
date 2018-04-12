<?php

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class InputCsvTest extends TestCase
{

    public function testIt()
    {

        $cmpArr = [['field1', 'field2', 'field3'], ['value1', 'value2','value3']];
        $root = vfsStream::setup('root', null, [
            'test.csv' => 'field1,field2,field3
value1,value2,value3',
            'testDelimiter.csv' => 'field1;field2;field3
value1;value2;value3'
        ]);

        $input = new MuchDataProcessor\InputCsv();
        $inputDelimiter = new MuchDataProcessor\InputCsv(';');

        $this->assertInstanceOf(MuchDataProcessor\InputCsv::class, $input);

        $testFile = $root->getChild('test.csv');
        $testDelimiterFile = $root->getChild('testDelimiter.csv');

        $input->open($testFile->url());
        $inputDelimiter->open($testDelimiterFile->url());

        $this->assertEquals($input->getData(), $cmpArr);
        $this->assertEquals($input->getName(), $testFile->url());

        $this->assertEquals($inputDelimiter->getData(), $cmpArr);
        $this->assertEquals($inputDelimiter->getName(), $testDelimiterFile->url());

    }

}