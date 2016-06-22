<?php
/**
* @package			SD Field Concat V2.5.3 for SEBLOD 3.x - www.seblod.com
* @license 			GNU General Public License version 2 or later
* @author       	Simon Dowdles - http://www.simondowdles.com
* @copyright		Copyright (C) 2013 Simon Dowdles New Media Holdings (Pty) Ltd. All Rights Reserved.
**/

// No Direct Access
defined( '_JEXEC' ) or die;

// Plugin Class
class plgCCK_FieldSd_Field_Concat extends JCckPluginField
{
	protected static $type	=	'sd_field_concat';
	protected static $friendly	=	1;
	protected static $path;
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Construct
	
	// onCCK_FieldConstruct
	public function onCCK_FieldConstruct( $type, &$data = array() )
	{
		if ( self::$type != $type ) {
			return;
		}
		
		parent::g_onCCK_FieldConstruct( $data );
	}
	
	// onCCK_FieldPrepareContent
	public function onCCK_FieldPrepareContent( &$field, $value = '', &$config = array() )
	{
		if ( self::$type != $field->type ) {
			return;
		}
		parent::g_onCCK_FieldPrepareContent( $field, $config );
		
		$field->value	=	$value;
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
			$id		=	( @$inherit['id'] != '' ) ? $inherit['id'] : $field->name;
			$name	=	( @$inherit['name'] != '' ) ? $inherit['name'] : $field->name;
		} else {
			$id		=	$field->name;
			$name	=	$field->name;
		}
		$value	=	( $value != '' ) ? $value : $field->defaultvalue;
		$value	=	( $value != ' ' ) ? $value : '';
		$value	=	htmlspecialchars( $value, ENT_QUOTES );
		
		// Validate
		$validate	=	'';
		if ( $config['doValidation'] > 1 ) {
			plgCCK_Field_ValidationRequired::onCCK_Field_ValidationPrepareForm( $field, $id, $config );
			parent::g_onCCK_FieldPrepareForm_Validation( $field, $id, $config );
			if ( $field->minlength > 0 ) {
				$field->validate[]	=	'minSize['.$field->minlength.']';
			}
			$validate	=	( count( $field->validate ) ) ? ' validate['.implode( ',', $field->validate ).']' : '';
		}
		
		// Prepare
		//$class	=	'textarea text'.$validate.' '.$field->css;
		//$form	=	'<input style="padding:10px;border:1px dotted #ccc;color:#666;width:250px;" id="'.$id.'" value="'.$value.'" />';
		$form = '';
		// Set
		if ( ! $field->variation ) {
			$field->form	=	$form;
			if ( $field->script ) {
				parent::g_addScriptDeclaration( $field->script );
			}
		} else {
			parent::g_getDisplayVariation( $field, $field->variation, $value, $value, $form, $id, $name, '<input' );
		}
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
		
		#################################################################
		// SD FIELD CONCAT - SIMON DOWDLES - http://www.simondowdles.com
		#################################################################
		
		$user = JFactory::getUser();
		$options2 = JCckDev::fromJSON( $field->options2 );
		$sdFieldList = @$options2['sd_core_field_concat_list'];
		$processOnEdit = @$options2['sd_core_field_concat_process_edit'];
		$isNew = $config['id'] == 0 ? true : false;
		if(!$isNew && !$processOnEdit){
			return;
		}
		$SdJtext = @$options2['sd_core_field_concat_enable_jtext'];
		$SdUserDateFormat = @$options2['sd_core_field_concat_date_format'];
		$SdDateFormat = ($SdUserDateFormat !== '' ? $SdUserDateFormat : 'Y-m-d');
		$sdFieldSeparator = (@$options2['sd_core_field_concat_separator'] > '' ? @$options2['sd_core_field_concat_separator'] : ' ');
		$sdFieldSeparator = ($sdFieldSeparator == 'ns' ? '' : $sdFieldSeparator);
		$sdFieldList = ( preg_match("^[||]^",$sdFieldList) ? explode("||",$sdFieldList) : $sdFieldList );
		
		$sdFieldTextValue = (@$options2['sd_core_field_concat_text_value'] > '' ? @$options2['sd_core_field_concat_text_value'] : 'v');
		
		if(is_array($sdFieldList)):
			$sdConcatValue = '';
			$sdNumFields = count($sdFieldList);
			$sdNumStep = 0;
	
			foreach($sdFieldList as $sdField):
				$sdNumStep++;
				$sdField = trim($sdField);
				
				if(preg_match("^<(.*?)>^", $sdField, $matches)):
				$aitable = $matches[1];
				/* Next Auto Increment */
				$sebid = $config['pk'];
				if($sebid == 0){	// Are we creating a new article? Yes
					$inifile = dirname(__FILE__).'/increments.ini';
					if(JFile::exists($inifile)){
						chmod($inifile, 0644);
							$ini = parse_ini_file($inifile);
							$db = JFactory::getDbo();
							$pfx = $db->getPrefix();
							// get auto increment of users desired table
							$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx.$aitable."'");
							$db->query();
							$dbresult = $db->loadAssoc();
							$ai = $dbresult['Auto_increment'];

							// get auto increment of content table for this ID
							//$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx."content'");
							//$db->query();
							//$dbresult = $db->loadAssoc();
							//$artid = $dbresult['Auto_increment'];
							$ini[$ai] = $ai;
							$fopen = fopen($inifile, 'w');
							$ini_in = "";
							foreach($ini as $key=>$val){
								$ini_in .= $key."=".$val."\r\n";
							}
							fwrite($fopen, $ini_in);
							fclose($fopen);
							//$ai = $artid;					
					};
				}else{ // We are updating an article
					if($aitable > ''){

						$sebid = $config['pk'];
						//$ai = $sebid;
						$db = JFactory::getDbo();
						$pfx = $db->getPrefix();
						$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx.$aitable."'");
						$db->query();
						$dbresult = $db->loadAssoc();
						$content_ai = $dbresult['Auto_increment'];

						//$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx."content'");
						//$db->query();
						//$dbresult = $db->loadAssoc();
						//$content_ai = $dbresult['Auto_increment'];
						$inifile = dirname(__FILE__).'/increments.ini';
						if(JFile::exists($inifile)){
							chmod($inifile, 0644);
							$ini = parse_ini_file($inifile); //get ini values as array
							if(array_key_exists($sebid, $ini)){
								if($ini[$sebid] > '' && !$ini[$sebid] == 0){
									$ai = $ini[$sebid];
								}else{
									$ini_in = "";
									if(!is_array($ini)){
										$ini = array();
									}
									$ini[$sebid] = $ai; //$ai for next auto increment
									foreach($ini as $key=>$value){
										$ini_in .= $key."=".$value."\r\n";
									}
									$fopen = fopen($inifile, 'w');
									fwrite($fopen, $ini_in);
									fclose($fopen);	
								}
							}else{
								$ini_in = "";
								$ini[$sebid] = $sebid; //$ai for next auto increment
								foreach($ini as $key=>$value){
									$ini_in .= $key."=".$value."\r\n";
								}
								$fopen = fopen($inifile, 'w');
								fwrite($fopen, $ini_in);
								fclose($fopen);	
							}
						};
						
					}else{
						$ai = "";
					};
				};
				
				$sdField = $ai;
				($sdField > '' ? $sdConcatValue .= $sdField.($sdNumStep < $sdNumFields && $sdField > '' ? $sdFieldSeparator : null) : null);
				
				/* End Next Auto Increment */
				
				elseif(preg_match("^[\[\]]^", $sdField)):
					print_r($sdField); echo " IS A STRING";
					$sdField = str_replace(array('[',']'), array('',''), $sdField);
					$sdField = ($SdJtext == 1 ? JText::_('PLG_CCK_FIELD_SD_FIELD_CONCAT_'.str_replace(array(';',':','`','~','}','{','>','<','!','=',',','-'),'',trim(strtoupper(str_replace(' ', '_', $sdField))))) : $sdField);
					($sdField > '' ? $sdConcatValue .= $sdField.($sdNumStep < $sdNumFields ? $sdFieldSeparator : null) : null);
				elseif(preg_match("^[\$]^", $sdField)):
					print_r($sdField); echo " IS A DATE";
					$sdFind = array('$date', '$time', '$username', '$userid');
					$sdReplace = array(date($SdDateFormat), date('H:i:s'), ($user->username > '' ? $user->username : ''), ($user->id > '' ? $user->id : ''));
					$sdField = str_replace($sdFind, $sdReplace, $sdField);
					($sdField > '' ? $sdConcatValue .= $sdField.($sdNumStep < $sdNumFields ? $sdFieldSeparator : null) : null);
				elseif(preg_match("/^#(.*)#/i", $sdField)):
					print_r($sdField); echo " IS A FIELD";
					$sdField = trim(str_replace('#','',$sdField));
					$sdFieldTmp = JCckDevField::getObject($sdField);
					if($sdFieldTmp->type == 'select_simple' || $sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade' || ($sdFieldTmp->type == 'radio' && !is_array($config['post'][$sdField])) || ($sdFieldTmp->type == 'checkbox' && !is_array($config['post'][$sdField])) || $sdFieldTmp->type == 'select_dynamic_cascade'){
						
						switch($sdFieldTextValue){
								case 't':
									// Le select dynamic
									if($sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade'){
										// JSON > Array
										$fieldOpt2 = JCckDev::fromJSON($sdFieldTmp->options2);
										// Set Query to get select dynamic criteria (thanks Lionel!)
										$query = 'SELECT '.$fieldOpt2['name'].' FROM '.$fieldOpt2['table'].' WHERE '.$fieldOpt2['value'].'="'.$config['post'][$sdField].'"';
										// Get database object as string (thanks SEBLOD devs!)
										$dynamicText =  JCckDatabase::loadResult($query);
										if(trim($dynamicText) > ''):
											$sdConcatValue .= $dynamicText;
											$sdConcatValue .= ($sdNumStep < $sdNumFields ? $sdFieldSeparator : null);
										endif;
										$sdFieldTmp = '';
									}else{ // if not dynamic
										$sdFieldTmp = parent::g_getOptionText( trim($config['post'][$sdField]), $sdFieldTmp->options, '', $config );
										if(trim($sdFieldTmp) > ''):
											$sdConcatValue .= $sdFieldTmp;
											$sdConcatValue .= ($sdNumStep < $sdNumFields ? $sdFieldSeparator : null);
										endif;
										$sdFieldTmp = '';
									};
								break;
								
								case 'v':
									// Le select dynamic
									if($sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade'){
										// JSON > Array
										$fieldOpt2 = JCckDev::fromJSON($sdFieldTmp->options2);
										// Set Query to get select dynamic criteria (thanks Lionel!)
										$query = 'SELECT '.$fieldOpt2['value'].' FROM '.$fieldOpt2['table'].' WHERE '.$fieldOpt2['value'].'="'.$config['post'][$sdField].'"';
										// Get database object as string (thanks SEBLOD devs!)
										$dynamicText =  JCckDatabase::loadResult($query);
										if(trim($dynamicText) > ''):
											$sdConcatValue .= $dynamicText;
											$sdConcatValue .= ($sdNumStep < $sdNumFields ? $sdFieldSeparator : null);
										endif;
										$sdFieldTmp = '';
									}else{
										($sdField > '' && trim($config['post'][$sdField]) > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields && trim($config['post'][$sdField]) > '' ? $sdFieldSeparator : null) : null);
										$sdFieldTmp = '';
									}
								break;
								
								default:
									($sdField > '' && trim($config['post'][$sdField]) > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields && trim($config['post'][$sdField]) > '' ? $sdFieldSeparator : null) : null);
									$sdFieldTmp = '';
								break;
							};

						}elseif($sdFieldTmp->type == 'select_multiple' || ($sdFieldTmp->type == 'checkbox' && is_array($config['post'][$sdField])) || ($sdFieldTmp->type == 'radio' && is_array($config['post'][$sdField]))){
							switch($sdFieldTextValue){
								case 't':
									$SdValHolder = "";
									$SdArrayCount = count($config['post'][$sdField]);
									$s = 0;
									foreach($config['post'][$sdField] as $selectOption){
										$s++;
										$tempsep = "";
										$sdFieldSeparator == "ns" || $sdFieldSeparator == "" ? $tempsep = " " : $tempsep = $sdFieldSeparator;
										$SdValHolder .= parent::g_getOptionText( trim($selectOption), $sdFieldTmp->options, '', $config ).($s !== $SdArrayCount ? $tempsep : NULL);
									}
									if(str_replace(' ','', $SdValHolder) > ''):
										$sdConcatValue .= $SdValHolder;
									endif;
									$SdValHolder = '';
								break;
								
								case 'v':
									$SdValHolder = "";
									$SdValHolder = "";
									$SdArrayCount = count($config['post'][$sdField]);
									$s = 0;
									foreach($config['post'][$sdField] as $selectOption){
										$s++;
										$tempsep = "";
										$sdFieldSeparator == "ns" || $sdFieldSeparator == "" ? $tempsep = " " : $tempsep = $sdFieldSeparator;
										$SdValHolder .= trim($selectOption).($s !== $SdArrayCount ? $tempsep : NULL);
									}
									if(str_replace(' ','', $SdValHolder) > ''):
										$sdConcatValue .= $SdValHolder;
									endif;
									$SdValHolder = '';
								break;
								
								default:
									//($sdField > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields ? $sdFieldSeparator : null) : null);
								break;
							};
						}else{
							
						$sdNumFields = trim($config['post'][$sdField]) == "" ? $sdNumFields-1 : $sdNumFields;
						
						if(trim($config['post'][$sdField]) !== ""){
							$sdConcatValue .= trim($config['post'][$sdField]);
							if($sdNumStep < $sdNumFields){
								$sdConcatValue .= $sdFieldSeparator;
							}
						}
					};				
				elseif(preg_match("/^@(.*?)\((.*?)\)/i", $sdField, $matches)):
					if(count($matches) > 0){
						if(trim($config['post'][$matches[2]]) !== ""){
							$colName = $matches[1];
							$fieldName = trim(strtolower($matches[2]));
							$proceed = true;
							// START EXTERNAL FIELD
							// What type of field is this?
							if(!ctype_digit($fieldName)){
								$sdFieldTmp = JCckDevField::getObject($fieldName);
								if($sdFieldTmp->type == 'select_simple' || $sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade' || ($sdFieldTmp->type == 'radio' && !is_array($config['post'][$sdField])) || ($sdFieldTmp->type == 'checkbox' && !is_array($config['post'][$sdField])) || $sdFieldTmp->type == 'select_dynamic_cascade'){
									if($sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade'){
										// JSON > Array
										$fieldOpt2 = JCckDev::fromJSON($sdFieldTmp->options2);
										// Set Query to get select dynamic criteria (thanks Lionel!)
										$query = 'SELECT '.$fieldOpt2['value'].' FROM '.$fieldOpt2['table'].' WHERE '.$fieldOpt2['value'].'="'.$config['post'][$fieldName].'"';
										// Get database object as string (thanks SEBLOD devs!)
										$dynamicText =  JCckDatabase::loadResult($query);
										if(trim($dynamicText) > ''):
												$artId = $dynamicText;
										endif;
										$sdFieldTmp = '';
									}else{
										($fieldName > '' && trim($config['post'][$fieldName]) > '') ? $artId .= trim($config['post'][$fieldName]) : NULL;
										$sdFieldTmp = '';
									}
								}elseif($sdFieldTmp->type == 'select_multiple' || ($sdFieldTmp->type == 'checkbox' && is_array($config['post'][$fieldName])) || ($sdFieldTmp->type == 'radio' && is_array($config['post'][$fieldName]))){
									$proceed = false;
								}else{
									$artId = trim($config['post'][$matches[2]]);
								}
							}elseif(ctype_digit($fieldName)){
								$artid = $fieldName;
							}
							// END EXTERNAL FIELD
							if($proceed){
								$db = JFactory::getDBO();
								$db->setQuery("SELECT $colName FROM #__content WHERE id = $artId LIMIT 1");
								$db->query();
								$result = $db->loadResult();
								$sdConcatValue .= $result.$sdFieldSeparator;
							}
						}
						else{
							$fieldName = trim(strtolower($matches[2]));
							$colName = $matches[1];
							if(ctype_digit($fieldName)){
								$artId = $fieldName;
								$db = JFactory::getDBO();
								$db->setQuery("SELECT $colName FROM #__content WHERE id = $artId LIMIT 1");
								$db->query();
								$result = $db->loadResult();
								$sdConcatValue .= $result.$sdFieldSeparator;
							}
						}
					}
				endif;
			endforeach;
		else: 
			/* SINGLE FIELDS*/
				$sdConcatValue = '';
				$sdNumFields = 1;
				$sdNumStep = 0;
				$sdField = trim($sdFieldList);
				if(preg_match("^<(.*?)>^", $sdField, $matches)):
				$aitable = $matches[1];
				/* Next Auto Increment */
				$sebid = $config['pk'];
				if($sebid == 0){	// Are we creating a new article? Yes
					$inifile = dirname(__FILE__).'/increments.ini';
					if(JFile::exists($inifile)){
						chmod($inifile, 0644);
							$ini = parse_ini_file($inifile);
							$db = JFactory::getDbo();
							$pfx = $db->getPrefix();
							// get auto increment of users desired table
							$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx.$aitable."'");
							$db->query();
							$dbresult = $db->loadAssoc();
							$ai = $dbresult['Auto_increment'];

							// get auto increment of content table for this ID
							//$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx."content'");
							//$db->query();
							//$dbresult = $db->loadAssoc();
							//$artid = $dbresult['Auto_increment'];
							$ini[$ai] = $ai;
							$fopen = fopen($inifile, 'w');
							$ini_in = "";
							foreach($ini as $key=>$val){
								$ini_in .= $key."=".$val."\r\n";
							}
							fwrite($fopen, $ini_in);
							fclose($fopen);
							//$ai = $artid;					
					};
				}else{ // We are updating an article
					if($aitable > ''){

						$sebid = $config['pk'];
						//$ai = $sebid;
						$db = JFactory::getDbo();
						$pfx = $db->getPrefix();
						$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx.$aitable."'");
						$db->query();
						$dbresult = $db->loadAssoc();
						$content_ai = $dbresult['Auto_increment'];

						//$db->setQuery("SHOW TABLE STATUS LIKE '".$pfx."content'");
						//$db->query();
						//$dbresult = $db->loadAssoc();
						//$content_ai = $dbresult['Auto_increment'];
						$inifile = dirname(__FILE__).'/increments.ini';
						if(JFile::exists($inifile)){
							chmod($inifile, 0644);
							$ini = parse_ini_file($inifile); //get ini values as array
							if(array_key_exists($sebid, $ini)){
								if($ini[$sebid] > '' && !$ini[$sebid] == 0){
									$ai = $ini[$sebid];
								}else{
									$ini_in = "";
									if(!is_array($ini)){
										$ini = array();
									}
									$ini[$sebid] = $ai; //$ai for next auto increment
									foreach($ini as $key=>$value){
										$ini_in .= $key."=".$value."\r\n";
									}
									$fopen = fopen($inifile, 'w');
									fwrite($fopen, $ini_in);
									fclose($fopen);	
								}
							}else{
								$ini_in = "";
								$ini[$sebid] = $sebid; //$ai for next auto increment
								foreach($ini as $key=>$value){
									$ini_in .= $key."=".$value."\r\n";
								}
								$fopen = fopen($inifile, 'w');
								fwrite($fopen, $ini_in);
								fclose($fopen);	
							}
						};
						
					}else{
						$ai = "";
					};
				};
				
				$sdField = $ai;
				($sdField > '' ? $sdConcatValue .= $sdField.($sdNumStep < $sdNumFields ? $sdFieldSeparator : null) : null);
				/* End Next Auto Increment */
				
				elseif(preg_match("^[\[\]]^",$sdField )):
					$sdField = str_replace(array('[',']'), array('',''), $sdField );
					$sdField = ($SdJtext == 1 ? JText::_('PLG_CCK_FIELD_SD_FIELD_CONCAT_'.str_replace(array(';',':','`','~','}','{','>','<','!','=',',','-'),'',trim(strtoupper(str_replace(' ', '_', $sdField))))) : $sdField);
					($sdField > '' ? $sdConcatValue .= $sdField.($sdNumStep < $sdNumFields && $sdField > '' ? $sdFieldSeparator : null) : null);
				elseif(preg_match("/^@(.*?)\((.*?)\)/i", $sdField, $matches)):
					if(count($matches) > 0){
						if(trim($config['post'][$matches[2]]) !== ""){
							$colName = $matches[1];
							$fieldName = trim(strtolower($matches[2]));
							$proceed = true;
							// START EXTERNAL FIELD
							// What type of field is this?
								$sdFieldTmp = JCckDevField::getObject($fieldName);
								if($sdFieldTmp->type == 'select_simple' || $sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade' || ($sdFieldTmp->type == 'radio' && !is_array($config['post'][$sdField])) || ($sdFieldTmp->type == 'checkbox' && !is_array($config['post'][$sdField])) || $sdFieldTmp->type == 'select_dynamic_cascade'){
									if($sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade'){
										// JSON > Array
										$fieldOpt2 = JCckDev::fromJSON($sdFieldTmp->options2);
										// Set Query to get select dynamic criteria (thanks Lionel!)
										$query = 'SELECT '.$fieldOpt2['value'].' FROM '.$fieldOpt2['table'].' WHERE '.$fieldOpt2['value'].'="'.$config['post'][$fieldName].'"';
										// Get database object as string (thanks SEBLOD devs!)
										$dynamicText =  JCckDatabase::loadResult($query);
										if(trim($dynamicText) > ''):
												$artId = $dynamicText;
										endif;
										$sdFieldTmp = '';
									}else{
										($fieldName > '' && trim($config['post'][$fieldName]) > '') ? $artId .= trim($config['post'][$fieldName]) : NULL;
										$sdFieldTmp = '';
									}
								}elseif($sdFieldTmp->type == 'select_multiple' || ($sdFieldTmp->type == 'checkbox' && is_array($config['post'][$fieldName])) || ($sdFieldTmp->type == 'radio' && is_array($config['post'][$fieldName]))){
									$proceed = false;
								}else{
									$artId = trim($config['post'][$matches[2]]);
								}
							// END EXTERNAL FIELD
							if($proceed){
								$db = JFactory::getDBO();
								$db->setQuery("SELECT $colName FROM #__content WHERE id = $artId LIMIT 1");
								$db->query();
								$result = $db->loadResult();
								$sdConcatValue .= $result;
							}
						}
						else{
							$fieldName = trim(strtolower($matches[2]));
							$colName = $matches[1];
							if(ctype_digit($fieldName)){
								$artId = $fieldName;
								$db = JFactory::getDBO();
								$db->setQuery("SELECT $colName FROM #__content WHERE id = $artId LIMIT 1");
								$db->query();
								$result = $db->loadResult();
								$sdConcatValue .= $result;
							}
						}
					};
				elseif(preg_match("^[\$]^", $sdField)):
					$sdFind = array('$date', '$time', '$username', '$userid');
					$sdReplace = array(date($SdDateFormat), date('H:i:s'), ($user->username > '' ? $user->username : ''), ($user->id > '' ? $user->id : ''));
					$sdField = str_replace($sdFind, $sdReplace, $sdField);
					($sdField > '' ? $sdConcatValue .= $sdField.($sdNumStep < $sdNumFields ? $sdFieldSeparator : null) : null);
				elseif(preg_match("^[#(.*)#]^", $sdField)):
					$sdField = trim(str_replace('#','',$sdField));
					$sdFieldTmp = JCckDevField::getObject($sdField);
					if($sdFieldTmp->type == 'select_simple' || $sdFieldTmp->type == 'select_dynamic' || ($sdFieldTmp->type == 'radio' && !is_array($config['post'][$sdField])) || ($sdFieldTmp->type == 'checkbox' && !is_array($config['post'][$sdField])) || $sdFieldTmp->type == 'select_dynamic_cascade'){
							switch($sdFieldTextValue){
								case 't':
									if($sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade'){
										// JSON > Array
										$fieldOpt2 = JCckDev::fromJSON($sdFieldTmp->options2);
										// Set Query to get select dynamic criteria (thanks Lionel!)
										$query = 'SELECT '.$fieldOpt2['name'].' FROM '.$fieldOpt2['table'].' WHERE '.$fieldOpt2['value'].'="'.$config['post'][$sdField].'"';
										// Get database object as string (thanks SEBLOD devs!)
										$dynamicText =  JCckDatabase::loadResult($query);
										if(trim($dynamicText) > ''):
											$sdConcatValue .= $dynamicText;
											$sdConcatValue .= ($sdNumStep < $sdNumFields ? $sdFieldSeparator : null);
										endif;
										$sdFieldTmp = '';
									}else{
										$sdFieldTmp = parent::g_getOptionText( trim($config['post'][$sdField]), $sdFieldTmp->options, '', $config );
										if(trim($sdFieldTmp) > ''):
											$sdConcatValue .= $sdFieldTmp;
										endif;
										$sdFieldTmp = '';
									}
								break;
								
								case 'v':
									if($sdFieldTmp->type == 'select_dynamic' || $sdFieldTmp->type == 'select_dynamic_cascade'){
										// JSON > Array
										$fieldOpt2 = JCckDev::fromJSON($sdFieldTmp->options2);
										// Set Query to get select dynamic criteria (thanks Lionel!)
										$query = 'SELECT '.$fieldOpt2['value'].' FROM '.$fieldOpt2['table'].' WHERE '.$fieldOpt2['value'].'="'.$config['post'][$sdField].'"';
										// Get database object as string (thanks SEBLOD devs!)
										$dynamicText =  JCckDatabase::loadResult($query);
										if(trim($dynamicText) > ''):
											$sdConcatValue .= $dynamicText;
											$sdConcatValue .= ($sdNumStep < $sdNumFields ? $sdFieldSeparator : null);
										endif;
										$sdFieldTmp = '';
									}else{
										($sdField > '' && trim($config['post'][$sdField]) > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields && trim($config['post'][$sdField]) > '' ? $sdFieldSeparator : null) : null);
									}
								break;
								
								default:
									($sdField > '' && trim($config['post'][$sdField]) > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields && trim($config['post'][$sdField]) > '' ? $sdFieldSeparator : null) : null);
								break;
							};
					}elseif($sdFieldTmp->type == 'select_multiple' || ($sdFieldTmp->type == 'checkbox' && is_array($config['post'][$sdField])) || ($sdFieldTmp->type == 'radio' && is_array($config['post'][$sdField]))){
							switch($sdFieldTextValue){
								case 't':
									$SdValHolder = "";
									$SdArrayCount = count($config['post'][$sdField]);
									$s = 0;
									foreach($config['post'][$sdField] as $selectOption){
										$s++;
										$tempsep = "";
										$sdFieldSeparator == "ns" || $sdFieldSeparator == "" ? $tempsep = " " : $tempsep = $sdFieldSeparator;
										$SdValHolder .= parent::g_getOptionText( trim($selectOption), $sdFieldTmp->options, '', $config ).($s !== $SdArrayCount ? $tempsep : NULL);
									}
									if(trim($SdValHolder) > ''):
										$sdConcatValue .= $SdValHolder;
									endif;
									$SdValHolder = '';
								break;
								
								case 'v':
									$SdValHolder = "";
									$SdArrayCount = count($config['post'][$sdField]);
									$s = 0;
									foreach($config['post'][$sdField] as $selectOption){
										$s++;
										$tempsep = "";
										$sdFieldSeparator == "ns" || $sdFieldSeparator == "" ? $tempsep = " " : $tempsep = $sdFieldSeparator;
										$SdValHolder .= trim($selectOption).($s !== $SdArrayCount ? $tempsep : NULL);
									}
									if(trim($sdConcatValue) > ''):
										$sdConcatValue .= $SdValHolder;
									endif;
									$SdValHolder = '';
								break;
								
								default:
									//($sdField > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields ? $sdFieldSeparator : null) : null);
								break;
							};
						}
					else{
						($sdField > '' && trim($config['post'][$sdField]) > '' ? $sdConcatValue .= trim($config['post'][$sdField]).($sdNumStep < $sdNumFields && trim($config['post'][$sdField]) > '' ? $sdFieldSeparator : null) : null);
						
					};				
				endif;
				/* END SINGLE FIELD */
		endif;
		
		// The following code checks to see if the final value has the field seperator at the end, and if it does, it removes it...
				
		$finalValue = substr($sdConcatValue, -(strlen($sdFieldSeparator)));
		
		if($finalValue == $sdFieldSeparator){
			$finalValue = substr($sdConcatValue, 0, -(strlen($sdFieldSeparator)));
		}else{
			$finalValue = $sdConcatValue;
		}

		$value = $finalValue; // Grrrt, over and out, all done...
		
		####################################################################
		// END SD FIELD CONCAT - SIMON DOWDLES - http://www.simondowdles.com
		####################################################################
		
		// Init
		if ( count( $inherit ) ) {
			$name	=	( @$inherit['name'] != '' ) ? $inherit['name'] : $field->name;
		} else {
			$name	=	$field->name;
		}
		
		// Validate
		parent::g_onCCK_FieldPrepareStore_Validation( $field, $name, $value, $config );
		
		// Set or Return
		if ( $return === true ) {
			return $value;
		}
		$field->value = $value;
		
		parent::g_onCCK_FieldPrepareStore( $field, $name, $value, $config );
	
	}
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Render
	
	// onCCK_FieldRenderContent
	public static function onCCK_FieldRenderContent( $field, &$config = array() )
	{
		return parent::g_onCCK_FieldRenderContent( $field );
	}
	
	// onCCK_FieldRenderForm
	public static function onCCK_FieldRenderForm( $field, &$config = array() )
	{		
		return parent::g_onCCK_FieldRenderForm( $field );
	}
	
	// onCCK_FieldBeforeStore
	public static function onCCK_FieldBeforeStore( $process, &$fields, &$storages, &$config = array() )
	{
	}
	
	// onCCK_FieldAfterStore
	public static function onCCK_FieldAfterStore( $process, &$fields, &$storages, &$config = array() )
	{
	}
	
	// onCCK_FieldPrepareSearch
	public function onCCK_FieldPrepareSearch( &$field, $value = '', &$config = array(), $inherit = array(), $return = false )
	{
		if ( self::$type != $field->type ) {
			return;
		}
		
		// Prepare
		self::onCCK_FieldPrepareForm( $field, $value, $config, $inherit, $return );
		
		// Return
		if ( $return === true ) {
			return $field;
		}
	}

}

?>