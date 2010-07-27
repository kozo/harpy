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
    
    // 処理エンコード
    var $encode = '';
    
    // Mail_mimeDecodeのデコードの設定
    var $mailMimeDecodeParams = array(
        'include_bodies' => true,
        'decode_bodies' => true,
        'decode_headers' => true,
        );
    
    function startup() {
        parent::startup();
        
        // 文字コード指定
        $this->encode = Configure::read('App.encoding');
    }

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
        
        // 前処理
        $this->beforefilter();
        
        // メールの読み込み
        $source = file_get_contents("php://stdin");
        if(empty($source)){
            return ;
        }
        // \nをつけてるのは、そのままだとSoftbankの空メールが取得できなかったから
        $source .= "\n";
        $this->hookMail($source);
        
        // メールをデコード
        //$this->out($this->params2);
        $Decoder = new Mail_mimeDecode($source);
        $mail = $Decoder->decode($this->mailMimeDecodeParams);
        
        // Fromを取得
        $from = $this->_parseAddress($mail, 'from');
        $this->hookFromAddress($from);
        // Toを取得
        $to = $this->_parseAddress($mail, 'to');
        $this->hookToAddress($to);
        
        // 本文・添付等をパース
        $this->_parseBody($mail);
        
        $this->afterfilter();
        
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
        $from = mb_convert_encoding($mail->headers[$type], $this->encode, $charset);
        preg_match($this->email_regix, $from, $match);
        if (!empty($match[1])) {
            $from = $match[1];
        }
        
        $from = trim($from, '"');
        
        return $from;
    }
    
    function _parseBody($mail) {
        if (strtolower($mail->ctype_primary) == "multipart") {
            //複数本文があるメール（本文を１件づつ処理する）
            foreach ($mail->parts as $part) {
                //タイプ
                if (@$part->disposition=="attachment") {
                    //添付ファイル
                    $type = strtolower($part->ctype_primary)."/".strtolower($part->ctype_secondary);
                    $name = $this->_getFileName($part);
                    $this->hookAttachment($part->body, $name, $type);
                } else {
                    switch (strtolower($part->ctype_primary)) {
                        case "image": //HTML本文中の画像                            
                            $type = strtolower($part->ctype_primary)."/".strtolower($part->ctype_secondary);
                            $name = $this->_getFileName($part);
                            $this->hookAttachment($part->body, $name, $type);
                            break;
                        case "text": //テキスト本文の抽出
                            /*if ($part->ctype_secondary=="plain") {
                                $ary['body'] = trim(mb_convert_encoding($part->body, mb_internal_encoding(), mb_detect_order()));
                            } else { //HTML本文
                                $ary['body'] = trim(mb_convert_encoding($part->body, mb_internal_encoding(), mb_detect_order()));
                            }*/
                            $body = trim(mb_convert_encoding($part->body, $this->encode, mb_detect_order()));
                            $this->hookBody($body);
                            break;
                        case "multipart": //マルチパートの中にマルチパートがある場合（MS-OutlookExpressからHTML形式で送信した場合）
                            // 動くのか未確認
                            $this->_parseBody($part);
                            break;
                    }
                }
            }
        } elseif (strtolower($mail->ctype_primary) == "text") {
            //テキスト本文のみのメール            
            $body = trim(mb_convert_encoding($mail->body, $this->encode, mb_detect_order()));
            $this->hookBody($body);
        }

        return ;
    }
    
    /**
     * ファイル名を取得する
     * 
     * @access private
     * @author sakuragawa
     */
    private function _getFileName($part){
        if(empty($part->ctype_parameters['name'])){
            return "";
        }
        
        // 日本語ファイル名に対応するために変換をかける
        $fileName = urldecode($part->ctype_parameters['name']);
        $enc = $this->_getCharset($fileName);
        $fileName = mb_convert_encoding($fileName, $this->encode, $enc);
        return $fileName;
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
    function beforefilter(){
    }
    function afterfilter(){
    }
    function hookMail($mail){
    }
    function hookFromAddress($from){
    }
    function hookToAddress($to){
    }
    function hookBody($body){
    }
    function hookAttachment($attachment, $name, $type){
    }
}