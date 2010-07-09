<?php

class HogeTask extends HarpyTask{
    var $uses = array('Test');

    var $dataList = array();

    
    function beforefilter(){
        $this->dataList = array();
    }
    
    function afterfilter(){
        $this->Test->save($this->dataList, false);
    }
    
    function hookFromAddress($from){
        //echo $from;
        $this->dataList['mail'] = $from;
    }
    
    function hookMail($mail){
        //echo $mail;
    }
    
    function hookToAddress($to){
        //echo $to;
    }
    function hookBody($body){
        //echo $body;
        $this->dataList['body'] = $body;
    }
    
    
    function hookAttachment($attachment, $name, $type){
        $this->_save($attachment);
    }
    
    // 機能 ファイルの保存
    // 引数 $file: ファイル名
    // $str: ファイル内容
    // 戻値 書き込んだファイルサイズ
    private function _save($file) {
        $unique = md5(uniqid(rand(), true));
        $name = sprintf("%s.jpg", $unique);
        
        $path = "";
        $path = sprintf("%s%s/%s", TMP, "hoge", $name);
        
        $fp = fopen($path, "w");
        if($fp === false){
            return false;
        }
        $size = fwrite($fp, $file);
        fclose($fp);
        chmod($path, 0777);
        return $size;
    }
}