<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url, $posttypes_reserved_IDs, $Blog, $edited_Item;

// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_items__type' );
if( ! empty( $posttypes_reserved_IDs ) )
{ // Exclude the reserved item types
	$SQL->WHERE( 'ityp_ID NOT IN ( '.implode( ', ', $posttypes_reserved_IDs ).' )' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'editityp_' );

$Results->title = T_('Change item type');

if( $edited_Item->ID > 0 )
{
	$close_url = $admin_url.'?ctrl=items&amp;action=edit&amp;blog='.$Blog->ID.'&amp;restore=1&amp;p='.$edited_Item->ID;
}
else
{
	$close_url = $admin_url.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;restore=1';
}
$Results->global_icon( T_('Do NOT change the type'), 'close', $close_url );


/**
 * Callback to make item type name depending on item type id
 */
function get_name_for_itemtype( $ityp_ID, $name )
{
	global $admin_url, $edited_Item;

	$current = $edited_Item->ityp_ID == $ityp_ID ? ' '.T_('(current)') : '';

	return '<strong><a href="'.$admin_url.'?ctrl=items&amp;action=update_type&amp;post_ID='.$edited_Item->ID.'&amp;ityp_ID='.$ityp_ID.'&amp;'.url_crumb( 'item' ).'">'
		.$name.'</a></strong>'
		.$current;
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ityp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '$ityp_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ityp_name',
		'td' => '%get_name_for_itemtype( #ityp_ID#, #ityp_name# )%',
	);

$Results->cols[] = array(
		'th' => T_('Template name'),
		'order' => 'ityp_template_name',
		'td' => '$ityp_template_name$',
		'th_class' => 'shrinkwrap',
	);

// Display results:
$Results->display();

?>