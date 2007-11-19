<?php
/******************************************************************************
Etano
===============================================================================
File:                       filters.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once 'includes/common.inc.php';
db_connect(_DBHOST_,_DBUSER_,_DBPASS_,_DBNAME_);
require_once 'includes/user_functions.inc.php';
require_once 'includes/tables/message_filters.inc.php';
check_login_member('manage_folders');

$tpl=new phemplate($tplvars['tplrelpath'].'/','remove_nonjs');

$o=isset($_GET['o']) ? (int)$_GET['o'] : 0;
$r=!empty($_GET['r']) ? (int)$_GET['r'] : current($accepted_results_per_page);

$my_folders=array(FOLDER_INBOX=>'INBOX',FOLDER_OUTBOX=>'SENT',FOLDER_TRASH=>'Trash',FOLDER_SPAMBOX=>'SPAMBOX'); // translate this
$query="SELECT `folder_id`,`folder` FROM `{$dbtable_prefix}user_folders` WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
while ($rsrow=mysql_fetch_row($res)) {
	$my_folders[$rsrow[0]]=sanitize_and_format($rsrow[1],TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
}

$from="`{$dbtable_prefix}message_filters`";
$where="`fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";

$query="SELECT count(*) FROM $from WHERE $where";
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
$totalrows=mysql_result($res,0,0);

$loop=array();
if (!empty($totalrows)) {
	if ($o>$totalrows) {
		$o=$totalrows-$r;
		$o=$o>=0 ? $o : 0;
	}
	$field_values=array();
	$query="SELECT * FROM $from WHERE $where ORDER BY `fk_folder_id` LIMIT $o,$r";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	while ($rsrow=mysql_fetch_assoc($res)) {
		$rsrow['fk_folder_id']=isset($my_folders[$rsrow['fk_folder_id']]) ? $my_folders[$rsrow['fk_folder_id']] : '?';
		$field_values[$rsrow['filter_type']]['field'][]=$rsrow['field'];
		$field_values[$rsrow['filter_type']]['value'][]=$rsrow['field_value'];
		$loop[]=$rsrow;
	}

	$filtered_senders=array();
	if (!empty($field_values[FILTER_SENDER]['value'])) {
		$query="SELECT `".USER_ACCOUNT_ID."` as `user_id`,`".USER_ACCOUNT_USER."` as `user` FROM `".USER_ACCOUNTS_TABLE."` WHERE `".USER_ACCOUNT_ID."` IN ('".join("','",$field_values[FILTER_SENDER]['value'])."')";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		while ($rsrow=mysql_fetch_row($res)) {
			$filtered_senders[$rsrow[0]]=$rsrow[1];
		}
	}

	$filtered_sender_profiles=array();
	if (!empty($field_values[FILTER_SENDER_PROFILE]['value'])) {
		for ($i=0;isset($field_values[FILTER_SENDER_PROFILE]['value'][$i]);++$i) {
			foreach ($_pfields as $k=>$field) {
				if ($field['dbfield']==$field_values[FILTER_SENDER_PROFILE]['field'][$i]) {
					$filtered_sender_profiles[$field_values[FILTER_SENDER_PROFILE]['field'][$i].'_'.$field_values[FILTER_SENDER_PROFILE]['value'][$i]]=$field['label'].': '.$field['accepted_values'][$field_values[FILTER_SENDER_PROFILE]['value'][$i]];
					break;
				}
			}
		}
	}

	for ($i=0;isset($loop[$i]);++$i) {
		switch ($loop[$i]['filter_type']) {

			case FILTER_SENDER:
				if (isset($filtered_senders[$loop[$i]['field_value']])) {
					$loop[$i]['field_value']=sprintf('User: %s',$filtered_senders[$loop[$i]['field_value']]);	// translate this
				} else {
					unset($loop[$i]);
				}
				break;

			case FILTER_SENDER_PROFILE:
				if (isset($filtered_sender_profiles[$loop[$i]['field'].'_'.$loop[$i]['field_value']])) {
					$loop[$i]['field_value']=$filtered_sender_profiles[$loop[$i]['field'].'_'.$loop[$i]['field_value']];
				} else {
					unset($loop[$i]);
				}
				break;

			case FILTER_MESSAGE:
			default:
				unset($loop[$i]);
				break;

		}
	}
	$tpl->set_var('pager2',pager($totalrows,$o,$r));
}

$tpl->set_file('content','filters.html');
$tpl->set_loop('loop',$loop);
$tpl->set_var('o',$o);
$tpl->set_var('r',$r);
$tpl->process('content','content',TPL_LOOP | TPL_NOLOOP);
$tpl->drop_loop('loop');
unset($loop);

$tplvars['title']='Manage your filters';     // translate
$tplvars['page_title']='Message filters';
$tplvars['page']='filters';
$tplvars['css']='filters.css';
if (is_file('filters_left.php')) {
	include 'filters_left.php';
}
include 'frame.php';