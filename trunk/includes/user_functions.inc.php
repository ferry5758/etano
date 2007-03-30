<?php
/******************************************************************************
newdsb
===============================================================================
File:                       includes/user_functions.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

include 'logs.inc.php';
$_access_level=array();
require_once 'general_functions.inc.php';
require_once 'access_levels.inc.php';

function get_userid_by_user($user) {
	$myreturn=0;
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	if (!empty($user)) {
		$query="SELECT `user_id` FROM ".USER_ACCOUNTS_TABLE." WHERE `user`='$user'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_num_rows($res)) {
			$myreturn=mysql_result($res,0,0);
		}
	}
	return $myreturn;
}


function get_user_by_userid($user_id) {
	$myreturn='';
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	if (!empty($user_id)) {
		$query="SELECT `user` FROM ".USER_ACCOUNTS_TABLE." WHERE `user_id`='$user_id'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_num_rows($res)) {
			$myreturn=mysql_result($res,0,0);
		}
	}
	return $myreturn;
}


function check_login_member($level_id) {
	$topass=array();
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	if (!isset($GLOBALS['_access_level'][$level_id])) {
		$GLOBALS['_access_level'][$level_id]=0;	// no access allowed if level not defined
	}
	// ask visitors to login if they land on a page that doesn't allow guests
	if (!($GLOBALS['_access_level'][$level_id]&1) && (!isset($_SESSION['user']['user_id']) || empty($_SESSION['user']['user_id']))) {
		$mysession=session_id();
		if (empty($mysession)) {
			session_start();
		}
		$_SESSION['timedout']=array('url'=>(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']),'method'=>$_SERVER['REQUEST_METHOD'],'qs'=>($_SERVER['REQUEST_METHOD']=='GET' ? $_GET : $_POST));
		redirect2page('login.php');
	}
//	unset($_SESSION['timedout']);
	// members from here on
	if (($GLOBALS['_access_level'][$level_id]&$_SESSION['user']['membership'])!=$_SESSION['user']['membership']) {
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][3];
		redirect2page('info.php',$topass);
	}
	$user_id=0;
	if (isset($_SESSION['user']['user_id'])) {
		$query="UPDATE ".USER_ACCOUNTS_TABLE." SET `last_activity`=now() WHERE `user_id`='".$_SESSION['user']['user_id']."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		$user_id=$_SESSION['user']['user_id'];
	}
	$query="REPLACE INTO `{$dbtable_prefix}online` SET `fk_user_id`='$user_id',`sess`='".session_id()."'";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
}

function get_module_stats($module_code,$user_id=0,$stat='') {
	$myreturn=array();
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	if (!empty($user_id)) {
		$query="SELECT `stat`,`value` FROM `{$dbtable_prefix}user_stats` WHERE `fk_user_id`='$user_id' AND `fk_module_code`='$module_code'";
		if (!empty($stat)) {
			$query.=" AND `stat`='$stat'";
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		while ($rsrow=mysql_fetch_row($res)) {
			$myreturn[$rsrow[0]]=$rsrow[1];
		}
	}
	return $myreturn;
}

function get_user_settings($user_id,$module_code) {
	$myreturn=array();
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	if (!empty($user_id)) {
		$query="SELECT `config_option`,`config_value` FROM `{$dbtable_prefix}user_settings2` WHERE `fk_user_id`='$user_id' AND `fk_module_code`='$module_code'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		while ($rsrow=mysql_fetch_row($res)) {
			$myreturn[$rsrow[0]]=$rsrow[1];
		}
	}
	return $myreturn;
}


function allow_at_level($level_id,$membership=1) {
	$myreturn=false;
	$membership=(int)$membership;
	if (($GLOBALS['_access_level'][$level_id]&((int)$membership))==$membership) {
		$myreturn=true;
	}
	return $myreturn;
}


function get_user_folder_name($folder_id,$user_id=null) {
	$myreturn='';
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	$query="SELECT `folder` FROM `{$dbtable_prefix}user_folders` WHERE `folder_id`='$folder_id'";
	if (isset($user_id)) {
		$query.=" AND `fk_user_id`='$user_id'";
	}
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res)) {
		$myreturn=mysql_result($res,0,0);
	}
	return $myreturn;
}


function add_member_score($user_ids,$act,$times=1,$points=0) {
	if (!is_array($user_ids)) {
		$user_ids=array($user_ids);
	}
	$dbtable_prefix=$GLOBALS['dbtable_prefix'];
	$scores=array('force'=>0,'login'=>5,'logout'=>-4,'approved'=>10,'rejected'=>-10,'add_main_photo'=>10,'del_main_photo'=>-10,'add_photo'=>2,'del_photo'=>-2,'add_blog'=>5,'payment'=>50,'unpayment'=>-50,);
	$scores['force']+=$points;
	if (isset($scores[$act]) && !empty($user_ids)) {
		$scores[$act]*=$times;
		$query="UPDATE `{$dbtable_prefix}user_profiles` SET `score`=`score`+".$scores[$act]." WHERE `fk_user_id` IN ('".join("','",$user_ids)."')";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	}
}