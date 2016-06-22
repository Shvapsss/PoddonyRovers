<?php

/**
 * JCH Optimize - Plugin to aggregate and minify external resources for
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
defined('_JCH_EXEC') or die('Restricted access');

class JchOptimizeAjax
{

        /**
         * 
         * @return type
         * @throws type
         */
        public static function optimizeImages(JchPlatformSettings $params)
        {
                error_reporting(0);

                $root = JchPlatformPaths::rootPath();

                set_time_limit(0);

                $dir      = JchPlatformUtility::get('dir', '', 'string');
                $current  = JchPlatformUtility::get('current', '0', 'int');
                $optimize = JchPlatformUtility::get('optimize', '0', 'int');

                $dir = JchPlatformUtility::decrypt($dir);

                $arr   = array('total' => 0, 'current' => $current, 'optimize' => $optimize, 'message' => '');
                $files = array();

                if (is_dir($root . $dir))
                {
                        //$files = glob($root . $dir . '*.{gif,jpg,png}', GLOB_BRACE);
                        if ($dh = opendir($root . $dir))
                        {
                                while (($file = readdir($dh)) !== false)
                                {
                                        if (preg_match('#\.(?:gif|jpe?g|png)$#i', $file))
                                        {
                                                $files[] = $root . $dir . $file;
                                        }
                                }

                                closedir($dh);
                        }

                        $arr['total'] = count($files);
                }
                else
                {
                        $files = array($dir);
                }

//                try
//                {
//                        $smushitclass = 'JchOptimize\SmushIt';
//                        $smushit      = new $smushitclass($files[$arr['current']], 0x02);
//
//                        $opfiles = $smushit->get();
//
//                        if (copy($opfiles[0]->destination, $opfiles[0]->source))
//                        {
//                                $arr['optimize'] ++;
//                                $arr['message'] = $opfiles[0]->source . ':Optimized!';
//                        }
//                        else
//                        {
//                                throw new Exception($opfiles[0]->source . ': Could not copy optimized image');
//                        }
//                }
//                catch (Exception $e)
//                {
//                        $arr['message'] = $e->getMessage();
//                }

                $kraken = new JchOptimize\Kraken($params->get('kraken_api_key'), $params->get('kraken_api_secret'));

                $options = array(
                        "file"  => $files[$arr['current']],
                        "wait"  => true,
                        "lossy" => $params->get('kraken_optimization_level', 0) ? TRUE : FALSE
                );

                if ($params->get('kraken_quality', 0))
                {
                        $options['quality'] = (int) $params->get('kraken_quality');
                }


                try
                {
                        $data = $kraken->upload($options);
                }
                catch (Exception $ex)
                {
                        $data = array(
                                'success' => FALSE,
                                'error'   => $ex->getMessage()
                        );
                }

                if (isset($data['success']))
                {
                        if ($data['success'])
                        {
                                if ($data['saved_bytes'] == 0)
                                {
                                        $arr['message'] = $files[$arr['current']] . ': This image can not be optimized any further.';
                                }
                                elseif (copy($data['kraked_url'], $files[$arr['current']]))
                                {
                                        $arr['optimize'] ++;
                                        $arr['message'] = $files[$arr['current']] . ': Optimized! You saved ' . $data['saved_bytes'] . ' bytes.';
                                }
                                else
                                {
                                        $arr['message'] = $files[$arr['current']] . ': Could not copy optimized file.';
                                }
                        }
                        else
                        {
                                $arr['message'] = $files[$arr['current']] . ': ' . $data['error'];
                        }
                }
                else
                {
                        $arr['message'] = $files[$arr['current']] . ': Unrecognizable response from server';
                }

                $arr['current'] ++;
                $arr['log_path'] = JchPlatformUtility::getLogsPath();

                try
                {
                        JchOptimizeLogger::logInfo($arr['message'], 'INFO');
                }
                catch (Exception $e)
                {
                        
                }

                return json_encode($arr);
        }

        /**
         * 
         * @return string
         */
        public static function fileTree()
        {
                $root = JchPlatformPaths::rootPath();

                $dir = urldecode(JchPlatformUtility::get('dir', '', 'string', 'get'));

                $dir = JchPlatformUtility::decrypt($dir);

                $response = '';

                if (file_exists($root . $dir))
                {
                        $files = scandir($root . $dir);
                        natcasesort($files);
                        if (count($files) > 2)
                        { /* The 2 accounts for . and .. */
                                $response .= '<ul class="jqueryFileTree" style="display: none; ">';
                                // All dirs
                                foreach ($files as $file)
                                {
                                        if (file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file))
                                        {
                                                $response .= '<li class="directory collapsed"><a href="#" rel="'
                                                        . JchPlatformUtility::encrypt($dir . $file . '/')
                                                        . '">' . htmlentities($file) . '</a></li>';
                                        }
                                }
                                // All files
                                foreach ($files as $file)
                                {
                                        if (file_exists($root . $dir . $file) && $file != '.' && $file != '..' && !is_dir($root . $dir . $file))
                                        {
                                                $ext = preg_replace('/^.*\./', '', $file);
                                                $response .= '<li class="file ext_' . $ext . '"><a href="#" rel="'
                                                        . JchPlatformUtility::encrypt($dir . $file)
                                                        . '">' . htmlentities($file) . '</a></li>';
                                        }
                                }
                                $response .= '</ul>';
                        }
                }

                return $response;
        }

}
