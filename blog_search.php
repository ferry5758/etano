<?php
/******************************************************************************
Etano
===============================================================================
File:                       blog_search.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

//define('CACHE_LIMITER','private');
require 'includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/blogs.inc.php';

$tpl=new phemplate(_BASEPATH_.'/skins_site/'.get_my_skin().'/','remove_nonjs');

$output=array();
$o=isset($_GET['o']) ? (int)$_GET['o'] : 0;
$r=!empty($_GET['r']) ? (int)$_GET['r'] : current($accepted_results_per_page);
$output['search_md5']=sanitize_and_format_gpc($_GET,'search',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');

$input=array();
$post_ids=array();
$error=false;
if (!empty($output['search_md5'])) {
	// if we have a query cache, retrieve all from cache
	$query="SELECT `results`,`search` FROM `{$dbtable_prefix}site_searches` WHERE `search_md5`='".$output['search_md5']."' AND `search_type`=".SEARCH_BLOG;
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res)) {
		$post_ids=mysql_result($res,0,0);
		$post_ids=explode(',',$post_ids);
		$input=unserialize(mysql_result($res,0,1));	// sanitized already
		check_login_member($input['acclevel_code']);
	}
} else {
	// first search here, no cache, must calculate everything
	$input['acclevel_code']='search_blog'; // default access level is the one for advanced search!!!!
	$select="a.`post_id`";
	$from="`{$dbtable_prefix}blog_posts` a";
	$where="a.`is_public`=1 AND a.`status`=".STAT_APPROVED;
	$orderby="a.`date_posted` DESC";

	// define here all search types
	if (isset($_GET['st'])) {
		$input['st']=$_GET['st'];
		switch ($_GET['st']) {

			case 'new':
				$tplvars['page_title']=$GLOBALS['_lang'][104];
				//$orderby="a.`date_posted` DESC";	// default
				break;

			case 'views':
				$tplvars['page_title']=$GLOBALS['_lang'][105];
				$input['acclevel_code']='search_blog';
				$orderby="a.`stat_views` DESC";
				break;

			case 'comm':
				$tplvars['page_title']=$GLOBALS['_lang'][106];
				$input['acclevel_code']='search_blog';
				$orderby="a.`stat_comments` DESC";
				break;

			case 'uid':
				$input['acclevel_code']='search_blog';
				$input['uid']=sanitize_and_format_gpc($_GET,'uid',TYPE_INT,0,0);
				$tplvars['page_title']=sprintf($GLOBALS['_lang'][108],get_user_by_userid($input['uid']));
				$where="a.`fk_user_id`=".$input['uid']." AND ".$where;
				$orderby="a.`post_id` DESC";
				break;

			case 'tag':
				$tplvars['page_title']=$GLOBALS['_lang'][107];
				$input['acclevel_code']='search_blog';
				$input['tags']=isset($_GET['tags']) ? $_GET['tags'] : '';
				// remove extra spaces and words with less than 3 chars
				$input['tags']=trim(preg_replace(array("/\s\s+/","/\b[^\s]{1,3}\b/"),array(' ',''),$input['tags']));
				$input['tags']=sanitize_and_format($input['tags'],TYPE_STRING,$__field2format[FIELD_TEXTFIELD]);
				if (!empty($input['tags'])) {
					$select.=",MATCH (a.`title`,a.`post_content`) AGAINST ('".$input['tags']."' IN BOOLEAN MODE) as `match_score`";
					$where.=" AND MATCH (a.`title`,a.`post_content`) AGAINST ('".$input['tags']."' IN BOOLEAN MODE)";
					$orderby="`match_score` DESC";
				} else {
					$error=true;
				}
				break;

			default:
				break;

		}
	}
	check_login_member($input['acclevel_code']);

	if (!$error) {
		$query="SELECT $select FROM $from WHERE $where ORDER BY $orderby";
//print $query;
//die;
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		for ($i=0;$i<mysql_num_rows($res);++$i) {
			$post_ids[]=mysql_result($res,$i,0);
		}
		$serialized_input=sanitize_and_format(serialize($input),TYPE_STRING,$__field2format[FIELD_TEXTFIELD]);
		$output['search_md5']=md5($serialized_input);
		$query="INSERT IGNORE INTO `{$dbtable_prefix}site_searches` SET `search_md5`='".$output['search_md5']."',`search_type`=".SEARCH_BLOG.",`search`='$serialized_input',`results`='".join(',',$post_ids)."'";
		if (!empty($_SESSION[_LICENSE_KEY_]['user']['user_id'])) {
			$query.=",`fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	}
}
$output['totalrows']=count($post_ids);

// get the results from user cache for the found post_ids
$loop=array();
if (!empty($output['totalrows'])) {
	if ($o>=$output['totalrows']) {
		$o=$output['totalrows']-$r;
	}
	$post_ids=array_slice($post_ids,$o,$r);
	require _BASEPATH_.'/includes/classes/blog_posts_cache.class.php';
	$blog_posts_cache=new blog_posts_cache();
	$loop=$blog_posts_cache->get_tpl_array($post_ids);
	unset($blog_posts_cache);
	if (isset($input['tags'])) {
		$search_words=explode(' ',$input['tags']);
		$replace_words=array();
		for ($i=0;isset($search_words[$i]);++$i) {
			$replace_words[$i]='<span class="matched_word">'.$search_words[$i].'</span>';
		}
	}
	for ($i=0;isset($loop[$i]);++$i) {
		$loop[$i]['date_posted']=strftime($_SESSION[_LICENSE_KEY_]['user']['prefs']['datetime_format'],$loop[$i]['date_posted']+$_SESSION[_LICENSE_KEY_]['user']['prefs']['time_offset']);
		if (isset($_list_of_online_members[$loop[$i]['fk_user_id']])) {
			$loop[$i]['is_online']='is_online';
			$loop[$i]['user_online_status']=$GLOBALS['_lang'][102];
		} else {
			$loop[$i]['user_online_status']=$GLOBALS['_lang'][103];
		}
		if (!is_file(_PHOTOPATH_.'/t1/'.$loop[$i]['photo'])) {
			$loop[$i]['photo']='no_photo.gif';
		}
		// fancy word coloring - lightning fast now :)
		if (isset($input['tags'])) {
			$loop[$i]['title_clean']=$loop[$i]['title'];
			$loop[$i]['title']=str_replace($search_words,$replace_words,$loop[$i]['title']);
			$loop[$i]['post_content']=str_replace($search_words,$replace_words,$loop[$i]['post_content']);
		}
	}

	// set $_GET for the pager.
	$_GET=array('search'=>$output['search_md5']);
	$output['pager2']=pager($output['totalrows'],$o,$r);
}

$tpl->set_file('content','blog_search.html');
$tpl->set_var('output',$output);
$tpl->set_loop('loop',$loop);
$tpl->process('content','content',TPL_LOOP | TPL_OPTLOOP | TPL_NOLOOP | TPL_OPTIONAL);
$tpl->drop_loop('loop');
unset($loop);

$tplvars['title']=$GLOBALS['_lang'][107];
$tplvars['page']='blog_search';
$tplvars['css']='blog_search.css';
if (is_file('blog_search_left.php')) {
	include 'blog_search_left.php';
}
include 'frame.php';
