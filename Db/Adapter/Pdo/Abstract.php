<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 20096 2010-01-06 02:05:09Z bkarwin $
 */


/**
 * @see Zend_Db_Adapter_Pdo_Abstract
 */
require_once 'Zend/Db/Adapter/Pdo/Abstract.php';


/**
 * @see Zend_Db_Statement_Pdo
 */
require_once 'Zend/Db/Statement/Pdo.php';

class PDOCSTM extends PDO {
    public function __construct($dsn, $username="", $password="", $driver_options=array()) {
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('STMT', array($this)));
    }
}
class STMT extends PDOStatement {
    public $dbh;
    protected function __construct($dbh) {
        $this->dbh = $dbh;
    }
//    function fetch($option=PDO::FETCH_ASSOC) {
//        $row = parent::fetch($option);
//        if (is_array($row)) {
//            foreach ($row as $key=>$val) {
//                $row[$key] = mb_convert_encoding($val, "UTF-8", "EUC-JP");
//            }
//        }
//        return $row;
//    }
//    function fetchAll($option=PDO::FETCH_ASSOC) {
//        $rows = parent::fetchAll($option);
//        foreach ($rows as $key => $row) {
//            foreach ($row as $crm => $val) {
//                $rows[$key][$crm] = mb_convert_encoding($val, "UTF-8", "EUC-JP");
//            }
//        }
//        return $rows;
//    }
    function execute($params=array()) {
        if (APPLICATION_ENV !== "production") {
            $sql = preg_replace('#(\r\n|\n)#', " ", $this->queryString);
            if (!preg_match("/SELECT/", $this->queryString)) {
                error_log("[SQL] " . $sql . ' ' . preg_replace("/(¥n|¥n¥r|¥r)/", " ", print_r($params,true)));
            }
            else {
                error_log("[SQL] " . $sql);
            }
//            if (!empty($params)) {
//                $params = App_Array::getEncodedData($params, "EUC-JP", "UTF-8");
//            }
        }
        return parent::execute($params);
    }
}
/**
 * Class for connecting to SQL databases and performing common operations using PDO.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class App_Db_Adapter_Pdo_Abstract extends Zend_Db_Adapter_Pdo_Abstract
{
    /**
     * Creates a PDO object and connects to the database.
     *
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _connect()
    {
        // if we already have a PDO object, no need to re-connect.
        if ($this->_connection) {
            return;
        }

        // get the dsn first, because some adapters alter the $_pdoType
        $dsn = $this->_dsn();

        // check for PDO extension
        if (!extension_loaded('pdo')) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception('The PDO extension is required for this adapter but the extension is not loaded');
        }

        // check the PDO driver is available
        if (!in_array($this->_pdoType, PDO::getAvailableDrivers())) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception('The ' . $this->_pdoType . ' driver is not currently installed');
        }

        // create PDO connection
        $q = $this->_profiler->queryStart('connect', Zend_Db_Profiler::CONNECT);

        // add the persistence flag if we find it in our config array
        if (isset($this->_config['persistent']) && ($this->_config['persistent'] == true)) {
            $this->_config['driver_options'][PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $this->_connection = new PDOCSTM(
                $dsn,
                $this->_config['username'],
                $this->_config['password'],
                $this->_config['driver_options']
            );

            $this->_profiler->queryEnd($q);

            // set the PDO connection to perform case-folding on array keys, or not
            $this->_connection->setAttribute(PDO::ATTR_CASE, $this->_caseFolding);

            // always use exceptions.
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            require_once 'Zend/Db/Adapter/Exception.php';
            throw new Zend_Db_Adapter_Exception($e->getMessage(), $e->getCode(), $e);
        }

    }

}
