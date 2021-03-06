<?php

/**
 * Class to abstract the routing to the Zend Framework MVC
 * module architecture.
 */
class Extzf_ExtDirect_Router extends ExtDirect_Router
{

    /**
     * Processes one request and dispatches it.
     * @param object $data Request object
     * @return array Result
     */
    protected function _parseRequest($data) {

        // Prepare response
        $response = array(
            'type' => 'rpc',
            'tid' => $data->tid,
            'action' => $data->action,
            'method' => $data->method
        );
        $data->module = ucfirst($data->module);
        
        // Dynamically load and invoke requested class method
        require_once APPLICATION_PATH . '/modules/' . strtolower($data->module) . '/controllers/' . ucfirst($data->action) . '.php';

        $controllerClassName = $data->module .'_'. $data->action;
        if (strtolower($data->module) == "core") {
            $controllerClassName = $data->action;
        }
        $controllerInst = new $controllerClassName(
            new Zend_Controller_Request_HttpTestCase(),
            new Zend_Controller_Response_HttpTestCase()
        );

        if (!isset($data->data) || !is_array($data->data)) {
            $data->data = array();
        }
        $response['result'] = call_user_func_array(array($controllerInst, $data->method), $data->data);

    	return $response;
    }


    /**
     * Parses one or many requests given and sets the MVC module
     * parameter on request data object. Also includes
     * the controller PHP file to allow instanciating it.
     *
     * @see ExtDirect_Router::parseRequest()
     * @return void
     */
    public function parseRequest()
    {
        if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {

            // Custom implemented high-performance call stack
            $this->data = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);

            // Multi-transaction handling
            if (is_array($this->data)) {

                $response = array();
                for ($i=0; $i<sizeof($this->data); $i++) {
                    $response[] = $this->_parseRequest($this->data[$i]);
                }
                echo Zend_Json::encode($response);
                exit();

            } else {

                // Handle only one request
                $response = $this->_parseRequest($this->data);
                echo Zend_Json::encode($response);
                exit();
            }

        } else if (isset($_POST['extAction'])) { // form post

            $this->isForm = true;
            $this->isUpload = $_POST['extUpload'] == 'true';

            $data = new BogusAction();
            $data->action = $_POST['extAction'];
            $data->method = $_POST['extMethod'];
            $data->module = $_POST['extModule'];
            $data->tid = $_POST['extTID'];
            $data->data = array($_POST, $_FILES);

            $this->data = $data;
            
            // Handle only one request
            echo Zend_Json::encode($this->_parseRequest($this->data));
            exit();
            
        } else {
            die('Invalid request.');
        }
    }
}