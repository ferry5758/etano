<?php
/******************************************************************************
newdsb
===============================================================================
File:                       admin/comment_results.php
$Revision: 21 $
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

require_once '../includes/sessions.inc.php';
require_once '../includes/vars.inc.php';
db_connect(_DBHOSTNAME_,_DBUSERNAME_,_DBPASSWORD_,_DBNAME_);
require_once '../includes/classes/phemplate.class.php';
require_once '../includes/admin_functions.inc.php';
allow_dept(DEPT_MODERATOR | DEPT_ADMIN);

$tpl=new phemplate('skin/','remove_nonjs');

$o=isset($_GET['o']) ? (int)$_GET['o'] : 0;
$r=(isset($_GET['r']) && !empty($_GET['r'])) ? (int)$_GET['r'] : current($accepted_results_per_page);

$input=array();
$ids=array();
$input['m']=sanitize_and_format_gpc($_GET,'m',TYPE_STRING,0,'');
$input['stat']=sanitize_and_format_gpc($_GET,'stat',TYPE_INT,0,0);
if (empty($input['stat'])) {
	unset($input['stat']);
}
$input['flagged']=sanitize_and_format_gpc($_GET,'flagged',TYPE_INT,0,0);
if (empty($input['flagged'])) {
	unset($input['flagged']);
}
$input['uid']=sanitize_and_format_gpc($_GET,'uid',TYPE_INT,0,0);
if (empty($input['uid'])) {
	unset($input['uid']);
}
$input['id']=sanitize_and_format_gpc($_GET,'id',TYPE_INT,0,0);
if (empty($input['id'])) {
	unset($input['id']);
}

$where="1";
switch ($input['m']) {

	case 'blog':
		if (isset($input['id'])) {
			$where="a.`fk_post_id`='".$input['id']."'";
		}
		$where.=" AND a.`fk_post_id`=b.`post_id`";
		$from="`{$dbtable_prefix}blog_comments` a,`{$dbtable_prefix}blog_posts` b";
		$select='b.`title` as `select1`';
		break;

	case 'user':
		if (isset($input['id'])) {
			$where="a.`fk_user_id_profile`='".$input['id']."'";
		}
		$where.=" AND a.`fk_user_id_profile`=b.`user_id`";
		$from="`{$dbtable_prefix}profile_comments` a,".USER_ACCOUNTS_TABLE." b";
		$select='b.`user` as `select1`';
		break;

	case 'photo':
		if (isset($input['id'])) {
			$where="a.`fk_photo_id`='".$input['id']."'";
		}
		$where.=" AND a.`fk_photo_id`=b.`photo_id`";
		$from="`{$dbtable_prefix}photo_comments` a,`{$dbtable_prefix}user_photos` b";
		$select="b.`_user` as `select1`";
		break;

}

if (isset($input['uid'])) {	// a user's comment
	$where.=" AND a.`fk_user_id`=".$input['uid'];
}
if (isset($input['flagged'])) {
	$where.=" AND a.`flagged`>0";
}
if (isset($input['stat'])) {
	$where.=" AND a.`status`='".$input['stat']."'";
}

$query="SELECT $select,a.`comment_id`,UNIX_TIMESTAMP(a.`date_posted`) as `date_posted`,a.`fk_user_id`,a.`_user`,a.`comment`,a.`status` FROM $from WHERE $where LIMIT $o,$r";
//print $query;
//die;
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
$loop=array();
if (mysql_num_rows($res)) {
	while ($rsrow=mysql_fetch_assoc($res)) {
		$rsrow['comment']=sanitize_and_format($rsrow['comment'],TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
		if ($rsrow['status']==STAT_PENDING) {
			$rsrow['pending']=true;
		} elseif ($rsrow['status']==STAT_EDIT) {
			$rsrow['need_edit']=true;
		} elseif ($rsrow['status']==STAT_APPROVED) {
			$rsrow['approved']=true;
		}
		if ($input['m']=='blog') {
			$rsrow['select1']=sanitize_and_format($rsrow['select1'],TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
			$rsrow['owner']='On post: '.$rsrow['select1'];
		} elseif ($input['m']=='user') {
			$rsrow['owner']=sprintf("On %s's profile",$rsrow['select1']);
		} elseif ($input['m']=='photo') {
			$rsrow['owner']=sprintf("On %s's photo",$rsrow['select1']);
		}
		$loop[]=$rsrow;
	}
	$totalrows=count($loop);
	$output['pager2']=pager($totalrows,$o,$r);
}

$output['m']=$input['m'];
$output['return2me']='comment_results.php';
if (!empty($_SERVER['QUERY_STRING'])) {
	$output['return2me'].='?'.$_SERVER['QUERY_STRING'];
}
$output['return2me']=rawurlencode($output['return2me']);
$tpl->set_file('content','comment_results.html');
$tpl->set_loop('loop',$loop);
$tpl->set_var('output',$output);
$tpl->process('content','content',TPL_LOOP | TPL_NOLOOP | TPL_OPTLOOP | TPL_OPTIONAL);
$tpl->drop_loop('loop');
unset($loop);

$tplvars['title']='Comments Search Results';
$tplvars['page']='comment_results';
$tplvars['css']='comment_results.css';
include 'frame.php';
?>