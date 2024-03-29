<?php
/******************************************************************************
Etano
===============================================================================
File:                       admin/popup_lang_string.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once '../includes/common.inc.php';
require_once '../includes/admin_functions.inc.php';
allow_dept(DEPT_ADMIN);

$tpl=new phemplate('skin/','remove_nonjs');

$lang_strings=array();
$lk_id=0;
if (isset($_SESSION['topass']['input'])) {
	$input=$_SESSION['topass']['input'];
	$lk_id=$input['lk_id'];
	$query="SELECT a.`module_code` as `skin`,b.`config_value` as `skin_name` FROM `{$dbtable_prefix}modules` a,`{$dbtable_prefix}site_options3` b WHERE a.`module_type`=".MODULE_SKIN." AND a.`module_code`=b.`fk_module_code` AND b.`config_option`='skin_name'";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	$i=0;
	while ($rsrow=mysql_fetch_assoc($res)) {
		$lang_strings[$i]['skin']=$rsrow['skin'];
		$lang_strings[$i]['lang_value']=isset($input['lang_strings'][$rsrow['skin']]) ? $input['lang_strings'][$rsrow['skin']] : '';
		$lang_strings[$i]['skin_name']=$rsrow['skin_name'];
		++$i;
	}
} elseif (!empty($_GET['lk_id'])) {
	$lk_id=(int)$_GET['lk_id'];
	// get the existing translations for this lk
	$query="SELECT a.`lang_value`,a.`skin`,b.`config_value` as `skin_name` FROM `{$dbtable_prefix}lang_strings` a,`{$dbtable_prefix}site_options3` b WHERE a.`skin`=b.`fk_module_code` AND b.`config_option`='skin_name' AND a.`fk_lk_id`=$lk_id";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	$lang_strings=array();
	$skins=array();
	while ($rsrow=mysql_fetch_assoc($res)) {
		$lang_strings[]=$rsrow;
		$skins[]=$rsrow['skin'];
	}
	// for the rest of skins having no translation for this lk, just set the translation to ''
	$query="SELECT a.`module_code` as `skin`,b.`config_value` as `skin_name` FROM `{$dbtable_prefix}modules` a,`{$dbtable_prefix}site_options3` b WHERE a.`module_type`=".MODULE_SKIN." AND a.`module_code`=b.`fk_module_code` AND b.`config_option`='skin_name'";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	while ($rsrow=mysql_fetch_assoc($res)) {
		if (!in_array($rsrow['skin'],$skins)) {
			$lang_strings[]=$rsrow;
		}
	}
}
$lang_strings=sanitize_and_format($lang_strings,TYPE_STRING,$__field2format[TEXT_DB2EDIT]);

$tpl->set_file('content','popup_lang_string.html');
$tpl->set_var('lk_id',$lk_id);
$tpl->set_loop('lang_strings',$lang_strings);
$message=isset($message) ? $message : (isset($topass['message']) ? $topass['message'] : (isset($_SESSION['topass']['message']) ? $_SESSION['topass']['message'] : array()));
if (!empty($message)) {
	$tpl->set_var('message',$message['text']);
	$tpl->set_var('message_class',($message['type']==MESSAGE_ERROR) ? 'message_error_small' : (($message['type']==MESSAGE_INFO) ? 'message_info_small' : 'message_info_small'));
}
echo $tpl->process('','content',TPL_FINISH | TPL_LOOP | TPL_OPTIONAL);
unset($_SESSION['topass']);
