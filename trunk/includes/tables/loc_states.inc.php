<?php
/******************************************************************************
newdsb
===============================================================================
File:                       includes/tables/loc_states.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

$states_default['defaults']=array('state_id'=>0,'fk_country_id'=>0,'state'=>'');
$states_default['types']=array('state_id'=>HTML_INT,'fk_country_id'=>HTML_INT,'state'=>HTML_TEXTFIELD);