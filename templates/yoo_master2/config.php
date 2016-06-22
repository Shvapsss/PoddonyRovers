<?php
/**
* @package   yoo_master2
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

return array(

    'path' => array(
        'theme'   => array(__DIR__),
        'js'      => array(__DIR__.'/js'),
        'css'     => array(__DIR__.'/css'),
        'less'    => array(__DIR__.'/less'),
        'layouts' => array(__DIR__.'/layouts'),
		'fonts' => array(__DIR__.'/fonts')
    ),

    'less' => array(

        'vars' => array(
            'less:theme.less'
        ),

        'files' => array(
            '/css/theme.css'     => 'less:theme.less',
            '/css/bootstrap.css' => 'less:bootstrap.less'
        )

    ),

    'cookie' => $cookie = md5(__DIR__),

    'customizer' => isset($_COOKIE[$cookie])

);
