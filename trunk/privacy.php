<?php
/******************************************************************************
Etano
===============================================================================
File:                       privacy.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

require_once 'includes/common.inc.php';
db_connect(_DBHOST_,_DBUSER_,_DBPASS_,_DBNAME_);
require_once 'includes/user_functions.inc.php';

$tpl=new phemplate($tplvars['tplrelpath'].'/','remove_nonjs');

$tpl->set_file('content','privacy.html');
$tpl->process('content','content');

$tplvars['title']='Privacy';
$tplvars['page_title']='Privacy';
$tplvars['page']='privacy';
$tplvars['css']='privacy.css';
include 'frame.php';
?>