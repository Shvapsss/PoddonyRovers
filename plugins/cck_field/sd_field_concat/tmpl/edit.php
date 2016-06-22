<?php
/**
* @package			SD Field Concat V2.5.3 for SEBLOD 3.x - www.seblod.com
* @license 			GNU General Public License version 2 or later
* @author       	Simon Dowdles - http://www.simondowdles.com
* @copyright		Copyright (C) 2013 Simon Dowdles New Media Holdings (Pty) Ltd. All Rights Reserved.
**/

// No Direct Access
defined( '_JEXEC' ) or die;
?>

<?php
$options2	=	CCK_Dev::fromJSON( $this->item->options2 );
//$config['doTranslation'] = 0;
?>

<div class="seblod">
	<?php echo JCckDev::renderLegend( JText::_( 'COM_CCK_CONSTRUCTION' ), JText::_( 'PLG_CCK_FIELD_'.$this->item->type.'_DESC' ) ); ?>
	<ul class="adminformlist adminformlist-2cols">
		<?php
		echo JCckDev::renderForm('sd_core_field_concat_list', @$options2['sd_core_field_concat_list'], $config);
		echo JCckDev::renderForm('sd_core_field_concat_separator', @$options2['sd_core_field_concat_separator'], $config);
		echo JCckDev::renderForm('sd_core_field_concat_date_format', @$options2['sd_core_field_concat_date_format'], $config);
		echo JCckDev::renderForm('sd_core_field_concat_enable_jtext', @$options2['sd_core_field_concat_enable_jtext'], $config);
		echo JCckDev::renderForm('sd_core_field_concat_text_value', @$options2['sd_core_field_concat_text_value'], $config);
		echo JCckDev::renderForm('sd_core_field_concat_process_edit', @$options2['sd_core_field_concat_process_edit'], $config);
		echo JCckDev::renderSpacer( JText::_( 'COM_CCK_STORAGE' ), JText::_( 'COM_CCK_STORAGE_DESC' ) );
		echo JCckDev::getForm( 'core_storage', $this->item->storage, $config );
        ?>
    </ul>
</div>
