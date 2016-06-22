<?php
/**
* @version 			SEBLOD 3.x More ~ $Id: index.php sebastienheraud $
* @package			SEBLOD (App Builder & CCK) // SEBLOD nano (Form Builder)
* @url				http://www.seblod.com
* @editor			Octopoos - www.octopoos.com
* @copyright		Copyright (C) 2013 SEBLOD. All Rights Reserved.
* @license 			GNU General Public License version 2 or later; see _LICENSE.php
**/

defined( '_JEXEC' ) or die;

// -- Initialize
require_once dirname(__FILE__).'/config.php';
$cck	=	CCK_Rendering::getInstance( $this->template );
if ( $cck->initialize() === false ) { return; }

// -- Prepare
$display_mode	=	(int)$cck->getStyleParam( 'masonry_display', '0' );
$grid_id		=	$cck->id; //todo
$html			=	'';
$items			=	$cck->getItems();
$fieldnames		=	$cck->getFields( 'element', '', false );
$multiple		=	( count( $fieldnames ) > 1 ) ? true : false;
$count			=	count( $items );
$params			=	array(
						'itemSelector'=>'.item',
						'isAnimated'=>true,
						'isFitWidth'=>true
					);
$height			=	$cck->getStyleParam( 'masonry_height', '' );
$height			=	( $height ) ? 'height:'.$height.';' : '';
$margin			=	$cck->getStyleParam( 'masonry_margin', '' );
if ( $margin == '-1' ) {
	$margin		=	$cck->getStyleParam( 'masonry_margin_custom', '' );
} else {
	$margin		.=	'px';
}
$margin			=	( $margin ) ? 'margin:'.$margin.';' : '';
$width			=	$cck->getStyleParam( 'masonry_width', '' );
$width			=	( $width ) ? 'width:'.$width.';' : '';

$js_params		=	json_encode( $params );
$js	 			=	'$("#'.$grid_id.'").masonry('.$js_params.');';
$cck->addStyleDeclaration( '#'.$grid_id.'{margin:0 auto;}' );
$cck->addStyleDeclaration( '#'.$grid_id.' .item{float:left;'.$margin.$width.$height.'}' );
$cck->addScript( $cck->base.'/templates/'.$cck->template.'/js/masonry.pkgd.min.js' );
$cck->addScriptDeclaration( $js, $cck->getStyleParam( 'loading_event', 'ready' ) );

// -- Render
?>
<div id="<?php echo $grid_id; ?>" class="<?php echo $cck->id_class; ?>masonry"><?php
	if ( $count ) {
		if ( $display_mode == 2 ) {
			foreach ( $items as $item ) {
				$row	=	$item->renderPosition( 'element' );
				if ( $row ) {
					$html	.=	'<div class="item">'.$row.'</div>';
				}
			}
		} elseif ( $display_mode == 1 ) {
			foreach ( $items as $pk=>$item ) {
				$row	=	$cck->renderItem( $pk );
				if ( $row ) {
					$html	.=	'<div class="item">'.$row.'</div>';
				}
			}
		} else {
			foreach ( $items as $i=>$item ) {
				$row	=	'';
				foreach ( $fieldnames as $fieldname ) {
					$content	=	$item->renderField( $fieldname );
					if ( $content != '' ) {
						if ( $item->getMarkup( $fieldname ) != 'none' && ( $multiple || $item->getMarkup_Class( $fieldname ) ) ) {
							$row	.=	'<div class="cck-clrfix'.$item->getMarkup_Class( $fieldname ).'">'.$content.'</div>';
						} else {
							$row	.=	$content;
						}
					}
				}
				if ( $row ) {
					$html	.=	'<div class="item">'.$row.'</div>';
				}
			}
		}
		echo $html;
	}
?>
</div>
<?php
// -- Finalize
$cck->finalize();
?>













<?php 

?>