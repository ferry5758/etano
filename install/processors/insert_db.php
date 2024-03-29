<?php
/******************************************************************************
Etano
===============================================================================
File:                       install/processors/insert_db.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

ini_set('include_path','.');
ini_set('session.use_cookies',1);
ini_set('session.use_trans_sid',0);
ini_set('date.timezone','GMT');	// temporary fix for the php 5.1+ TZ compatibility
ini_set('error_reporting',2047);
ini_set('display_errors',0);
define('_LICENSE_KEY_','');
require_once '../../includes/sessions.inc.php';
require_once '../../includes/sco_functions.inc.php';
set_time_limit(0);

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='install/step4.php';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	if (isset($_SESSION['install']['input'])) {
		$input=$_SESSION['install']['input'];
		if (function_exists('mysql_connect')) {
			$link=mysql_connect($input['dbhost'],$input['dbuser'],$input['dbpass']);
			if ($link) {
				if (!@mysql_select_db($input['dbname'],$link)) {
					$error=true;
					$topass['message']['type']=MESSAGE_ERROR;
					$topass['message']['text'][]='Database Host, user and password are ok but the database name is wrong.';
					mysql_close($link);
				}
			} else {
				$error=true;
				$topass['message']['type']=MESSAGE_ERROR;
				$topass['message']['text'][]='Database Host or user or password are wrong.';
			}
		} else {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]='Server configuration does not allow db connections.';
		}
	} else {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text'][]='Server configuration does not allow db connections.';
	}

	if (!$error) {
			require_once '../../includes/classes/etano_package.class.php';
			db_connect($_SESSION['install']['input']['dbhost'],$_SESSION['install']['input']['dbuser'],$_SESSION['install']['input']['dbpass'],$_SESSION['install']['input']['dbname']);
			$p=new etano_package();
			if (!$p->db_insert_file(dirname(__FILE__).'/../sql/db.sql')) {
				trigger_error($p->manual_actions[count($p->manual_actions)-1]['error'],E_USER_ERROR);
			}
	} else {
		$nextpage='install/step3.php';
	}
}
$my_url=str_replace('/install/processors/insert_db.php','',$_SERVER['PHP_SELF']);
define('_BASEURL_',((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$my_url);
redirect2page($nextpage,$topass,$qs);
