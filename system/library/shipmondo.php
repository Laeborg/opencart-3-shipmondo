<?php
require 'shipmondo/ShipmondoAPI.php';

class Shipmondo extends ShipmondoAPI
{
    function __construct($api_user, $api_key)
    {
        parent::__construct($api_user, $api_key);
    }
    
    private function writeLog($message)
    {
        $file = DIR_STORAGE . 'logs/shipmondo.log';
        
        if(!file_exists($file))
        {
            touch($file);
        }
        elseif(filesize($file) > 1024 * 1024 * 2) // Max 2 MB
        {
            rename($file, $file . '-' . date('Y-m-dH:i:s'));
            touch($file);
        }
        
        $fp = fopen($file, 'a');
        fwrite($fp, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL);
        fclose($fp);
    }
}