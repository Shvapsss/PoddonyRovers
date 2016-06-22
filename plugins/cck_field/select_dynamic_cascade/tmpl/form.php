<?php
/**
* @version 			SEBLOD 3.x More
* @package			SEBLOD (App Builder & CCK) // SEBLOD nano (Form Builder)
* @url				http://www.seblod.com
* @editor			Octopoos - www.octopoos.com
* @copyright		Copyright (C) 2013 SEBLOD. All Rights Reserved.
* @license 			GNU General Public License version 2 or later; see _LICENSE.php
**/

defined( '_JEXEC' ) or die;

$app		=	JFactory::getApplication();
$type		=	$app->input->getString( 'type', 'get' );
$where		=	$app->input->getString( 'where' );
$item		=	$app->input->getString( 'item' );
$lvl		=	$app->input->getString( 'prev' );
$lvl		=	explode( ',', $lvl );
$options	=	'';
$query		=	'';

if ( $item && $where ) {
	$query		=	'SELECT selectlabel, options2, bool2, bool6 FROM #__cck_core_fields WHERE name="'.$item.'" AND type="select_dynamic_cascade"';
	$field		=	JCckDatabase::loadObject( $query );
	$one		=	$field->bool6;

	if ( $field->options2 ) {
		$query		=	'';
		$options2	=	JCckDev::fromJSON( $field->options2 );

		if ( !$field->bool2 ) {
			$opt_table	=	isset( $options2['table'] ) ? ' FROM '.$options2['table'] : '';
			$opt_name	=	isset( $options2['name'] ) ? $options2['name'].' as text' : '';
			$opt_value	=	isset( $options2['value'] ) ? $options2['value']. ' as value ': '';
			$opt_parent	=	isset( $options2['parent'] ) ? ' WHERE '.$options2['parent'].'="'.$where.'"' : '';
			$opt_and	=	( @$options2['and'] != '' ) ? ' AND '.$options2['and']: '';
			$opt_orderby=	@$options2['orderby'] != '' ? ' ORDER BY '.$options2['orderby'].' '.( ( @$options2['orderby_direction'] != '' ) ? $options2['orderby_direction'] : 'ASC' ) : '';

			if ( $opt_name && $opt_value && $opt_table ) {
				$query	=	'SELECT '.$opt_name.','.$opt_value.$opt_table.$opt_parent.$opt_and.$opt_orderby;
			}
		} else {
			$opt_query	=	isset( $options2['query'] ) ? $options2['query'] : '';
			$opt_query	=	str_replace( array( '[parent]', '#parent_id#' ), $where, $opt_query );
			
			if ( $lvl ) {
				$matches	=	null;
				preg_match_all( '#\#([0-9])\##U', $opt_query, $matches );
				if ( count( $matches[1] ) ) {
					foreach ( $matches[1] as $match ) {
						if ( trim( @$lvl[$match] != '' ) ) {
							$opt_query	=	str_replace( '#'.$match.'#', $lvl[$match], $opt_query );
						} else {
							$opt_query	=	str_replace( '#'.$match.'#', '', $opt_query );
						}
					}
				}
			}
			
			if ( $opt_query && stripos( $opt_query, 'select' ) === 0 ) {
				$query	=	$opt_query;
			}
		}

		if ( $query ) {
			/*
			$acl	=	'';
			$user	=	JFactory::getUser();
			$admin	=	( @$user->authorise( 'core.admin' ) ) ? 1 : 0;
			if ( ! $admin ) {
				$autho	=	$user->getAuthorisedViewLevels();
				$groups	=	implode( ',', $autho );
				$acl	=	' AND access IN ('.$groups.') ';
			}
			$query	=	str_replace( '#acl#', $acl, $query );
			*/
			// Language Detection
			$lang_code	=	'';
			/* _languageDetection */
			if ( @$options2['geoip'] && function_exists( 'geoip_country_code_by_name' ) ) {
				$lang_code	=	geoip_country_code_by_name( $_SERVER['REMOTE_ADDR'] );
			} else {
				jimport( 'joomla.language.helper' );
				$languages	=	JLanguageHelper::getLanguages( 'lang_code' );
				$lang_tag	=	JFactory::getLanguage()->getTag();
				$lang_code	=	( isset( $languages[$lang_tag] ) ) ? strtoupper( $languages[$lang_tag]->sef ) : '';
			}
			$languages		=	explode( ',', @$options2['language_codes'] );
			if ( ! in_array( $lang_code, $languages ) ) {
				$lang_code	=	@$options2['language_default'] ? $options2['language_default'] : '';
			}
			/* _languageDetection */
			$query		=	str_replace( '[lang]', $lang_code, $query );
			$query		=	JCckDevHelper::replaceLive( $query );
			$items		=	JCckDatabase::loadObjectList( $query );
		}
		$count		=	count( $items );
		$only_one	=	( $one && $count == 1 ) ? 1 : 0;
		if ( $count ) {
			foreach ( $items as $o ) {
				$options	.=	'<option value="'.$o->value.'">'.$o->text.'</option>';
			}
		}
	}

	if ( trim( $field->selectlabel ) && !$only_one ) {
		$options	=	'<option value="">'.'- '.$field->selectlabel.' -'.'</option>'.$options;
	}
	if ( !$options ) {
		$options	=	'<option></option>';
	}
	echo $options;
} elseif ( $item && !$where ) {
	$query	=	'SELECT selectlabel, bool3 FROM #__cck_core_fields WHERE name="'.$item.'" AND type="select_dynamic_cascade"';
	$field	=	JCckDatabase::loadObject( $query );

	if ( trim( $field->selectlabel ) ) {
		$options	=	'<option value="">'.'- '.$field->selectlabel.' -'.'</option>';
	}
	echo $options;
}
?>