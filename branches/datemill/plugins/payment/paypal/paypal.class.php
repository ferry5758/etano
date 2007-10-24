<?php
/******************************************************************************
Etano
===============================================================================
File:                       plugins/payment/paypal/paypal.class.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once _BASEPATH_.'/includes/interfaces/ipayment.class.php';

class payment_paypal extends ipayment {
	var $paypal_server='www.paypal.com';
	var $module_code='paypal';
	var $from_paypal=array('defaults'=>array(	'residence_country'=>'',
												'first_name'=>'',
												'last_name'=>'',
												'business'=>'',
												'receiver_email'=>'',
												'payer_email'=>'',
												'txn_id'=>'',
												'txn_type'=>'',
												'payment_status'=>'',
												'mc_gross'=>'',
												'mc_currency'=>'',
												'verify_sign'=>'',
												'test_ipn'=>0,
												'recurring'=>0,
												'item_number'=>0,
												'custom'=>''
											),
							'types'=>	array(	'residence_country'=>FIELD_TEXTFIELD,
												'first_name'=>FIELD_TEXTFIELD,
												'last_name'=>FIELD_TEXTFIELD,
												'business'=>FIELD_TEXTFIELD,
												'receiver_email'=>FIELD_TEXTFIELD,
												'payer_email'=>FIELD_TEXTFIELD,
												'txn_id'=>FIELD_TEXTFIELD,
												'txn_type'=>FIELD_TEXTFIELD,
												'payment_status'=>FIELD_TEXTFIELD,
												'mc_gross'=>FIELD_FLOAT,
												'mc_currency'=>FIELD_TEXTFIELD,
												'verify_sign'=>FIELD_TEXTFIELD,
												'test_ipn'=>FIELD_INT,
												'recurring'=>FIELD_INT,
												'item_number'=>FIELD_INT,
												'custom'=>FIELD_TEXTFIELD
											));

	function payment_paypal() {
		$this->ipayment();
		$this->_init();
	}


	function get_buy_button($payment=array()) {
		$this->_set_payment($payment);
		$custom=array();
		$custom[]='dit='.$this->payment['dm_item_type'];
		if (!empty($this->payment['user_id'])) {
			$custom[]='uid='.$this->payment['user_id'];
		}
		$myreturn='<form action="https://'.$this->paypal_server.'/cgi-bin/webscr" method="post" id="payment_paypal">
		<input type="hidden" name="business" value="'.$this->config['paypal_email'].'" />
		<input type="hidden" name="return" value="'._BASEURL_.'/thankyou.php?p='.$this->module_code.'" />
		<input type="hidden" name="notify_url" value="'._BASEURL_.'/processors/ipn.php?p='.$this->module_code.'" />
		<input type="hidden" name="cancel_return" value="'._BASEURL_.'" />
		<input type="hidden" name="item_name" value="'.$this->payment['internal_name'].'" />
		<input type="hidden" name="item_number" value="'.$this->payment['internal_id'].'" />
		<input type="hidden" name="custom" value="'.join('&',$custom).'" />
		<input type="hidden" name="quantity" value="1" />
		<input type="hidden" name="no_shipping" value="1" />
		<input type="hidden" name="no_note" value="1" />
		<input type="hidden" name="rm" value="2" />
		<input type="hidden" name="currency_code" value="'.$this->payment['currency'].'" />';
		if ($this->payment['dm_item_type']=='subscr') {
			$myreturn.="\n".'<input type="hidden" name="cmd" value="_xclick-subscriptions" />
			<input type="hidden" name="p3" value="'.$this->payment['duration'].'" />
			<input type="hidden" name="t3" value="DAY" />
			<input type="hidden" name="a3" value="'.$this->payment['price'].'" />
			<input type="hidden" name="sra" value="1" />';
			if ($this->payment['is_recurent']==1) {
				$myreturn.="\n".'<input type="hidden" name="src" value="1" />';
			}
		} elseif ($this->payment['dm_item_type']=='prod') {
			$myreturn.="\n".'<input type="hidden" name="cmd" value="_xclick" />
			<input type="hidden" name="amount" value="'.$this->payment['price'].'" />';
		}
		$myreturn.="\n".'<input type="image" src="'._BASEURL_.'/images/paypal_buynow.gif" alt="Buy with PayPal" />
		</form>';
		return $myreturn;
	}


	function redirect2gateway($payment=array()) {
		$this->_set_payment($payment);
		$custom=array();
		$custom[]='dit='.$this->payment['dm_item_type'];
		if (!empty($this->payment['user_id'])) {
			$custom[]='uid='.$this->payment['user_id'];
		}
		$topass=array(	'business'=>$this->config['paypal_email'],
						'return'=>_BASEURL_.'/thankyou.php?p='.$this->module_code,
						'notify_url'=>_BASEURL_.'/processors/ipn.php?p='.$this->module_code,
						'cancel_return'=>_BASEURL_,
						'item_name'=>$this->payment['internal_name'],
						'item_number'=>$this->payment['internal_id'],
						'custom'=>join('&',$custom),
						'quantity'=>1,
						'no_shipping'=>1,
						'no_note'=>1,
						'rm'=>2,
						'currency_code'=>$this->payment['currency'],
						);
		if ($this->payment['dm_item_type']=='subscr') {
			$topass['cmd']='_xclick-subscriptions';
			$topass['p3']=$this->payment['duration'];
			$topass['t3']='DAY';
			$topass['a3']=$this->payment['price'];
			$topass['sra']=1;
			if ($this->payment['is_recurent']==1) {
				$topass['src']=1;
			}
		} elseif ($this->payment['dm_item_type']=='prod') {
			$topass['cmd']='_xclick';
			$topass['amount']=$this->payment['price'];
		}
		post2page('https://'.$this->paypal_server.'/cgi-bin/webscr',$topass,true);
	}


	function thankyou(&$tpl) {
		global $dbtable_prefix;
		if (!empty($this->config['pdt_token']) && isset($_GET['tx'])) {
			$postpdt='tx='.$_GET['tx'].'&at='.$this->config['pdt_token'].'&cmd=_notify-synch';
			$errno=0;
			$errstr='';
			$socket=fsockopen($this->paypal_server,80,$errno,$errstr,30);
			if ($socket) {
				$header="POST /cgi-bin/webscr HTTP/1.0\r\n";
				$header.='Host: '.$this->paypal_server."\r\n";
				$header.="Content-Type: application/x-www-form-urlencoded\r\n";
				$header.='Content-Length: '.strlen($postpdt)."\r\n";
				$header.="Connection: close\r\n\r\n";
				fputs($socket,$header.$postpdt."\r\n\r\n");
				$reply='';
				$headerdone=false;
				while(!feof($socket)) {
					$line=fgets($socket);
					if (strcmp($line,"\r\n")==0) {
						// read the header
						$headerdone=true;
					} elseif ($headerdone) {
						// header has been read. now read the contents
						$reply.=$line;
					}
				}
				fclose($socket);
				// parse the data
				$lines=explode("\n",trim($reply));
				if (strcmp($lines[0],'SUCCESS')==0) {
					for ($i=1,$num_lines=count($lines);$i<$num_lines;++$i) {
						list($k,$v)=explode('=',$lines[$i]);
						$_POST[urldecode($k)]=urldecode($v);
					}
					foreach ($this->from_paypal['types'] as $k=>$v) {
						$input[$k]=sanitize_and_format_gpc($_POST,$k,$GLOBALS['__field2type'][$v],$GLOBALS['__field2format'][$v],$this->from_paypal['defaults'][$k]);
					}
					$this->process($input,'pdt');
				} else {
					$tpl->set_var('gateway_text','The paypal payment plugin received an unhandled error code. Please contact the administrator about this if you think this is an error!');
					require_once _BASEPATH_.'/includes/classes/log_error.class.php';
					new log_error(array('module_name'=>get_class($this),'text'=>'PDT received non-SUCCESS code: '.array2qs($lines)));
				}
			} else {
				// socket problem
				$tpl->set_var('gateway_text','Unable to connect to Paypal for validation. Please contact the administrator!');
				require_once _BASEPATH_.'/includes/classes/log_error.class.php';
				new log_error(array('module_name'=>get_class($this),'text'=>'Connection to paypal server failed with error: '.$errstr."\n".array2qs($_GET)));
			}
		}
	}


	function ipn() {
/*
		ob_start();
		print_r($_REQUEST);
		$debug_text=ob_get_contents();
		ob_end_clean();
		$fp=fopen('/tmp/ipn.txt','ab');
		fwrite($fp,$debug_text."\n-------\n\n");
*/
		header('Status: 200 OK');
		$myreturn=false;
		global $dbtable_prefix;
		$input=array();
		foreach ($this->from_paypal['types'] as $k=>$v) {
			$input[$k]=sanitize_and_format_gpc($_POST,$k,$GLOBALS['__field2type'][$v],$GLOBALS['__field2format'][$v],$this->from_paypal['defaults'][$k]);
		}

		// validation
		$postipn='cmd=_notify-validate&'.array2qs($_POST,array('p'));
		$errno=0;
		$errstr='';
		$socket=fsockopen($this->paypal_server,80,$errno,$errstr,30);
		if ($socket) {
			$header="POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header.='Host: '.$this->paypal_server."\r\n";
			$header.="Content-Type: application/x-www-form-urlencoded\r\n";
			$header.='Content-Length: '.strlen($postipn)."\r\n";
			$header.="Connection: close\r\n\r\n";
			fputs($socket,$header.$postipn."\r\n\r\n");
			$reply='';
			$headerdone=false;
			while(!feof($socket)) {
				$line=fgets($socket);
				if (strcmp($line,"\r\n")==0) {
					// read the header
					$headerdone=true;
				} elseif ($headerdone) {
					// header has been read. now read the contents
					$reply.=$line;
				}
			}
			fclose ($socket);
			$reply=trim($reply);
			if (strcasecmp($reply,'VERIFIED')==0 || strcasecmp($reply,'VERIFIED')!=0) {
				$this->process($input,'ipn');
			} elseif (strcasecmp($reply,'INVALID')==0) {
				require_once _BASEPATH_.'/includes/classes/log_error.class.php';
				new log_error(array('module_name'=>get_class($this),'text'=>'Transaction verification with paypal server failed as invalid: '.array2qs($_POST)));
			} else {
				require_once _BASEPATH_.'/includes/classes/log_error.class.php';
				new log_error(array('module_name'=>get_class($this),'text'=>'Transaction verification with paypal server failed with unknown code: '.$reply.' '.array2qs($_POST)));
			}
		} else {
			// socket problem
			require_once _BASEPATH_.'/includes/classes/log_error.class.php';
			new log_error(array('module_name'=>get_class($this),'text'=>'Connection to paypal server failed with error: '.$errstr."\n".array2qs($_POST)));
		}
//fclose($fp);
	}


	function check_fraud($pay_result) {
		$fraud_managers=get_site_options_by_module_type('enabled',MODULE_FRAUD);
		foreach ($fraud_managers as $module_code=>$v) {
			if ($v['enabled']) {
				require_once(_BASEPATH_.'/plugins/fraud/'.$module_code.'/'.$module_code.'.class.php');
				$class='fraud_'.$module_code;
				$fraud=new $class;
				if ($fraud->is_fraud($pay_result)) {
					$this->is_fraud=true;
					$this->fraud_reason=$fraud->get_fraud_reason();
					break;
				}
			}
		}
	}


	function process(&$input,$type) {
		global $dbtable_prefix;
		require_once _BASEPATH_.'/includes/classes/log_error.class.php';
		new log_error(array('module_name'=>get_class($this),'text'=>$type.': new notif from paypal: $_POST:'.var_export($_POST,true).' $_GET:'.var_export($_GET,true).' $input:'.var_export($input,true)));
		if (strcasecmp($input['business'],$this->config['paypal_email'])==0 || strcasecmp($input['receiver_email'],$this->config['paypal_email'])==0) {
			// some transformations
			parse_str($input['custom'],$temp);
			if (!empty($temp['uid'])) {
				$input['user_id']=$temp['uid'];
			}
			$input['dm_item_type']=$temp['dit'];
			$input['business']=strtolower($input['business']);
			$input['receiver_email']=strtolower($input['receiver_email']);
			$input['first_name']=ucwords(strtolower($input['first_name']));
			$input['last_name']=ucwords(strtolower($input['last_name']));
			$query="SELECT get_lock('".$input['txn_id']."',10)";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			if (mysql_result($res,0,0)==1) {
				$query="SELECT `payment_id`,`is_subscr`,`name` FROM `{$dbtable_prefix}payments` WHERE `gw_txn`='".$input['txn_id']."' AND `date`>=now()-INTERVAL 1 DAY";
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				if (mysql_num_rows($res)) {	// the other process already did the job. Let's release the lock
					if ($type=='pdt') {
						$output=mysql_fetch_assoc($res);
						// tell member that he will receive everything by email
						if ($output['is_subscr']) {
							$GLOBALS['tpl']->set_file('gateway_text','thankyou_subscr_nok.html');
						} else {
							$GLOBALS['tpl']->set_file('gateway_text','thankyou_prod_nok.html');
						}
						$GLOBALS['tpl']->set_var('output',$output);
						$GLOBALS['tpl']->process('gateway_text','gateway_text',TPL_OPTIONAL);
					}
					$query="SELECT release_lock('".$input['txn_id']."')";
					if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				} else {	// we arrived before the other process, let's do the dirty work...
					if ($input['dm_item_type']=='subscr') {
						$query="SELECT `".USER_ACCOUNT_ID."` as `user_id`,`".USER_ACCOUNT_USER."` as `user` FROM `".USER_ACCOUNTS_TABLE."` WHERE `".USER_ACCOUNT_ID."`=".$input['user_id'];
						if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
						if (mysql_num_rows($res)) {
							$real_user=mysql_fetch_assoc($res);
							if (strcasecmp($input['txn_type'],'web_accept')==0 || strcasecmp($input['txn_type'],'send_money')==0 || strcasecmp($input['txn_type'],'subscr_payment')==0) {
								if (strcasecmp($input['payment_status'],'Completed')==0) {
									$query="SELECT `subscr_id`,`price`,`m_value_to`,`duration` FROM `{$dbtable_prefix}subscriptions` WHERE `subscr_id`=".$input['item_number']." AND `is_visible`=1";
									if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
									if (mysql_num_rows($res)) {
										$real_subscr=mysql_fetch_assoc($res);
										if (number_format($real_subscr['price'],2)==number_format($input['mc_gross'],2)) {
											if ($input['test_ipn']!=1 || ($this->config['demo_mode']==1 && $input['test_ipn']==1)) {
												require_once _BASEPATH_.'/includes/iso31661a2.inc.php';
												if (isset($GLOBALS['iso31661a2'][$input['residence_country']])) {
													$input['country']=$GLOBALS['iso31661a2'][$input['residence_country']];
													$input['email']=$input['payer_email'];
													$this->check_fraud($input);
												} else {
													$this->is_fraud=true;
													$this->fraud_reason='Invalid country code received from paypal. Please contact administrator.';
													require_once _BASEPATH_.'/includes/classes/log_error.class.php';
													new log_error(array('module_name'=>get_class($this),'text'=>'country code received from paypal not found in iso31661a2.inc.php file'.array2qs($_POST)));
												}
												if (!empty($real_subscr['duration'])) {
													// if the old subscription is not over yet, we need to extend the new one with some days
													$query="SELECT a.`payment_id`,UNIX_TIMESTAMP(a.`paid_until`) as `paid_until`,b.`price`,b.`duration` FROM `{$dbtable_prefix}payments` a LEFT JOIN `{$dbtable_prefix}subscriptions` b ON a.`fk_subscr_id`=b.`subscr_id` WHERE a.`fk_user_id`=".$real_user['user_id']." AND a.`refunded`=0 AND a.`is_active`=1 AND a.`is_subscr`=1 AND a.`m_value_to`>2 ORDER BY a.`paid_until` DESC LIMIT 1";
													if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
													if (mysql_num_rows($res)) {
														$rsrow=mysql_fetch_assoc($res);
														if ((int)$rsrow['paid_until']>(int)time()) {
															$remaining_days=((int)$rsrow['paid_until']-(int)time())/86400;  //86400 seconds in a day
															if ($remaining_days>0) {
																$remaining_value=(((int)$rsrow['price'])/((int)$rsrow['duration']))*$remaining_days;
																$day_value_new=((int)$real_subscr['price'])/((int)$real_subscr['duration']);
																$days_append=round($remaining_value/$day_value_new);
																$real_subscr['duration']=(int)$real_subscr['duration'];
																$real_subscr['duration']+=$days_append;
															}
														}
													}
												}
												// all old active subscriptions end now!
												$query="UPDATE `{$dbtable_prefix}payments` SET `paid_until`=CURDATE(),`is_active`=0 WHERE `fk_user_id`=".$real_user['user_id']." AND `is_active`=1 AND `is_subscr`=1";
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												// insert the new subscription
												$query="INSERT INTO `{$dbtable_prefix}payments` SET `is_active`=1,`fk_user_id`=".$real_user['user_id'].",`_user`='".$real_user['user']."',`gateway`='".$this->module_code."',`is_subscr`=1,`fk_subscr_id`=".$real_subscr['subscr_id'].",`gw_txn`='".$input['txn_id']."',`name`='".$input['first_name'].' '.$input['last_name']."',`country`='".$input['country']."',`email`='".$input['payer_email']."',`m_value_to`=".$real_subscr['m_value_to'].",`amount_paid`='".$input['mc_gross']."',`is_suspect`=".(int)$this->is_fraud.",`suspect_reason`='".$this->fraud_reason."',`paid_from`=CURDATE(),`date`=now()";
												if (!empty($real_subscr['duration'])) {
													$query.=",`paid_until`=CURDATE()+INTERVAL ".$real_subscr['duration'].' DAY';
												}
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												if (!$this->is_fraud) {
													$query="UPDATE `".USER_ACCOUNTS_TABLE."` SET `membership`=".$real_subscr['m_value_to']." WHERE `".USER_ACCOUNT_ID."`=".$real_user['user_id'];
													if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
													$myreturn=true;
													add_member_score($real_user['user_id'],'payment');
													if ($type=='pdt') {
														$GLOBALS['tpl']->set_file('gateway_text','thankyou_subscr_ok.html');
													}
												} else {
													if ($type=='pdt') {
														$output['name']=$input['card_holder_name'];
														$GLOBALS['tpl']->set_file('gateway_text','thankyou_subscr_nok.html');
														$GLOBALS['tpl']->set_var('output',$output);
														$GLOBALS['tpl']->process('gateway_text','gateway_text',TPL_OPTIONAL);
													}
													// DEPT_ADMIN from includes/admin_functions.inc.php is hardcoded below as 4
													$query="SELECT `email` FROM `{$dbtable_prefix}admin_accounts` WHERE `dept_id`=4 ORDER BY `admin_id` DESC LIMIT 1";
													if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
													if (mysql_num_rows($res)) {
														send_template_email(mysql_result($res,0,0),'Possible fraud detected on '._SITENAME_.', please investigate','','',array(),$this->module_code.' TXN: '.$input['txn_id'].': '.$this->fraud_reason);
													}
												}
											} else {
												// a demo transaction when we're not in demo mode
												if ($type=='pdt') {
													$GLOBALS['tpl']->set_var('gateway_text','We\'re sorry but there were some problems processing your payment. Please contact us to upgrade your subscription');	// translate this
												}
												require_once _BASEPATH_.'/includes/classes/log_error.class.php';
												new log_error(array('module_name'=>get_class($this),'text'=>'Demo transaction when demo is not enabled: '.array2qs($_POST)));
											}
										} else {
											// paid price doesn't match the subscription price
											if ($type=='pdt') {
												$GLOBALS['tpl']->set_var('gateway_text','We\'re sorry but the price you\'ve paid doesn\'t match the subscription price. Please contact us to upgrade your subscription');	// translate this
											}
											require_once _BASEPATH_.'/includes/classes/log_error.class.php';
											new log_error(array('module_name'=>get_class($this),'text'=>'Invalid amount paid: '.array2qs($_POST)));
										}
									} else {
										// if the subscr_id was not found
										if ($type=='pdt') {
											$GLOBALS['tpl']->set_var('gateway_text','We\'re sorry but the system doesn\'t recognize the subscription for the payment you\'ve made. Please contact us to upgrade your subscription');	// translate this
										}
										require_once _BASEPATH_.'/includes/classes/log_error.class.php';
										new log_error(array('module_name'=>get_class($this),'text'=>'Invalid subscr_id received after payment: '.array2qs($_POST)));
									}
								} else {
									if ($type=='pdt') {
										$GLOBALS['tpl']->set_var('gateway_text','Thank you for your payment. The transaction is still <strong>pending</strong> at the moment. As soon as the payment clears, a receipt for your purchase will be emailed to you and your membership will be upgraded.<br>You may log into your account at http://www.paypal.com to view details of this transaction.');	// translate this
									}
									require_once _BASEPATH_.'/includes/classes/log_error.class.php';
									new log_error(array('module_name'=>get_class($this),'text'=>'Payment status not Completed: '.$input['payment_status']."\n".array2qs($_POST)));
								}
							} elseif (strcasecmp($input['txn_type'],'subscr_eot')==0) {
								$query="SELECT `payment_id` FROM `{$dbtable_prefix}payments` WHERE `fk_user_id`=".$real_user['user_id']." AND `fk_subscr_id`=".$input['item_number']." AND `is_active`=1 ORDER BY `payment_id` DESC LIMIT 1";
								if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
								if (mysql_num_rows($res)) {
									$payment_id=mysql_result($res,0,0);
									$query="UPDATE `{$dbtable_prefix}payments` SET `paid_until`=CURDATE() WHERE `payment_id`=$payment_id";
									if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
								} else {
									// invalid eot.
									require_once _BASEPATH_.'/includes/classes/log_error.class.php';
									new log_error(array('module_name'=>get_class($this),'text'=>'Received End of Term notification for a subscription but subscription doesn\'t exist or not active. Maybe this member has 2 running subscriptions? '.array2qs($_POST)));
								}
							} else {
								// unhandled txn_type
								if ($type=='pdt') {
									$GLOBALS['tpl']->set_var('gateway_text','Error: Unhandled transaction type received from Paypal.');	// translate this
								}
								require_once _BASEPATH_.'/includes/classes/log_error.class.php';
								new log_error(array('module_name'=>get_class($this),'text'=>'Unhandled txn_type (probably not an error): '.$input['txn_type']."\n".array2qs($_POST)));
							}
						} else {
							// if the user_id was not found
							if ($type=='pdt') {
								$GLOBALS['tpl']->set_var('gateway_text','Error: Sorry, your username could not be found. Please contact us to upgrade your membership.');	// translate this
							}
							require_once _BASEPATH_.'/includes/classes/log_error.class.php';
							new log_error(array('module_name'=>get_class($this),'text'=>'Invalid user_id received after payment: '.array2qs($_POST)));
						}

					} elseif ($input['dm_item_type']=='prod') {

						if (strcasecmp($input['txn_type'],'web_accept')==0 || strcasecmp($input['txn_type'],'send_money')==0) {
							if (strcasecmp($input['payment_status'],'Completed')==0) {
								$real_user=array();
								if (!empty($input['user_id'])) {
									$query="SELECT `fk_user_id` as `user_id`,`_user` as `user`,`f2` as `email1`,`f7` as `email2`,`f8` as `email3` FROM `{$dbtable_prefix}user_profiles` WHERE `fk_user_id`=".$input['user_id'];
									if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
									if (mysql_num_rows($res)) {
										$real_user=mysql_fetch_assoc($res);
									}
								}
								// alternate method(s) of figuring out if we already have this customer in the db
								if (empty($real_user['user_id'])) {
									$query="SELECT `fk_user_id` as `user_id`,`_user` as `user`,`f2` as `email1`,`f7` as `email2`,`f8` as `email3` FROM `{$dbtable_prefix}user_profiles` WHERE `f2`='".$input['payer_email']."' OR `f7`='".$input['payer_email']."' OR `f8`='".$input['payer_email']."' LIMIT 1";
									if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
									if (mysql_num_rows($res)) {
										$real_user=mysql_fetch_assoc($res);
									}
								}
								$query="SELECT `prod_id`,`price`,`bundle_of` FROM `products` WHERE `prod_id`=".$input['item_number'];
								if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
								if (mysql_num_rows($res)) {
									$real_prod=mysql_fetch_assoc($res);
									if (number_format($real_prod['price'],2)==number_format($input['mc_gross'],2)) {
										if ($input['test_ipn']!=1 || ($this->config['demo_mode']==1 && $input['test_ipn']==1)) {
											require_once(_BASEPATH_.'/includes/iso31661a2.inc.php');
											if (isset($GLOBALS['iso31661a2'][$input['residence_country']])) {
												$input['country']=$GLOBALS['iso31661a2'][$input['residence_country']];
												$input['email']=$input['payer_email'];
												$this->check_fraud($input);
											} else {
												$this->is_fraud=true;
												$this->fraud_reason='Invalid country code received from paypal. Please contact administrator.';
												require_once _BASEPATH_.'/includes/classes/log_error.class.php';
												new log_error(array('module_name'=>get_class($this),'text'=>'country code received from paypal not found in iso31661a2.inc.php file'.array2qs($_POST)));
											}
											// insert the new payment
											$query="INSERT INTO `{$dbtable_prefix}payments` SET `gateway`='".$this->module_code."',`is_subscr`=0,`is_active`=0,`fk_subscr_id`=0,`gw_txn`='".$input['txn_id']."',`name`='".$input['first_name'].' '.$input['last_name']."',`country`='".$input['residence_country']."',`email`='".$input['payer_email']."',`amount_paid`='".$input['mc_gross']."',`is_suspect`=".((int)$this->is_fraud).",`suspect_reason`='".addslashes($this->fraud_reason)."',`date`=now()";
											if (isset($real_user['user_id'])) {
												$query.=",`fk_user_id`=".$real_user['user_id'].",`_user`='".$real_user['user']."'";
											}
											if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
											$payment_id=mysql_insert_id();

											$prods=array();
											if (!empty($real_prod['bundle_of'])) {
												$prods=explode('|',$real_prod['bundle_of']);
											} else {
												$prods[]=$real_prod['prod_id'];
											}
											// if this is a new customer, create an account and profile for him
											if (empty($real_user['user_id'])) {
												$input['pass']=gen_pass(6);
												$query="INSERT IGNORE INTO `".USER_ACCOUNTS_TABLE."` SET `".USER_ACCOUNT_USER."`='".$input['payer_email']."',`".USER_ACCOUNT_PASS."`=md5('".$input['pass']."'),`email`='".$input['payer_email']."',`membership`=4";
												if ($this->is_fraud) {
													$query.=',`status`='.ASTAT_SUSPENDED;
												} else {
													$query.=',`status`='.ASTAT_ACTIVE;
												}
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												$real_user['user_id']=mysql_insert_id();
												$real_user['user']=$input['payer_email'];
												$now=gmdate('YmdHis');
												$query="INSERT IGNORE INTO `{$dbtable_prefix}user_profiles` SET `fk_user_id`='".$real_user['user_id']."',`_user`='".$real_user['user']."',`last_changed`='$now',`date_added`='$now',`status`=".STAT_APPROVED.",`f1`='".$input['first_name'].' '.$input['last_name']."',`f2`='".$input['payer_email']."',`score`='".$input['mc_gross']."'";
												if ($this->is_fraud) {
													$query.=",`f11`=1";	// is_blocked
												}
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												// update the payment with this user_id
												$query="UPDATE `{$dbtable_prefix}payments` SET `fk_user_id`=".$real_user['user_id'].",`_user`='".$real_user['user']."' WHERE `payment_id`=$payment_id";
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
											} else {
												// older customer found. Update score and email
												$query="UPDATE `{$dbtable_prefix}user_profiles` SET `score`=`score`+".$input['mc_gross'];
												if ($input['payer_email']!=$real_user['email1'] && $input['payer_email']!=$real_user['email2'] && $input['payer_email']!=$real_user['email3']) {
													if (empty($real_user['email2'])) {
														$query.=",`f7`='".$input['payer_email']."'";
													} elseif (empty($real_user['email3'])) {
														$query.=",`f8`='".$input['payer_email']."'";
													} else {
														$query.=",`f9`=concat(`f9`,'".$input['payer_email']."\n')";
													}
												}
												$query.=" WHERE `fk_user_id`=".$real_user['user_id'];
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
											}

											// if he bought Etano, create a new site
											if (in_array(ETANO_PROD_ID,$prods)) {
												$query="INSERT INTO `user_sites` SET `fk_user_id`=".$real_user['user_id'].",`active`=".((int)(!$this->is_fraud));
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												$input['site_id']=mysql_insert_id();
												$input['license']=gen_license(array('site_id'=>$input['site_id'],'name'=>$input['first_name'].' '.$input['last_name']));
												$output['license']=$input['license'];
												$query="UPDATE `user_sites` SET `license`='".$input['license']."',`license_md5`=md5('".$input['license']."') WHERE `site_id`=".$input['site_id'];
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
											} else {
												$query="SELECT `site_id`,`license` FROM `user_sites` WHERE `fk_user_id`=".$real_user['user_id']." LIMIT 1";
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												if (mysql_num_rows($res)) {
													$input=array_merge($input,mysql_fetch_assoc($res));
												} else {	// something must be wrong here....
													$input['site_id']=0;
													$input['license']='';
												}
											}

											// insert all bought products into db
											$query="INSERT INTO `user_products` (`fk_prod_id`,`fk_site_id`,`fk_user_id`,`fk_payment_id`,`license`,`license_md5`) VALUES ";
											for ($i=0;isset($prods[$i]);++$i) {
												$query.="(".$prods[$i].",".$input['site_id'].",".$real_user['user_id'].",$payment_id";
												if ($prods[$i]==ETANO_PROD_ID) {
													$query.=",'".$input['license']."','".md5($input['license'])."'";
												} else {
													$query.=",'',''";
												}
												$query.="),";
											}
											$query=substr($query,0,-1);
											if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}

											// send the thank you email with all required details.
											global $tpl;
											$skin=get_default_skin_dir();
											if (!isset($tpl)) {
												$tpl=new phemplate(_BASEPATH_.'/skins_site/'.$skin.'/','remove_nonjs');
											}
											$output['name']=$input['first_name'].' '.$input['last_name'];
											$output['email']=$input['payer_email'];
											if (!$this->is_fraud) {
												$output['license_md5']=md5($input['license']);
												$output['prod_id']=$real_prod['prod_id'];
												if (isset($input['pass'])) {
													$output['pass']=$input['pass'];
												}
												$output['user']=$real_user['user'];
												$tpl->set_file('gateway_text','thankyou_prod_ok.html');
												$tpl->set_var('output',$output);
												$tpl->process('gateway_text','gateway_text',TPL_OPTIONAL);
												if ($type=='ipn') {
													send_template_email($input['payer_email'],sprintf('Your %s purchase details',_SITENAME_),'general.html',$skin,array('content'=>$tpl->get_var_silent('gateway_text')));
												}
											} else {
												$tpl->set_file('gateway_text','thankyou_prod_nok.html');
												$tpl->set_var('output',$output);
												$tpl->process('gateway_text','gateway_text');
												if ($type=='ipn') {
													send_template_email($input['payer_email'],sprintf('Your %s purchase details',_SITENAME_),'general.html',$skin,array('content'=>$tpl->get_var_silent('gateway_text')));
												}
												// DEPT_ADMIN from includes/admin_functions.inc.php is hardcoded below as 4
												$query="SELECT `email` FROM `{$dbtable_prefix}admin_accounts` WHERE `dept_id`=4 ORDER BY `admin_id` DESC LIMIT 1";
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												if (mysql_num_rows($res)) {
													send_template_email(mysql_result($res,0,0),'Possible fraud detected on '._SITENAME_.', please investigate','','',array(),$this->module_code.' TXN: '.$input['txn_id'].': '.$this->fraud_reason);
												}
											}
										} else {
											// a demo transaction when we're not in demo mode
											if ($type=='pdt') {
												$GLOBALS['tpl']->set_var('gateway_text','We\'re sorry but there were some problems processing your payment. Please contact us to finalize the payment.');	// translate this
											}
											require_once _BASEPATH_.'/includes/classes/log_error.class.php';
											new log_error(array('module_name'=>get_class($this),'text'=>'Demo transaction when demo is not enabled: '.array2qs($_POST)));
										}
									} else {
										// paid price doesn't match the product price
										if ($type=='pdt') {
											$GLOBALS['tpl']->set_var('gateway_text','We\'re sorry but the price you\'ve paid doesn\'t match the product price. Please contact us if you feel this is an error.');	// translate this
										}
										require_once _BASEPATH_.'/includes/classes/log_error.class.php';
										new log_error(array('module_name'=>get_class($this),'text'=>'Invalid amount paid: '.array2qs($_POST)));
									}
								} else {
									// if the prod_id was not found
									if ($type=='pdt') {
										$GLOBALS['tpl']->set_var('gateway_text','We\'re sorry but the system cannot find the product for the payment you\'ve made. Please contact us to finalize the payment.');	// translate this
									}
									require_once _BASEPATH_.'/includes/classes/log_error.class.php';
									new log_error(array('module_name'=>get_class($this),'text'=>'Invalid prod_id received after payment: '.array2qs($_POST)));
								}
							} else {
								if ($type=='pdt') {
									$GLOBALS['tpl']->set_var('gateway_text','Thank you for your payment. The transaction is still <strong>pending</strong> at the moment. As soon as the payment clears, a receipt for your purchase will be emailed to you and your membership will be upgraded.<br>You may log into your account at http://www.paypal.com to view details of this transaction.');	// translate this
								}
								require_once _BASEPATH_.'/includes/classes/log_error.class.php';
								new log_error(array('module_name'=>get_class($this),'text'=>'Payment status not Completed: '.$input['payment_status']."\n".array2qs($_POST)));
							}
						} else {
							// unhandled txn_type
							if ($type=='pdt') {
								$GLOBALS['tpl']->set_var('gateway_text','Error: Unhandled transaction type received from Paypal.');	// translate this
							}
							require_once _BASEPATH_.'/includes/classes/log_error.class.php';
							new log_error(array('module_name'=>get_class($this),'text'=>'Unhandled txn_type (probably not an error): '.$input['txn_type']."\n".array2qs($_POST)));
						}
					} else {	// dm_item_type is neither 'prod' nor 'subscr'
						if ($type=='pdt') {
							$GLOBALS['tpl']->set_var('gateway_text','Invalid payment received. Please contact us if you feel this is an error.');	// translate this
						}
						require_once _BASEPATH_.'/includes/classes/log_error.class.php';
						new log_error(array('module_name'=>get_class($this),'text'=>'Invalid dm_item_type: '.array2qs($_POST)));
					}
					// job done, release the lock
					$query="SELECT release_lock('".$input['txn_id']."')";
					if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				}
			} else {
				// we could not obtain the lock.
				// The other process is taking too long but at least this should mean that it is handling this
			}
		} else {
			require_once _BASEPATH_.'/includes/classes/log_error.class.php';
			new log_error(array('module_name'=>get_class($this),'text'=>'Payment was not made into our account: '.array2qs($_POST)));
		}
	}

	function _init() {
		$this->config=get_site_option(array(),$this->module_code);
		if ($this->config['demo_mode']==1) {
			$this->paypal_server='www.sandbox.paypal.com';
//			$this->paypal_server='www.eliteweaver.co.uk';
		}
	}
}