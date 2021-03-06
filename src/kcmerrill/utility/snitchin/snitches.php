<?php

namespace kcmerrill\utility\snitchin;
use GuzzleHttp\Client;

class snitches {

    static function file() {
        return function($log_entry, $filename) {
            if(!is_file($filename)) {
                if(!is_dir(dirname($filename))) {
                    mkdir(dirname($filename),0755, true);
                }
            }

            file_put_contents($filename, '[' . date('F j, Y, g:i a', $log_entry['timestamp']) . '] ' . str_pad(strtoupper($log_entry['level']['text']), 10, ' ', STR_PAD_RIGHT) . $log_entry['msg'] . ' ' . json_encode($log_entry['additional_params'], TRUE) . PHP_EOL, FILE_APPEND);
        };
    }

    static function email() {
        return function($log_entry, $options) {
            mail($options, strtoupper($log_entry['level']['text']) . ': ' . $log_entry['msg'], 'Message generated by the fine folks at Snitch.');
        };
    }

    static function http() {
        return function($log_entry, $options) {
            $client = new Client;
            $url = $options;
            $method = 'put';
            if(is_array($options)) {
                if(!isset($options['url'])) {
                    throw new \LogicException('HTTP requires either a string(url) or an array of options with the key url to be set to CURL');
                }
                $url = $options['url'];
                if(isset($options['method'])) {
                    $method = $options['method'];
                }
            }
            if(!is_string($url)) {
                throw new \LogicException('HTTP requires either a string(url) or an array of options with the key url to be set to CURL');
            }
            if($method == 'get') {
                $client->getAsync($url);
            } else {
                $client->postAsync($url, array('body'=>json_encode($log_entry)));
            }
        };
    }

    static function slack() {
        return function($log_entry, $options) {
            $client = new Client;
            $url = $options;
            if(is_array($options)) {
                if(!isset($options['url'])) {
                    throw new \LogicException('Slack requires either a string(url) or an array of options with the key url to be set to CURL');
                }
                $url = $options['url'];
            }
            $client->postAsync($url, array('body'=>json_encode(array(
                'text'=>strtoupper($log_entry['level']['text']) . ': ' . $log_entry['msg']
            ))));
        };
    }

    static function json() {
        return function ($log_entry, $options) {
            $options = is_array($options) ? $options : array();
            echo json_encode($log_entry, JSON_NUMERIC_CHECK) . PHP_EOL;
        };
    }

    static function standard() {
        return function($log_entry, $options) {

            $options['msg_length'] = isset($options['msg_length']) ? $options['msg_length'] : 100;

            $color = 'white';
            switch ($log_entry['level']['value']) {
                case $log_entry['level']['value'] <= 10:
                    $color = '1:34';
                break;
                case $log_entry['level']['value'] <= 20:
                    $color = '0;34';
                break;
                case $log_entry['level']['value'] <= 30:
                    $color = '1;37';
                break;
                case $log_entry['level']['value'] <= 40:
                    $color = '1;33';
                break;
                case $log_entry['level']['value'] <= 50:
                    $color = '1;31';
                break;
                case $log_entry['level']['value'] <= 60:
                    $color = '0;31';
                break;
                default:
                    $color = '0;32';
            }
            echo '[' . date('F j, Y, g:i a', $log_entry['timestamp']) . '] ' . "\033[" . $color . "m" . str_pad(strtoupper($log_entry['level']['text']), 10, ' ', STR_PAD_RIGHT) . "\033[0m " . str_pad(substr($log_entry['msg'], 0, $options['msg_length']), $options['msg_length'] +5)  . json_encode($log_entry['additional_params'], TRUE) . PHP_EOL;
            return true;
        };
    }
}
