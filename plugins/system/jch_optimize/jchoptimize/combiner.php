<?php

use JchOptimize\JS_Optimize;
use JchOptimize\CSS_Optimize;

/**
 * JCH Optimize - Joomla! plugin to aggregate and minify external resources for
 * optmized downloads
 * @author Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2014 Samuel Marshall
 * @license GNU/GPLv3, See LICENSE file
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */
defined('_JCH_EXEC') or die('Restricted access');

class JchOptimizeCombinerBase
{

        /**
         * 
         * @param type $sPath
         * @return boolean
         */
        protected function loadAsync()
        {
                return false;
        }

        /**
         * 
         * @param type $sContent
         * @return type
         */
        protected function replaceImports($sContent)
        {
                return $sContent;
        }

}

/**
 * 
 * 
 */
class JchOptimizeCombiner extends JchOptimizeCombinerBase
{

        public $params            = NULL;
        public $sLnEnd            = '';
        public $sTab              = '';
        public $bBackend          = FALSE;
        public static $bLogErrors = FALSE;
        public $css               = '';
        public $js                = '';
        public $oCssParser;

        /**
         * Constructor
         * 
         */
        public function __construct($params, $bBackend = FALSE)
        {
                $this->params   = $params;
                $this->bBackend = $bBackend;

                $this->sLnEnd = JchPlatformUtility::lnEnd();
                $this->sTab   = JchPlatformUtility::tab();

                $this->oCssParser = new JchOptimizeCssParser($params, $bBackend);

                self::$bLogErrors = $this->params->get('jsmin_log', 0) ? TRUE : FALSE;
        }

        /**
         * 
         * @return type
         */
        public function getLogParam()
        {
                if (self::$bLogErrors == '')
                {
                        self::$bLogErrors = $this->params->get('log', 0);
                }

                return self::$bLogErrors;
        }

        /**
         * Get aggregated and possibly minified content from js and css files
         *
         * @param array $aUrlArray      Array of urls of css or js files for aggregation
         * @param string $sType         css or js
         * @return string               Aggregated (and possibly minified) contents of files
         */
        public function getContents($aUrlArray, $sType, $oParser)
        {
                JCH_DEBUG ? JchPlatformProfiler::start('GetContents - ' . $sType, TRUE) : null;                
                
                $oCssParser   = $this->oCssParser;
                $sCriticalCss = '';
                $aSpriteCss   = array();
                $aFontFace    = array();

                $aContentsArray = array();

                foreach ($aUrlArray as $index => $aUrlInnerArray)
                {
                        $sContents = $this->combineFiles($aUrlInnerArray, $sType, $oCssParser);
                        $sContents = $this->prepareContents($sContents);

                        $aContentsArray[$index] = $sContents;
                }

                if ($sType == 'css')
                {
                        if ($this->params->get('csg_enable', 0))
                        {
                                try
                                {
                                        $oSpriteGenerator = new JchOptimizeSpriteGenerator($this->params);
                                        $aSpriteCss       = $oSpriteGenerator->getSprite($this->$sType);
                                }
                                catch (Exception $ex)
                                {
                                        JchOptimizeLogger::log($ex->getMessage(), $this->params);
                                        $aSpriteCss = array();
                                }
                        }

                        if ($this->params->get('pro_optimizeCssDelivery', '0'))
                        {
                                if (is_array($aSpriteCss) && !empty($aSpriteCss) && isset($aSpriteCss['needles']) && isset($aSpriteCss['replacements']))
                                {
                                        $this->$sType = str_replace($aSpriteCss['needles'], $aSpriteCss['replacements'], $this->$sType);
                                }

                                $oParser->params->set('pro_InlineScripts', '1');
                                $oParser->params->set('pro_InlineStyles', '1');

                                $sHtml = $oParser->cleanHtml();

                                $aCssContents = $oCssParser->optimizeCssDelivery($this->$sType, $sHtml);
                                $sCriticalCss .= $oCssParser->sortImports($aCssContents['criticalcss']);
                                $sCriticalCss = $sCriticalCss;
                                $aFontFace    = preg_split('#}\K[^@]*+#', $aCssContents['font-face'], -1, PREG_SPLIT_NO_EMPTY);
                        }
                }

                try
                {
                        $oAdmin = new JchOptimizeAdmin($this->params);
                        $oAdmin->getAdminLinks($oParser, JchPlatformUtility::menuId(), $this->css);
                }
                catch (Exception $ex)
                {
                        JchOptimizeLogger::log($ex->getMessage(), $this->params);
                }

                $aContents = array(
                        'filemtime'   => JchPlatformUtility::unixCurrentDate(),
                        'file'        => $aContentsArray,
                        'criticalcss' => $sCriticalCss,
                        'spritecss'   => $aSpriteCss,
                        'font-face'   => $aFontFace
                );

                JCH_DEBUG ? JchPlatformProfiler::stop('GetContents - ' . $sType) : null; 
                
                return $aContents;
        }

        /**
         * Aggregate contents of CSS and JS files
         *
         * @param array $aUrlArray      Array of links of files
         * @param string $sType          CSS or js
         * @return string               Aggregarted contents
         * @throws Exception
         */
        public function combineFiles($aUrlArray, $sType, $oCssParser)
        {
                $sContents = '';
                $sLifetime = (int) $this->params->get('lifetime', '30') * 24 * 60 * 60;

                $this->bAsync    = FALSE;
                $this->sAsyncUrl = '';

                $oFileRetriever = JchOptimizeFileRetriever::getInstance();

                foreach ($aUrlArray as $aUrl)
                {
                        $sUrl = $this->prepareFileUrl($aUrl, $sType);
                        
                        JCH_DEBUG ? JchPlatformProfiler::start('CombineFile - ' . $sUrl) : null;
                        
                        if ($sType == 'js')
                        {
                                $sJsContents = $this->handleAsyncUrls($aUrl);

                                if ($sJsContents === FALSE)
                                {
                                        continue;
                                }

                                $sContents .= $sJsContents;
                                unset($sJsContents);
                        }

                        if (isset($aUrl['id']) && $aUrl['id'] != '')
                        {
                                $function = array($this, 'cacheContent');
                                $args     = array($aUrl, $sType, $oFileRetriever, $oCssParser, TRUE);

                                $sCachedContent = JchPlatformCache::getCallbackCache($aUrl['id'], $sLifetime, $function, $args);

                                $this->$sType .= $sCachedContent;

                                if (!isset($aUrl['url']) && $this->sAsyncUrl != '' && $sType == 'js')
                                {
                                        $this->sAsyncUrl = '';
                                }

                                if ($this->sAsyncUrl == '')
                                {
                                        $sContents .= $this->addCommentedUrl($sType, $aUrl) . '[[JCH_' . $aUrl['id'] . ']]' .
                                                $this->sLnEnd . 'DELIMITER';
                                }
                        }
                        else
                        {
                                $sContent = $this->cacheContent($aUrl, $sType, $oFileRetriever, $oCssParser, FALSE);
                                $sContents .= $this->addCommentedUrl($sType, $aUrl) . $sContent . '|"LINE_END"|';
                        }
                        
                        JCH_DEBUG ? JchPlatformProfiler::stop('CombineFile - ' . $sUrl, TRUE) : null;
                }

                if ($this->bAsync)
                {
                        $sContents = $this->getLoadScript() . $sContents;

                        if ($this->sAsyncUrl != '')
                        {
                                $sContents .= $this->addCommentedUrl('js', $this->sAsyncUrl)
                                        . 'loadScript("' . $this->sAsyncUrl . '", function(){});  DELIMITER';
                                $this->sAsyncUrl = '';
                        }
                }

                return $sContents;
        }

        /**
         * 
         * @param type $aUrl
         * @param type $sType
         * @param type $oFileRetriever
         * @return type
         * @throws Exception
         */
        public function cacheContent($aUrl, $sType, $oFileRetriever, $oCssParser, $bPrepare)
        {
                $sContent = '';

                if (isset($aUrl['url']))
                {
                        $sPath = JchOptimizeHelper::getFilePath($aUrl['url']);
                        $sContent .= $oFileRetriever->getFileContents($sPath);
                }
                else
                {
                        if ($this->sAsyncUrl == '')
                        {
                                $sContent .= $aUrl['content'];
                        }
                }

                if ($sType == 'css')
                {
                        $sContent = $oCssParser->addRightBrace($sContent);

                        $oCssParser->aUrl = $aUrl;

                        $sImportContent = preg_replace('#@import\s(?:url\()?[\'"]([^\'"]+)[\'"](?:\))?#', '@import url($1)', $sContent);

                        if (is_null($sImportContent))
                        {
                                JchOptimizeLogger::log(sprintf(JchPlatformUtility::translate('Error occured trying to parse for @imports in %s'),
                                                                                             $aUrl['url']), $this->params);

                                $sImportContent = $sContent;
                        }

                        $sContent = $sImportContent;
                        unset($sImportContent);
                        
                        $sContent = $oCssParser->correctUrl($sContent, $aUrl);
                        $sContent = $this->replaceImports($sContent, $aUrl);
                        $sContent = $oCssParser->handleMediaQueries($sContent, $aUrl['media']);

                        if (function_exists('mb_convert_encoding'))
                        {
                                $sContent = mb_convert_encoding($sContent, 'utf-8');
                        }
                }

                if ($sType == 'js' && trim($sContent) != '')
                {
                        if ($this->params->get('try_catch', '1'))
                        {
                                $sContent = $this->addErrorHandler($sContent, $aUrl);
                        }
                        else
                        {
                                $sContent = $this->addSemiColon($sContent, $aUrl);
                        }
                }

                if ($bPrepare)
                {
                        $sContent = $this->minifyContent($sContent, $sType, $aUrl);
                        $sContent = $this->prepareContents($sContent);
                }

                return $sContent;
        }

        /**
         * 
         * @param type $sUrl
         */
        protected function handleAsyncUrls($aUrl)
        {
                $sJsContents = '';

                if (isset($aUrl['url']))
                {
                        if ($this->sAsyncUrl != '')
                        {
                                $sJsContents     = $this->addCommentedUrl('js', $this->sAsyncUrl) .
                                        'loadScript("' . $this->sAsyncUrl . '", function(){});  DELIMITER';
                                $this->sAsyncUrl = '';
                        }

                        if ($this->loadAsync($aUrl['url']))
                        {
                                $this->sAsyncUrl = $aUrl['url'];
                                $this->bAsync    = TRUE;

                                if ($sJsContents == '')
                                {
                                        return FALSE;
                                }
                        }
                }
                else
                {
                        if ($this->sAsyncUrl != '')
                        {
                                $sAsyncContent = $this->addCommentedUrl('js', $this->sAsyncUrl) .
                                        'loadScript("' . $this->sAsyncUrl . '", function(){' . $this->sLnEnd . $aUrl['content'] . $this->sLnEnd . '});  
                                                DELIMITER';

                                $sJsContents = $this->sLnEnd . $this->minifyContent($sAsyncContent, 'js', $aUrl);
                        }
                }

                return $sJsContents;
        }

        /**
         * 
         * @param type $sContent
         * @param type $sUrl
         */
        protected function minifyContent($sContent, $sType, $aUrl)
        {
                if ($this->params->get($sType . '_minify', 0) && preg_match('#\s++#', trim($sContent)))
                {
                        $sUrl = $this->prepareFileUrl($aUrl, $sType);

                        $sMinifiedContent = trim($sType == 'css' ? CSS_Optimize::optimize($sContent) : JS_Optimize::optimize($sContent));

                        if (is_null($sMinifiedContent) || $sMinifiedContent == '')
                        {
                                JchOptimizeLogger::log(sprintf(JchPlatformUtility::translate('Error occurred trying to minify: %s'), $aUrl['url']),
                                                                                             $this->params);
                                $sMinifiedContent = $sContent;
                        }

                        return $sMinifiedContent;
                }

                return $sContent;
        }
        
        /**
         * 
         * @param type $aUrl
         * @param type $sType
         * @return type
         */
        public function prepareFileUrl($aUrl, $sType)
        {
                $sUrl = isset($aUrl['url']) ? 
                        JchOptimizeAdmin::prepareFileValues($aUrl['url'], '', 40) : 
                                ($sType == 'css' ? 'Style' : 'Script') . ' Declaration';
                
                return $sUrl;
        }

        /**
         * 
         * @param type $sType
         * @param type $sUrl
         * @return string
         */
        protected function addCommentedUrl($sType, $sUrl)
        {
                $sComment = '';

                if ($this->params->get('debug', '1'))
                {
                        if (is_array($sUrl))
                        {
                                $sUrl = isset($sUrl['url']) ? $sUrl['url'] : (($sType == 'js' ? 'script' : 'style') . ' declaration');
                        }

                        $sComment = '|"COMMENT_START ' . $sUrl . ' COMMENT_END"|';
                }

                return $sComment;
        }

        /**
         * Add semi-colon to end of js files if non exists;
         * 
         * @param string $sContent
         * @return string
         */
        public function addErrorHandler($sContent, $aUrl)
        {
                if (trim($sContent) != '')
                {
                        $sContent = 'try {' . $this->sLnEnd . $sContent . $this->sLnEnd . '} catch (e) {' . $this->sLnEnd;
                        $sContent .= 'console.error(\'Error in ';
                        $sContent .= isset($aUrl['url']) ? 'file:' . $aUrl['url'] : 'script declaration';
                        $sContent .= '; Error:\' + e.message);' . $this->sLnEnd . '};';
                }

                return $sContent;
        }

        /**
         * Add semi-colon to end of js files if non exists;
         * 
         * @param string $sContent
         * @return string
         */
        public function addSemiColon($sContent)
        {
                $sContent = rtrim($sContent);

                if (substr($sContent, -1) != ';' && !preg_match('#\|"COMMENT_START File[^"]+not found COMMENT_END"\|#', $sContent))
                {
                        $sContent = $sContent . ';';
                }

                return $sContent;
        }

        /**
         * Remove placeholders from aggregated file for caching
         * 
         * @param string $sContents       Aggregated file contents
         * @param string $sType           js or css
         * @return string
         */
        public function prepareContents($sContents, $test=FALSE)
        {
                $sContents = str_replace(
                        array(
                        '|"COMMENT_START',
                        '|"COMMENT_IMPORT_START',
                        'COMMENT_END"|',
                        'DELIMITER',
                        '|"LINE_END"|'
                        ),
                        array(
                        $this->sLnEnd . '/***! ',
                        $this->sLnEnd . $this->sLnEnd . '/***! @import url',
                        ' !***/' . $this->sLnEnd . $this->sLnEnd,
                        ($test) ? 'DELIMITER' : '',
                        $this->sLnEnd
                        ), trim($sContents));
                

                return $sContents;
        }

        ##<procode>##

        /**
         * Determines if js file should be loaded asynchronously. Would be aggregated otherwise.
         * 
         * @param type $sPath    File path
         * @return boolean
         */
        protected function loadAsync($sUrl = '')
        {
                return (JchOptimizeHelper::findExcludes(JchOptimizeHelper::getArray($this->params->get('pro_loadFilesAsync', '')), $sUrl));
        }

        /**
         * Resolves @imports in css files, fetching contents of these files and adding them to the aggregated file
         * 
         * @param string $sContent      
         * @return string
         */
        protected function replaceImports($sContent)
        {
                if ($this->params->get('pro_replaceImports', '1'))
                {
                        $oCssParser = $this->oCssParser;

                        $u = $oCssParser->u;

                        $sImportFileContents = preg_replace_callback(
                                "#(?>@?[^@'\"/]*+(?:{$u}|/|\()?)*?\K(?:@import url\((?=[^\)]+\.(?:css|php))([^\)]+)\)([^;]*);|\K$)#",
                                array(__CLASS__, 'getImportFileContents'), $sContent
                        );

                        if (is_null($sImportFileContents))
                        {
                                JchOptimizeLogger::log(JchPlatformUtility::translate('Failed getting @import file contents'), $this->params);

                                return $sContent;
                        }

                        $sContent = $sImportFileContents;
                }
                else
                {
                        $sContent = parent::replaceImports($sContent);
                }

                return $sContent;
        }

        /**
         * Fetches the contents of files declared with @import 
         * 
         * @param array $aMatches       Array of regex matches
         * @return string               file contents
         */
        protected function getImportFileContents($aMatches)
        {
                if (!isset($aMatches[1]) || $aMatches[1] == '' || preg_match('#^(?>\(|/(?>/|\*))#', $aMatches[0]))
                {
                        return $aMatches[0];
                }

                $aUrlArray = array();

                $aUrlArray[0]['url']   = $aMatches[1];
                $aUrlArray[0]['media'] = $aMatches[2];
                //$aUrlArray[0]['id']    = md5($aUrlArray[0]['url'] . $this->oParser->sFileHash);

                $oCssParser    = $this->oCssParser;
                $sFileContents = $this->combineFiles($aUrlArray, 'css', $oCssParser);

                if ($sFileContents === FALSE)
                {
                        return $aMatches[0];
                }

                return $sFileContents;
        }

        /**
         * Javascript function used to load files asynchronously
         * 
         * @return string       Javascript function added to the top of aggregated js file
         */
        protected function getLoadScript()
        {
                $sLoadScript = <<<LOADSCRIPT
function loadScript(url, callback){
    var script = document.createElement("script")
    script.type = "text/javascript";

    if (script.readyState){  //IE
        script.onreadystatechange = function(){
            if (script.readyState == "loaded" ||
                    script.readyState == "complete"){
                script.onreadystatechange = null;
                callback();
            }
        };
    } else {  //Others
        script.onload = function(){
            callback();
        };
    }

    script.src = url;
    document.getElementsByTagName("head")[0].appendChild(script);

};
LOADSCRIPT;
                if ($this->params->get('js_minify', 0))
                {
                        $sLoadScript = $this->MinifyContent($sLoadScript, 'js', 'loadScript');
                }
                else
                {
                        $sLoadScript = $sLoadScript . $this->sLnEnd;
                }

                return $sLoadScript;
        }

        ##</procode>##
}
