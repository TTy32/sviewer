<?php
/*
description : Display Airtight AutoViewer
author      : Ikuo Obataya
email       : I.Obataya@gmail.com
lastupdate  : 2008-07-03
depends     : cache (2008-03-22 or later)
license     : GPL 2 (http://www.gnu.org/licenses/gpl.html)
*/

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
  require_once(DOKU_INC.'inc/init.php');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
  require_once(DOKU_PLUGIN.'syntax.php');
if (!class_exists('JpegMeta')) @require(DOKU_INC.'inc/JpegMeta.php');

if(!file_exists(DOKU_PLUGIN.'cache/plugin_cache.php')){
  echo '<b>AViewer plugin requires <a href="http://wiki.symplus.co.jp/computer/en/cache_plugin" target="_blank">cache plugin.</b>';
  exit;
}
if (!class_exists('plugin_cache')) @require(DOKU_PLUGIN.'cache/plugin_cache.php');

class syntax_plugin_aviewer extends DokuWiki_Syntax_Plugin {
  var $xmlCache;
  var $attrPattern;
  var $listPattern;
  var $swfLoc;
  var $swfObjPath;
  // Constructor
  function syntax_plugin_aviewer(){
    $this->xmlCache    = new plugin_cache("aviewer",'',"xml");
    $this->attrPattern = '/(\d+) (\d+)( left| right| noalign)?>|(clear_cache)>|(remove_dir)>/';
    
    $this->listPattern = '/\{\{([^}|]+)\|?([^}]*)\}\}/';
    $this->swfLoc      = DOKU_BASE.'lib/plugins/aviewer/autoviewer/autoviewer.swf';
    $this->swfJsPath   = DOKU_BASE.'lib/plugins/aviewer/autoviewer/swfobject.js';
  }
  function getInfo(){
    return array(
      'author' => 'Ikuo Obataya',
      'email'  => 'I.Obataya@gmail.com',
      'date'  => '2008-07-03',
      'name'  => 'Airtight AutoViewer plugin',
      'desc'  => 'Create AutoViewer by www.airtightinteractive.com
      <aviewer>
      image files or media namespace
      </aviewer>',
      'url'  => 'http://wiki.symplus.co.jp/computer/en/aviewer_plugin',
    );
  }
  function getType(){  return 'protected';  }
  function getSort(){  return 917;  }
  // Entry
  function connectTo($mode) {
    $this->Lexer->addEntryPattern('<aviewer(?=.*?>.*?</aviewer>)',$mode,'plugin_aviewer');
  }
  // Exit
  function postConnect() {
    $this->Lexer->addExitPattern('</aviewer>','plugin_aviewer');
  }
  // Handler
  function handle($match, $state, $pos) {
    global $ID;
    global $conf;
    switch ($state) {
      case DOKU_LEXER_UNMATCHED :
        $m = preg_match_all($this->attrPattern,$match,$cmd);
        
        if ($m!=1){
          $width  = $this->getConf('width');
          $height = $this->getConf('height');
        }else{
          // extra commands
          if ($cmd[3][0]=='clear_cache'){$this->xmlCache->ClearCache();return array($state,'');}
          if ($cmd[4][0]=='remove_dir') {$this->xmlCache->RemoveDir(); return array($state,'');}
          
          // width/height/alignment
          $width  = $cmd[1][0];
          $height = $cmd[2][0];
          $align  = $cmd[3][0];
        }
        if(empty($align)) $align = $this->getConf('align');
        
        $sz = preg_match_all($this->listPattern,$match,$img);
        if ($sz==0){
          $img = array();
          $img[1][0] = getNS($ID);
          $img[2][0] = $ID;
          $sz = 1;
        }
        
        $xml.=sprintf('<?xml version="1.0" encoding="UTF-8"?>
                       <gallery frameColor="%s" frameWidth="%d" imagePadding="%d" displayTime="%d" enableRightClickOpen="%s">',
                       $this->getConf('frameColor'),
                       $this->getConf('frameWidth'),
                       $this->getConf('imagePadding'),
                       $this->getConf('displayTime'),
                       $this->getConf('enableRightClickOpen')?'true':'false').NL;

        for($i=0;$i<$sz;$i++){

          // build filepaths from an input line
          $mediaID   = (substr($mediaID,0,1)==':')?substr($img[1][$i],1):$img[1][$i];
          $path      = mediaFN($mediaID);
          $mlink     = ml($mediaID,'',true,'',true);
          $caption   = $img[2][$i];
          $paths     = array();
          $urls      = array();

          if(is_dir($path)){
            // get file paths from a namespace
            $d = $conf['useslash']?'/':':';
            $dir_handle = @opendir($path);
            $caption = '(in '.$mediaID.')';
            while(($f = readdir($dir_handle))!==false){
              $p = $path.'/'.$f;
              if ($f=='.'||$f=='..'||is_dir($p)) continue;
              $paths[] = $p;
              $urls[]  = $mlink.$d.$f;
            }
            @closedir($dir_handle);
          }else{
            // get file path
            $paths[] = $path;
            $urls[]  = $mlink;
          }

          $fsz = count($paths);
          for($j=0;$j<$fsz;$j++){
            $path = $paths[$j];
            $url = $urls[$j];
            $title = (empty($caption))?$path:$caption;
            if(!file_exists($path))continue;
            $jm      = new JpegMeta($path);
            $f       = @$jm->getResizeRatio($width,$height);
            $info    = @$jm->getBasicInfo();
            $rwidth  = floor($info['Width']*$f);
            $rheight = floor($info['Height']*$f);
            $xml.='<image>'.NL;
            $xml.='  <url>'.$url.'</url>'.NL;
            $xml.='  <caption>'.$title.'</caption>'.NL;
            $xml.='  <width>'.$rwidth.'</width>'.NL;
            $xml.='  <height>'.$rheight.'</height>'.NL;
            $xml.='</image>'.NL;
          }
        }
        $xml.='</gallery>'.NL;
        return array($state, array($xml,$width,$height,$align));

      case DOKU_LEXER_ENTER :return array($state,$match);
      case DOKU_LEXER_EXIT  :return array($state, '');
    }
  }
  // Render
  function render($mode, &$renderer, $data){
    if ($mode!='xhtml') return false;
    global $conf;
    list($state, $match) = $data;
    switch ($state) {
      case DOKU_LEXER_ENTER: break;
      case DOKU_LEXER_UNMATCHED:
        if (empty($match))
          return;

        list($xml,$width,$height,$align)=$match;
        $hash = md5(serialize($match));
        $savePath = $this->xmlCache->GetMediaPath($hash);
        if (!file_exists($savePath)){
          if(io_saveFile($savePath, $xml)){
            chmod($savePath,$conf['fmode']);
          }
        }
        $fetchPath = ml('aviewer:'.$hash.'.xml','',true,'',true);
        $renderer->doc.=sprintf('<div class="aviewer"><div class="%s">
                                 <div id="flashcontent">AutoViewer requires JavaScript and the Flash Player. <a href="http://www.macromedia.com/go/getflashplayer/">Get Flash here.</a></div>
                                 <script type="text/javascript" src="%s"></script>
                                 <script type="text/javascript">
                                   var fo = new SWFObject("%s", "autoviewer", "%s", "%s", "8", "%s");
                                   fo.addVariable("xmlURL","%s");
                                   fo.write("flashcontent");
                                 </script>
                                 </div></div>',
                                $align,
                                $this->swfJsPath,
                                $this->swfLoc,
                                $width,
                                $height,
                                $this->getConf('bgcolor'),
                                $fetchPath);
        break;
      case DOKU_LEXER_EXIT: break;
    }
    return true;
  }
 /**
  * Build <image> element by fetch url and caption
  */
  function getImageElement($url,$caption){
    $path = $paths[$j];
    $url = $urls[$j];
    $title = (empty($caption))?$path:$caption;
    if(!file_exists($path))continue;
    $jm      = new JpegMeta($path);
    $f       = @$jm->getResizeRatio($width,$height);
    $info    = @$jm->getBasicInfo();
    $rwidth  = floor($info['Width']*$f);
    $rheight = floor($info['Height']*$f);
    $xml.='<image>'.NL;
    $xml.='  <url>'.$url.'</url>'.NL;
    $xml.='  <caption>'.$title.'</caption>'.NL;
    $xml.='  <width>'.$rwidth.'</width>'.NL;
    $xml.='  <height>'.$rheight.'</height>'.NL;
    $xml.='</image>'.NL;
    return $xml;
  }
 /**
  * print debug info
  */
  function _aviewer_debug($msg){
    global $conf;
    if($conf['allowdebug']!=0){
      echo '<!-- aviewer plugin debug:'.$msg.'-->'.NL;
    }
  }
}
?>