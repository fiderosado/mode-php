<?php

namespace Core;

class File{
    public function __construct(){}
    public static function loadFile($rs,$type){
        if (file_exists($rs)) {
            if (is_file($rs)) {
                if (is_readable($rs)) {
                    $reader = fopen($rs, "r");
                    if (is_readable($rs)) {
                        $d = fread($reader, filesize($rs));
                        switch ($type){
                            case 'json':
                                $resConfig = json_decode($d);
                                if (is_object($resConfig)) {
                                    return $resConfig;
                                }
                                break;
                            case 'css': return $d;
                                break;
                        }

                        fclose($reader);
                    }
                }
            }
        }
        return null;
    }
    public static function load( $type , $namespace ){
        $ruta = "";
        switch ($type){
            case "css":
                $type = 'css';
                $ruta = self::preparePath($namespace , true , false).'.css';
                break;
            case "resources":
                $ap = array('');
                $type = 'json';
                $ap = array_merge( $ap ,explode('\\', $namespace ) );
                $ruta = substr( implode('\.', $ap), 1) .'.config.json';
                break;
        }
        return self::loadFile($ruta , $type );
    }
    public static function preparePath( $ns , $forRoutes , $fromClass ){
        if ($forRoutes){
            $ap = array('');
            $ns = $fromClass ? str_replace( '/' , '\\' , $ns ): $ns;
            $ap = array_merge( $ap , explode('\\', $ns ) );
            return substr( implode('\.', $ap), 1);
        }else{
            $na = str_replace( '/' , '\\' , $ns );
            return $na;
        }
    }
}
