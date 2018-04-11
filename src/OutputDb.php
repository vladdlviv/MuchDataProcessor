<?php

namespace MuchDataProcessor;


class OutputDb implements OutputInterface
{
    private $_dbHost;
    private $_dbUser;
    private $_dbPassword;
    private $_dbName;
    private $_dbPrefix;

    private $_pdo;

    private $_input;
    private $_tableName = null;
    private $_schema = [];

    public function __construct($dbHost, $dbUser, $dbPassword, $dbName, $dbPrefix = '')  {
        $this->_dbHost = $dbHost;
        $this->_dbUser = $dbUser;
        $this->_dbPassword = $dbPassword;
        $this->_dbName = $dbName;
        $this->_dbPrefix = $dbPrefix;

        $this->_makeConnection();

        //let's catch exceptions!
        $this->getConnection()->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    }

    private function _makeConnection()
    {
        $this->_pdo = new \PDO("mysql:dbname={$this->_dbName};host={$this->_dbHost};charset=utf8",
            $this->_dbUser, $this->_dbPassword);
    }

    /**
     * @return \PDO
     */
    private function getConnection()
    {
        return $this->_pdo;
    }

    /**
     * function generates schema based on data size if user didn't provide one
     */
    public function getSchema()
    {
        //making schema from header row
        foreach ($this->_input->getData()[0] as $schemaKey => $schemaField) {
            $this->_schema[$schemaKey] = [
                'name' => preg_replace("/[^A-Za-z0-9?!]/",'', $schemaField),
                'length0' => null
            ];
        }

        $schemaSize = count($this->_schema);

        foreach ($this->_input->getData() as $dataKey => $dataItem) {

            if($dataKey == 0) {
                //skip header
                continue;
            }

            if(count($dataItem) > $schemaSize) {
                // we have got more columns that defined in header
                throw new \Exception('Solidarity with header violation at data row #'.($dataKey+1));
            }

            foreach ($dataItem as $dataItemKey => $dataItemVal) {

                // check if length isn't defined yet, or if this item has at least one character more
                if($this->_schema[$dataItemKey]['length0'] == null
                    || isset($dataItemVal[$this->_schema[$dataItemKey]['length0']+1])) {

                    //if so, let's measure and set as maximum length for this column
                    $this->_schema[$dataItemKey]['length0'] = strlen($dataItemVal)-1;
                }

            }

        }

        return $this->_schema;
    }

    /**
     * set table name and schema manually
     */
    public function setTable($tableName, $schema = null)
    {
        $this->_tableName = $this->_dbPrefix.$tableName;
        if($schema != null) {
            $this->_schema = $schema;
        }
    }

    private function _createTable()
    {
        $params = [];
        $sql ="CREATE TABLE IF NOT EXISTS {$this->_tableName}(
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ";

        for ($i = 0; $i <= (count($this->_schema)-1); $i++) {
            $sql .= $this->_schema[$i]['name']." VARCHAR(".($this->_schema[$i]['length0']+1).")"
                .(($i < (count($this->_schema)-1)) ? ',' : '');
        }

        $sql .= ");";

        try {
            $st = $this->getConnection()->prepare($sql);
            $st->execute($params);
        } catch (\Exception $e) {
            die('DB exception '.$e);
        }

    }

    private function _storeData()
    {
        try {
            $this->getConnection()->beginTransaction();

            foreach ($this->_input->getData() as $dataKey => $data) {

                $params = [];

                if($dataKey == 0) {
                    // skip header
                    continue;
                }

                $sql = "INSERT INTO {$this->_tableName} (";

                for ($i = 0; $i <= (count($this->_schema)-1); $i++) {
                    $sql .= $this->_schema[$i]['name']
                        .(($i < (count($this->_schema)-1)) ? ',' : '');
                }

                $sql .= ") VALUES (";

                for ($i = 0; $i <= (count($data)-1); $i++) {
                    $sql .= ":value{$i}".(($i < (count($data)-1)) ? ',' : '');
                    $params[":value{$i}"] = $data[$i];
                }

                $sql .= ");";

                $st = $this->getConnection()->prepare($sql);

                foreach ($params as $param => $paramVal) {
                    $st->bindValue($param, $paramVal);
                }

                $st->execute();

            }

            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            die('DB exception '.$e);
        }
    }

    public function store(InputInterface $input)
    {
        $this->_input = $input;

        if($this->_tableName == null) {
            $this->_tableName = $this->_dbPrefix.preg_replace("/[^A-Za-z0-9?!]/",'', pathinfo($this->_input->getName(), PATHINFO_FILENAME));
        }

        if($this->_schema == []) {
            $this->_schema = $this->getSchema();
        }

        $this->_createTable();

        $this->_storeData();
    }

}