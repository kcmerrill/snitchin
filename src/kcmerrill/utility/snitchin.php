<?php

namespace kcmerrill\utility;

class snitchin extends \Pimple\Container {

    private $default_level;
    private $default_snitches;

    function __construct($default_level = 40, $default_snitches = 'standard') {
        $this->default_level = $default_level;
        $this->default_snitches = $default_snitches;
        /* Create a default plain jane logger */
        $this->channel('default', $default_snitches, $default_level);
        /* Call pimple's construct() */
        parent::__construct();
    }

    function channel($name, $default_snitches = false, $default_level = false) {
        $default_level = $default_level === false ? $this->default_level : $default_level;
        $default_snitches = $default_snitches === false ? $this->default_snitches : $default_snitches;
        $this[$name] = function($c) use ($default_level, $default_snitches) {
            return new \kcmerrill\utility\snitchin\snitch($default_level, $default_snitches);
        };
    }

    function __call($method, $params) {
        call_user_func_array(array($this['default'], $method), $params);
    }
}
