<?php
/******************************************************************************
Etano
===============================================================================
File:                       includes/general_functions.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

function update_stats($user_id,$stat,$add_val,$type='+') {
	global $dbtable_prefix;
	if (!empty($user_id)) {
		if ($type=='+') {
			$query="UPDATE `{$dbtable_prefix}user_stats` SET `value`=`value`+$add_val WHERE `fk_user_id`=$user_id AND `stat`='$stat' LIMIT 1";
		} else {
			$query="UPDATE `{$dbtable_prefix}user_stats` SET `value`=$add_val WHERE `fk_user_id`=$user_id AND `stat`='$stat' LIMIT 1";
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (!mysql_affected_rows()) {
			$query="INSERT INTO `{$dbtable_prefix}user_stats` SET `fk_user_id`=$user_id,`stat`='$stat',`value`=$add_val";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		}
	}
}


function get_user_stats($user_id,$stat='') {
	$myreturn=array();
	if (is_array($stat)) {
		for ($i=0;isset($stat[$i]);++$i) {
			$myreturn[$stat[$i]]=0;
		}
	} else {
		$myreturn[$stat]=0;
	}
	global $dbtable_prefix;
	if (!empty($user_id)) {
		$query="SELECT `stat`,`value` FROM `{$dbtable_prefix}user_stats` WHERE `fk_user_id`=$user_id";
		if (!empty($stat)) {
			if (is_array($stat)) {
				$query.=" AND `stat` IN ('".join("','",$stat)."')";
			} else {
				$query.=" AND `stat`='$stat'";
			}
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		while ($rsrow=mysql_fetch_row($res)) {
			$myreturn[$rsrow[0]]=$rsrow[1];
		}
	}
	return $myreturn;
}


function get_site_option($option,$module_code) {
	$myreturn=0;
	global $dbtable_prefix;
	$query="SELECT `config_option`,`config_value` FROM `{$dbtable_prefix}site_options3` WHERE `fk_module_code`='$module_code'";
	if (!empty($option)) {
		if (is_array($option)) {
			$query.=" AND `config_option` IN ('".join("','",$option)."')";
		} else {
			$query.=" AND `config_option`='$option'";
		}
	}
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res)) {
		$myreturn=array();
		while ($rsrow=mysql_fetch_row($res)) {
			$myreturn[$rsrow[0]]=$rsrow[1];
		}
		if (is_string($option)) {
			$myreturn=array_shift($myreturn);
		}
	}
	return $myreturn;
}


function get_site_options_by_module_type($option,$module_type) {
	$myreturn=array();
	global $dbtable_prefix;
	$query="SELECT a.`module_code`,b.`config_option`,b.`config_value` FROM `{$dbtable_prefix}modules` a,`{$dbtable_prefix}site_options3` b WHERE a.`module_type`='$module_type' AND a.`module_code`=b.`fk_module_code`";
	if (!empty($option)) {
		if (is_array($option)) {
			$query.=" AND `config_option` IN ('".join("','",$option)."')";
		} else {
			$query.=" AND `config_option`='$option'";
		}
	}
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res)) {
		while ($rsrow=mysql_fetch_assoc($res)) {
			$myreturn[$rsrow['module_code']][$rsrow['config_option']]=$rsrow['config_value'];
		}
	}
	return $myreturn;
}


function get_module_codes_by_type($module_type) {
	$myreturn=array();
	global $dbtable_prefix;
	$query="SELECT `module_code` FROM `{$dbtable_prefix}modules` WHERE `module_type`='$module_type'";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	for ($i=0;$i<mysql_num_rows($res);++$i) {
		$myreturn[]=mysql_result($res,$i,0);
	}
	return $myreturn;
}


// This function does NOT convert html to text.
// Make sure that the string is clean before calling this function
function bbcode2html($str) {
	$from=array('~\[url=(http://[^<">\[\]]*?)\](.*?)\[/url\]~','~\[b\](.*?)\[/b\]~s','~\[u\](.*?)\[/u\]~s','~\[quote\](.*?)\[/quote\]~s','~\[img=(http://[^<">\[\]]*?)\]~');
	$to=array('<a class="content-link simple" rel="external" href="$1">$2</a>','<strong>$1</strong>','<span class="underline">$1</span>','<blockquote>$1</blockquote>','<img src="$1" />');
	$str=preg_replace($from,$to,$str);
	// leftovers
	$from=array('~\[url=(http://.*?)\]~','~\[/url\]~','~\[b\]~','~\[/b\]~','~\[u\]~','~\[/u\]~','~\[quote\]~','~\[/quote\]~','~\[img=(http://.*?)\]~');
	return preg_replace($from,'',$str);
}


// wrapper for the create_pager2() function
function pager($totalrows,$offset,$results) {
	$lang_strings['page']=$GLOBALS['_lang'][179];
	$lang_strings['rpp']=$GLOBALS['_lang'][180];
	$lang_strings['goto_first']=$GLOBALS['_lang'][181];
	$lang_strings['goto_last']=$GLOBALS['_lang'][182];
	$lang_strings['goto_next']=$GLOBALS['_lang'][183];
	$lang_strings['goto_prev']=$GLOBALS['_lang'][184];
	$lang_strings['goto_page']='';
	return create_pager2($totalrows,$offset,$results,$lang_strings);
}


function get_default_skin_dir() {
	$myreturn='';
	global $dbtable_prefix;
	$query="SELECT a.`config_value` FROM `{$dbtable_prefix}site_options3` a,`{$dbtable_prefix}modules` b,`{$dbtable_prefix}site_options3` c WHERE a.`config_option`='skin_dir' AND a.`fk_module_code`=b.`module_code` AND b.`module_code`=c.`fk_module_code` AND b.`module_type`=".MODULE_SKIN." AND c.`config_option`='is_default' AND c.`config_value`=1";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res)) {
		$myreturn=mysql_result($res,0,0);
	}
	if (empty($myreturn)) {
		$myreturn='basic';
	}
	return $myreturn;
}


function get_default_skin_code() {
	$myreturn='';
	global $dbtable_prefix;
	$query="SELECT a.`module_code` FROM `{$dbtable_prefix}modules` a,`{$dbtable_prefix}site_options3` b WHERE a.`module_code`=b.`fk_module_code` AND a.`module_type`=".MODULE_SKIN." AND b.`config_option`='is_default' AND b.`config_value`=1";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res)) {
		$myreturn=mysql_result($res,0,0);
	}
	if (empty($myreturn)) {
		$myreturn='basic';
	}
	return $myreturn;
}


function send_template_email($to,$subject,$template,$skin,$output=array(),$message_body='') {
	$myreturn=true;
	if (empty($message_body)) {
		if (isset($GLOBALS['tpl'])) {
			global $tpl;
			$old_root=$tpl->get_root();
			$tpl->set_root(_BASEPATH_.'/skins_site/'.$skin.'/');
		} else {
			$tpl=new phemplate(_BASEPATH_.'/skins_site/'.$skin.'/','remove_nonjs');
		}
		$tpl->set_file('temp','emails/'.$template);
		if (!empty($output)) {
			$tpl->set_var('output',$output);
		}
		global $tplvars;
		$tpl->set_var('tplvars',$tplvars);
		$message_body=$tpl->process('temp','temp',TPL_LOOP | TPL_OPTLOOP | TPL_OPTIONAL | TPL_FINISH);
		$tpl->drop_var('temp');
		$tpl->drop_var('output');
	}
	$config=get_site_option(array('mail_from','mail_crlf'),'core');
	require_once _BASEPATH_.'/includes/classes/phpmailer.class.php';
	$mail=new PHPMailer();
	$mail->IsHTML(true);
	$mail->From=$config['mail_from'];
	$mail->Sender=$config['mail_from'];
	$mail->FromName=_SITENAME_;
	if ($config['mail_crlf']) {
		$mail->LE="\r\n";
	} else {
		$mail->LE="\n";
	}
	$mail->IsMail();
	$mail->AddAddress($to);
	$mail->Subject=$subject;
	$mail->Body=$message_body;
	if (!$mail->Send()) {
		$myreturn=false;
		$GLOBALS['topass']['message']['type']=MESSAGE_ERROR;
		$GLOBALS['topass']['message']['text']=$mail->ErrorInfo;
		require_once _BASEPATH_.'/includes/classes/log_error.class.php';
		new log_error(array('module_name'=>'send_template_email','text'=>'sending mail to '.$to.' failed:'.$message_body));
	}
	if (isset($old_root)) {
		$tpl->set_root($old_root);
	}
	return $myreturn;
}

// $mess_array must contain keys from $queue_message_default and should be already sanitized
function queue_or_send_message($mess_array,$force_send=false) {
	global $dbtable_prefix;
	if (!$force_send) {
		require _BASEPATH_.'/includes/tables/queue_message.inc.php';
		$query="INSERT INTO `{$dbtable_prefix}queue_message` SET `date_sent`='".gmdate('YmdHis')."'";
		foreach ($queue_message_default['defaults'] as $k=>$v) {
			if (isset($mess_array[$k])) {
				$query.=",`$k`='".$mess_array[$k]."'";
			}
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	} else {
		require _BASEPATH_.'/includes/tables/user_inbox.inc.php';
		$was_sent=false;	// was sent by a filter?
		$notify_receiver=get_user_settings($mess_array['fk_user_id'],'def_user_prefs','notify_me');
		// see if the receiver has any filters in place to re-route our message
		$query="SELECT `filter_type`,`field`,`field_value`,`fk_folder_id` FROM `{$dbtable_prefix}message_filters` WHERE `fk_user_id`=".$mess_array['fk_user_id'];
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		$filters=array();
		$filters[$mess_array['fk_user_id']]=array();
		while ($rsrow=mysql_fetch_assoc($res)) {
			$filters[$mess_array['fk_user_id']][]=$rsrow;
		}
		if (!empty($filters[$mess_array['fk_user_id']])) {
			for ($i=0;isset($filters[$mess_array['fk_user_id']][$i]);++$i) {
				$filter=&$filters[$mess_array['fk_user_id']][$i];
				switch ($filter['filter_type']) {

					case FILTER_SENDER:
						if ($mess_array['fk_user_id_other']==$filter['field_value']) {
							if ($filter['fk_folder_id']==FOLDER_SPAMBOX) {
								$into="`{$dbtable_prefix}user_spambox`";
								$notify_receiver=false;
								require _BASEPATH_.'/includes/tables/user_inbox.inc.php';
								$defaults_table=&$user_spambox_default;
							} else {
								$into="`{$dbtable_prefix}user_inbox`";
								$mess_array['fk_folder_id']=$filter['fk_folder_id'];
								$defaults_table=&$user_inbox_default;
							}
							$query="INSERT INTO $into SET `date_sent`='".gmdate('YmdHis')."'";
							foreach ($defaults_table['defaults'] as $k=>$v) {
								if (isset($mess_array[$k])) {
									$query.=",`$k`='".$mess_array[$k]."'";
								}
							}
							if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
							$was_sent=true;
						}
						break 2;	// exit the filters for() too

				}
			}
		}

		if (!$was_sent) {
			// no filter here - insert directly in inbox
			$query="INSERT INTO `{$dbtable_prefix}user_inbox` SET `date_sent`='".gmdate('YmdHis')."'";
			foreach ($user_inbox_default['defaults'] as $k=>$v) {
				if (isset($mess_array[$k])) {
					$query.=",`$k`='".$mess_array[$k]."'";
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		}

		if ($notify_receiver) {	//	new message notification
			$mess_array['subject']=sanitize_and_format($mess_array['subject'],TYPE_STRING,FORMAT_STRIPSLASH | FORMAT_TEXT2HTML);
			$def_skin=get_default_skin_dir();
			if (empty($mess_array['_user_other']) && $mess_array['message_type']==MESS_SYSTEM) {
				include_once _BASEPATH_.'/skins_site/'.$def_skin.'/lang/mailbox.inc.php';
				$mess_array['_user_other']=&$GLOBALS['_lang'][135];
			}
			$query="SELECT a.`email`,b.`_user` FROM `".USER_ACCOUNTS_TABLE."` a,`{$dbtable_prefix}user_profiles` b WHERE a.`".USER_ACCOUNT_ID."`=b.`fk_user_id` AND a.`".USER_ACCOUNT_ID."`='".$mess_array['fk_user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			if (mysql_num_rows($res)) {
				$receiver_email=mysql_result($res,0,0);
				$mess_array['user']=mysql_result($res,0,1);
				send_template_email($receiver_email,$mess_array['subject'],'new_message.html',$def_skin,$mess_array);
			}
		}
	}
}


//	$email must have the keys: 'subject','message_body'
//	Both the subject and the message_body are assumed to be NOT sanitized but STRIPSLASH_MQ'ed
function queue_or_send_email($email_addrs,$email,$force_send=false) {
	$myreturn=true;
	if (is_string($email_addrs)) {
		$email_addrs=array($email_addrs);
	}
	$config=get_site_option(array('mail_from','mail_crlf'),'core');
	$query_len=10000;
	if (!$force_send) {
		$email['subject']=sanitize_and_format($email['subject'],TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTFIELD]);
		$email['message_body']=sanitize_and_format($email['message_body'],TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTAREA]);
		global $dbtable_prefix;
		$base="INSERT INTO `{$dbtable_prefix}queue_email` (`to`,`subject`,`message_body`) VALUES ";
		$query=$base;
		for ($i=0;isset($email_addrs[$i]);++$i) {
			$temp="('".$email_addrs[$i]."','".$email['subject']."','".$email['message_body']."')";
			if (strlen($query)+strlen($temp)<$query_len) {
				$query.=$temp.',';
			} else {
				if ($query!=$base) {
					$query=substr($query,0,-1);
					if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
					$query=$base.$temp.',';
				}
			}
		}
		if ($query!=$base) {
			$query=substr($query,0,-1);
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		}
	} else {
		$email['subject']=sanitize_and_format($email['subject'],TYPE_STRING,FORMAT_STRIP_MQ | FORMAT_ONELINE | FORMAT_TRIM);
		$email['message_body']=sanitize_and_format($email['message_body'],TYPE_STRING,FORMAT_STRIP_MQ);
		require_once _BASEPATH_.'/includes/classes/phpmailer.class.php';
		$mail=new PHPMailer();
		$mail->IsHTML(true);
		$mail->From=$config['mail_from'];
		$mail->Sender=$config['mail_from'];
		$mail->FromName=_SITENAME_;
		if ($config['mail_crlf']) {
			$mail->LE="\r\n";
		} else {
			$mail->LE="\n";
		}
		$mail->IsMail();
		for ($i=0;isset($email_addrs[$i]);++$i) {
			$mail->ClearAddresses();
			$mail->AddAddress($email_addrs[$i]);
			$mail->Subject=$email['subject'];
			$mail->Body=$email['message_body'];
			if (!$mail->Send()) {
				$myreturn=false;
				$GLOBALS['topass']['message']['type']=MESSAGE_ERROR;
				$GLOBALS['topass']['message']['text']=$mail->ErrorInfo;
			}
		}
	}
	return $myreturn;
}


function add_member_score($user_ids,$act,$times=1,$just_read_value=false,$points=0) {
	$myreturn=0;
	$scores=array('force'=>0,'login'=>15,'login_bonus'=>1,'approved'=>10,'rejected'=>-10,'add_main_photo'=>10,'del_main_photo'=>-10,'add_photo'=>2,'del_photo'=>-2,'add_blog'=>5,'del_blog'=>-5,'payment'=>50,'unpayment'=>-50,'received_comment'=>0.4,'removed_comment'=>-0.4,'pview'=>0.1,'block_member'=>-5,'unblock_member'=>5,'join'=>40,'inactivity'=>-2);
	if (!$just_read_value) {
		if (!is_array($user_ids)) {
			$user_ids=array($user_ids);
		}
		global $dbtable_prefix;
		$scores['force']+=$points;
		if (isset($scores[$act]) && !empty($user_ids)) {
			$scores[$act]*=$times;
			$query="UPDATE `{$dbtable_prefix}user_profiles` SET `score`=`score`+".$scores[$act]." WHERE `fk_user_id` IN ('".join("','",$user_ids)."')";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		}
	}
	if (isset($scores[$act])) {
		$myreturn=$scores[$act];
	}
	return $myreturn;
}


function get_user_by_userid($user_id) {
	$myreturn='';
	global $dbtable_prefix;
	if (!empty($user_id)) {
		$query="SELECT `_user` FROM `{$dbtable_prefix}user_profiles` WHERE `fk_user_id`=$user_id";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_num_rows($res)) {
			$myreturn=mysql_result($res,0,0);
		}
	}
	return $myreturn;
}


function set_user_settings($user_id,$module_code,$option,$value) {
	global $dbtable_prefix;
	$query="UPDATE `{$dbtable_prefix}user_settings2` SET `config_value`='$value' WHERE `config_option`='$option' AND `fk_module_code`='$module_code' AND `fk_user_id`=$user_id";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (!mysql_affected_rows()) {
		$query="INSERT INTO `{$dbtable_prefix}user_settings2` SET `config_value`='$value',`config_option`='$option',`fk_module_code`='$module_code',`fk_user_id`=$user_id";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	}
	return true;
}


// it returns the defaults for non members
function get_user_settings($user_id,$module_code,$option='') {
	$myreturn=array();
	global $dbtable_prefix;
	if (is_array($option)) {
		$remaining=array_flip($option);	// to keep options which are in default but not in user's settings
	} else {
		$remaining=array($option=>1);
	}
	if (!empty($user_id)) {
		$query="SELECT `config_option`,`config_value` FROM `{$dbtable_prefix}user_settings2` WHERE `fk_user_id`=$user_id AND `fk_module_code`='$module_code'";
		if (!empty($option)) {
			if (is_array($option)) {
				$query.=" AND `config_option` IN ('".join("','",$option)."')";
			} else {
				$query.=" AND `config_option`='$option'";
			}
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_num_rows($res)) {
			while ($rsrow=mysql_fetch_row($res)) {
				$myreturn[$rsrow[0]]=$rsrow[1];
				unset($remaining[$rsrow[0]]);
			}
		}
	}

	if (!empty($remaining)) {
		$remaining=array_flip($remaining);
		$query="SELECT `config_option`,`config_value` FROM `{$dbtable_prefix}site_options3` WHERE `fk_module_code`='$module_code'";
		$query.=" AND `config_option` IN ('".join("','",$remaining)."')";
		// we don't use "AND `per_user`=1" so we can inject some other site_options into user_settings
		// The injected values will not be editable by the user
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_num_rows($res)) {
			while ($rsrow=mysql_fetch_row($res)) {
				$myreturn[$rsrow[0]]=$rsrow[1];
			}
			if (!empty($user_id)) {
				$query="INSERT IGNORE INTO `{$dbtable_prefix}user_settings2` (`fk_user_id`,`config_option`,`config_value`,`fk_module_code`) VALUES ";
				foreach ($remaining as $k=>$v) {
					$query.="('$user_id','$v','".$myreturn[$v]."','$module_code'),";
				}
				$query=substr($query,0,-1);
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			}
		}
	}
	if (!empty($option) && !is_array($option)) {
		$myreturn=array_shift($myreturn);
	}
	return $myreturn;
}


function add_message_filter($filter) {
	$myreturn=false;
	global $dbtable_prefix;
	$query="INSERT IGNORE INTO `{$dbtable_prefix}message_filters` SET ";
	foreach ($filter as $k=>$v) {
		$query.="`$k`='$v',";
	}
	$query=substr($query,0,-1);
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_affected_rows()) {
		$myreturn=true;
	}
	return $myreturn;
}


function del_message_filter($filter) {
	$myreturn=false;
	global $dbtable_prefix;
	$query="DELETE FROM `{$dbtable_prefix}message_filters` WHERE 1";
	foreach ($filter as $k=>$v) {
		$query.=" AND `$k`='$v'";
	}
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_affected_rows()) {
		$myreturn=true;
	}
	return $myreturn;
}


function text2smilies($str) {
	$from=array('&gt;:(',':D','o.O','|o','-.-','8)',':~(','&gt;:)',':doh:','&lt;.&lt;',':grr:','^,^',':h:',':huh:',':lol:',':x',':,',':O',':r:',':(',':)',':t:',':P',':u:',':w:',':.',';)',':!:');
	$to=array('<img class="smiley" src="'._BASEURL_.'/images/emoticons/angry.gif" alt="angry" title="angry" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/biggrin.gif" alt="grin" title="grin" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/blink.gif" alt="blink" title="blink" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/censor.gif" alt="censor" title="censor" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/closedeyes.gif" alt="closed eyes" title="closed eyes" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/cool.gif" alt="cool" title="cool" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/cry.gif" alt="cry" title="cry" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/devil.gif" alt="devil" title="devil" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/doh.gif" alt="doh" title="doh" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/dry.gif" alt="dry" title="dry" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/grrrr.gif" alt="grrrr" title="grrrr" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/happy.gif" alt="happy" title="happy" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/holy.gif" alt="holy" title="holy" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/huh.gif" alt="huh" title="huh" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/laugh.gif" alt="laugh" title="laugh" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/lips.gif" alt="lips" title="lips" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/mellow.gif" alt="mellow" title="mellow" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/ohmy.gif" alt="ohmy" title="ohmy" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/rolleyes.gif" alt="roll eyes" title="roll eyes" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/sad.gif" alt="sad" title="sad" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/smile.gif" alt="smile" title="smile" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/thumbsup.gif" alt="thumbs up" title="thumbs up" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/tongue.gif" alt="tongue" title="tongue" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/unsure.gif" alt="unsure" title="unsure" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/wacko.gif" alt="wacko" title="wacko" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/whistling.gif" alt="whistling" title="whistling" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/wink.gif" alt="wink" title="wink" />',
			'<img class="smiley" src="'._BASEURL_.'/images/emoticons/yay.gif" alt="yay" title="yay" />'
	);
	return str_replace($from,$to,$str);
}


function remove_banned_words($str) {
	include _BASEPATH_.'/includes/banned_words.inc.php';
	if (!empty($_banned_words)) {
		$str=str_replace($_banned_words,'#######',$str);
	}
	return $str;
}


function create_search_form($search_fields) {
	$myreturn=array();
	global $dbtable_prefix,$_pfields;
	$user_defaults=array();
	if (!empty($_SESSION[_LICENSE_KEY_]['user']['user_id'])) {
		$query="SELECT `search` FROM `{$dbtable_prefix}user_searches` WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."' AND `is_default`=1";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_num_rows($res)) {
			$user_defaults=unserialize(mysql_result($res,0,0));
			for ($i=0;isset($search_fields[$i]);++$i) {
				$temp=$_pfields[$search_fields[$i]]->search();
				$temp->set_value($user_defaults,false);
				$_pfields[$search_fields[$i]]->search=$temp;	// php4 sucks
			}
			unset($user_defaults);
		}
	}

	for ($i=0;isset($search_fields[$i]);++$i) {
		if (!empty($_pfields[$search_fields[$i]]->config['search_type'])) {
			$temp=$_pfields[$search_fields[$i]]->search();
			$myreturn[]=array('label'=>$temp->config['label'],
							'dbfield'=>$temp->config['dbfield'],
							'field'=>$temp->edit($i+4),
							'js'=>$temp->edit_js()
						);
		}
	}
	return $myreturn;
}


function get_online_ids() {
	$myreturn=array();
	global $dbtable_prefix;
	$query="SELECT DISTINCT `fk_user_id` FROM `{$dbtable_prefix}online` WHERE `fk_user_id` IS NOT NULL";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	for ($i=0;$i<mysql_num_rows($res);++$i) {
		$myreturn[mysql_result($res,$i,0)]=1;
	}
	return $myreturn;
}


function allow_at_level($level_code,$membership=1) {
	$myreturn=false;
	$membership=(int)$membership;
	if (isset($GLOBALS['_access_level'][$level_code]) && ($GLOBALS['_access_level'][$level_code]&$membership)==$membership) {
		$myreturn=true;
	}
	return $myreturn;
}
