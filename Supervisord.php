<?php

/**
 * Class Supervisord
 *
 * @method  getAPIVersion
 * @method getSupervisorVersion
 * @method getIdentification
 * @method getState
 * @method getPID
 * @method readLog(int $offset, int $length)
 * @method clearLog
 * @method shutdown
 * @method restart
 * @method getProcessInfo($name)
 * @method getAllProcessInfo
 * @method startProcess($name, $wait = true)
 * @method startAllProcesses($wait = true)
 * @method startProcessGroup($name, $wait = true)
 * @method stopProcess($name, $wait = true)
 * @method stopProcessGroup($name, $wait = true)
 * @method stopAllProcesses($wait = true)
 * @method signalProcess($name, $signal)
 * @method signalProcessGroup($name, $signal)
 * @method signalAllProcesses($signal)
 * @method sendProcessStdin($name, $chars)
 * @method sendRemoteCommEvent($type, $data)
 * @method reloadConfig()
 * @method addProcessGroup($name)
 * @method removeProcessGroup($name)
 *
 * @method readProcessStdoutLog($name, $offset, $length)
 * @method readProcessStderrLog($name, $offset, $length)
 * @method tailProcessStdoutLog($name, $offset, $length)
 * @method tailProcessStderrLog($name, $offset, $length)
 * @method clearProcessLogs($name)
 * @method clearAllProcessLogs($name)
 *
 *
 *  system method
 * @method listMethods()
 * @method methodHelp()
 * @method methodsSignature()
 *
 *
 *
 * @see http://supervisord.org/api.html
 */

class Supervisord
{
    private $server = array(
        "url" => "/RPC2",
        "port" => 9001,
    );

    static $systemCommands = array(
        "listMethods",
        "methodHelp",
        "methodsSignature",
    );

    static $options = array();

    function __construct($host, $port = "", $url = "", $user = "", $pass = "")
    {
        $this->server ['host'] = $host;
        $this->server ['port'] = $port;
        $this->server ['url'] = $url;

        if (func_num_args() == 5) {
            $this->server ['user'] = $user;
            $this->server ['pass'] = $pass;
        }

        $this->setOptions();
    }

    private function setOptions()
    {
        $url = $this->server['host'];
        if (isset($this->server['port'])) {
            $url .= ":" . $this->server['port'];
        }
        if (isset($this->server['url'])) {
            $url .= $this->server['url'];
        }

        self::$options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 10,
        );

        // 判断是否需要授权
        if (isset($this->server['user'])) {
            self::$options [CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
            self::$options [CURLOPT_USERPWD] = $this->server['user'] . ":" . $this->server['pass'];
        }
    }

    /**
     * @param string $method
     * @param $params
     * @return mixed
     */
    function __call($method, $params)
    {
        if (in_array($method, static::$systemCommands)) {
            $method = "system." . $method;
        } else {
            $method = "supervisor." . $method;
        }
        $request = xmlrpc_encode_request($method, $params);
        $header[] = "Content-type: text/xml";
        $header[] = "Content-length: " . strlen($request);
        $curl_options = self::$options;
        $curl_options[CURLOPT_HTTPHEADER] = $header;
        $curl_options[CURLOPT_POSTFIELDS] = $request;
        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            $data['error'] = curl_error($ch);
        } else {
            $data = xmlrpc_decode($data);
        }
        curl_close($ch);
        return $data;
    }
}