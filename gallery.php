<?php
/******************************************************************************
Etano
===============================================================================
File:                       gallery.php
$Revision: 221 $
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once 'includes/common.inc.php';
db_connect(_DBHOST_,_DBUSER_,_DBPASS_,_DBNAME_);
require_once 'includes/user_functions.inc.php';
//check_login_member('purchase');

$tpl=new phemplate($tplvars['tplrelpath'].'/','remove_nonjs');

$output=array();
if (isset($_SESSION['topass']['input'])) {
	$output=$_SESSION['topass']['input'];
}

$tpl->set_file('content','gallery.html');
$tpl->set_var('output',$output);
$tpl->process('content','content',TPL_OPTIONAL);

$tplvars['title']='Gallery';
$tplvars['page_title']='Gallery';
$tplvars['page']='gallery';
$tplvars['menu_gallery']='active';
$tplvars['css']='gallery.css';
include 'frame.php';
