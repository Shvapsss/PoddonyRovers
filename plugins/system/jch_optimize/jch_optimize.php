<?php

/**
 * JCH Optimize - Joomla! plugin to aggregate and minify external resources for
 * optmized downloads
 * @author Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2010 Samuel Marshall
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
// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

if (!defined('JCH_PLUGIN_DIR'))
{
        define('JCH_PLUGIN_DIR', dirname(__FILE__) . '/');
}

include_once dirname(__FILE__) . '/jchoptimize/loader.php';

class plgSystemJCH_Optimize extends JPlugin
{

        /**
         * 
         * @return boolean
         * @throws Exception
         */
        public function onAfterRender()
        {
                $app = JFactory::getApplication();
                $config = JFactory::getConfig();
                $user = JFactory::getUser();
                
                if (($app->getName() != 'site') || (JFactory::getDocument()->getType() != 'html')
                        || ($app->input->get('jchbackend', '', 'int') == 1)
                        || ($config->get('offline') && $user->guest)
                        || $this->isEditorLoaded())
                {
                        return FALSE;
                }
                
                if ($this->params->get('log', 0))
                {
                        error_reporting(E_ALL & ~E_NOTICE);
                }

                if (version_compare(JVERSION, '3.2.3', '>='))
                {
                        $sHtml = $app->getBody();
                }
                else
                {
                        $sHtml = JResponse::getBody();
                }

                if ($app->input->get('jchbackend') == '2')
                {
                        echo $sHtml;
                        while (@ob_end_flush());
                        exit;
                }
                
                
                if (!defined('JCH_VERSION'))
                {
                        define('JCH_VERSION', '4.2.3');
                }
 
                try
                {
                        loadJchOptimizeClass('JchOptimize');
                        
                        $sOptimizedHtml = JchOptimize::optimize($this->params, $sHtml);
                }
                catch (Exception $ex)
                {
                        JchOptimizeLogger::log($ex->getMessage(), JchPlatformSettings::getInstance($this->params));

                        $sOptimizedHtml = $sHtml;
                }

                if (version_compare(JVERSION, '3.2.3', '>='))
                {
                        $app->setBody($sOptimizedHtml);
                }
                else
                {
                        JResponse::setBody($sOptimizedHtml);
                }
        }

        
        /**
         * Gets the name of the current Editor
         * 
         * @staticvar string $sEditor
         * @return string
         */
        protected function isEditorLoaded()
        {
                $sEditor = JFactory::getUser()->getParam('editor');
                $sEditor = !isset($sEditor) ? JFactory::getConfig()->get('editor') : $sEditor;

                $sEditorClass = 'plgEditor' . $sEditor;
                
                return class_exists($sEditorClass, FALSE);
        }
        
        ##<procode>##
        
        /**
         * 
         */
        public function onAfterDispatch()
        {
                $app = JFactory::getApplication();
                
                if($app->getName() == 'site' &&  $this->params->get('pro_lazyload', '0')
                        && $app->input->get('jchbackend', '', 'int') != 1)
                {
                        if (version_compare(JVERSION, '3.0', '<'))
                        {
                                JHtml::script('plg_jchoptimize/jquery.min.js', FALSE, TRUE);
                                JHtml::script('plg_jchoptimize/jquery-noconflict.js', FALSE, TRUE);
                        }
                        else
                        {
                                JHtml::script('jui/jquery.min.js', FALSE, TRUE);
                                JHtml::script('jui/jquery-noconflict.js', FALSE, TRUE);
                        }
                        
                        JHtml::script('plg_jchoptimize/pro-jquery.unveil.js', FALSE, TRUE);
                        
                        $document = JFactory::getDocument();
                        $document->addStyleDeclaration('
                                img[data-jchll=true]{
                                        opacity: 0;
                                        transition: opacity .3s ease-in;
                                }
                                ');
                }
        }

        /**
         * 
         * @param type $url
         * @param type $headers
         * @return boolean
         */
        public function onInstallerBeforePackageDownload(&$url, &$headers)
        {
                $uri = JUri::getInstance($url);

                // I don't care about download URLs not coming from our site
                $host = $uri->getHost();
                if ($host != 'www.jch-optimize.net')
                {
                        return true;
                }

                // Get the download ID
                $dlid = $this->params->get('pro_downloadid', '');

                // If the download ID is invalid, return without any further action
                if (!preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid))
                {
                        return true;
                }

                // Append the Download ID to the download URL
                if (!empty($dlid))
                {
                        $uri->setVar('dlid', $dlid);
                        $url = $uri->toString();
                }

                return true;
        }

        /**
         * 
         * @return string
         */
        public function onAjaxFiletree()
        {
                return JchOptimizeAjax::fileTree();
        }

        /**
         * 
         */
        public function onAjaxOptimizeimages()
        {
                return JchOptimizeAjax::optimizeImages(JchPlatformSettings::getInstance($this->params));
        }

        /**
         * 
         * @param type $arr
         */
        ##</procode>##
}

//function jchprint($variable, $name = '', $exit = FALSE, $silent = FALSE)
//{
//        if ($silent) echo '<script> ';
//        
//        echo '<pre>';
//        
//        if ($name != '')
//        {
//                echo $name . ' = ';
//        }
//        
//        if ($silent) {$variable = str_replace ('</script>', '</+script>', $variable);}
//        print_r($variable);
//        
//        echo '</pre>';
//        
//        if ($silent) echo ' </script>';
//        
//        if ($exit)
//        {
//                exit();
//        }
//}