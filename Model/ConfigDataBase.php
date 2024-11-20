<?php
class ConfigDataBase
{
    public static function getConfig($base){
        switch ($base){
            case 'produccion':
                $configDB = array(
                    'host' => 'localhost',
                    'user' => 'hirbo',
                    'password' => 'bgL9711CQ6Rs1Iux',
                    'bd' => 'hirbo',
                    'puerto' => '3306'
                );
                break;
            default:
                $configDB = array(
                    'host' => 'localhost',
                    'user' => 'root',
                    'password' => '',
                    'bd' => 'hirbo',
                    'puerto' => '3306'
                );
                break;
        }
        return $configDB;
    }
}