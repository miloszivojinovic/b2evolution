<?php
/*
 * This is the template that displays the linkblog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 */
if( substr(basename($_SERVER['SCRIPT_FILENAME']), 0, 1 ) == '_' )
	die( 'Please, do not access this page directly.' );

if( !$Blog->get('linkblog') )
{	// No linkblog blog defined, revert to basic linkblog:
	bloginfo('notes');
	return;
}


# maximum number of linkblog entries to display:
if(!isset($linkblog_limit)) $linkblog_limit = 20;
# global linkblog delimiters:
if(!isset($linkblog_main_start)) $linkblog_main_start = '';
if(!isset($linkblog_main_end)) $linkblog_main_end = '';
# Category delimiters:
if(!isset($linkblog_catname_before)) $linkblog_catname_before = '<h4>';
if(!isset($linkblog_catname_after)) $linkblog_catname_after = '</h4><ul>';
if(!isset($linkblog_catlist_end)) $linkblog_catlist_end = '</ul>';
# Item delimiters:
if(!isset($linkblog_item_before)) $linkblog_item_before = '<li>';
if(!isset($linkblog_item_after)) $linkblog_item_after = '</li>';


// --- //


// Load the linkblog blog:
$LinkblogList = & new ItemList( $linkblog_blog, array(), '', '', '', $linkblog_cat, $linkblog_catsel, '', 'ASC', 'category title', '', '', '', '', '', '', '', '', $linkblog_limit, 'posts', $timestamp_min, $timestamp_max );


// Dirty trick until we get everything into objects:
$saved_blog = $blog;  
$blog = $linkblog_blog;

// Open the global list
echo $linkblog_main_start;
	
$previous_cat = '';
$linkblog_cat = '';

while( $Item = $LinkblogList->get_category_group() )
{
	// Open new cat:
	echo $linkblog_catname_before;
	$Item->main_category();
	echo $linkblog_catname_after;
	
	while( $Item = $LinkblogList->get_item() )
	{
		echo $linkblog_item_before;
		$Item->title(); 
		echo ' ';
		$Item->content( 1, 0, T_('more'), '[', ']' );	// Description + more link 
		?>
		<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="<?php echo T_('Permalink') ?>" width="12" height="9" class="middle" /></a>
		<?php
		echo $linkblog_item_after;
	}

	// Close cat
	echo $linkblog_catlist_end;
}
// Close the global list
echo $linkblog_main_end;

// Restore after dirty trick:
$blog = $saved_blog;		
?>