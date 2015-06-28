<?php

namespace kcmerrill\utility\snitchin;

use kcmerrill\utility\snitchin\snitches;

class snitch {

    /* Given levels of snitchin */
    private $levels = array();
    private $level = 40;
    private $activated_snitches = false;
    private $snitches = array();

    function __construct($default_level = 40, $default_snitches = 'standard') {
        $this->level = is_numeric($default_level) ? $default_level : $this->cliLevel($default_level);
        $this->activated_snitches = is_string($default_snitches) ? explode('|', $default_snitches) : $default_snitches;

        /* Setup some custom levels by default */
        $this->level('fatal', 60);
        $this->level('error', 50);
        $this->level('warn', 40);
        $this->level('info', 30);
        $this->level('debug', 20);
        $this->level('trace', 10);

        /* Setup default snitchers */
        foreach($this->activated_snitches as $snitcher) {
            if(method_exists('\kcmerrill\utility\snitchin\snitches', $snitcher)) {
                $this->snitcher($snitcher, snitches::$snitcher(), $this->level);
            }
        }
    }

    function level($level, $value = false) {
        $level = strtoupper($level);
        if($value === false) {
            /* Trying to retreive the value of $level */
            return isset($this->levels[$level]) ? $this->levels[$level] : $this->level($level, 61);
        } else {
            if(!isset($this->levels[$level])) {
                /* Setting up a new custom level */
                $this->levels[$level] = $value;
                if(!defined('SNITCH_' . $level)) {
                    define('SNITCH_' . $level, $value);
                }
                return $value;
            } else {
                /* Make it so that levels cannot be redefined once set */
                throw new \LogicException($level . ' was prevously set to ' . $this->levels[$level]);
            }
        }
    }

    private function cliLevel($args) {
        /* Calculate the level based on the number of v's! */
        $args = is_array($args) ? $args : array();
        $level = 60;
        for($x=1;$x<=15;$x++) {
            if(in_array('-' . str_repeat('v', $x), $args)) {
                $level = ($x * 10);
            }
        }
        /* If argv is only 1, then no -v was given. Set to Fatals */
        return count($args) == 1 ? 60 : 60 - $level;
    }

    function snitcher($name, $snitch = false, $level = false, $activated = true) {
        /* First off, if $level is false, set it to the default level */
        $level = $level === false ? $this->level : $level;

        if(is_callable($snitch)) {
            /* A brand new shiny snitch, or overwriting one of mine */
            $this->snitches[$name] = array(
                'callback'=>$snitch,
                'level'=>$level,
                'options'=>array(),
                'activated'=>$activated
            );
        } else if (is_array($snitch) || is_string($snitch)) {
            /* User is giving us options to pass along */
            if(isset($this->snitches[$name])) {
                $this->snitches[$name]['options'] = $snitch;
                $this->snitches[$name]['level'] = $level;
            } else {
                throw new \LogicException('The snitch ' . $name . ' must first be created and/or activated before setting options!');
            }
        } else if (is_numeric($snitch)) {
            /* User wants to change the emit level for this particular snitch */
            if(isset($this->snitches[$name])) {
                $this->snitches[$name]['level'] = $snitch;
            } else {
                throw new \LogicException('the snitch ' . $name . ' must first be created before setting it\'s new emit level');
            }
        } else {
            /* I know I missed something, placeholder */
        }
    }

    private function emit($level, $msg, $additional_params = array()) {
        foreach($this->activated_snitches as $snitch) {
            /* If the snitch chosen doesn't exist, default to the default_snitch */
            $snitch = isset($this->snitches[$snitch]) ? $snitch : 'standard';

            if($this->snitches[$snitch]['level'] <= $this->level($level) && $this->snitches[$snitch]['activated']) {
                $this->snitches[$snitch]['callback'](array(
                    'msg'=>$msg,
                    'additional_params'=>$additional_params,
                    'timestamp'=>time(),
                    'level'=>array('text'=>$level, 'value'=>$this->level($level))
                ), $this->snitches[$snitch]['options']);
            }
        }
    }

    public function __call($method, $params) {
        $additional_params = array();
        if(!isset($params[0])) {
            $params[0] = '';
        } else {
            $additional_params = isset($params[1]) ? $params[1] : array();
        }
        return $this->emit($method, $params[0], $additional_params);
    }
}
