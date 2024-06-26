<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class gaz_ftp {
    public $_host;
    public $_user;
    public $_pwd;
    public $_port;
    public $_timeout;
    private $_id;
    
    public function  __construct($host = null, $user = null, $password = null, $port = 21, $timeout = 90) {
	$this->_host = $host;
	$this->_user = $user;
	$this->_pwd = $password;
	$this->_port = (int)$port;
        $this->_timeout = (int)$timeout;
    }
    
    public function connect() {
        $this->_id = ftp_connect($this->_host);
        $login_result = ftp_login($this->_id, $this->_user, $this->_pwd);
        return true;
    }
    
    public function chdir( $valore ) {
        ftp_chdir($this->_id, $valore);
    }
    
    public function put( $file, $remote, $mode = FTP_ASCII ) {
        if (ftp_put($this->_id, $file, $remote, $mode)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function get( $file, $remote, $mode = FTP_ASCII ) {
        if(ftp_get($this->_id, $file, $remote, $mode)) {
            return true;
	} else {
            return false;
        }
    }
    
    public function append($file, $folder, $string ) {
            $conn_str = "ftp://".$this->_user.":".$this->_pwd."@".$this->_host."/".$folder."/";
            //echo $conn_str."<br>";
            file_put_contents($conn_str.$file, $string, FILE_APPEND ); //| LOCK_EX
            //file_put_contents($file, $person, FILE_APPEND | LOCK_EX);
    }
        
    public function delete($remote = null) {
	if(ftp_delete($this->_id, $remote)) {
            return true;
	} else {
            return false;
	}
    }
    
    public function ls($directory = ".") {
	$list = array();
	if($list = ftp_nlist($this->_id, $directory)) {
            return $list;
	} else {
            //echo "Failed to get directory list";
            return false;
	}
    }
    
    public function file_exists( $check_file_exist ) {
        $contents_on_server = $this->ls();
        if ( $contents_on_server ) {
            if (in_array($check_file_exist, $contents_on_server)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function filesize($file = null) {
        if($res = ftp_size($this->_id, $file)) {
            return $res;
        } else {
            echo "Failed to get filesize \"{$file}\"";
            return false;
        }
    }
        
    public function destruct() {
        ftp_close($this->_id);
    }
}