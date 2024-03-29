<?php
/******************************************************************************
Etano
===============================================================================
File:                       admin/ajax/field_values.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once dirname(__FILE__).'/../../includes/common.inc.php';
require_once dirname(__FILE__).'/../../includes/admin_functions.inc.php';
allow_dept(DEPT_ADMIN);

$output='';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$optype=sanitize_and_format_gpc($_POST,'optype',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');
	$val=sanitize_and_format_gpc($_POST,'val',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');
	$lk_id=sanitize_and_format_gpc($_POST,'lk_id',TYPE_INT,0,0);
	switch ($optype) {

		case 'add':
			$query="INSERT INTO `{$dbtable_prefix}lang_keys` SET `lk_type`=".FIELD_TEXTFIELD.",`lk_diz`='Field value',`lk_use`=".LK_FIELD;
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$lk_id=mysql_insert_id();
			$query="INSERT INTO `{$dbtable_prefix}lang_strings` SET `lang_value`='$val',`fk_lk_id`=$lk_id,`skin`='".get_default_skin_code()."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$output=$lk_id;
			break;

		case 'edit':
			$query="UPDATE `{$dbtable_prefix}lang_strings` SET `lang_value`='$val' WHERE `fk_lk_id`=$lk_id AND `skin`='".get_default_skin_code()."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			if (!mysql_affected_rows()) {
				$query="INSERT IGNORE INTO `{$dbtable_prefix}lang_keys` SET `lk_id`=$lk_id,`lk_type`=".FIELD_TEXTFIELD.",`lk_diz`='Field value',`lk_use`=".LK_FIELD;
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				$query="INSERT IGNORE INTO `{$dbtable_prefix}lang_strings` SET `fk_lk_id`=$lk_id,`lang_value`='$val',`skin`='".get_default_skin_code()."'";
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			}
			$output=$lk_id;
			break;

		case 'del':
			$query="DELETE FROM `{$dbtable_prefix}lang_strings` WHERE `fk_lk_id`=$lk_id";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$query="DELETE FROM `{$dbtable_prefix}lang_keys` WHERE `lk_id`=$lk_id";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$output=$lk_id;
			break;

	}
}
echo $output;
