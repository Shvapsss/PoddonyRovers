<?php
/**
* @version 			SEBLOD 3.x Core ~ $Id: script.php sebastienheraud $
* @package			SEBLOD (App Builder & CCK) // SEBLOD nano (Form Builder)
* @url				http://www.seblod.com
* @editor			Octopoos - www.octopoos.com
* @copyright		Copyright (C) 2013 SEBLOD. All Rights Reserved.
* @license 			GNU General Public License version 2 or later; see _LICENSE.php
**/

defined( '_JEXEC' ) or die;
defined( 'CCK_COM' ) or define( 'CCK_COM', 'com_cck' );

jimport( 'joomla.filesystem.file' );
jimport( 'joomla.filesystem.folder' );

// Script
class plgContentCCKInstallerScript
{
	// install
	function install( $parent )
	{
		$data	=	"<!DOCTYPE html><title></title>";
		$groups	=	array( 'cck_field', 'cck_field_link', 'cck_field_live', 'cck_field_restriction', 'cck_field_typo', 'cck_field_validation', 'cck_storage', 'cck_storage_location' );
		foreach ( $groups as $group ) {
			JFile::write( JPATH_PLUGINS.'/'.$group.'/'.'index.html', $data );	
		}
	}
	
	// uninstall
	function uninstall( $parent )
	{
		if ( JFile::exists( JPATH_ADMINISTRATOR.'/language/en-GB/en-GB.lib_cck.ini' ) ) {
			JFile::delete( JPATH_ADMINISTRATOR.'/language/en-GB/en-GB.lib_cck.ini' );
		}
		if ( JFile::exists( JPATH_ADMINISTRATOR.'/language/fr-FR/fr-FR.lib_cck.ini' ) ) {
			JFile::delete( JPATH_ADMINISTRATOR.'/language/fr-FR/fr-FR.lib_cck.ini' );
		}
		
		$groups	=	array( 'cck_field', 'cck_field_link', 'cck_field_live', 'cck_field_restriction', 'cck_field_typo', 'cck_field_validation', 'cck_storage', 'cck_storage_location' );
		foreach ( $groups as $group ) {
			if ( JFolder::exists( JPATH_PLUGINS.'/'.$group ) ) {
				JFolder::delete( JPATH_PLUGINS.'/'.$group );
			}
		}
	}
	
	// update
	function update( $parent )
	{		
	}
	
	// preflight
	function preflight( $type, $parent )
	{
		// WAITING FOR JOOMLA 1.7.x FIX
		$app		=	JFactory::getApplication();
		$config		=	JFactory::getConfig();
		$tmp_path	=	$config->get( 'tmp_path' );
		$tmp_dir 	=	$app->cck_core_temp_var;
		$path 		= 	$tmp_path.'/'.$tmp_dir;
		$dest		=	JPATH_SITE.'/libraries/cck/rendering/variations';
		$protected	=	array( 'empty' );
		if ( $tmp_dir && JFolder::exists( $path ) ) {
			$vars		=	JFolder::folders( $path );
			foreach ( $vars as $var ) {
				if ( ! in_array( $var, $protected ) ) {
					JFolder::move( $path.'/'.$var, $dest.'/'.$var );
				}
			}
			JFolder::delete( $path );
		}
		// WAITING FOR JOOMLA 1.7.x FIX
	}
	
	// postflight
	function postflight( $type, $parent )
	{
		$app	=	JFactory::getApplication();
		$db		=	JFactory::getDbo();
		
		// Force { CCK } Plugins + { CCK } Library to be published
		$db->setQuery( 'UPDATE #__extensions SET enabled = 1 WHERE element = "cck"' );
		$db->execute();
		
		// Rename Menu Item
		$db->setQuery( 'UPDATE #__menu SET alias = "SEBLOD 3.x", path="SEBLOD 3.x" WHERE link = "index.php?option=com_cck"' );
		$db->execute();
		// Re-build menu
		$query	=	'SELECT id, level, lft, path FROM #__menu WHERE link = "index.php?option=com_cck"';
		$db->setQuery( $query );
		$seblod	=	$db->loadObject();
		if ( $seblod->id > 0 ) {		
			$query	=	'SELECT extension_id as id, element FROM #__extensions WHERE type = "component" AND element LIKE "com_cck_%" ORDER BY name';
			$db->setQuery( $query );
			$addons	=	$db->loadObjectList();
			if ( count( $addons ) ) {			
				JLoader::register( 'JTableMenu', JPATH_PLATFORM.'/joomla/database/table/menu.php' );
				$titles	=	array(
								'com_cck_builder'=>'Builder',
								'com_cck_developer'=>'Developer',
								'com_cck_ecommerce'=>'eCommerce',
								'com_cck_exporter'=>'Exporter',
								'com_cck_importer'=>'Importer',
								'com_cck_manager'=>'Manager',
								'com_cck_multilingual'=>'Multilingual',
								'com_cck_packager'=>'Packager',
								'com_cck_toolbox'=>'Toolbox',
								'com_cck_updater'=>'Updater',
								'com_cck_webservices'=>'WebServices'
							);
				foreach ( $addons as $addon ) {
					$addon->title	=	$titles[$addon->element];
					self::_addAddon( $addon, $seblod );
				}
			}
		}	
		
		// Reorder Plugins
		$i		=	2;
		$ids	=	'';
		$query	=	'SELECT extension_id FROM #__extensions WHERE type = "plugin" AND folder = "content" AND element != "cck" ORDER BY ordering';
		$db->setQuery( $query );
		$plgs	=	$db->loadObjectList();
		$sql	=	'UPDATE #__extensions SET ordering = CASE extension_id';
		foreach ( $plgs as $p ) {
			$ids	.=	$p->extension_id.',';
			$sql	.=	' WHEN '.$p->extension_id.' THEN '.$i;
			$i++;
		}
		$ids	=	substr( $ids, 0, -1 );
		$sql	.=	' END WHERE extension_id IN ('.$ids.')';
		$db->setQuery( $sql );
		$db->execute();			
		$db->setQuery( 'UPDATE #__extensions SET ordering = 1 WHERE type = "plugin" AND folder = "content" AND element = "cck"' );
		$db->execute();
		
		if ( $type == 'install' ) {
			// Manage Modules
			$modules	=	array(	0=>array( 'name'=>'mod_cck_menu', 'update'=>'title = "Admin Menu - SEBLOD", access = 3, published = 1, position = "menu", ordering = 2' ),
									1=>array( 'name'=>'mod_cck_quickadd', 'update'=>'title = "Quick Add - SEBLOD", access = 3, published = 1, position = "status", ordering = 0' ),
									2=>array( 'name'=>'mod_cck_quickicon', 'update'=>'title = "Quick Icons - SEBLOD", access = 3, published = 1, position = "icon", ordering = 2' ),
									3=>array( 'name'=>'mod_cck_breadcrumbs', 'update'=>'title = "Breadcrumbs - SEBLOD"' ),
									4=>array( 'name'=>'mod_cck_form', 'update'=>'title = "Form - SEBLOD"' ),
									5=>array( 'name'=>'mod_cck_list', 'update'=>'title = "List - SEBLOD"' ),
									6=>array( 'name'=>'mod_cck_search', 'update'=>'title = "Search - SEBLOD"' ) );
			foreach ( $modules as $module ) {
				$query	=	'UPDATE #__modules SET '.$module['update'].' WHERE module = "'.$module['name'].'"';
				$db->setQuery( $query );
				$db->execute();
				$query	=	'SELECT id FROM #__modules WHERE module="'.$module['name'].'"';
				$db->setQuery( $query );
				$mid	=	$db->loadResult();
				$query	=	'INSERT INTO #__modules_menu (moduleid, menuid) VALUES ('.$mid.', 0)';
				$db->setQuery( $query );
				$db->execute();
			}
				
			// Publish Plugins
			$query	=	'UPDATE #__extensions SET enabled = 1 WHERE folder LIKE "cck_%"';
			$db->setQuery( $query );
			$db->execute();
			
			// Revert Version for Joomla! 2.5.x
			if ( !JCck::on() ) {
				$query	=	'SELECT id FROM #__cck_core_types WHERE version = 2 ORDER BY id';
				$db->setQuery( $query );
				$forms	=	$db->loadObjectList();
				if ( count( $forms ) ) {
					require_once JPATH_ADMINISTRATOR.'/components/com_cck/helpers/helper_version.php';
					foreach ( $forms as $f ) {
						Helper_Version::revert( 'type', $f->id, '1' );
					}
				}
			}

			// Set Template Styles
			$query	=	'SELECT id FROM #__template_styles WHERE template="seb_one" ORDER BY id';
			$db->setQuery( $query );
			$style	=	$db->loadResult();
			$query	=	'SELECT id FROM #__template_styles WHERE template="seb_blog" ORDER BY id';
			$db->setQuery( $query );
			$style2	=	$db->loadResult();
			//
			$query	=	'UPDATE #__cck_core_types SET template_admin = '.$style.', template_site = '.$style.', template_content = '.$style.', template_intro = '.$style;
			$db->setQuery( $query );
			$db->execute();
			//
			$query	=	'UPDATE #__cck_core_searchs SET template_search = '.$style.', template_filter = '.$style.', template_list = '.$style2.', template_item = '.$style;
			$db->setQuery( $query );
			$db->execute();
			
			// Add Categories
			$categories	=	array(	0=>array( 'title'=>'Users', 'published'=>'1', 'access'=>'2', 'language'=>'*', 'parent_id'=>1, 'plg_name'=>'joomla_user' ),
									1=>array( 'title'=>'User Groups', 'published'=>'1', 'access'=>'2', 'language'=>'*', 'parent_id'=>1, 'plg_name'=>'joomla_user_group' ) );
			JLoader::register( 'JTableCategory', JPATH_PLATFORM.'/joomla/database/table/category.php' );
			foreach ( $categories as $category ) {
				$table	=	JTable::getInstance( 'category' );
				$table->access	=	2;
				$table->setLocation( 1, 'last-child' );	
				$table->bind( $category );
				$rules	=	new JAccessRules( '{"core.create":{"1":0},"core.delete":[],"core.edit":[],"core.edit.state":[],"core.edit.own":[]}' );
				$table->setRules( $rules );
				$table->check();
				$table->extension	=	'com_content';
				$table->path		.=	$table->alias;
				$table->language	=	'*';
				$table->store();
				$dispatcher	=	JDispatcher::getInstance();
				JPluginHelper::importPlugin( 'content' );
				$dispatcher->trigger( 'onContentBeforeSave', array( '', &$table, true ) );
				$table->store();
				$dispatcher->trigger( 'onContentAfterSave', array( '', &$table, true ) );
				//
				$query			=	'SELECT extension_id as id, params FROM #__extensions WHERE type="plugin" AND folder="cck_storage_location" AND element="'.$category['plg_name'].'"';
				$db->setQuery( $query );
				$plugin			=	$db->loadObject();
				$params			=	str_replace( '"bridge_default-catid":"2"', '"bridge_default-catid":"'.$table->id.'"', $plugin->params );
				$query			=	'UPDATE #__extensions SET params = "'.$db->escape( $params ).'" WHERE extension_id = '.(int)$plugin->id;
				$db->setQuery( $query );
				$db->execute();
			}
			
			// Init Default Author
			$res	=	JCckDatabase::loadResult( 'SELECT id FROM #__users ORDER BY id ASC' );
			$params =	JComponentHelper::getParams( 'com_cck' );
			$params->set( 'integration_user_default_author', (int)$res );
			$db->setQuery( 'UPDATE #__extensions SET params = "'.$db->escape( $params ).'" WHERE name = "com_cck"' );
			$db->execute();
			
			// Init ACL
			require_once JPATH_ADMINISTRATOR.'/components/com_cck/helpers/helper_admin.php';
			$pks	=	JCckDatabase::loadColumn( 'SELECT id FROM #__cck_core_folders ORDER BY lft' );
			if ( count( $pks ) ) {
				$rules	=	'{"core.create":[],"core.delete":[],"core.delete.own":[],"core.edit":[],"core.edit.state":[],"core.edit.own":[]}';
				Helper_Admin::initACL( array( 'table'=>'folder', 'name'=>'folder', 'rules'=>$rules ), $pks );
			}
			$pks	=	JCckDatabase::loadColumn( 'SELECT id FROM #__cck_core_types ORDER BY id' );
			if ( count( $pks ) ) {
				$rules	=	'{"core.create":[],"core.create.max.parent":{"8":0},"core.create.max.parent.author":{"8":0},"core.create.max.author":{"8":0},'
						.	'"core.delete":[],"core.delete.own":[],"core.edit":[],"core.edit.own":[]}';
				$rules2	=	array( 8=>'{"core.create":{"1":1,"2":0},"core.create.max.parent":{"8":0},"core.create.max.parent.author":{"8":0},"core.create.max.author":{"8":0},'
									. '"core.delete":[],"core.delete.own":[],"core.edit":{"4":0},"core.edit.own":{"2":1}}' );
				Helper_Admin::initACL( array( 'table'=>'type', 'name'=>'form', 'rules'=>$rules ), $pks, $rules2 );
			}
		} else {
			$new		=	$app->cck_core_version;
			$old		=	$app->cck_core_version_old;
			$root		=	JPATH_ADMINISTRATOR.'/components/com_cck';
			require_once JPATH_ADMINISTRATOR.'/components/'.CCK_COM.'/helpers/helper_folder.php';

			// ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** //
			$versions	=	array(	0=>'2.0.0', 1=>'2.0.0.RC2', 2=>'2.0.0.RC2-1', 3=>'2.0.0.RC2-2', 4=>'2.0.0.RC2-3', 5=>'2.0.0.RC3', 6=>'2.0.0.RC4',
									7=>'2.0.0.GA', 8=>'2.0.5', 9=>'2.0.6', 10=>'2.0.7', 11=>'2.1.0', 12=>'2.1.5', 13=>'2.2.0', 14=>'2.2.5',
									15=>'2.3.0', 16=>'2.3.1', 17=>'2.3.5', 18=>'2.3.6', 19=>'2.3.7', 20=>'2.3.8', 21=>'2.3.9', 22=>'2.3.9.2',
									23=>'2.4.5', 24=>'2.4.6', 25=>'2.4.7', 26=>'2.4.8', 27=>'2.4.8.5', 28=>'2.4.9',
									29=>'2.4.9.1', 30=>'2.4.9.2', 31=>'2.4.9.5', 32=>'2.4.9.6', 33=>'2.4.9.7', 34=>'2.4.9.8', 35=>'2.5.0', 36=>'2.5.1', 37=>'2.5.2',
									38=>'2.6.0', 39=>'2.7.0', 40=>'2.8.0', 41=>'2.9.0', 42=>'3.0.0', 43=>'3.0.1', 44=>'3.0.2', 45=>'3.0.3', 46=>'3.0.4', 47=>'3.0.5',
									48=>'3.1.0', 49=>'3.1.1', 50=>'3.1.2', 51=>'3.1.3', 52=>'3.1.4', 53=>'3.1.5',
									54=>'3.2.0', 55=>'3.2.1', 56=>'3.2.2', 57=>'3.3.0', 58=>'3.3.1', 59=>'3.3.2', 60=>'3.3.3', 61=>'3.3.4', 62=>'3.3.5', 63=>'3.3.6', 64=>'3.3.7', 65=>'3.3.8',
									66=>'3.4.0', 67=>'3.4.1', 68=>'3.4.2', 69=>'3.4.3', 70=>'3.5.0', 71=>'3.5.1',
									72=>'3.6.0', 73=>'3.6.1', 74=>'3.6.2', 75=>'3.6.3', 76=>'3.6.4', 77=>'3.6.5',
									78=>'3.7.0', 79=>'3.7.1', 80=>'3.7.2' );
			// ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** ******** //
			
			$i			=	array_search( $old, $versions );
			$i2			=	$i;
			$n			=	array_search( $new, $versions );
			if ( $i < 7 ) {		// ONLY < 2.0 GA
				$prefix	=	JFactory::getConfig()->get( 'dbprefix' );
				$tables	=	JCckDatabase::loadColumn( 'SHOW TABLES' );
				if ( count( $tables ) ) {
					foreach ( $tables as $table ) {
						if ( strpos( $table, $prefix.'cck_item_' ) !== false ) {
							$replace	=	str_replace( $prefix.'cck_item_', $prefix.'cck_store_item_', $table );
							if ( $replace ) {
								JCckDatabase::doQuery( 'ALTER TABLE '.$table.' RENAME '.$replace );
							}
						} elseif ( strpos( $table, $prefix.'cck_type_' ) !== false ) {
							$replace	=	str_replace( $prefix.'cck_type_', $prefix.'cck_store_form_', $table );
							if ( $replace ) {
								JCckDatabase::doQuery( 'ALTER TABLE '.$table.' RENAME '.$replace );
							}
						}
					}
				}
				
				$fields	=	JCckDatabase::loadObjectList( 'SELECT id, storage_table FROM #__cck_core_fields WHERE storage_table LIKE "#__cck_item_%"' );
				if ( count( $fields ) ) {
					foreach ( $fields as $field ) {
						$replace	=	str_replace( '#__cck_item_', '#__cck_store_item_', $field->storage_table );
						JCckDatabase::doQuery( 'UPDATE #__cck_core_fields SET storage_table = "'.$replace.'" WHERE id = '.(int)$field->id );
					}
				}
				$fields	=	JCckDatabase::loadObjectList( 'SELECT id, storage_table FROM #__cck_core_fields WHERE storage_table LIKE "#__cck_type_%"' );
				if ( count( $fields ) ) {
					foreach ( $fields as $field ) {
						$replace	=	str_replace( '#__cck_type_', '#__cck_store_form_', $field->storage_table );
						JCckDatabase::doQuery( 'UPDATE #__cck_core_fields SET storage_table = "'.$replace.'" WHERE id = '.(int)$field->id );
					}
				}
				$fields	=	JCckDatabase::loadObjectList( 'SELECT id, options2 FROM #__cck_core_fields WHERE type = "select_dynamic"' );
				if ( count( $fields ) ) {
					foreach ( $fields as $field ) {
						$options2		=	$field->options2;
						if ( strpos( $options2, '#__cck_item_' ) !== false ) {
							$options2	=	str_replace( '#__cck_item_', '#__cck_store_item_', $options2 );
						}
						if ( strpos( $options2, '#__cck_type_' ) !== false ) {
							$options2	=	str_replace( '#__cck_type_', '#__cck_store_form_', $options2 );
						}
						if ( $options2 != $field->options2 ) {
							JCckDatabase::doQuery( 'UPDATE #__cck_core_fields SET options2 = "'.$db->escape( $options2 ).'" WHERE id = '.(int)$field->id );
						}
					}
				}
			}
			
			if ( $i < 23 ) {	// ONLY < 2.4.5
				JCckDatabase::doQuery( 'ALTER TABLE #__cck_core_folders ADD path VARCHAR( 1024 ) NOT NULL AFTER parent_id;' );
				require_once JPATH_ADMINISTRATOR.'/components/'.CCK_COM.'/helpers/helper_folder.php';
				$folders	=	JCckDatabase::loadColumn( 'SELECT id FROM #__cck_core_folders WHERE lft ORDER BY lft' );
				foreach ( $folders as $f ) {
					$path	=	Helper_Folder::getPath( $f, '/' );
					JCckDatabase::doQuery( 'UPDATE #__cck_core_folders SET path = "'.$path.'" WHERE id = '.(int)$f );
				}
				if ( JCckDatabase::doQuery( 'INSERT IGNORE #__cck_core_folders (id) VALUES (29)' ) ) {
					require_once JPATH_ADMINISTRATOR.'/components/'.CCK_COM.'/tables/folder.php';
					$folder			=	JTable::getInstance( 'folder', 'CCK_Table' );
					$folder->load( 29 );
					$folder_data	=	array( 'parent_id'=>13, 'path'=>'joomla/user/profile', 'title'=>'Profile', 'name'=>'profile', 'color'=>'#0090d1',
											   'introchar'=>'U.', 'colorchar'=>'#ffffff', 'elements'=>'field', 'featured'=>0, 'published'=>1 );
					$rules	=	new JAccessRules( '{"core.create":[],"core.delete":[],"core.edit":[],"core.edit.state":[],"core.edit.own":[]}' );
					$folder->setRules( $rules );
					$folder->bind( $folder_data );
					$folder->store();
				}
			}
			
			for ( $i = $i + 1; $i <= $n; $i++ ) {
				$file		=	$root.'/install/upgrades/'.strtolower( $versions[$i] ).'.sql';
				if ( JFile::exists( $file ) ) {
					$buffer		=	file_get_contents( $file );
					$queries	=	JInstallerHelper::splitSql( $buffer );
					foreach ( $queries as $query ) {
						$query	=	trim( $query );
						if ( $query != '' && $query{0} != '#' ) {
							$db->setQuery( $query );
							$db->execute();
						}
					}
				}
			}
			
			if ( $i2 < 23 ) {	// ONLY < 2.4.5
				$bool	=	true;
				$live	=	JCckDatabase::loadObjectList( 'SELECT typeid, fieldid, client, live, live_value FROM #__cck_core_type_field WHERE live IN ("url_var_int","url_var_string","user_profile")' );
				if ( count( $live ) ) {
					foreach ( $live as $l ) {
						if ( $l->live == 'user_profile' ) {
							$live_type		=	'joomla_user';
							$live_options	=	'{"content":"","property":"'.$l->live_value.'"}';
						} elseif ( $l->live == 'url_var_int' ) {
							$live_type		=	'url_variable';
							$live_options	=	'{"variable":"'.$l->live_value.'","type":"int"}';
						} elseif ( $l->live == 'url_var_string' ) {
							$live_type		=	'url_variable';
							$live_options	=	'{"variable":"'.$l->live_value.'","type":"string"}';
						}
						if ( !JCckDatabase::doQuery( 'UPDATE #__cck_core_type_field SET live = "'.$live_type.'", live_options = "'.$db->escape( $live_options ).'" WHERE typeid = '.$l->typeid.' AND fieldid = '.$l->fieldid.' AND client = "'.$l->client.'"' ) ) {
							$bool	=	false;
						}
					}
				}
				$live	=	JCckDatabase::loadObjectList( 'SELECT searchid, fieldid, client, live, live_value FROM #__cck_core_search_field WHERE live IN ("url_var_int","url_var_string","user_profile")' );
				if ( count( $live ) ) {
					foreach ( $live as $l ) {
						if ( $l->live == 'user_profile' ) {
							$live_type		=	'joomla_user';
							$live_options	=	'{"content":"","property":"'.$l->live_value.'"}';
						} elseif ( $l->live == 'url_var_int' ) {
							$live_type		=	'url_variable';
							$live_options	=	'{"variable":"'.$l->live_value.'","type":"int"}';
						} elseif ( $l->live == 'url_var_string' ) {
							$live_type		=	'url_variable';
							$live_options	=	'{"variable":"'.$l->live_value.'","type":"string"}';
						}
						if ( !JCckDatabase::doQuery( 'UPDATE #__cck_core_search_field SET live = "'.$live_type.'", live_options = "'.$db->escape( $live_options ).'" WHERE searchid = '.$l->searchid.' AND fieldid = '.$l->fieldid.' AND client = "'.$l->client.'"' ) ) {
							$bool	=	false;
						}
					}
				}
				if ( $bool ) {
					JCckDatabase::doQuery( 'UPDATE #__extensions SET enabled = 0 WHERE element IN ("url_var_int","url_var_string","user_profile") AND folder = "cck_field_live"' );
				}
			}
			if ( $i2 < 25 ) {
				$table	=	JTable::getInstance( 'asset' );
				$table->loadByName( 'com_cck' );
				if ( $table->rules ) {
					$rules	=	(array)json_decode( $table->rules );
					$rules['core.delete.own']	=	array( 6=>"1" );
					$table->rules	=	json_encode( $rules );
					$table->store();
				}
			}
			if ( $i2 < 31 ) {
				$src	=	JPATH_ADMINISTRATOR.'/components/com_cck/install/src/tmp/joomla_message';
				if ( JFolder::exists( $src ) ) {
					JFolder::copy( $src, JPATH_SITE.'/plugins/cck_storage_location/joomla_message', '', true );
				}
			}
			if ( $i2 < 33 ) {
				$folders	=	array( 10, 11, 12, 13, 14 );
				foreach ( $folders as $folder ) {
					Helper_Folder::rebuildBranch( $folder );
				}
			}
			
			if ( $i2 < 35 ) {
				$objects	=	array(
									'joomla_article'=>'article',
									'joomla_category'=>'category',
									'joomla_user'=>'user',
									'joomla_user_group'=>'user_group',
								);
				foreach ( $objects as $k=>$v ) {
					$params	=	JCckDatabase::loadResult( 'SELECT options FROM #__cck_core_objects WHERE name = "'.$k.'"' );
					$params	=	json_decode( $params );
					$params->default_type	=	JCck::getConfig_Param( 'integration_'.$v, '' );
					$params->add_redirect	=	( $params->default_type != '' ) ? '1' : '0';
					$params->edit			=	JCck::getConfig_Param( 'integration_'.$v.'_edit', '0' );
					if ( $k == 'joomla_category' ) {
						$params->exclude	=	JCck::getConfig_Param( 'integration_'.$v.'_exclude', '' );
					}
					JCckDatabase::doQuery( 'UPDATE #__cck_core_objects SET options = "'.$db->escape( json_encode( $params ) ).'" WHERE name = "'.$k.'"' );
				}
			}
			
			if ( $i2 < 45 ) {
				$table		=	'#__cck_store_item_users';
				$columns	=	$db->getTableColumns( $table );
				if ( isset( $columns['password2'] ) ) {
					JCckDatabase::doQuery( 'ALTER TABLE '.JCckDatabase::quoteName( $table ).' DROP '.JCckDatabase::quoteName( 'password2' ) );
				}
			}
			
			if ( $i2 < 66 ) {
				$path	=	JPATH_ADMINISTRATOR.'/components/com_cck/download.php';
				if ( JFile::exists( $path ) ) {
					JFile::delete( $path );
				}
			}
			
			if ( $i2 < 70 ) {
				$plg_image	=	JPluginHelper::getPlugin( 'cck_field', 'upload_image' );
				$plg_params	=	new JRegistry( $plg_image->params );

				$com_cck	=	JComponentHelper::getComponent( 'com_cck' );
				$com_cck->params->set( 'media_quality_jpeg', $plg_params->get( 'quality_jpeg', '90' ) );
				$com_cck->params->set( 'media_quality_png', $plg_params->get( 'quality_png', '3' ) );
				
				JCckDatabase::doQuery( 'UPDATE #__extensions SET params = "'.$db->escape( $com_cck->params->toString() ).'" WHERE type = "component" AND element = "com_cck"' );
			}
			
			// Folder Tree
			Helper_Folder::rebuildTree( 2, 1 );
		}
		
		// Overrides
		$path	=	JPATH_ADMINISTRATOR.'/components/com_cck/install/src';
		if ( JFolder::exists( $path ) ) {
			$folders	=	JFolder::folders( $path, '^joomla' );
			$folders	=	array_reverse( $folders );
			$count		=	count( $folders );
			foreach ( $folders as $folder ) {
				$version	=	str_replace( 'joomla', '', $folder );
				if ( version_compare( JVERSION, $version, 'lt' ) ) {
					$path	.=	'/'.$folder;
					$len	=	strlen( $path );
					$items	=	JFolder::files( $path, '.', true, true, array('index.html' ) );
					if ( count( $items ) ) {
						foreach ( $items as $item ) {
							$dest	=	JPATH_SITE.substr( $item, $len );
							JFile::copy( $item, $dest );
						}
					}
					break;
				}
			}
		}

		// Tmp
		$path	=	JPATH_ADMINISTRATOR.'/components/com_cck/install/src/tmp';
		if ( JFolder::exists( $path ) ) {
			JFolder::delete( $path );
		}
	}
	
	// _addAddon
	protected function _addAddon( $addon, $parent )
	{
		$db		=	JFactory::getDbo();
		$name	=	str_replace( 'com_cck_', '', $addon->element );
		$table	=	JTable::getInstance( 'menu' );
		
		$data	=	array( 'menutype'=>'main', 'title'=>'SEBLOD '.$addon->title, 'alias'=>$addon->title, 'path'=>'SEBLOD 3.x/'.$addon->title,
						   'link'=>'index.php?option=com_cck_'.$name, 'type'=>'component', 'published'=>0, 'parent_id'=>$parent->id,
						   'level'=>2, 'component_id'=>$addon->id, 'access'=>1, 'img'=>'class:component', 'client_id'=>1 );
		
		$table->setLocation( $data['parent_id'], 'last-child' );
		$table->bind( $data );
		$table->check();
		$table->alias	=	$addon->title;
		$table->path	=	'SEBLOD 3.x/'.$addon->title;
		$table->store();
		$table->rebuildPath( $table->id );
	}
}
?>