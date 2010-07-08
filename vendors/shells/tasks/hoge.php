<?php

//App::import('Vendor', 'pear_path');
//App::import('Vendor', 'Mail_mimeDecode', array('file'=>'Mail_mimeDecode/mimeDecode.php'));

//require_once(APP . 'config/bootstrap.php');

// 参考サイト
// http://www.hand-in-hand.biz/c-board/c-board.cgi?cmd=ntr;tree=28;id=0002

//App::import('Vendor', 'harpy', array('file'=>'shells/tasks/harpy.php'));

class HogeTask extends HarpyTask{
    var $uses = array();


    function _welcome(){
        //$this->Dispatch->clear();
        /*$this->out();
        $this->out('Welcome to CakePHP v' . Configure::version() . ' Console');
        $this->hr();
        $this->out('App : '. $this->params['app']);
        $this->out('Path: '. $this->params['working']);
        $this->hr();*/
    }
    
    function hookFromAddress($from){
        echo $from;
    }
    
    function hookMail($mail){
        echo $mail;
    }
    
    function hookToAddress($to){
        echo $to;
    }
    /*function getFrom($mail){
        echo $mail;
    }*/
    /**
     * メール受信時の処理
     * 
     * @access public
     * @author sakuragawa
     */
    /*function execute(){
        $this->hogeo();
    }*/
    
    /*function hogeo(){
        echo "ue";
    }*/
}