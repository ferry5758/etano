<?php
/******************************************************************************
Etano
===============================================================================
File:                       admin/comment_search.php
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
$output['stat']=vector2options($accepted_pstats);

$tpl->set_file('content','comment_search.html');
$tpl->set_var('output',$output);
$tpl->process('content','content',TPL_LOOP);

$tplvars['title']='Search';
$tplvars['css']='comment_search.css';
$tplvars['page']='comment_search';
include 'frame.php';
