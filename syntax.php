<?php
/*
description : Display Airtight Simpleviewer
author      : TTy32 (Original by Ikuo Obataya)
email       : randy@tty32.org
lastupdate  : 2011-04-15
depends     : cache (2008-03-22 or later)
license     : GPL 2 (http://www.gnu.org/licenses/gpl.html)
*/

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
  require_once(DOKU_INC.'inc/init.php');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/'); 
  require_once(DOKU_PLUGIN.'syntax.php');
if (!class_exists('JpegMeta')) @require(DOKU_INC.'inc/JpegMeta.php');

if(!file_exists(DOKU_PLUGIN.'cache/plugin_cache.php')){
  echo '<b>sviewer plugin requires <a href="http://www.dokuwiki.org/plugin:cache" target="_blank">cache plugin.</b>';
  exit;
}
if (!class_exists('plugin_cache')) @require(DOKU_PLUGIN.'cache/plugin_cache.php');

class syntax_plugin_sviewer extends DokuWiki_Syntax_Plugin {
  var $xmlCache;
  var $attrPattern;
  var $listPattern;
  var $swfLoc;
  var $swfObjPath;
  // Constructor
  function syntax_plugin_sviewer(){
    $this->xmlCache    = new plugin_cache("sviewer",'',"xml");
    
	//By TTy32
	
	$this->attrPattern = '/(\d+) (\d+) (\w+) (\w+) (\w+) (\w+) (\d*) (\w+) (\d*) (\d*) (\w+) (\w+) (\d+) (\d+) (\d*) "([^"]*)"?>|(clear_cache)>|(remove_dir)>/';
    
    $this->listPattern = '/\{\{([^}|]+)\|?([^}]*)\}\}/';
    $this->swfLoc      = DOKU_BASE.'lib/plugins/sviewer/simpleviewer/simpleviewer.swf';
    $this->swfJsPath   = DOKU_BASE.'lib/plugins/sviewer/simpleviewer/simpleviewer.js';
  }
  function getInfo(){
    return array(
      'author' => 'TTy32 (Original by Ikuo Obataya)',
      'email'  => 'randy@tty32.org',
      'date'  => '2011-04-15',
      'name'  => 'Airtight Simpleviewer plugin',
      'desc'  => 'Create Simpleviewer by www.airtightinteractive.com
      <sviewer>
      image files or media namespace
      </sviewer>',
      'url'  => 'http://tty32.org',
    );
  }
  function getType(){  return 'protected';  }
  function getSort(){  return 917;  }
  // Entry
  function connectTo($mode) {
    $this->Lexer->addEntryPattern('<sviewer(?=.*?>.*?</sviewer>)',$mode,'plugin_sviewer');
  }
  // Exit
  function postConnect() {
    $this->Lexer->addExitPattern('</sviewer>','plugin_sviewer');
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
          // extra commands - Mod By TTy32
          if (strtolower($cmd[17][0])=='clear_cache'){$this->xmlCache->ClearCache();return array($state,'');}
          if (strtolower($cmd[18][0])=='remove_dir') {$this->xmlCache->RemoveDir(); return array($state,'');}
          
          // width/height/alignment
          $width  = $cmd[1][0];
          $height = $cmd[2][0];
          $align  = $cmd[3][0];
		  
		  // By TTy32
		  $option_galleryStyle = strtoupper($cmd[4][0]);
		  $option_textColor = $cmd[5][0];
		  $option_frameColor = $cmd[6][0];
		  $option_frameWidth = $cmd[7][0];
		  $option_thumbPosition = strtoupper($cmd[8][0]);
		  $option_thumbColumns = $cmd[9][0];
		  $option_thumbRows = $cmd[10][0];
		  $option_showOpenButton = strtoupper($cmd[11][0]);
		  $option_showFullscreenButton = strtoupper($cmd[12][0]);
		  $option_maxImageWidth = $cmd[13][0];
		  $option_maxImageHeight = $cmd[14][0];
		  $option_captionMode = $cmd[15][0];
		  $option_title = $cmd[16][0];
        }
        if(empty($align)) $align = $this->getConf('align');

		// By TTy32
		if(empty($option_galleryStyle)) $option_galleryStyle = $this->getConf('galleryStyle');
		if(empty($option_textColor)) $option_textColor = $this->getConf('textColor');
		if(empty($option_frameColor)) $option_frameColor = $this->getConf('frameColor');
		if(empty($option_frameWidth)) $option_frameWidth = $this->getConf('frameWidth');
		if(empty($option_thumbPosition)) $option_thumbPosition = $this->getConf('thumbPosition');
		if(empty($option_thumbColumns)) $option_thumbColumns = $this->getConf('thumbColumns');
		if(empty($option_thumbRows)) $option_thumbRows = $this->getConf('thumbRows');
		if(empty($option_showOpenButton)) $option_showOpenButton = $this->getConf('showOpenButton');
		if(empty($option_showFullscreenButton)) $option_showFullscreenButton = $this->getConf('showFullscreenButton');
		if(empty($option_maxImageWidth)) $option_maxImageWidth = $this->getConf('maxImageWidth');
		if(empty($option_maxImageHeight)) $option_maxImageHeight = $this->getConf('maxImageHeight');
		if(empty($option_captionMode)) $option_captionMode = $this->getConf('captionMode');
		if(empty($option_title)) $option_title = $this->getConf('title');
		
		
        $sz = preg_match_all($this->listPattern,$match,$img);
        if ($sz==0){
          $img = array();
          $img[1][0] = getNS($ID);
          $img[2][0] = $ID;
          $sz = 1;
        }
		
			// By TTy32
			$xml.=sprintf('<?xml version="1.0" encoding="UTF-8"?>

			<simpleviewergallery
			
			galleryStyle="%s"
			title="%s"
			textColor="%s"
			frameColor="%s"
			frameWidth="%s"
			thumbPosition="%s"
			thumbColumns="%s"
			thumbRows="%s"
			showOpenButton="%s"
			showFullscreenButton="%s"	
			maxImageWidth="%s"
			maxImageHeight="%s"
			useFlickr="false"
			flickrUserName=""
			flickrTags=""
			languageCode="AUTO"
			languageList=""		
			imagePath="images/"
			thumbPath="thumbs/"
			
			>',

			$option_galleryStyle,
			$option_title,
			$option_textColor,
			$option_frameColor,
			$option_frameWidth,
			$option_thumbPosition,
			$option_thumbColumns,
			$option_thumbRows,
			$option_showOpenButton,
			$option_showFullscreenButton,
			$option_maxImageWidth,
			$option_maxImageHeight);
		
		
		
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
			/* By SuicideFunky >> */
			$caption2 = "";
			$filename = substr($path, strrpos($path, "/")+1, 99); //Filename with extension - By SuicideFunky
			$filename2 = substr($filename, 1,strrpos($filename, ".")-1); //Filename without extension - By SuicideFunky
				if ($option_captionMode == 0) { 
					$caption2 = "";
				} else if ($option_captionMode == 1) { 
					$caption2 = $filename;
				} else {
					$caption2 = $filename2;
				}
			/* end */
            $url = $urls[$j];
            $title = (empty($caption))?$path:$caption;
            if(!file_exists($path))continue;
            $jm      = new JpegMeta($path);
            $f       = @$jm->getResizeRatio($width,$height);
            $info    = @$jm->getBasicInfo();
            $rwidth  = floor($info['Width']*$f);
            $rheight = floor($info['Height']*$f);
            
			// By TTy32
			$xml.='<image '.NL;
            $xml.='imageURL="'.$url.'" ';
			$xml.='thumbURL="'.$url.'" ';
			$xml.='linkURL="" ';
			$xml.='linkTarget="" >'.NL;
            $xml.='  <caption>'.$caption2.'</caption>'.NL;
            $xml.='</image>'.NL;
			
          }
        }
        $xml.='</simpleviewergallery>'.NL; //By TTy32
        return array($state, array($xml,$width,$height,$align));

      case DOKU_LEXER_ENTER :return array($state,$match);
      case DOKU_LEXER_EXIT  :return array($state, '');
    }
  }
  // Render
  function render($mode, Doku_Renderer $renderer, $data){
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
        $fetchPath = ml('sviewer:'.$hash.'.xml','',true,'',true);
        
		// By TTy32
		$renderer->doc.=sprintf('<div class="sviewer">								

			<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0" width="%s" height="%s" id="header1" align="%s">
			<param name="allowScriptAccess" value="sameDomain" />
			<param name="allowFullScreen" value="true" />
			
			<param name="movie" value="%s?galleryURL=%s" /><param name="menu" value="false" /><param name="quality" value="best" /><param name="scale" value="exactfit" /><param name="salign" value="t" /><param name="wmode" value="transparent" /><param name="bgcolor" value="" />	<embed src="%s?galleryURL=%s" menu="false" quality="best" scale="exactfit" salign="t" wmode="transparent" bgcolor="" width="%s" height="%s" name="header1" align="%s" allowScriptAccess="sameDomain" allowFullScreen="true" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" />
			</object>

			</div>',
			
			$width,
			$height,
			$align,
			$this->swfLoc,
			$fetchPath,
			$this->swfLoc,
			$fetchPath,
			$width,
			$height,
			$align);
		
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
	
	// By TTy32
	$xml.='<image '.NL;
	$xml.='imageURL="'.$url.'" ';
	$xml.='thumbURL="'.$url.'" ';
	$xml.='linkURL="" ';
	$xml.='linkTarget="" >'.NL;
	$xml.='  <caption>'.$title.'</caption>'.NL;
	$xml.='</image>'.NL;
    return $xml;
  }

 /**
  * print debug info
  */
  function _sviewer_debug($msg){
    global $conf;
    if($conf['allowdebug']!=0){
      echo '<!-- sviewer plugin debug:'.$msg.'-->'.NL;
    }
  }
}
?>