<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;
global $unread_messages_count;
global $read_unread_recipients;
global $DB;

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}
// set default values
$display_params = array_merge( array(
	'show_only_date' => 0,
	), $display_params );

// Select read/unread users for each thread

$recipients_SQL = new SQL();

$recipients_SQL->SELECT( 'ts.tsta_thread_ID AS thr_ID,
							GROUP_CONCAT(DISTINCT ur.user_login ORDER BY ur.user_login SEPARATOR \', \') AS thr_read,
    						GROUP_CONCAT(DISTINCT uu.user_login ORDER BY uu.user_login SEPARATOR \', \') AS thr_unread' );

$recipients_SQL->FROM( 'T_messaging__threadstatus ts
							LEFT OUTER JOIN T_messaging__threadstatus tsr
								ON ts.tsta_thread_ID = tsr.tsta_thread_ID AND tsr.tsta_first_unread_msg_ID IS NULL
							LEFT OUTER JOIN T_users ur
								ON tsr.tsta_user_ID = ur.user_ID AND ur.user_ID <> '.$current_User->ID.'
							LEFT OUTER JOIN T_messaging__threadstatus tsu
								ON ts.tsta_thread_ID = tsu.tsta_thread_ID AND tsu.tsta_first_unread_msg_ID IS NOT NULL
							LEFT OUTER JOIN T_users uu
								ON tsu.tsta_user_ID = uu.user_ID AND uu.user_ID <> '.$current_User->ID );

$recipients_SQL->WHERE( 'ts.tsta_user_ID ='.$current_User->ID );

$recipients_SQL->GROUP_BY( 'ts.tsta_thread_ID' );

foreach( $DB->get_results( $recipients_SQL->get() ) as $row )
{
	$read_by = '';

	if( !empty( $row->thr_read ) )
	{
		$read_by .= '<div>';
		$read_by .= get_avatar_imgtags( $row->thr_read, true, false, $Blog->get_setting('image_size_messaging'), 'avatar_before_login', '', true );
		if( !empty( $row->thr_unread ) )
		{
			$read_by .= ', ';
		}
		$read_by .= '</div>';
	}

	if( !empty( $row->thr_unread ) )
	{
		$read_by .= '<div>'.get_avatar_imgtags( $row->thr_unread, true, false, $Blog->get_setting('image_size_messaging'), 'avatar_before_login', '', false ).'</div>';
	}

	$read_unread_recipients[$row->thr_ID] = $read_by;
}

// Get params from request
$s = param( 's', 'string', '', true );
$u = param( 'u', 'string', '', true );

if( !empty( $s ) || !empty( $u ) )
{	// We want to filter on search keyword:

	$filter_sql = array();
	if( !empty( $s ) )
	{ // Search by title
		$filter_sql[] = 'threads.thrd_title LIKE "%'.$DB->escape($s).'%"';
	}
	if( !empty( $u ) )
	{ // Search by user names
		$filter_sql[] = 'CONCAT_WS( " ", threads.thrd_recipients, threads.thrd_usernames) LIKE "%'.$DB->escape($u).'%"';
	}
	$filter_sql = ( count( $filter_sql ) > 0 ) ? ' WHERE '.implode( ' OR ', $filter_sql) : '';

	// Create SELECT query
	$select_SQL = 'SELECT * FROM
						(SELECT mt.thrd_ID, mt.thrd_title, mt.thrd_datemodified,
								mts.tsta_first_unread_msg_ID AS thrd_msg_ID, mm.msg_datetime AS thrd_unread_since,
								(SELECT GROUP_CONCAT(ru.user_login ORDER BY ru.user_login SEPARATOR \', \')
									FROM T_messaging__threadstatus AS rts
										LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
										WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients,
								(SELECT CONCAT_WS(" ", GROUP_CONCAT(ru.user_firstname), GROUP_CONCAT(ru.user_lastname), GROUP_CONCAT(ru.user_nickname))
									FROM T_messaging__threadstatus AS rts
										LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
										WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_usernames
						FROM T_messaging__threadstatus mts
								LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
								LEFT OUTER JOIN T_messaging__message mm ON mts.tsta_first_unread_msg_ID = mm.msg_ID
								WHERE mts.tsta_user_ID = '.$current_User->ID.'
								ORDER BY mts.tsta_first_unread_msg_ID DESC, mt.thrd_datemodified DESC) AS threads'.
						$filter_sql;

	// Create COUNT query
	$count_SQL = 'SELECT COUNT(*) FROM
					(SELECT mt.thrd_title,
						(SELECT GROUP_CONCAT(ru.user_login SEPARATOR \', \')
								FROM T_messaging__threadstatus AS rts
									LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
											WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients,
										(SELECT CONCAT_WS(" ", GROUP_CONCAT(ru.user_firstname), GROUP_CONCAT(ru.user_lastname), GROUP_CONCAT(ru.user_nickname))
						FROM T_messaging__threadstatus AS rts
							LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
							WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_usernames
						FROM T_messaging__threadstatus mts
							LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
									WHERE mts.tsta_user_ID = '.$current_User->ID.') AS threads'.
						$filter_sql;
}
else
{
	// Create SELECT query
	$select_SQL = 'SELECT * FROM
					(SELECT mt.thrd_ID, mt.thrd_title, mt.thrd_datemodified,
							mts.tsta_first_unread_msg_ID AS thrd_msg_ID, mm.msg_datetime AS thrd_unread_since,
						(SELECT GROUP_CONCAT(ru.user_login ORDER BY ru.user_login SEPARATOR \', \')
						FROM T_messaging__threadstatus AS rts
							LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
							WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients
					FROM T_messaging__threadstatus mts
						LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
						LEFT OUTER JOIN T_messaging__message mm ON mts.tsta_first_unread_msg_ID = mm.msg_ID
						WHERE mts.tsta_user_ID = '.$current_User->ID.'
						ORDER BY mts.tsta_first_unread_msg_ID DESC, mt.thrd_datemodified DESC) AS threads';

	// Create COUNT quiery
	$count_SQL = 'SELECT COUNT(*)
					FROM T_messaging__threadstatus
						WHERE tsta_user_ID = '.$current_User->ID;
}

// Create result set:

$Results = new Results( $select_SQL, 'thrd_', '', NULL, $count_SQL );

$Results->Cache = & get_ThreadCache();

$Results->title = T_('Conversations list');

if( $unread_messages_count > 0 )
{
	$Results->title = $Results->title.' <span class="badge">'.$unread_messages_count.'</span></b>';
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_recipients( & $Form )
{
	$Form->text( 's', get_param('s'), 20, T_('Search'), '', 255 );
	$Form->text( 'u', get_param('u'), 10, T_('User'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_recipients',
	'presets' => array(
		'all' => array( T_('All'), get_messaging_url( 'threads' ) ),
		)
	);

$Results->cols[] = array(
					'th' => T_('With'),
					'th_class' => 'thread_with shrinkwrap',
					'td_class' => 'thread_with',
					'td' => '%get_avatar_imgtags( #thrd_recipients#, true, true, "'.$Blog->get_setting('image_size_messaging').'" )%',
					);

$messages_url = get_messaging_url( 'messages' );
$Results->cols[] = array(
					'th' => T_('Subject'),
					'th_class' => 'thread_subject',
					'td_class' => 'thread_subject',
					'td' => '<a href="'.$messages_url.'&amp;thrd_ID=$thrd_ID$&amp;thrd_title=~conditional( #thrd_title#!="", #thrd_title#, "(no subject)")~" title="'.T_('Show messages...').'"><strong>~conditional( #thrd_title#!="", #thrd_title#, "<i>(no subject)</i>")~</strong></a>',
					);

function convert_date( $date, $show_only_date )
{
	if( $show_only_date )
	{
		return mysql2localedate( $date );
	}

	return mysql2localedatetime( $date );
}

$show_only_date = $display_params[ 'show_only_date' ];
$Results->cols[] = array(
					'th' => T_('Last msg'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '~conditional( #thrd_msg_ID#>0, \'<span style="color:red">%convert_date(#thrd_unread_since#,'.$show_only_date.')%</span>\', \'<span style="color:green">%convert_date(#thrd_datemodified#,'.$show_only_date.')%</span>\')~' );

function get_read_by( $thread_ID )
{
	global $read_unread_recipients;

	return $read_unread_recipients[$thread_ID];
}

$Results->cols[] = array(
					'th' => T_('Read by'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'top',
					'td' => '%get_read_by( #thrd_ID# )%',
					);

function delete_action( $thread_ID )
{
	global $Blog, $samedomain_htsrv_url;

	if( is_admin_page() )
	{
		return action_icon( T_( 'Delete'), 'delete', regenerate_url( 'action', 'thrd_ID='.$thread_ID.'&action=delete&'.url_crumb( 'thread' ) ) );
	}
	else
	{
		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=threads' );
		return action_icon( T_( 'Delete'), 'delete', $samedomain_htsrv_url.'messaging.php?thrd_ID='.$thread_ID.'&action=delete&redirect_to='.$redirect_to.'&'.url_crumb( 'thread' ) );
	}
}

if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
{	// We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Del'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%delete_action(  #thrd_ID#  )%',
						);
}

if( is_admin_page() )
{
	$newmsg_url = regenerate_url( 'action', 'action=new' );
}
else
{
	$newmsg_url = regenerate_url( 'disp', 'disp=threads&action=new' );
}

$Results->global_icon( T_('Create a new conversation...'), 'new', $newmsg_url, T_('Compose new').' &raquo;', 3, 4  );

$Results->display( $display_params );

/*
 * $Log$
 * Revision 1.33  2011/10/05 12:05:02  efy-yurybakh
 * Blog settings > features tab refactoring
 *
 * Revision 1.32  2011/10/03 12:00:33  efy-yurybakh
 * Small messaging UI design changes
 *
 * Revision 1.31  2011/10/02 15:25:03  efy-yurybakh
 * small messaging UI design changes
 *
 * Revision 1.30  2011/09/27 07:45:58  efy-asimo
 * Front office messaging hot fixes
 *
 * Revision 1.29  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.28  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.27  2011/09/07 00:28:26  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.26  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.25  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.24  2009/10/27 18:48:48  fplanque
 * minor
 *
 * Revision 1.23  2009/10/11 12:15:51  efy-maxim
 * filter by author of the message and message text
 *
 * Revision 1.22  2009/10/11 11:31:32  efy-maxim
 * Extend filter of thread list. Search by user login, user full name, user nuckname and thread title/subject.
 *
 * Revision 1.21  2009/10/10 10:45:44  efy-maxim
 * messaging module - @action_icon()@
 *
 * Revision 1.20  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.19  2009/10/04 08:26:57  efy-maxim
 * messaging module improvements
 *
 * Revision 1.18  2009/10/02 15:07:27  efy-maxim
 * messaging module improvements
 *
 * Revision 1.17  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.16  2009/09/25 07:32:53  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.15  2009/09/20 02:02:45  fplanque
 * fixed read/unread colors
 *
 * Revision 1.14  2009/09/18 10:38:31  efy-maxim
 * 15x15 icons next to login in messagin module
 *
 * Revision 1.13  2009/09/17 10:54:21  efy-maxim
 * Read/Unread (green/red) users columns in thread list
 *
 * Revision 1.12  2009/09/16 15:14:48  efy-maxim
 * badge for unread message number
 *
 * Revision 1.11  2009/09/15 23:17:12  fplanque
 * minor
 *
 * Revision 1.10  2009/09/15 15:49:32  efy-maxim
 * "read by" column
 *
 * Revision 1.9  2009/09/14 19:33:02  efy-maxim
 * Some queries has been wrapped by SQL object
 *
 * Revision 1.8  2009/09/14 13:52:07  tblue246
 * Translation fixes; removed now pointless doc comment.
 *
 * Revision 1.7  2009/09/14 10:33:20  efy-maxim
 * messagin module improvements
 *
 * Revision 1.6  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.5  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.4  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
