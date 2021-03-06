<?php
/**
 * This file implements the controller for item types management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Itemtype class:
load_class( 'items/model/_itemtype.class.php', 'ItemType' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var User
 */
global $current_User;

global $dispatcher;

// get reserved ids
global $special_range;
$special_range = ItemType::get_special_range();

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$tab = param( 'tab', 'string', 'settings', true );

$tab3 = param( 'tab3', 'string', 'types', true );

$AdminUI->set_path( 'collections', $tab, $tab3 );

// Get action parameter from request:
param_action();

if( param( 'ityp_ID', 'integer', '', true) )
{// Load itemtype from cache:
	$ItemtypeCache = & get_ItemTypeCache();
	if( ($edited_Itemtype = & $ItemtypeCache->get_by_ID( $ityp_ID, false )) === false )
	{	// We could not find the item type to edit:
		unset( $edited_Itemtype );
		forget_param( 'ityp_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), 'Itemtype' ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_Itemtype) )
		{	// We don't have a model to use, start with blank object:
			$edited_Itemtype = new ItemType();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Itemtype = duplicate( $edited_Itemtype ); // PHP4/5 abstraction
			// Load all custom fields of the copied item type
			$edited_Itemtype->get_custom_fields();
			// Reset ID of new item type
			$edited_Itemtype->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ityp_ID:
		param( 'ityp_ID', 'integer', true );
		break;

	case 'create': // Record new Itemtype
	case 'create_new': // Record Itemtype and create new
	case 'create_copy': // Record Itemtype and create similar
		// Insert new item type...:
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );
		
		$edited_Itemtype = new ItemType();

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_Itemtype->load_from_Request() )
		{	// We could load data from form without errors:

			if( $edited_Itemtype->is_special() )
			{ // is special item type
				param_error( 'ityp_ID',
					sprintf( T_('Item types with ID from %d to %d are reserved. Please use another ID.' ), $special_range[0], $special_range[1] ) );
				// Set to 0 in order to display an edit input box for name field
				$edited_Itemtype->ID = 0;
				// Set name from request
				$edited_Itemtype->set( 'name', param( 'ityp_name', 'string', '' ) );
			}
			else
			{ // ID is good

				// While inserting into DB, ID property of Userfield object will be set to autogenerated ID
				// So far as we set ID manualy, we need to preserve this value
				// When assignment of wrong value will be fixed, we can skip this
				$entered_itemtype_id = $edited_Itemtype->ID;

				// Insert in DB:
				$DB->begin();
				// because of manual assigning ID,
				// member function ItemType::dbexists() is overloaded for proper functionality
				$q = $edited_Itemtype->dbexists();
				if($q)
				{	// We have a duplicate entry:

					param_error( 'ityp_ID',
						sprintf( T_('This item type already exists. Do you want to <a %s>edit the existing item type</a>?'),
							'href="?ctrl=itemtypes&amp;tab='.$tab.'&amp;tab3='.$tab3.'&amp;action=edit&amp;ityp_ID='.$q.'"' ) );
				}
				else
				{
					$edited_Itemtype->dbinsert();
					$Messages->add( T_('New item type created.'), 'success' );
				}
				$DB->commit();

				if( empty($q) )
				{	// What next?
					switch( $action )
					{
						case 'create_copy':
							// Redirect so that a reload doesn't write to the DB twice:
							header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'&action=new&ityp_ID='.$entered_itemtype_id, 303 ); // Will EXIT
							// We have EXITed already at this point!!
							break;
						case 'create_new':
							// Redirect so that a reload doesn't write to the DB twice:
							header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'&action=new', 303 ); // Will EXIT
							// We have EXITed already at this point!!
							break;
						case 'create':
							// Redirect so that a reload doesn't write to the DB twice:
							header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
							// We have EXITed already at this point!!
							break;
					}
				}
			}
		}
		break;

	case 'update':
		// Edit item type form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ityp_ID:
		param( 'ityp_ID', 'integer', true );

		// load data from request
		if( $edited_Itemtype->load_from_Request() )
		{	// We could load data from form without errors:

			if( $edited_Itemtype->is_reserved() )
			{ // is reserved item type
				param_error( 'ityp_ID',
					sprintf( T_('Item types with IDs = ( %d ) are reserved. You can not edit this item type.' ), implode( ', ', $posttypes_reserved_IDs ) ) );
			}
			else
			{ // ID is good
				// Update in DB:
				$DB->begin();

				$edited_Itemtype->dbupdate();
				$Messages->add( T_('Item type updated.'), 'success' );

				$DB->commit();

				header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'delete':
		// Delete item type:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtype' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ityp_ID:
		param( 'ityp_ID', 'integer', true );

		$default_ids = ItemType::get_default_ids();

		if( $edited_Itemtype->is_special() )
		{ // is special item type
			param_error( 'ityp_ID',
				sprintf( T_('Item types with ID from %d to %d are reserved. You can not delete this item type.' ), $special_range[0], $special_range[1] ) );
			// To don't display a confirmation question
			$action = 'edit';
		}
		elseif( ( $item_type_blog_ID = array_search( $edited_Itemtype->ID, $default_ids ) ) !== false )
		{ // is default item type of the blog
			if( $item_type_blog_ID == 'default' )
			{
				$Messages->add( T_('This item type is default of all blogs. You can not delete this item type.' ) );
			}
			else
			{
				$BlogCache = & get_BlogCache();
				$blog_names = array();
				foreach( $default_ids as $blog_ID => $item_type_ID )
				{
					if( $edited_Itemtype->ID == $item_type_ID && ( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) ) )
					{
						$blog_names[] = '<a href="'.$admin_url.'?ctrl=coll_settings&tab=features&blog='.$Blog->ID.'#fieldset_wrapper_post_options"><b>'.$Blog->get('name').'</b></a>';
					}
				}
				$Messages->add( sprintf( T_('This item type is default of the blogs: %s. You can not delete this item type.' ), implode( ', ', $blog_names ) ) );
			}
			// To don't display a confirmation question
			$action = 'edit';
		}
		else
		{ // ID is good
			if( param( 'confirm', 'integer', 0 ) )
			{ // confirmed, Delete from DB:
				$msg = sprintf( T_('Item type &laquo;%s&raquo; deleted.'), $edited_Itemtype->dget('name') );
				$edited_Itemtype->dbdelete( true );
				unset( $edited_Itemtype );
				forget_param( 'ityp_ID' );
				$Messages->add( $msg, 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( $admin_url.'?ctrl=itemtypes&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
			else
			{	// not confirmed, Check for restrictions:
				if( ! $edited_Itemtype->check_delete( sprintf( T_('Cannot delete item type &laquo;%s&raquo;'), $edited_Itemtype->dget('name') ) ) )
				{	// There are restrictions:
					$action = 'view';
				}
			}
		}
		break;

}

// Generate available blogs list:
$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'itemtypes', 'tab' => $tab, 'tab3' => 'types' ) );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=dashboard&amp;blog=$blog$' ) );
$AdminUI->breadcrumbpath_add( T_('Settings'), $admin_url.'?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
$AdminUI->breadcrumbpath_add( T_('Item Types'), $admin_url.'?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=types' );

$AdminUI->set_page_manual_link( 'managing-item-types' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'delete':
		// We need to ask for confirmation:
		$edited_Itemtype->confirm_delete(
				sprintf( T_('Delete item type &laquo;%s&raquo;?'),  $edited_Itemtype->dget('name') ),
				'itemtype', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'items/views/_itemtype.form.php' );
		break;


	default:
		// No specific request, list all item types:
		// Cleanup context:
		forget_param( 'ityp_ID' );
		// Display item types list:
		$AdminUI->disp_view( 'items/views/_itemtypes.view.php' );
		break;

}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>