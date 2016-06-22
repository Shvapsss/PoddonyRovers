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

// Plugin
class plgCCK_FieldSelect_Dynamic_Cascade extends JCckPluginField
{
	protected static $type			=	'select_dynamic_cascade';
	protected static $convertible	=	1;
	protected static $friendly		=	1;
	protected static $path;
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Construct
	
	// onCCK_FieldConstruct
	public function onCCK_FieldConstruct( $type, &$data = array() )
	{
		if ( self::$type != $type ) {
			return;
		}
		
		// Add Database Process
		if ( $data['bool2'] == 0 ) {
			$app 	= 	JFactory::getApplication();
			$ext	=	$app->getCfg( 'dbprefix' );

			if ( isset( $data['json']['options2']['table'] ) ) {
				$data['json']['options2']['table']	=	str_replace( $ext, '#__', $data['json']['options2']['table'] );
			}
		}
		parent::g_onCCK_FieldConstruct( $data );
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Prepare
	
	// onCCK_FieldPrepareContent
	public function onCCK_FieldPrepareContent( &$field, $value = '', &$config = array() )
	{
		if ( self::$type != $field->type ) {
			return;
		}
		parent::g_onCCK_FieldPrepareContent( $field, $config );
		
		// Init
		$options2	=	JCckDev::fromJSON( $field->options2 );
		$divider	=	'';
		$lang_code	=	'';
		$value2		=	'';

		/* tmp */
		$jtext						=	$config['doTranslation'];
		$config['doTranslation']	=	0;
		/* tmp */

		// Prepare
		self::_languageDetection( $lang_code, $value2, $options2 );
		if ( $field->bool3 ) {
			$divider	=	( $field->divider != '' ) ? $field->divider : ',';
		}
		$options_2			=	self::_getOptionsList( $options2, $field->bool2, $lang_code, $value, true );
		$field->options		=	( $field->options ) ? $field->options.'||'.$options_2 : $options_2;
		
		// Set
		$field->text		=	parent::g_getOptionText( $value, $field->options, $divider, $config ); // self::_getOptionText( $value, $field->options2 );
		$field->value		=	$value;
		$field->typo_target	=	'text';

		/* tmp */
		$config['doTranslation']	=	$jtext;
		/* tmp */
	}
	
	// onCCK_FieldPrepareForm
	public function onCCK_FieldPrepareForm( &$field, $value = '', &$config = array(), $inherit = array(), $return = false )
	{
		if ( self::$type != $field->type ) {
			return;
		}
		self::$path	=	parent::g_getPath( self::$type.'/' );
		parent::g_onCCK_FieldPrepareForm( $field, $config );
		
		// Init
		if ( count( $inherit ) ) {
			$id		=	( isset( $inherit['id'] ) && $inherit['id'] != '' ) ? $inherit['id'] : $field->name;
			$name	=	( isset( $inherit['name'] ) && $inherit['name'] != '' ) ? $inherit['name'] : $field->name;
		} else {
			$id		=	$field->name;
			$name	=	$field->name;
		}
		$value		=	( $value != '' ) ? $value : $field->defaultvalue;
		$name		=	( @$field->bool3 ) ? $name.'[]' : $name;
		$label		=	'';
		$divider	=	'';
		if ( $field->bool3 ) {
			$divider	=	( $field->divider != '' ) ? $field->divider : ',';			
			if ( !is_array( $value ) ) {
				$value		=	explode( $divider, $value );
			}
		} else {
			$field->divider	=	'';
		}
		
		// Validate
		$validate	=	'';
		if ( $config['doValidation'] > 1 ) {
			plgCCK_Field_ValidationRequired::onCCK_Field_ValidationPrepareForm( $field, $id, $config );
			$validate	=	( count( $field->validate ) ) ? ' validate['.implode( ',', $field->validate ).']' : '';
		}
		
		// Prepare
		if ( parent::g_isStaticVariation( $field, $field->variation, true ) ) {
			$form			=	'';
			$field->text	=	'';
			parent::g_getDisplayVariation( $field, $field->variation, $value, $field->text, $form, $id, $name, '<select', '', '', $config );
		} else {
			// Prepare
			$items		=	array();
			$opts		=	array();
			$options2	=	JCckDev::fromJSON( $field->options2 );
			$optgroups	=	false;

			if ( $field->bool4 == 1 ) {
				$results	=	self::_getStaticOption( $field, $field->options, $config, $optgroups );
				foreach ( $results as $result ) {
					$opts[]	=	$result;
				}
			}

			$exist		=	false;
			$group_id	=	( isset( $options2['group'] ) && $options2['group'] ) ? $options2['group'] : 'cck_sdc1';
			$opt_rank	=	( isset( $options2['rank'] ) ) ? $options2['rank'] : '';
			$opt_method	=	( isset( $options2['method'] ) ) ? $options2['method'] : '_GET';
			$opt_method	=	str_replace( array( '_GET', '_POST' ), array( 'get', 'post' ), $opt_method );

			if ( trim( $field->selectlabel ) ) {
				$label	=	JText::_( $field->selectlabel );
			}
			$lab	=	( $label ) ? '- '.$label.' -' : '';

			if ( @$inherit['empty'] != 1 && $field->variation != 'hidden' ) {
				self::_addScripts( $lab, $name, $value, 0, $group_id, $opt_rank, $opt_method );
			}
			if ( $label ) {
				$opts[]	=	JHtml::_( 'select.option',  '', '- '.$label.' -', 'value', 'text' );
			}

			if ( $field->bool2 == 0 ) {
				$opt_table			=	isset( $options2['table'] ) ? ' FROM '.$options2['table'] : '';
				$opt_name			=	isset( $options2['name'] ) ? $options2['name'] : '';
				$opt_value			=	isset( $options2['value'] ) ? $options2['value'] : '';
				$opt_where			=	( @$options2['where'] != '' ) ? ' WHERE '.$options2['where']: '';
				$opt_parent			=	isset( $options2['parent'] ) ? $options2['parent']: 'id';
				$opt_and			=	( @$options2['and'] != '' ) ? ' AND '.$options2['and']: '';
				$exist				=	( $config['pk'] || $config['client'] == 'search' ) ? true : false;
				$opt_orderby		=	@$options2['orderby'] != '' ? ' ORDER BY '.$options2['orderby'].' '.( ( @$options2['orderby_direction'] != '' ) ? $options2['orderby_direction'] : 'ASC' ) : '';

				// Language Detection
				$lang_code		=	'';
				self::_languageDetection( $lang_code, $value, $options2 );
				$opt_value			=	str_replace( '[lang]', $lang_code, $opt_value );
				$opt_name			=	str_replace( '[lang]', $lang_code, $opt_name );
				$opt_where			=	str_replace( '[lang]', $lang_code, $opt_where );
				$opt_orderby		=	str_replace( '[lang]', $lang_code, $opt_orderby );
				$opt_group			=	'';
			} else {
				$opt_query			=	( @$options2['query'] != '' ) ? $options2['query']: '';
				//$exist			=	( $config['pk'] || $config['client'] == 'search' ) ? true : false;  //TODO
				
				// Language Detection
				$lang_code			=	'';
				self::_languageDetection( $lang_code, $value, $options2 );
				$opt_query			=	str_replace( '[lang]', $lang_code, $opt_query );
				$opt_query			=	JCckDevHelper::replaceLive( $opt_query );
				$opt_name			=	'text';
				$opt_value			=	'value';  //TODO
				$opt_group			=	'optgroup';
			}

			if ( $exist && $opt_rank != 0 && $value != '' ) {
				$query			=	'SELECT '.$opt_parent.$opt_table.' WHERE '.$opt_value.'='.$value.''.$opt_and;  //TODO
				$query			=	JCckDevHelper::replaceLive( $query );
				$parent_value	=	JCckDatabase::loadResult( $query );
				$opt_where		=	( $parent_value != '' ) ? ' WHERE '.$opt_parent.'=\''.$parent_value.'\'' : '';
			}
			
			if ( $exist && $value != '' && ! $field->bool2 || $opt_rank == 0 ) {
				/*
				$acl	=	'';
				$user	=	JFactory::getUser();
				$admin	=	( @$user->authorise( 'core.admin' ) ) ? 1 : 0;
				if ( ! $admin ) {
					$autho	=	$user->getAuthorisedViewLevels();
					$groups	=	implode( ',', $autho );
					$acl	=	' AND access IN ('.$groups.') ';
				}
				*/
				if ( $opt_name && $opt_value && @$opt_table && ! $field->bool2 ) {
					$query		=	'SELECT '.$opt_name.','.$opt_value.$opt_table.$opt_where.$opt_and.$opt_orderby;
					// $query	=	str_replace( '#acl#', $acl, $query );
					$query		=	JCckDevHelper::replaceLive( $query );
					$items		=	JCckDatabase::loadObjectList( $query );
				}
				if ( $opt_rank == 0 && $field->bool2 ) {
					$query		=	$opt_query;
					// $query	=	str_replace( '#acl#', $acl, $query );
					$query		=	JCckDevHelper::replaceLive( $query );
					$items		=	JCckDatabase::loadObjectList( $query );
				}

				if ( count( $items ) ) {
					$optgroup	=	0;
					foreach ( $items as $o ) {
						$db_name	=	( !$field->bool2 && $opt_name != '' ) ? $o->$opt_name : $o->text;
						$db_value	=	( !$field->bool2 && $opt_value != '' ) ? $o->$opt_value : $o->value;
						if ( $db_value == 'optgroup' ) {
							if ( $optgroup == 1 ) {
								$opts[]	=	JHtml::_( 'select.option', '</OPTGROUP>' );
							}
							$opts[]		=	JHtml::_( 'select.option', '<OPTGROUP>', $db_name );
							$optgroup	=	1;
						} elseif ( $db_value == 'endgroup' && $optgroup == 1 ) {
							$opts[]		=	JHtml::_( 'select.option', '</OPTGROUP>' );
							$optgroup	=	0;
						} else {
							$opts[]		=	JHtml::_( 'select.option', $db_value, $db_name, 'value', 'text' );
						}
					}
					if ( $optgroup == 1 ) {
						$opts[]	=	JHtml::_( 'select.option', '</OPTGROUP>' );
						$optgroup	=	0;
					}
				}
			}
			
			if ( !count( $opts ) ) {
				$opts[]		=	JHtml::_( 'select.option',  '', '', 'value', 'text' );
			}
			if ( $field->bool4 == 2 ) {
				$results	=	self::_getStaticOption( $field, $field->options, $config );
				foreach ( $results as $result ) {
					$opts[]	=	$result;
				}
			}
			
			$class	=	'inputbox select '.$group_id.$validate.( $field->css ? ' '.$field->css : '' );
			$multi	=	'';
			$size	=	'1';
			$attr	=	'class="'.$class.'" size="'.$size.'"'.$multi . ( $field->attributes ? ' '.$field->attributes : '' );
			$form	=	'';
			if ( count( $opts ) ) {
				$form	=	JHtml::_( 'select.genericlist', $opts, $name, $attr, 'value', 'text', $value, $id );
			}
			
			/* tmp */
			$jtext						=	$config['doTranslation'];
			$config['doTranslation']	=	0;
			/* tmp */

			// Set
			if ( ! $field->variation ) {
				$field->form	=	$form;
				if ( $field->script ) {
					parent::g_addScriptDeclaration( $field->script );
				}
			} else {
				$options_2			=	self::_getOptionsList( $options2, $field->bool2, $lang_code, '' );
				if ( $field->bool4 ) {
					$field->text	=	parent::g_getOptionText( $value, ( ( $field->options ) ? $field->options.'||'.$options_2 : $options_2 ), $divider, $config );
				} else {
					$field->text	=	parent::g_getOptionText( $value, $options_2, $divider, $config ); // self::_getOptionText( $value, $field->options2 );
				}
				parent::g_getDisplayVariation( $field, $field->variation, $value, $field->text, $form, $id, $name, '<select', '', '', $config );
			}

			/* tmp */
			$config['doTranslation']	=	$jtext;
			/* tmp */
		}
		$field->value	=	$value;
		
		// Return
		if ( $return === true ) {
			return $field;
		}
	}
	
	// onCCK_FieldPrepareSearch
	public function onCCK_FieldPrepareSearch( &$field, $value = '', &$config = array(), $inherit = array(), $return = false )
	{
		if ( self::$type != $field->type ) {
			return;
		}
		
		// Init
		if ( $field->bool3 ) {
			$divider			=	$field->match_value ? $field->match_value : $field->divider;
			$field->match_value	=	$divider;
			if ( is_array( $value ) ) {
				$value	=	implode( $divider, $value );
			}
			
			$field->divider	=	$divider;
		} else {
			$field->match_value	=	$field->match_value ? $field->match_value : ',';
		}
		self::onCCK_FieldPrepareForm( $field, $value, $config, $inherit, $return );
		
		// Set
		$field->value	=	$value;
		
		// Return
		if ( $return === true ) {
			return $field;
		}
	}
	
	// onCCK_FieldPrepareStore
	public function onCCK_FieldPrepareStore( &$field, $value = '', &$config = array(), $inherit = array(), $return = false )
	{
		if ( self::$type != $field->type ) {
			return;
		}
		
		// Init
		if ( count( $inherit ) ) {
			$name	=	( isset( $inherit['name'] ) && $inherit['name'] != '' ) ? $inherit['name'] : $field->name;
		} else {
			$name	=	$field->name;
		}
		$divider	=	'';
		$value2		=	'';
		
		// Prepare
		if ( $field->bool3 ) {
			// Set Multiple
			$divider	=	( $field->divider != '' ) ? $field->divider : ',';
			if ( $divider ) {
				$nb			=	count( $value );
				if ( is_array( $value ) && $nb > 0 ) {
					$value	=	implode( $divider, $value );
				}
			}
		}

		/* tmp */
		$jtext						=	$config['doTranslation'];
		$config['doTranslation']	=	0;
		/* tmp */

		$options2		=	JCckDev::fromJSON( $field->options2 );
		self::_languageDetection( $lang_code, $value2, $options2 );
		$options_2		=	self::_getOptionsList( $options2, $field->bool2, $lang_code, $value );
		$field->options	=	( $field->options ) ? $field->options.'||'.$options_2 : $options_2;
		
		// Validate
		$text	=	parent::g_getOptionText( $value, $field->options, $divider, $config );
		parent::g_onCCK_FieldPrepareStore_Validation( $field, $name, $value, $config ); // self::_getOptionText( $value, $field->options2 );
		
		/* tmp */
		$config['doTranslation']	=	$jtext;
		/* tmp */

		// Set or Return
		$field->text	=	$text;
		if ( $return === true ) {
			return $value;
		}
		$field->value	=	$value;
		parent::g_onCCK_FieldPrepareStore( $field, $name, $value, $config );
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Render
	
	// onCCK_FieldRenderContent
	public static function onCCK_FieldRenderContent( $field, &$config = array() )
	{
		return parent::g_onCCK_FieldRenderContent( $field, 'text' );
	}
	
	// onCCK_FieldRenderForm
	public static function onCCK_FieldRenderForm( $field, &$config = array() )
	{
		return parent::g_onCCK_FieldRenderForm( $field );
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Stuff & Script

	// _addScripts
	protected static function _addScripts( $label, $name, $value, $toggle, $group, $rank, $method )
	{
		$doc	=	JFactory::getDocument();
		$js		=	'';

		static $loaded	=	0;
		if ( !$loaded ) {
			$loaded		=	1;
			$doc->addScript( self::$path.'assets/js/script-1.1.2.min.js' );
		}

		// Gx Check Name
		preg_match_all( '#\[([a-zA-Z0-9_]*)\]#U', $name, $matches );
		if ( count ( $matches[1] ) ) {
			// First Group
			if ( $matches[1][0] == 0 ) {
				if ( $rank == 0 ) {
					$js	.=	'var '.$group.'_gx_field = new Array("'.$matches[1][1] .'"); ';
					$js	.=	'var '.$group.'_gx_label = new Array("'.$label .'");';
					$js	.=	'var '.$group.'_gx_value = new Array("'.$value .'");';
					$js	.=	'var '.$group.'_gx_toggle = new Array("'.$toggle .'");';
				}
				if ( $rank == 1 || $rank == 2 ) {
					$js	.=	$group.'_gx_field.push("'. $matches[1][1] . '"); ';
					$js	.=	$group.'_gx_label.push("'. $label . '");';
					$js	.=	$group.'_gx_value.push("'. $value . '");';
					$js	.=	$group.'_gx_toggle.push("'. $toggle . '");';
				}
				if ( $rank == 2 ) {
					$js	.=	"\n".'jQuery(document).ready(function($) { JCck.Sdc.load('.$group.'_gx_field, '.$group.'_gx_label, '.$group.'_gx_value, '.$group.'_gx_toggle, ".'.$group.'", "'.$method.'", "'.JUri::base().'"); });';
				}
			}
		} else {
			if ( $rank == 0 ) {
				$js	.=	'var '.$group.'_field = new Array("'.$name .'"); ';
				$js	.=	'var '.$group.'_label = new Array("'.$label .'");';
				$js	.=	'var '.$group.'_value = new Array("'.$value .'");';
				$js	.=	'var '.$group.'_toggle = new Array("'.$toggle .'");';
			}
			if ( $rank == 1 || $rank == 2 ) {
				$js	.=	'if ( typeof '.$group.'_field != "undefined" ){'.$group.'_field.push("'. $name . '"); }';
				$js	.=	'if ( typeof '.$group.'_label != "undefined" ){'.$group.'_label.push("'. $label . '"); }';
				$js	.=	'if ( typeof '.$group.'_value != "undefined" ){'.$group.'_value.push("'. $value . '"); }';
				$js	.=	'if ( typeof '.$group.'_toggle != "undefined" ){'.$group.'_toggle.push("'. $toggle . '"); }';
			}
			if ( $rank == 2 ) {
				$js	.=	"\n".'if ( typeof '.$group.'_field != "undefined" ){'.'jQuery(document).ready(function($) { JCck.Sdc.load('.$group.'_field, '.$group.'_label, '.$group.'_value, '.$group.'_toggle, ".'.$group.'", "'.$method.'", "'.JUri::base().'"); }); }';
			}
		}

		$doc->addScriptDeclaration( $js );
	}
	
	// _languageDetection
	protected static function _languageDetection( &$lang_code, &$value, $options2 )
	{
		if ( @$options2['geoip'] && function_exists( 'geoip_country_code_by_name' ) ) {
			$lang_code	=	geoip_country_code_by_name( $_SERVER['REMOTE_ADDR'] );
		} else {
			jimport( 'joomla.language.helper' );
			$languages	=	JLanguageHelper::getLanguages( 'lang_code' );
			$lang_tag	=	JFactory::getLanguage()->getTag();
			$lang_code	=	( isset( $languages[$lang_tag] ) ) ? strtoupper( $languages[$lang_tag]->sef ) : '';
		}
		$value			=	str_replace( '[lang]', $lang_code, $value );
		$languages		=	explode( ',', @$options2['language_codes'] );
		if ( ! in_array( $lang_code, $languages ) ) {
			$lang_code	=	@$options2['language_default'] ? $options2['language_default'] : '';
		}
	}
	
	// _getStaticOption
	protected static function _getStaticOption( $field, $options, $config, &$optgroups = false )
	{
		$results	=	array();
		$optgroup	=	0;
		$options	=	explode( '||', $options );
		if ( $field->bool8 ) {
			$field->bool8	=	$config['doTranslation'];
		}
		if ( count( $options ) ) {
			foreach ( $options as $val ) {
				$latest	=	0;
				if ( trim( $val ) != '' ) {
					if ( JString::strpos( $val, '=' ) !== false ) {
						$opt	=	explode( '=', $val );
						if ( $opt[1] == 'optgroup' ) {
							if ( $optgroup == 1 ) {
								$results[]	=	JHtml::_( 'select.option', '</OPTGROUP>' );
							}
							$results[]		=	JHtml::_( 'select.option', '<OPTGROUP>', $opt[0] );
							$optgroup	=	1;
							$latest		=	1;
						} elseif ( $opt[1] == 'endgroup' && $optgroup == 1 ) {
							$results[]		=	JHtml::_( 'select.option', '</OPTGROUP>' );
							$optgroup	=	0;
						} else {
							if ( $field->bool8 && trim( $opt[0] ) ) {
								$opt[0]	=	JText::_( 'COM_CCK_' . str_replace( ' ', '_', trim( $opt[0] ) ) );
							}
							$results[]	=	JHtml::_( 'select.option', $opt[1], $opt[0], 'value', 'text' );
						}
					} else {
						if ( $val == 'endgroup' && $optgroup == 1 ) {
							$results[]		=	JHtml::_( 'select.option', '</OPTGROUP>' );
							$optgroup	=	0;
						} else {
							$text	=	$val;
							if ( $field->bool8 && trim( $text ) != '' ) {
								$text	=	JText::_( 'COM_CCK_' . str_replace( ' ', '_', trim( $text ) ) );
							}
							$results[]	=	JHtml::_( 'select.option', $val, $text, 'value', 'text' );
						}
					}
				}
			}
			if ( $optgroup == 1 ) {
				if ( $latest == 1 ) {
					$optgroups		=	true;
				} else {
					$results[]		=	JHtml::_( 'select.option', '</OPTGROUP>' );
				}
			}
		}

		return $results;
	}

	// _getOptionText
	protected static function _getOptionText( $value, $json )
	{
		$options2	=	JCckDev::fromJSON( $json );
		$texts		=	'';
		$text		=	'';

		$opt_table	=	isset( $options2['table'] ) ? ' FROM '.$options2['table'] : '';
		$opt_name	=	isset( $options2['name'] ) ? $options2['name'] : '';
		$opt_value	=	isset( $options2['value'] ) ? $options2['value'] : '';
		$opt_where	=	( $opt_value != '' && $value ) ? ' WHERE '.$opt_value.' IN ('.$value.')' : '';
		if ( $opt_name && $opt_where && $opt_table ) {
			$query		=	'SELECT '.$opt_name.$opt_table.$opt_where;
			$query		=	JCckDevHelper::replaceLive( $query );
			// $query	=	str_replace( '#acl#', '', $query );
			$texts		=	JCckDatabase::loadColumn( $query );
		}
		if ( count( $texts ) ) {
			foreach( $texts as $t ) {
				$text .= ( $text == '' ) ? $t : ', '.$t;
			}
		}
		return $text;
	}
	
	// _getOptionsList
	protected static function _getOptionsList( $options2, $free_sql, $lang_code, $value, $static = false )
	{
		$options	=	'';
		
		if ( $free_sql == 0 ) {
			$opt_table	=	isset( $options2['table'] ) ? ' FROM '.$options2['table'] : '';
			$opt_name	=	isset( $options2['name'] ) ? $options2['name'] : '';
			$opt_value	=	isset( $options2['value'] ) ? $options2['value'] : '';
			// $opt_where	=	@$options2['where'] != '' ? ' WHERE '.$options2['where']: ''; // ??
			$opt_where	=	( $opt_value != '' && $value ) ? ' WHERE '.$opt_value.' IN ('.$value.')' : '';
			
			// Language Detection
			$opt_value	=	str_replace( '[lang]', $lang_code, $opt_value );
			$opt_name	=	str_replace( '[lang]', $lang_code, $opt_name );
			$opt_where	=	str_replace( '[lang]', $lang_code, $opt_where );
			
			if ( $opt_name && $opt_table ) {
				$query	=	'SELECT '.$opt_name.','.$opt_value.$opt_table.$opt_where;
				$query	=	JCckDevHelper::replaceLive( $query );
				$lists	=	( $static ) ? JCckDatabaseCache::loadObjectList( $query ) : JCckDatabase::loadObjectList( $query );
				if ( count( $lists ) ) {
					foreach ( $lists as $list ) {
						$options	.=	$list->$opt_name.'='.$list->$opt_value.'||';
					}
				}
			}
		} else {
			$opt_query	=	isset( $options2['query'] ) ? $options2['query'] : '';
			$opt_parent	=	isset( $options2['parent'] ) ? $options2['parent'] : '';
			$opt_value	=	isset( $options2['value'] ) ? $options2['value'] : '';
			$opt_query	=	preg_replace( '/('.$opt_parent.' ?= ?\[parent\]|'.$opt_parent.' ?= ?\#parent_id\#)/', $opt_value.' = "'.$value.'"', $opt_query ); // TODO

			// Language Detection
			$opt_query	=	str_replace( '[lang]', $lang_code, $opt_query );
			$opt_query	=	JCckDevHelper::replaceLive( $opt_query );
			$lists		=	( $static ) ? JCckDatabaseCache::loadObjectList( $opt_query ) : JCckDatabase::loadObjectList( $opt_query );
			if ( count( $lists ) ) {
				foreach ( $lists as $list ) {
					$options	.=	@$list->text.'='.@$list->value.'||';
				}
			}
		}
		
		return $options;
	}
	
	// getValueFromOptions
	public static function getValueFromOptions( $field, $value, $config = array() )
	{
		// Init
		$options2	=	JCckDev::fromJSON( $field->options2 );
		$divider	=	'';
		$lang_code	=	'';
		$value2		=	'';
		
		// Prepare
		self::_languageDetection( $lang_code, $value2, $options2 );
		if ( $field->bool3 ) {
			$divider	=	( $field->divider != '' ) ? $field->divider : ',';
		}
		$options_2			=	self::_getOptionsList( $options2, $field->bool2, $lang_code, '' );
		$field->options		=	( $field->options ) ? $field->options.'||'.$options_2 : $options_2;
		
		return parent::getValueFromOptions( $field, $value, $config );
	}
	
	// isConvertible
	public static function isConvertible()
	{
		return self::$convertible;
	}
	
	// isFriendly
	public static function isFriendly()
	{
		return self::$friendly;
	}
}
?>