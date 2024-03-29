<?php
/******************************************************************************
Etano
===============================================================================
File:                       admin/payment_history.php
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

$output=array();
if (!empty($_GET['date_start'])) {
	$output['date_start']=date('Y-m-d',strtotime($_GET['date_start']));
} else {
	$output['date_start']=date('Y-m-01');
}
if (!empty($_GET['date_end'])) {
	$output['date_end']=$_GET['date_end'];
} else {
	$output['date_end']=date('Y-m-t');
}

$query="SELECT `m_value`,`m_name` FROM `{$dbtable_prefix}memberships`";
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
$memberships=array();
while ($rsrow=mysql_fetch_row($res)) {
	$memberships[$rsrow[0]]=$rsrow[1];
}

$config=get_site_option(array('date_format','time_offset'),'def_user_prefs');

$query="SELECT `payment_id`,`fk_user_id`,`_user`,`gateway`,`gw_txn`,`name`,`country`,`email`,`is_subscr`,`m_value_to`,`amount_paid`,`refunded`,UNIX_TIMESTAMP(`paid_from`) as `paid_from`,UNIX_TIMESTAMP(`paid_until`) as `paid_until`,UNIX_TIMESTAMP(`date`) as `date`,`is_suspect`,`suspect_reason` FROM `{$dbtable_prefix}payments` WHERE `date`>='".$output['date_start']."' AND `date`<='".$output['date_end']."' ORDER BY `payment_id`";
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
$output['total']=0;
$loop=array();
while ($rsrow=mysql_fetch_assoc($res)) {
	if (!empty($rsrow['is_subscr'])) {
		$rsrow['m_value_to']=isset($memberships[$rsrow['m_value_to']]) ? $memberships[$rsrow['m_value_to']] : '?';
		$rsrow['paid_from']=strftime($config['date_format'],$rsrow['paid_from']+$config['time_offset']);
		$rsrow['paid_until']=!empty($rsrow['paid_until']) ? strftime($config['date_format'],$rsrow['paid_until']+$config['time_offset']) : 'Forever';
	} else {
		$rsrow['paid_from']=strftime($config['date_format'],$rsrow['date']+$config['time_offset']);
		$rsrow['m_value_to']='Product';
		unset($rsrow['paid_until']);
	}
	if (empty($rsrow['is_suspect'])) {
		$output['total']+=((float)$rsrow['amount_paid']-(float)$rsrow['refunded']);
	}
	if ($rsrow['refunded']!=0) {
		$rsrow['refunded']='(<span class="alert">-$'.$rsrow['refunded'].'</span>)';
	} else {
		unset($rsrow['refunded']);
	}
	if (!empty($rsrow['is_suspect'])) {
		$rsrow['suspect_reason']=sanitize_and_format($rsrow['suspect_reason'],TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
	} else {
		unset($rsrow['is_suspect']);
	}
	$loop[]=$rsrow;
}
//$loop=sanitize_and_format($loop,TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
$output['total']=number_format($output['total'],2);

$tpl->set_file('content','payment_history.html');
$tpl->set_var('output',$output);
$tpl->set_loop('loop',$loop);
$tpl->process('content','content',TPL_LOOP | TPL_OPTLOOP);

$tplvars['title']='Payment History';
$tplvars['page']='payment_history';
$tplvars['css']='payment_history.css';
include 'frame.php';
