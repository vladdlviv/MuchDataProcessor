<?php

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class OutputDbTest extends TestCase
{

    private $_pdo = null;

    private $_config = [
        'host'      => 'localhost',
        'user'      => 'root',
        'password'  => 'secret',
        'name'      => 'test',
        'prefix'    => '__test_',
        'tableName' => 'testTable'
    ];

    public function getConnection()
    {
        if ($this->_pdo === null) {
            try {
                $this->_pdo = new \PDO("mysql:host={$this->_config['host']};dbname={$this->_config['name']}",
                    $this->_config['user'], $this->_config['password']);
            } catch (PDOException $e) {
                throw new \Exception($e);
            }
        }
        return $this->_pdo;
    }

    public function testIt()
    {

        $cmpArr = [[
                    'id' => '1',
                    'field1' => 'value1',
                    'field2' => 'value2',
                    'field3' => 'value3'
        ]];
        $root = vfsStream::setup('root', null, [
            $this->_config['tableName'].'.csv' => 'field1,field2,field3
value1,value2,value3'
        ]);

        $this->getConnection();

        $input = new MuchDataProcessor\InputCsv();
        $output = new \MuchDataProcessor\OutputDb($this->_config['host'], $this->_config['user'],
            $this->_config['password'], $this->_config['name'], $this->_config['prefix']);

        $testFile = $root->getChild($this->_config['tableName'].'.csv');

        $input->open($testFile->url());

        $this->assertInstanceOf(\PDO::class, $output->getConnection());

        $this->tearDown();

        $this->assertEmpty($this->_getResults());

        $output->store($input);

        $this->assertEquals($this->_getResults(), $cmpArr);
    }

    private function _getResults()
    {
        try {
            $query = $this->getConnection()->query('SELECT * FROM  `'.$this->_config['prefix'].$this->_config['tableName'].'`');
            if($query) {
                return $query->fetchAll(PDO::FETCH_ASSOC);
            }
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function tearDown()
    {

        try {
            $this->getConnection()->exec('DROP TABLE `'.$this->_config['prefix'].$this->_config['tableName'].'`');
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

    }

}