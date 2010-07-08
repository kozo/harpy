<?php

App::import('Vendor', 'pear_path');
App::import('Vendor', 'Mail_mimeDecode', array('file'=>'Mail_mimeDecode/mimeDecode.php'));

require_once(APP . 'config/bootstrap.php');

// 参考サイト
// http://www.hand-in-hand.biz/c-board/c-board.cgi?cmd=ntr;tree=28;id=0002

class HarpyTask extends Shell{
    var $uses = array();

    // charsetの順番
    var $charset_order = 'ISO-2022-JP, SJIS, EUC-JP, UTF-8';
    // デフォルトのcharset
    var $default_charset = 'ISO-2022-JP';
    // メールアドレス用の正規表現
    var $email_regix = "/([_\w\.\-\"]+@[_0-9a-zA-Z\.\-]+\.[a-zA-Z]+)/";
    
    // Mail_mimeDecodeのデコードの設定
    var $params = array(
            'include_bodies' => true,
            'decode_bodies' => true,
            'decode_headers' => true,
            );
    

    function _welcome(){
        //$this->Dispatch->clear();
        /*$this->out();
        $this->out('Welcome to CakePHP v' . Configure::version() . ' Console');
        $this->hr();
        $this->out('App : '. $this->params['app']);
        $this->out('Path: '. $this->params['working']);
        $this->hr();*/
    }
    
    
    /**
     * メール受信時の処理
     * 
     * @access public
     * @author sakuragawa
     */
    function execute(){
        ini_set('memory_limit',-1);
        
        // メールの読み込み
        $source = file_get_contents("php://stdin");
        if(empty($source)){
            return ;
        }
        // \nをつけてるのは、そのままだとSoftbankの空メールが取得できなかったから
        $source .= "\n";
        $this->hookMail($source);
        
        // メールをデコード
        $Decoder = new Mail_mimeDecode($source);
        $mail = $Decoder->decode($this->params);
        
        // Fromを取得
        $from = $this->_parseAddress($mail, 'from');
        $this->hookFromAddress($from);
        // Toを取得
        $to = $this->_parseAddress($mail, 'to');
        $this->hookToAddress($to);
        
        // Toを取得
        /*$to = $this->_getTo($mail);
        $this->getTo($to);*/
    }
    
    /**
     * FROMのアドレスを取得
     * 
     * @access private
     * @author sakuragawa
     */
    private function _parseAddress($mail, $type){        
        // 文字コードを判定
        $charset = $this->_getCharset($mail->headers[$type]);
        
        $match = array();
        $from = mb_convert_encoding($mail->headers[$type], mb_internal_encoding(), $charset);
        preg_match($this->email_regix, $from, $match);
        if (!empty($match[1])) {
            $from = $match[1];
        }
        
        $from = trim($from, '"');
        
        return $from;
    }
    
    /**
     * エンコードチェック
     * ヘッダでのSJIS使用は、スパムっぽいのでチェック
     *
     * @param string $string
     * @return string charset
     */
    private function _getCharset($string)
    {
        mb_detect_order($this->charset_order);
        $charset = mb_detect_encoding($string);
        if ($this->default_charset === $charset) {
            return $this->default_charset;
        } else {
            return 'auto';
        }
    }
    
    
    //--------------------------------------------------------------------
    // Hook用メソッド一覧
    //--------------------------------------------------------------------
    function hookMail($mail){
    }
    function hookFromAddress($mail){
    }
    function hookToAddress($mail){
    }
}