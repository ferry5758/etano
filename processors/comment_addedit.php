<?php
/******************************************************************************
Etano
===============================================================================
File:                       processors/comment_addedit.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require '../includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/comments.inc.php';
check_login_member('write_comments');

if (is_file(_BASEPATH_.'/events/processors/comment_addedit.php')) {
	include _BASEPATH_.'/events/processors/comment_addedit.php';
}

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
	$input['comment_type']=sanitize_and_format_gpc($_POST,'comment_type',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');
	switch ($input['comment_type']) {

		case 'blog':
			require _BASEPATH_.'/includes/tables/comments_blog.inc.php';
			$item_default=&$comments_blog_default;
			$nextpage='blog_post_view.php';
			$table="{$dbtable_prefix}comments_blog";
			break;

		case 'photo':
			require _BASEPATH_.'/includes/tables/comments_photo.inc.php';
			$item_default=&$comments_photo_default;
			$nextpage='photo_view.php';
			$table="{$dbtable_prefix}comments_photo";
			break;

		case 'user':
			require _BASEPATH_.'/includes/tables/comments_profile.inc.php';
			$item_default=&$comments_profile_default;
			$nextpage='profile.php';
			$table="{$dbtable_prefix}comments_profile";
			break;

		default:
			$error=true;
			break;
	}

// get the input we need and sanitize it
	if (!$error) {
		foreach ($item_default['types'] as $k=>$v) {
			$input[$k]=sanitize_and_format_gpc($_POST,$k,$__field2type[$v],$__field2format[$v],$item_default['defaults'][$k]);
		}
	}
	$input['fk_user_id']=!empty($_SESSION[_LICENSE_KEY_]['user']['user_id']) ? $_SESSION[_LICENSE_KEY_]['user']['user_id'] : 0;
	if (!empty($_POST['return'])) {
		$input['return']=sanitize_and_format_gpc($_POST,'return',TYPE_STRING,$__field2format[FIELD_TEXTFIELD] | FORMAT_RUDECODE,'');
		$nextpage=$input['return'];
	} else {
		$input['return']='';
	}

	if (empty($input['comment'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][23];
	}
	if (!$error && $input['fk_user_id']==0 && get_site_option('use_captcha','core')) {
		$captcha=sanitize_and_format_gpc($_POST,'captcha',TYPE_STRING,0,'');
		if (!$error && (!isset($_SESSION['captcha_word']) || strcasecmp($captcha,$_SESSION['captcha_word'])!=0)) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text']=$GLOBALS['_lang'][24];
			$input['error_captcha']='red_border';
		}
	}
	unset($_SESSION['captcha_word']);

	if (!$error) {
		$input['comment']=remove_banned_words($input['comment']);
		$config=get_site_option(array('manual_com_approval'),'core');
		if (!empty($input['comment_id'])) {
			// only members can edit their comments
			if (!empty($_SESSION[_LICENSE_KEY_]['user']['user_id'])) {
				$input['comment'].="\n\n".sprintf($GLOBALS['_lang'][203],$_SESSION[_LICENSE_KEY_]['user']['user'],gmdate('Y-m-d H:i'));
				$query="UPDATE `$table` SET `last_changed`='".gmdate('YmdHis')."'";
				if ($config['manual_com_approval']==1) {
					$query.=",`status`=".STAT_PENDING;
				} else {
					$query.=",`status`=".STAT_APPROVED;
				}
				foreach ($item_default['defaults'] as $k=>$v) {
					if (isset($input[$k])) {
						$query.=",`$k`='".$input[$k]."'";
					}
				}
				$query.=" WHERE `comment_id`=".$input['comment_id']." AND `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
				if (isset($_on_before_update)) {
					for ($i=0;isset($_on_before_update[$i]);++$i) {
						call_user_func($_on_before_update[$i]);
					}
				}
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				$topass['message']['type']=MESSAGE_INFO;
				if (empty($config['manual_com_approval'])) {
					$topass['message']['text']=$GLOBALS['_lang'][25];
				} else {
					$topass['message']['text']=$GLOBALS['_lang'][26];
				}
				if (isset($_on_after_update)) {
					for ($i=0;isset($_on_after_update[$i]);++$i) {
						call_user_func($_on_after_update[$i]);
					}
				}
			} else {
				$topass['message']['type']=MESSAGE_INFO;
				$topass['message']['text']=$GLOBALS['_lang'][27];
			}
		} else {
			unset($input['comment_id']);
			$now=gmdate('YmdHis');
			$query="INSERT INTO `$table` SET `_user`='".$_SESSION[_LICENSE_KEY_]['user']['user']."',`date_posted`='$now',`last_changed`='$now'";
			if ($config['manual_com_approval']==1) {
				$query.=",`status`=".STAT_PENDING;
			} else {
				$query.=",`status`=".STAT_APPROVED;
			}
			foreach ($item_default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.=",`$k`='".$input[$k]."'";
				}
			}
			if (isset($_on_before_insert)) {
				for ($i=0;isset($_on_before_insert[$i]);++$i) {
					call_user_func($_on_before_insert[$i]);
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$input['comment_id']=mysql_insert_id();
			$topass['message']['type']=MESSAGE_INFO;
			if (empty($config['manual_com_approval'])) {
				$topass['message']['text']=$GLOBALS['_lang'][28];
				$nextpage.='#comm'.$input['comment_id'];
			} else {
				$topass['message']['text']=$GLOBALS['_lang'][29];
			}
			if (isset($_on_after_insert)) {
				for ($i=0;isset($_on_after_insert[$i]);++$i) {
					call_user_func($_on_after_insert[$i]);
				}
			}
		}
		if (empty($config['manual_com_approval'])) {
			if (isset($_on_after_approve)) {
				$GLOBALS['comment_ids']=array($input['comment_id']);
				$GLOBALS['comment_type']=$input['comment_type'];
				for ($i=0;isset($_on_after_approve[$i]);++$i) {
					call_user_func($_on_after_approve[$i]);
				}
			}
		}
	} else {
		$input['comment']=isset($_POST['comment']) ? addslashes_mq($_POST['comment']) : '';
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
