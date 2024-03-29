<?php
/******************************************************************************
Etano
===============================================================================
File:                       processors/blog_addedit.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require '../includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/includes/tables/user_blogs.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/blogs.inc.php';
check_login_member('write_blogs');

if (is_file(_BASEPATH_.'/events/processors/blog_addedit.php')) {
	include _BASEPATH_.'/events/processors/blog_addedit.php';
}

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='my_blogs.php';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
// get the input we need and sanitize it
	foreach ($user_blogs_default['types'] as $k=>$v) {
		$input[$k]=sanitize_and_format_gpc($_POST,$k,$__field2type[$v],$__field2format[$v],$user_blogs_default['defaults'][$k]);
	}
	$input['fk_user_id']=$_SESSION[_LICENSE_KEY_]['user']['user_id'];
	if (!empty($_POST['return'])) {
		$input['return']=sanitize_and_format_gpc($_POST,'return',TYPE_STRING,$__field2format[FIELD_TEXTFIELD] | FORMAT_RUDECODE,'');
		$nextpage=$input['return'];
	}

// check for input errors
	if (empty($input['blog_name'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][13];
	}

	if (!$error) {
		$input['blog_name']=remove_banned_words($input['blog_name']);
		$input['blog_diz']=remove_banned_words($input['blog_diz']);
		require _BASEPATH_.'/includes/classes/fileop.class.php';
		$fileop=new fileop();
		$towrite=array();	// what to write in the cache file
		if (!empty($input['blog_id'])) {
			foreach ($input as $k=>$v) {
				$towrite[$k]=sanitize_and_format_gpc($_POST,$k,TYPE_STRING,$__field2format[TEXT_GPC2DISPLAY],'');
			}
			$query="UPDATE IGNORE `{$dbtable_prefix}user_blogs` SET ";
			foreach ($user_blogs_default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.="`$k`='".$input[$k]."',";
				}
			}
			$query=substr($query,0,-1);
			$query.=" WHERE `blog_id`=".$input['blog_id']." AND `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (isset($_on_before_update)) {
				for ($i=0;isset($_on_before_update[$i]);++$i) {
					call_user_func($_on_before_update[$i]);
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$topass['message']['type']=MESSAGE_INFO;
			$topass['message']['text']=$GLOBALS['_lang'][14];
			$input['blog_id']=(string)$input['blog_id'];
			if (isset($_on_after_update)) {
				for ($i=0;isset($_on_after_update[$i]);++$i) {
					call_user_func($_on_after_update[$i]);
				}
			}
		} else {
			unset($input['blog_id']);
			foreach ($input as $k=>$v) {
				$towrite[$k]=sanitize_and_format_gpc($_POST,$k,TYPE_STRING,$__field2format[TEXT_GPC2DISPLAY],'');
			}
			$query="INSERT INTO `{$dbtable_prefix}user_blogs` SET ";
			foreach ($user_blogs_default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.="`$k`='".$input[$k]."',";
				}
			}
			$query=substr($query,0,-1);
			if (isset($_on_before_insert)) {
				for ($i=0;isset($_on_before_insert[$i]);++$i) {
					call_user_func($_on_before_insert[$i]);
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$input['blog_id']=mysql_insert_id();
			$towrite['blog_id']=$input['blog_id'];
			$input['blog_id']=(string)$input['blog_id'];

			// create the blog cache folder if it doesn't exist
			if (!is_dir(_CACHEPATH_.'/blogs/'.$input['blog_id']{0}.'/'.$input['blog_id'])) {
				$fileop->mkdir(_CACHEPATH_.'/blogs/'.$input['blog_id']{0}.'/'.$input['blog_id']);
			}
			$temp='<?php $blog_archive=array();';
			$fileop->file_put_contents(_CACHEPATH_.'/blogs/'.$input['blog_id']{0}.'/'.$input['blog_id'].'/blog_archive.inc.php',$temp);
			$topass['message']['type']=MESSAGE_INFO;
			$topass['message']['text']=$GLOBALS['_lang'][15];
			if (isset($_on_after_insert)) {
				for ($i=0;isset($_on_after_insert[$i]);++$i) {
					call_user_func($_on_after_insert[$i]);
				}
			}
		}

		$towrite['fk_user_id']=$input['fk_user_id'];
		unset($towrite['return']);
		$towrite='<?php $blog='.var_export($towrite,true).';';
		$fileop->file_put_contents(_CACHEPATH_.'/blogs/'.$input['blog_id']{0}.'/'.$input['blog_id'].'/blog.inc.php',$towrite);
	} else {
		$nextpage='blog_addedit.php';
// 		you must re-read all textareas from $_POST like this:
//		$input['x']=addslashes_mq($_POST['x']);
		$input['return']=rawurlencode($input['return']);
		$input=sanitize_and_format($input,TYPE_STRING,FORMAT_HTML2TEXT_FULL | FORMAT_STRIPSLASH);
		$topass['input']=$input;
		if (isset($_on_error)) {
			for ($i=0;isset($_on_error[$i]);++$i) {
				call_user_func($_on_error[$i]);
			}
		}
	}
}
$nextpage=_BASEURL_.'/'.$nextpage;
redirect2page($nextpage,$topass,'',true);
