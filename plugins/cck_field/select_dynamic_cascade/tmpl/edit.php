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

$options2	=	JCckDev::fromJSON( $this->item->options2 );
if ( isset( $options2['method'] ) ) {
	$options2['method']	=	str_replace( array( '_GET', '_POST' ), array( 'get', 'post' ), $options2['method'] );
}
?>

<div class="seblod">
    <?php echo JCckDev::renderLegend( JText::_( 'COM_CCK_CONSTRUCTION' ), JText::_( 'PLG_CCK_FIELD_'.$this->item->type.'_DESC' ) ); ?>
    <ul class="adminformlist adminformlist-2cols">
        <?php
		echo JCckDev::renderForm( 'core_label', $this->item->label, $config );
		echo JCckDev::renderForm( 'core_defaultvalue', $this->item->defaultvalue, $config );
		echo JCckDev::renderForm( 'core_query', $this->item->bool2, $config );
		echo '<li><label>'.JText::_( 'COM_CCK_SELECT_LABEL' ).'</label>'
		 .	 JCckDev::getForm( 'core_selectlabel', $this->item->selectlabel, $config, array( 'size'=>'20' ) )
		 .	 JCckDev::getForm( 'core_bool', $this->item->bool6, $config, array( 'defaultvalue'=>0, 'options'=>'Always=0||Auto Hide=1', 'storage_field'=>'bool6', 'attributes'=>'style="max-width:88px;"' ) )
		 .	 '</li>';

		// Ajax Setting
		echo JCckDev::renderForm( 'core_cascade_behavior', @$options2['rank'], $config );
		echo JCckDev::renderForm( 'core_options_value', @$options2['group'], $config, array( 'label'=>'GROUP_IDENTIFIER', 'defaultvalue'=>'', 'storage_field'=>'json[options2][group]' ) );

		// 1
		echo JCckDev::renderForm( 'core_options_query', @$options2['query'], $config, array(), array(), 'w100' );
		// 2
		echo JCckDev::renderForm( 'core_options_table', @$options2['table'], $config, array( 'required'=>'required' ) );
		echo JCckDev::renderForm( 'core_options_name', @$options2['name'], $config, array( 'required'=>'required' ) );
		echo JCckDev::renderForm( 'core_options_where', @$options2['where'], $config );
		echo JCckDev::renderForm( 'core_options_where', @$options2['and'], $config, array( 'label'=>'Where', 'storage_field'=>'json[options2][and]' ) );
		echo JCckDev::renderForm( 'core_options_value', @$options2['value'], $config, array( 'required'=>'required' ) );	
		echo '<li><label>'.JText::_( 'COM_CCK_ORDER_BY' ).'</label>'
		.	 JCckDev::getForm( 'core_options_orderby', @$options2['orderby'], $config )
		.	 JCckDev::getForm( 'core_options_orderby_direction', @$options2['orderby_direction'], $config )
		.	 '</li>';
		echo JCckDev::renderForm( 'core_options_where', @$options2['parent'], $config, array( 'label'=>'Parent', 'required'=>'required', 'storage_field'=>'json[options2][parent]' ) );
		echo JCckDev::renderBlank( '<input type="hidden" id="blank_li" value="" />' );

		// Multiple
		echo '<li style="display:none;">'
		 .	 JCckDev::getForm( 'core_bool3', $this->item->bool3, $config, array( 'label'=>'Multiple' ) )
		 .	 JCckDev::getForm( 'core_rows', $this->item->rows, $config )
		 .	 JCckDev::getForm( 'core_separator', $this->item->divider, $config )
		 .	 '</li>';

		// Language
		echo JCckDev::renderForm( 'core_options_language_detection', @$options2['language_detection'], $config );
		echo '<li><label>'.JText::_( 'COM_CCK_LANGUAGE_CODES_DEFAULT' ).'</label>'
		 .	 JCckDev::getForm( 'core_options_language_codes', @$options2['language_codes'], $config, array( 'size' => 21 ) )
		 .	 JCckDev::getForm( 'core_options_language_default', @$options2['language_default'], $config, array( 'size' => 5 ) )
		 .	 '</li>';

		echo JCckDev::renderForm( 'core_method', @$options2['method'], $config );

        echo JCckDev::renderSpacer( JText::_( 'COM_CCK_STORAGE' ), JText::_( 'COM_CCK_STORAGE_DESC' ) );
        echo JCckDev::getForm( 'core_storage', $this->item->storage, $config );
        ?>
	</ul>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	if ($("#bool2").val() == 1) {
		$('#json_options2_query').parent().show();
		$('#json_options2_table, #json_options2_name, #json_options2_where, #json_options2_orderby, #blank_li, #json_options2_and').parent().hide();
	} else {
		$('#json_options2_query').parent().hide();
		$('#json_options2_table, #json_options2_name, #json_options2_where, #json_options2_orderby').parent().show();
		if ($("#json_options2_rank").val() == 0) {
			$('#json_options2_where,#blank_li').parent().show();
			$('#json_options2_and').parent().hide();
		} else {
			$('#json_options2_where,#blank_li').parent().hide();
			$('#json_options2_and').parent().show();
		}
	}
	$("div#layer").on("change", "#bool2", function() {
		if ($(this).val() == 1) {
			$('#json_options2_query').parent().show();
			$('#json_options2_table, #json_options2_name, #json_options2_where, #json_options2_orderby, #blank_li, #json_options2_and').parent().hide();
		} else {
			$('#json_options2_query').parent().hide();
			$('#json_options2_table, #json_options2_name, #json_options2_where, #json_options2_orderby').parent().show();
			if ($("#json_options2_rank").val() == 0) {
				$('#json_options2_where,#blank_li').parent().show();
				$('#json_options2_and').parent().hide();
			} else {
				$('#json_options2_where,#blank_li').parent().hide();
				$('#json_options2_and').parent().show();
			}
		}
	});
	$("div#layer").on("change", "#json_options2_rank", function() {
		if ($(this).val() == 1 || $(this).val() == 2) {
			if ($("#bool2").val() == 0) {
				$('#json_options2_and').parent().show();
			} else {
				$('#json_options2_and').parent().hide();
			}
			$('#json_options2_where,#blank_li').parent().hide();
		} else {
			if (!$("#bool2").val() == 0) {
				$('#json_options2_where,#blank_li').parent().show();
			} else {
				$('#json_options2_where,#blank_li').parent().hide();
			}
			$('#json_options2_and').parent().hide();
		}
	});
	$('#json_options2_parent').isVisibleWhen('json_options2_rank','1,2');
	$('#json_options2_method').isVisibleWhen('json_options2_rank','2');
});
</script>