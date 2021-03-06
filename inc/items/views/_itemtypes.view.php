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


// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_items__type' );

// Create result set:
$Results = new Results( $SQL->get(), 'ityp_' );

$Results->title = T_('Item/Post/Page types');

// get reserved and default ids
global $default_ids;
$default_ids = ItemType::get_default_ids();

/**
 * Callback to build possible actions depending on item type id
 *
 */
function get_actions_for_itemtype( $id )
{
	global $default_ids;
	$action = action_icon( T_('Duplicate this item type...'), 'copy',
										regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=new') );

	if( ! ItemType::is_reserved( $id ) )
	{ // Edit all item types except of not reserved item type
		$action = action_icon( T_('Edit this item type...'), 'edit',
										regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=edit') )
							.$action;
	}

	if( ! ItemType::is_special( $id ) && ! in_array( $id, $default_ids ) )
	{ // Delete only the not reserved and not default item types
		$action .= action_icon( T_('Delete this item type!'), 'delete',
									regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=delete&amp;'.url_crumb('itemtype').'') );
	}
	return $action;
}

/**
 * Callback to make item type name depending on item type id
 *
 */
function get_name_for_itemtype( $id, $name )
{
	if( ! ItemType::is_reserved( $id ) )
	{ // not reserved id
		$ret_name = '<strong><a href="'.regenerate_url( 'action,ID', 'ityp_ID='.$id.'&amp;action=edit' ).'">'.$name.'</a></strong>';
	}
	else
	{
		$ret_name = '<strong>'.$name.'</strong>';
	}
	return $ret_name;
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
		'td' => '%get_name_for_itemtype(#ityp_ID#, #ityp_name#)%',
	);

$Results->cols[] = array(
		'th' => T_('Template name'),
		'order' => 'ityp_template_name',
		'td' => '$ityp_template_name$',
		'th_class' => 'shrinkwrap',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%get_actions_for_itemtype( #ityp_ID# )%',
						);

	$Results->global_icon( T_('Create a new element...'), 'new',
				regenerate_url( 'action', 'action=new' ), T_('New item type').' &raquo;', 3, 4  );
}

// Display results:
$Results->display();

?>