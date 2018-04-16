<?php
session_start();
include_once('../controller.php');
$error = null;
switch ( $_POST['OPT'] ) 
{		
	case 'CONVERT_ITEM_DB':
		if (!$FNC->isgm())
        	exit;
		
		if ( file_exists('../../db/idnum2itemdesctable.txt') && file_exists('../../db/item_db.txt'))
		{
			if ( $ITEM->setItemDBMain() === 'ok')
			{
				if (file_exists("../../db/item_db.sql")) 
				{
					echo 'error@'.$CONFIG->getConfig('Web').'/modules/admin/admin.item_db.cache_download.php';
					exit;
				}
				else echo 'Can\'t Find the item_db.sql file';
			} else 
				echo $ITEM->setItemDBMain();
		} else 
			echo 'Can\'t find the file idnum2itemdesctable.txt or item_db.txt, did you Upload It?';
	break;
		
	/**
	 *
	 *
	 *
	 **/
	case 'RECOVERPASS':
		if (empty($_POST['uid']) || empty($_POST['email']))
		{
			echo 'missing fields';
			exit;
		}
		
		$consult = "SELECT `email` FROM `login` WHERE `userid`=? AND `email`=?";
		$result = $DB->execute($consult, [$_POST['uid'], $_POST['email']]);
		if (!$DB->num_rows())
		{
			echo "There is no user with this userid or mail";
			exit;
		}
			
		$row = $result->fetch();
			
		$random = (mt_rand() + mt_rand()) * 3;
		$consult = "UPDATE `login` set `user_pass`=? WHERE `userid`=? AND `email`=?";
		$param = [$FNC->CheckMD5($random),$_POST['uid'], $_POST['email']];
		$DB->execute($consult, $param);

		$email = $row["email"];
		$email .= ',';
		$email .= 'recover@oborocp.noreply.com';
		$asunto = "Oboro CP (C) - Account Password Request";
		$message = " ";
		$message.= "An Account Recovery request was made.";
		$message.= "\n	Your New password: ". $random;
		$message.= "\n\n Sincerely: Oboro CP.";
		mail($email, $asunto, $message, "From: Oboro CP");
		echo 'ok';
	break;
		
	/**
	 *
	 *
	 *
	 **/
	case 'LOGIN':
		if (empty($_POST['user']) || empty($_POST['pass']))
		{
			echo 'Missing user or password fields';
			exit;
		}
		
		$consult =
		"
			SELECT 
				`account_id`, `userid`, ".($FNC->get_emulator() == EAMOD ? "`level`" : "`group_id`") .", `user_pass`, `geo_localization`
			FROM 
				`login` 
			WHERE 
				userid = ? 
			AND 
				`user_pass`= ?
			AND 
				state != 5
		";
		$result = $DB->execute($consult, [$_POST['user'], $FNC->CheckMD5($_POST['pass'])]);
		$row = $result->fetch();
		$forum_disp = $DB->execute("SELECT `display_name`, `img_url` FROM `oboro_forum_user` WHERE `account_id`=?", [$row['account_id']], "Forum")->fetch();

		if (!$row['account_id'])
		{
			echo 'Wrong username or password';
			exit;
		}
		
		if ($CONFIG->getConfig('UseGeoLocalization') == 'yes')
		{
			$GIP = geoip_open('GeoIP.dat',GEOIP_STANDARD);
			$GeoLocalization = geoip_country_name_by_addr($GIP, $FNC->getIP());
	
			if (!empty($GeoLocalization))
				; // continua en error... [Isaac]
			elseif (empty($row['geo_localization']) || $row['geo_localization'] == 'Undefined')
			{
				$consult =
				"
					UPDATE 
						`login`
					SET	
						`geo_localization` = ?
					WHERE 
						`account_id` = ?
				";
				$DB->execute($consult, [(!empty($GeoLocalization) ? $GeoLocalization : "Undefined" ),$row['account_id']]);	
			}
			else if ( geoip_country_name_by_addr($GIP, $FNC->getIP()) != $row['geo_localization'] )
			{
				$consult =
				"
					INSERT INTO `oboro_geo_localization_fail_log`(`ip`, `userid`, `date`, `zone`)
					VALUES (?,?,?,?)
				";
				$DB->execute($consult, [$FNC->getIP(), $_POST['user'], date("Y-m-d H:i:s"), $GeoLocalization]);
				
				if ($result->rowCount())
				{
					$_SESSION['GEO_USERID'] = $_POST['user'];
						echo 'User location: '.geoip_country_name_by_addr($GIP, $FNC->getIP()).'. Authentication fail';
						echo '
							<script type="text/javascript">
								window.location = "'.$CONFIG->getConfig('Web').'?account.recover.geolocalization"
							</script>
						';
						die();
				}
				else
				{
					echo 'Something wrong happened';
					exit;
				}
			}
		}
					
		$_SESSION['account_id']  = $row['account_id'];
		$_SESSION['userid']		 = $row['userid'];
		$_SESSION['level']		 = $row[($FNC->get_emulator() == EAMOD ? "level" : "group_id")];
		$_SESSION['password']	 = $row['user_pass'];
		$_SESSION['ip']			 = $FNC->getIP();
        $_SESSION['display_name']= $forum_disp['display_name'];
        $_SESSION['tmpimg']      = $forum_disp['img_url'];
        echo 'ok';
	break;

	/**
	 *
	 *
	 *
	 **/
	case 'REGISTRO':
		if ( empty($_POST['user']) ||
			 empty($_POST['pass']) ||
			 empty($_POST['pass2']) ||
			 empty($_POST['mail']) ||
			 empty($_POST['ip']) || 
			 empty($_POST['sex']) ||
			 empty($_POST['pais']) ||
			 !isset($_POST['question']) ||
			 empty($_POST['question_response']) ||
             empty($_POST['dispname'])
		   )
		{
			echo 'Missings fields';
			exit;
		}

		if (preg_match('/[^a-zA-Z0-9_]/', $_POST['user'])) 
            $error .=  'Invalid character(s) used in username <br/>';
        if (strlen($_POST['user']) < 4)
            $error .= 'Username is too short (min. 4) <br/>';
        if (strlen($_POST['user']) > 23)
            $error .= 'Username is too long (max. 23) <br/>';
        if (stripos($_POST['pass'], $_POST['user']) !== false)
            $error .= 'Password must not contain Username <br/>';
        if (!ctype_graph($_POST['pass']))
            $error .=  'Invalid character(s) used in password <br/>';
        if(strlen($_POST['pass']) < 8)
            $error .= 'Password is too short (min. 8) <br/>';
        if(strlen($_POST['pass']) > 26)
            $error .= 'Password is too long (max. 23) <br/>';
        if ($_POST['pass'] !== $_POST['pass2'])
            $error .= 'Passwords and confirm do not match <br/>';
        if(!in_array(strtoupper($_POST['sex']), array('M', 'F')))
            $error .= 'Invalid gender <br/>';
        if (strlen($_POST['pais']) != 2)
            $error .= 'Invalid Country<br/>';
        if (!is_numeric($_POST['question']))
            $error .= 'Invalid Question';
        if ($_POST['pass'] == $_POST['question_response'])
            $error .= 'For security reasons your Question and Password cannot be the same';
        if (strlen($_POST['question_response']) > 23)
            $error .= 'Question Response too long, only 23 chars per input';
        if (strlen($_POST['dispname']) < 4)
            $error .= 'Display Name too short';
        if (strlen($_POST['dispname']) > 23)
            $error .= 'Display Name too long';
        if(preg_match_all('/[^A-Za-z0-9]/', $_POST['dispname'], $matches) > 0)
		  $error .= 'Incorrect symbol detected in Display Name <br/>';
        if ($_POST['dispname'] === $_POST['user'])
            $error .= 'Username and Display name can\'t be equal<br/>';
        
		if ($CONFIG->getConfig('UseSecurePass') == 'yes')
		{
			if (preg_match_all('/[A-Z]/', $_POST['pass'], $matches) < 1)
				$error .= 'Passwords must contain at least 1 Upper case <br/>';
			if (preg_match_all('/[a-z]/', $_POST['pass'], $matches) < 1)
				$error .= 'Passwords must contain at least 1 lower case <br/>';
			if (preg_match_all('/[0-9]/', $_POST['pass'], $matches) < 1)
				$error .= 'Passwords must contains at least 1 number <br/>';
			if(preg_match_all('/[^A-Za-z0-9]/', $_POST['pass'], $matches) <	1)
				$error .= 'Passwords must contains at least 1 symbol <br/>';
			if (!preg_match('/^(.+?)@(.+?)$/', $_POST['mail']))
				$error .= 'Invalid e-mail address <br/>';
		}
        
        
		
		$consult = "SELECT `userid` FROM `login` WHERE `userid`=? LIMIT 1";
		$result = $DB->execute($consult, [$_POST['user']]);
		if ($DB->num_rows())
			$error .= 'User in use';
		
        $consult = "SELECT `display_name` FROM `oboro_forum_user` WHERE `display_name`=? LIMIT 1";
		$result = $DB->execute($consult, [$_POST['dispname']], "Forum");
		if ($DB->num_rows("Forum"))
			$error .= 'Display Name in use';
        
		if (!is_null($error))
		{
			echo $error;
			break;
		}
		
		if ($CONFIG->getConfig('UseGeoLocalization') == 'yes')
			$GIP = geoip_open('GeoIP.dat',GEOIP_STANDARD);
			
		$consult = "
			INSERT INTO `login` (`userid`, `user_pass`, `sex`, `email`, `last_ip`, `pais`, `geo_localization`, `question`, `question_response`) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
		";
		$param = [
			$_POST['user'], 
			$FNC->CheckMD5($_POST['pass']), 
			$_POST['sex'], 
			$_POST['mail'],
			$_POST['ip'], 
			strtolower($_POST['pais']),
			($CONFIG->getConfig('UseGeoLocalization') == 'yes' ? geoip_country_name_by_addr($GIP, $_POST['ip']) : NULL),
			$_POST['question'],
			$_POST['question_response'],
		];
			
		$result = $DB->execute($consult, $param);
			
		if ($CONFIG->getConfig('UseGeoLocalization') == 'yes')
			geoip_close($GIP);
		if ($result->rowCount())
		{
			$consult =
			"
				SELECT 
					`account_id`, `userid`, `user_pass`, `geo_localization` FROM `login` 
				WHERE 
					userid =? 
				AND 
					`user_pass`=?
				AND 
					state != '5'
			";
			
			$result = $DB->execute($consult, [$_POST['user'], $FNC->CheckMD5($_POST['pass'])]);
			$row = $result->fetch();
			if (!$DB->num_rows())
			{
				echo 'Wrong username or password';
				exit;
			}

			$oboro_user = $DB->execute("INSERT INTO `oboro_forum_user`(`account_id`, `display_name`) VALUES (?,?)", [$row['account_id'], $_POST['dispname']], "Forum");

			
			$_SESSION['account_id']  = $row['account_id'];
			$_SESSION['userid']		 = $row['userid'];
			$_SESSION['password']	 = $row['user_pass'];
			$_SESSION['ip']			 = $FNC->getIP();
			$_SESSION['level']		 = 0;
			echo 'ok';
		} 
		else
			echo 'something went wrong';
	break;

	/**
	 *
	 *
	 *
	 **/
	case 'CHARPANEL':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to create a post';
            exit;
        }
		
		if (!isset($_POST['cid']) || !isset($_POST['slot']) || 	!isset($_POST['nn'])) 
		{
			echo 'Missing fields';
			exit;
		}
		
		$consult = "SELECT `name`, `zeny`, `class`, `char_num`, `last_map`,`partner_id`, `online` FROM `char` WHERE `char_id` = ?";
		$result = $DB->execute($consult, [$_POST['cid']]);

		if (!$DB->num_rows())
		{
			echo 'can not retrive data from char';
			exit;
		}
		
		$row  = $result->fetch();
		$consult = FALSE;
		$error = FALSE;
				
		if ( $_POST['nn'] != $row['name'] ) 
		{
			$consult = "SELECT `name` FROM `char` WHERE `name`=?";
			$result = $DB->execute($consult, [$_POST['nn']]);
			
			if ($DB->num_rows())
				$error = 'User in use. <br/>';
			if(preg_match_all('/[^A-Za-z0-9]/', $_POST['nn'], $matches) >	0)
				$error .= "Incorrect character detected in new name. <br/>";
			if (strlen($_POST['nn']) > 23)
				$error .= "Name too long";
			if (strlen($_POST['nn']) < 4 )
				$error .= 'Name to short <br/>';
		}
			
		if (!empty($error))
		{
			echo $error;
			break;
		} 
			
		$consult = "`name`=?, `char_num`= ?,";
		if (isset($_POST['divorse']))
			$consult .= "`partner_id` = 0, ";
		if (isset($_POST['reset_map']))
			$consult .= "`last_map` = 'prontera', ";
		if (isset($_POST['reset_char']))
			$consult .= "`hair` = 1, `hair_color` = 0, `clothes_color` = 0, ";

		$ALL = 'UPDATE `char` SET '.$consult;
		$ALL = rtrim($ALL, ', ');
		$ALL .= ' WHERE `char_id` = ?';
		$result = $DB->execute($ALL, [$_POST['nn'], $_POST['slot'], $_POST['cid']]);
		
		if ($result->rowCount()) 
		{
			$consult = "INSERT INTO `oboro_nameslog`(`date`,`old_name`,`new_name`)";
			$result = $DB->execute($consult, [date("Y-m-d H:i:s"), $row['name'], $_POST['nn']]);
			if ($DB->num_rows())
				echo 'ok';
			else
				echo 'Log name can\'t be storage, but name has been changed.';
			break;	
		}
		else 
		{
			echo 'Seems to be no changes';
			break;
		}
	break;

	/**
	 *
	 *
	 *
	 **/
    
    case 'CHANGEPASSWORDPANEL':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to create a post';
            exit;
        }
		
        if (empty($_POST['oldpassword']) || empty($_POST['newpassword']))
        {
            echo 'Missing fields';
            exit;
        }

		$consult = "SELECT `userid`,`user_pass` FROM `login` WHERE `account_id` = ?";
		$result = $DB->execute($consult, [$_SESSION['account_id']]);
		$row = $result->fetch();
		if (!$DB->num_rows())
		{
			echo "Something wrong happened";
			exit;
		}

        			
        if (stripos($_POST['newpassword'], $_SESSION['userid']) !== false)
			$error .= 'Password must not contain Username <br/>';
		if (!ctype_graph($_POST['newpassword']))
			$error .=  'Invalid character(s) used in password <br/>';
		if(strlen($_POST['newpassword']) < 8)
			$error .= 'Password is too short (min. 8) <br/>';
		if(strlen($_POST['newpassword']) > 26)
			$error .= 'Password is too long (max. 23) <br/>';
		if ($CONFIG->getConfig('UseSecurePass') == 'yes')
		{
			if (preg_match_all('/[A-Z]/', $_POST['newpassword'], $matches) < 1)
				$error .= 'Passwords must contain at least 1 Upper case <br/>';
			if (preg_match_all('/[a-z]/', $_POST['newpassword'], $matches) < 1)
				$error .= 'Passwords must contain at least 1 lower case <br/>';
			if (preg_match_all('/[0-9]/', $_POST['newpassword'], $matches) < 1)
				$error .= 'Passwords must contains at least 1 number <br/>';
			if(preg_match_all('/[^A-Za-z0-9]/', $_POST['newpassword'], $matches) <	1)
				$error .= 'Passwords must contains at least 1 symbol <br/>';
		}
		
		if ($FNC->CheckMD5($_POST['oldpassword']) != $row['user_pass'])
			$error .= 'Incorrect current password. <br/>';
		if ($_POST['oldpassword'] == $_POST['newpassword'])
            $error .= 'password are the same';
        
		if ( strlen($error) > 0 ) 
		{
			echo $error;
			break;
		}
		
		$result = $DB->execute("UPDATE `login` SET `user_pass`=? WHERE `account_id`=?", [$FNC->CheckMD5($_POST['newpassword']), $_SESSION['account_id']]);
		if ($result->rowCount())
			echo 'ok';
		else
			echo 'Something wrong happened';
    break;
    
	case 'ACCOUNTPANEL':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to create a post';
            exit;
        }
		
		if ( 
			!isset($_POST['sex']) || 
			!isset($_POST['pais'])
		) 
		{
            echo 'Missing fields';
            exit;
        }
        
            
		$error = '';
		$consult = "SELECT `userid`,`email`,`state`,`sex`,`lastlogin`,`last_ip`, `pais` FROM `login` WHERE `account_id` = ?";
		$result = $DB->execute($consult, [$_SESSION['account_id']]);
		if (!$DB->num_rows())
		{
		  echo "Something wrong happened";
            exit;
		}
			
		$row = $result->fetch();

		if (!in_array($_POST['sex'], array('F', 'M')))
		  $error = 'Invalid Sex. <br/>';
		if ( strlen($_POST['pais']) != 2)
		  $error .= 'Pais no valido. <br/>';

        if (strlen($_POST['dispname']) < 4)
            $error .= 'Display Name too short';
        if (strlen($_POST['dispname']) > 23)
            $error .= 'Display Name too long';
        if(preg_match_all('/[^A-Za-z0-9]/', $_POST['dispname'], $matches) > 0)
            $error .= 'Incorrect symbol detected in Display Name <br/>';
            
        $consult = "SELECT `display_name` FROM `oboro_forum_user` WHERE `display_name`=? AND `account_id` != ? LIMIT 1";
        $result = $DB->execute($consult, [$_POST['dispname'], $_SESSION['account_id']], "Forum");
		if ($DB->num_rows("Forum"))
            $error .= 'Display Name in use';
            
		if ( strlen($error) > 0 ) 
		{
            echo $error;
            break;
        }
				
		$consult = "UPDATE `login` SET `sex`=?, `pais`=? WHERE `account_id`=?";
		$result = $DB->execute($consult, [$_POST['sex'], strtolower($_POST['pais']), $_SESSION['account_id']]);
				
		$result2 = $DB->execute("INSERT INTO `oboro_forum_user`(`account_id`, `display_name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `display_name`=?", [$_SESSION['account_id'], $_POST['dispname'], $_POST['dispname']], "Forum")->fetch();
		
		if ($result->rowCount() || $DB->num_rows("Forum"))
		{
			if (isset($_POST['recoverSec']))
			{
				$consult =
				"
					SELECT 
						`value` 
					FROM 
						". ($FNC->get_emulator() == EAMOD ? '`global_reg_value`' : '`char_reg_num`')." AS `global`
					WHERE 
						`account_id`=?
					AND 
						`global`.".($FNC->get_emulator() == EAMOD ? '`str`':'`key`')."='#SECURITYCODE'
					LIMIT 1
				";
				$result = $DB->execute($consult, [$_SESSION['account_id']]);
				if ($DB->num_rows())
				{
					$get_sec = $result->fetch();
					mail($row['email'], 'Security Password', 'Your security of the account: '.$row['userid'].' is: '. $get_sec['value']);	
				}
			}		
			echo 'ok';
		}
		else
			echo 'No changes detected';
	break;

	/**
	 *
	 *
	 *
	 **/
	case 'LOGIN_WITH_GEO':
		if (!empty($_POST['user_question_response']) && !empty($_SESSION['GEO_USERID']))
		{
			if(preg_match_all('/[^A-Za-z0-9 ]/', $_POST['user_question_response'], $matches) > 0)
			{
				echo 'Incorrect character detected in Question Response. <br/>';
				break;
			}
			
			$consult = 'SELECT `question_response` FROM `login` WHERE `userid`=?';
			$result = $DB->execute($consult,[$_SESSION['GEO_USERID']]);
			if ($DB->num_rows())
			{
				$row = $result->fetch();
				similar_text(strtolower($row['question_response']), strtolower($_POST['user_question_response']), $similar);
				if ( $similar > 96 )
				{
					$GIP = geoip_open('GeoIP.dat',GEOIP_STANDARD);
					$GeoLocalization = geoip_country_name_by_addr($GIP, $FNC->getIP());
					
					$consult = 'UPDATE `login` SET `geo_localization`=? WHERE `userid`=?';
					$param = [(!empty($GeoLocalization) ? $GeoLocalization : "Undefined" ), $_SESSION['GEO_USERID']];
					$DB->execute($consult, $param);
					geoip_close($GIP);
					
					//el usuario ya había autentificado su cuenta exitosamente...
					//lo que falló fue su localización, no es necesario volver a autentificarlo.
					// con password y login. :) [isaac]
					$consult = 'SELECT `account_id`, `userid`,'.$FNC->get_emulator_query().',`user_pass` FROM `login` WHERE `userid`=?';
					$result = $DB->execute($consult, [$_SESSION['GEO_USERID']]);
					if ($DB->num_rows())
					{
						$row = $result->fetch();
						$_SESSION['account_id']  = $row['account_id'];
						$_SESSION['userid']		 = $row['userid'];
						$_SESSION['level']   = $row[($FNC->get_emulator() == EAMOD ? "level" : "group_id")];
						$_SESSION['password']	 = $row['user_pass'];
						$_SESSION['ip']			 = $FNC->getIP();
						echo 'ok';
					} else
						echo 'Something went wrong';
				} else
					echo 'Invalid Answer';
				break;
			} else
				echo 'Something went wrong';
		} else
			echo 'Missing Fields';
	break;
	
		

		
	/**
	 *
	 **/
	case 'UPDATE_GEO_INFO':
		if (empty($_SESSION['account_id']) || !isset($_POST['question']) || empty($_POST['question_response_update']))
		{
			echo 'Missing Fields';
			exit;
		}
		
		if(preg_match_all('/[^A-Za-z0-9 ]/', $_POST['question_response_update'], $matches) > 0)
		{
			echo 'Incorrect character detected in Question Response. <br/>';
			break;
		}
			
		if ( strlen($_POST['question_response_update']) > 23 )
			$error .= 'Question Response too long (max. 23)';
		
		if ( !is_numeric($_POST['question']))
			$error .= 'Invalid Question';
		
		if (!is_null($error))
		{
			echo $error;
			break;
		}
		
		$GIP = geoip_open('GeoIP.dat',GEOIP_STANDARD);
		$consult = "UPDATE `login` SET `question`=?, `question_response`=? WHERE `account_id`=?";
		$result = $DB->execute($consult, [$_POST['question'], $_POST['question_response_update'], $_SESSION['account_id']]);
		if ($result->rowCount())
			echo 'ok';
		else
			echo 'No changes detected';
	break;

	/**
	 *
	 *
	 *
	 **/
	case 'DonationAdminUpdate':
		if (!$FNC->isgm())
        	exit;
		
		if (!empty($_POST['item_id']) && isset($_POST['name']) && isset($_POST['desc']) && isset($_POST['dona']))
		{
			$consult = "UPDATE `item_db` SET `name_japanese`= ?, `description`=?, `dona`=? WHERE `id`=?";
			$param = [$_POST['name'],$_POST['desc'],$_POST['dona'],$_POST['item_id']];
			$result = $DB->execute($consult, $param);
			if ($result->rowCount())
			{
				$CACHE->delete('ItemDB');
				echo 'ok';
			} else
				echo 'No changes applied';
		}
	break;
		
	/**
	 *
	 *
	 *
	 **/
	case 'DonationAdminUpdateImg':
		if (!$FNC->isgm())
        	exit;
		
		$fichero_subido = '../../img/db/item_db/large/'.basename($_FILES['image']['name']);
		move_uploaded_file($_FILES['image']['tmp_name'], $fichero_subido);
		echo 'ok';
	break;
		
	
	/**
	 * Oboro Forum Management (c)
	 * Crea o modifica una categoria o subcategoria.
	 * es un modelo de administración: admin.management-5,
	 * la funcion de eliminar se ejecuta bajo admin.management-5-(post)
	 **/
	case 'CREATEMODIFYCATEGORY':
		if (empty($_POST['description']) || empty($_POST['name']) || !isset($_POST['forum_categories']) || !isset($_POST['lvtoread']) || !isset($_POST['lvtowrite']))
		{
			echo 'missing fields';
			exit;
		}
		
		if (!$FNC->isgm())
        	exit;
		
		if (empty($_POST['catid']))
		{
			//INSERT..
			$result = $DB->execute("INSERT INTO `oboro_forum_categories`(`category_name`, `parent_category`, `category_descript`, `account_level_access`, `account_level_create`) VALUES (?,?,?,?,?)",
						[
							$_POST['name'],
							$_POST['forum_categories'],
							$_POST['description'],
							$_POST['lvtoread'],
							$_POST['lvtowrite']
						], "Forum"			  
					);
			if ($result->rowCount())
				echo 'ok';
			else
				echo 'Something wrong happenend';
		}
		else
		{
			$result = $DB->execute("UPDATE `oboro_forum_categories` SET `category_name`=?, parent_category=?, category_descript=?, account_level_access=?, account_level_create=? WHERE category_id=?",
									[
										$_POST['name'],
										$_POST['forum_categories'],
										$_POST['description'],
										$_POST['lvtoread'],
										$_POST['lvtowrite'],
										$_POST['catid']
									], "Forum");
				if ($result->rowCount())
					echo 'ok';
				else
					echo 'Something wrong happened';
		}
	break;
		

    case 'CREATEMODIFYGROUPS':
		if (empty($_POST['name']) || !isset($_POST['htmls']) || !isset($_POST['htmle']))
		{
			echo 'missing fields';
			exit;
		}
		
		if (!$FNC->isgm())
        	exit;
		
		if (empty($_POST['gid']))
		{
			//INSERT..
			$result = $DB->execute("INSERT INTO `oboro_user_groups` (`group_name`, `html_start`, `html_end`) VALUES (?, ?, ?)",
						[
							$_POST['name'],
							$_POST['htmls'],
							$_POST['htmle']
						], "Forum"			  
					);
			if ($result->rowCount())
				echo 'ok';
			else
				echo 'Something wrong happenend';
		}
		else
		{
			$result = $DB->execute("UPDATE `oboro_user_groups` SET `group_name`=?,`html_start`=?,`html_end`=? WHERE `user_group_id`=?",
									[
                                        $_POST['name'],
                                        $_POST['htmls'],
                                        $_POST['htmle'],
										$_POST['gid']
									], "Forum");
				if ($result->rowCount())
					echo 'ok';
				else
					echo 'Something wrong happened';
		}        
    break;

	/**
	 * Oboro Forum management (c)
	 * Create a Post from forum.create
	 **/
    case 'CREATETOPIC':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to create a post';
            exit;
        }
		
 		if (empty($_POST['title']) || empty($_POST['text']) || !isset($_POST['catid']))
		{
			echo 'missing fields';
			exit;
		}
        
			$result = $DB->execute(
                "INSERT INTO `oboro_forum_posts`(`date_create`, `title`, `text_html`, `owner_id`, `category`) VALUES (?,?,?,?,?)", 
                [
                    date("Y-m-d"), 
                    $_POST['title'], 
                     htmlentities($_POST['text'], ENT_QUOTES, 'utf-8'), 
                    $_SESSION['account_id'], 
                    $_POST['catid']
                ], "Forum"
            );        
            if ($result->rowCount())
				echo 'ok';
			else
				echo 'something wrong happened';
    break;
        
	/**
	 * Oboro Forum management (c)
	 * Modify a Post from forum.mod
	 **/
    case 'MODIFY_POST':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to modify information';
            exit;
        }
        
        if (empty($_POST['text']) || empty($_POST['catid']) || empty($_POST['title']))
        {
            echo 'Missing fields';
            exit;
        }
        
		$result = $DB->execute("SELECT `owner_id` FROM `oboro_forum_posts` WHERE `blog_id`=?", [$_POST['catid']], "Forum");
        $row = $result->fetch();
        
        if ($result->rowCount())
        {
            if 
            (
                $row['owner_id'] != $_SESSION['account_id'] && 
                ($FNC->isgm() && $_SESSION['level'] < $CONFIG->getConfig("GM_Delete_Level"))
            )
            {
                echo 'You do not have sufficient permissions';
                exit;
            }
            
            $result = $DB->execute("UPDATE `oboro_forum_posts` SET `title`=?, `text_html`=?, `date_modify`=? WHERE `blog_id`=? ", 
                        [
                           $_POST['title'],
                           htmlentities($_POST['text'], ENT_QUOTES, 'utf-8'),
                           date("Y-m-d"),
                           $_POST['catid']
                        ], "Forum");
            $row = $result->fetch();
            if ($result->rowCount())
                echo 'ok';
            else
                echo 'No se ha modificado nada';
        }
        else
            echo 'Something wrong happened';        
    break;
        
    /**
	 * Oboro Forum management (c)
	 * Delete a Post from forum.cat
	 **/
    case 'DELETE_POST':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to delete a post';
            exit;
        }
            
        if (empty($_POST['bid']))
        {
            echo 'Missing fields';
            exit;
        }
        
        if ($FNC->isgm() && $_SESSION['level'] >= $CONFIG->getConfig("GM_Delete_Level"))
        {
            $consult = "DELETE FROM `oboro_forum_posts` WHERE `blog_id`=?";
            $param = [$_POST['bid']];
            $result = $DB->execute($consult, $param, "Forum");
            if ($result->rowCount())
                echo 'ok';
            else
                echo 'Something wrong happened';
        }
        else if ($CONFIG->getConfig("User_Delete_Own") == 'yes')
        {
            $consult = "SELECT `owner_id` FROM `oboro_forum_posts` WHERE `blog_id`=?";
            $param = [$_POST['bid']];
            $result = $DB->execute($consult, $param, "Forum");
            $row = $result->fetch();

            if ($_SESSION['account_id'] == $row['owner_id'])
            {
                $consult = "DELETE FROM `oboro_forum_posts` WHERE `blog_id`=? AND `owner_id`=?";
                $param = [$_POST['bid'], $_SESSION['account_id']];
                $result = $DB->execute($consult, $param, "Forum");
                if ($result->rowCount())
                    echo 'ok';
                else
                    echo 'Something wrong happened';
            }
            else
                echo 'Something wrong happenend';
        }
    break;
		
	/**
	 * Oboro Forum management (c)
	 * Retrive a Post information to modify by forum.mod
	 **/
	case 'GET_FORUM_POST':
        if (!$FNC->islogged())
        {
            echo 'You need to login, in order to retrive this information';
            exit;
        }    
        
		if (!isset($_POST['catid']))
		{
			echo 'missing fields';
			exit;
		}
		
		$result = $DB->execute("SELECT `text_html`, `title`, `owner_id` FROM `oboro_forum_posts` WHERE `blog_id`=?", [$_POST['catid']], "Forum");
        $row = $result->fetch();
        
        if ($result->rowCount())
        {
            if 
            (
                $row['owner_id'] == $_SESSION['account_id'] || 
                $FNC->isgm() && $_SESSION['level'] >= $CONFIG->getConfig("GM_Delete_Level")
            )
            {
                $arr = [
                    $row['title'],
                    $row['text_html']
                ];

              echo json_encode($arr);
            }
            else
                echo 'error';
        }
        else
            echo 'error';
	break;
		
	case 'CREATEUSERPOST':
		
		if (!$FNC->islogged())
		{
			echo 'You need to login, in order to post a reply';
			exit;
		}
				
		if (!isset($_POST['txthtml']) || !isset($_POST['catid']))
		{
			echo 'Empty post';
			exit;
		}
		
		$user = $DB->execute("SELECT `display_name` FROM `oboro_forum_user` WHERE `account_id`=?", [$_SESSION['account_id']], "Forum")->fetch();
		
		if (empty($user['display_name']))
		{
			echo 'You must have a display forum name, in order to post a new reply...';
			exit;
		}
		
		//debe ver si está lock y si es usuario o gm
		$locked = $DB->execute("SELECT `lock` FROM `oboro_forum_posts` WHERE `blog_id`=?", [$_POST['catid']], "Forum")->fetch();
		
		if ($locked['lock']  && !$FNC->isgm())
		{
			echo 'topic is locked';
			exit;
		}
		
		$consult = "INSERT INTO oboro_forum_user_reply(`subcategory_id`, `account_id`, `date_create`, `text_html`) VALUES (?,?,?,?)";
		$result = $DB->execute($consult, [$_POST['catid'], $_SESSION['account_id'],  date("Y-m-d H:i:s"), htmlentities($_POST['txthtml'], ENT_QUOTES, 'utf-8')], "Forum");
		if ($result->rowCount())
		{
			//se insertó...
			echo 'ok';								  
		 }
		else
			echo 'Something went wrong.';
	break;
		
	
	case 'IMGURAPIWIN':
        if (!$FNC->islogged())
        {
            echo 'You need to login';
            exit;
        }
		
		//array(5) { ["name"]=> string(17) "maxresdefault.jpg" ["type"]=> string(10) "image/jpeg" ["tmp_name"]=> string(24) "C:\xampp\tmp\phpFDDE.tmp" ["error"]=> int(0) ["size"]=> int(49675) } 
		if (!isset($_FILES['img']))
		{
			echo 'cannot find image';
			exit;
		}
		
		if (strcmp($_FILES['img']['type'], "image/jpeg") != 0)
		{
			echo 'only jpeg image accepted';
			exit;
		}	
		
		$img=$_FILES['img'];

		if($img['name']=='')
		{
			echo 'invalid image: ' . $img['name'];
			exit;
		}
		
		$filename = $img['tmp_name'];
		$handle = fopen($filename, "r");
		$data = fread($handle, filesize($filename));
		$pvars   = array('image' => base64_encode($data));

		$curl = curl_init();

		//if ($FNC->islocalhost())
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		/*
		else
		{
			//si no tiene ceritifaco se debe crear 1.
			//o obtain the certificate, you should go with the browser to the page, and then with "view certificate" you have to export it. Remember that you must export it as X.509 Certificate (PEM) for this to work. For a //more detailed guide on how to export the certificate, visit the link provided.

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, '/path/to/crt/file.crxt');
		}
		*/

		curl_setopt($curl, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . "89ddfe775b79a75"));
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_USERAGENT,  "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.204 Safari/534.16");

		curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);
		$out = curl_exec($curl);

		if ($out === false) 
			echo curl_error($curl);

		curl_close ($curl);
		$pms = json_decode($out,true);
		$url=$pms['data']['link'];

		if ($FNC->isvalidurl($url))
		{
			if (strpos($url, 'i.imgur.com') !== false)
			{
				//valid imgur
				$result = $DB->execute("UPDATE `oboro_forum_user` SET `img_url`=? WHERE account_id=?",[$url, $_SESSION['account_id']], "Forum");
				if ($result->rowCount("Forum"))
					echo 'ok';
				else
					echo 'Something wrong happened';
			}
			else
				echo 'cannot verify imgur url';
		} else
			echo 'invalid url';  
	break;

		
	case 'UN_LOCKPOST':
		if (!$FNC->isgm())
		{
			echo 'You don\'t have permission to do this';
			exit;
		}
		
        if (empty($_POST['bid']))
        {
            echo 'Missing fields';
            exit;
        }
		
		$result = $DB->execute("UPDATE `oboro_forum_posts` SET `lock`= NOT `lock` WHERE `blog_id`=?", [$_POST['bid']], "Forum");
		if ($result->rowCount())
			echo 'ok';
		else
			echo 'Something wrong happened';
	break;
		
	case 'NEWSHOUTBOX':
		if (!$FNC->islogged())
		{
			echo 'You most be logged in, to make a shout';
			exit;
		}
		
		if (empty($_POST['shout']))
		{
			echo 'Empty shout';
			exit;
		}
		
		if (strlen($_POST['shout']) > 255)
		{
			echo 'max len is 255';
			exit;
		}
		
		$result = $DB->execute("INSERT INTO `oboro_forum_shoutbox`(`account_id`, `shout_text`, `date_create`) VALUES(?,?,?)", [$_SESSION['account_id'], htmlspecialchars($_POST['shout'], ENT_QUOTES), date("Y-m-d H:i:s")], "Forum");
		if ($result->rowCount())
		{
			echo 'ok';								  
		 }
		else
			echo 'Something went wrong.';		
	break;
		
	
	case 'AJAXSHOUTBOX':
        $result  = $DB->execute("
        SELECT 
                `shout`.`shout_id`, 
                `shout`.`account_id`, 
                `shout`.`shout_text`, 
                `shout`.`date_create`, 
                `user`.`display_name`, 
                `user`.`img_url`,
                `group`.`html_start`,
                `group`.`html_end`
            FROM `oboro_forum_shoutbox` AS `shout` 
            INNER JOIN `oboro_forum_user` AS `user` ON `user`.`account_id` = `shout`.`account_id`
            LEFT JOIN `oboro_user_groups` AS `group` ON `user`.`forum_group_id` = `group`.`user_group_id`
            ORDER BY `shout_id` DESC LIMIT 20
        ", [], "Forum");

        while ($row = $result->fetch())
        {
            echo '
                <tr>
                    <td>
                        <div class="oboro_forum_shoutbox_user_img">
                            <img src="'.($row['img_url'] ? $row['img_url'] : './img/banners/user-1.jpg').'" />
                        </div>
                        <span class="shoutbox_user_name">'. $row['html_start'] . $row['display_name']. $row['html_end'] .'</div>
                    </td>
                    <td>
                        '.$row['shout_text'].'
                    </td>
                    <td>
                        <span class="shoutbox-time">' .date_format(date_create($row['date_create']), 'd M, Y H:i:s'). '</span>
                    </td>
                </tr>
            ';
        }
	break;
		
		
	/**
	 *
	 *
	 **/
	case 'CREATE_DONATION_ITEM':
		if (!$FNC->isgm())
			exit;

		if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']) || empty($_POST['dona']) || !is_numeric($_POST['dona']))
		{
			echo 'invalid type detected';
			exit;
		}

		$result = $DB->execute('UPDATE `item_db` SET `dona`=? WHERE `id`=?', [$_POST['dona'], $_POST['item_id']]);
		if ($result->rowCount())
		{
			//we have to delete the cache.
			$CACHE->delete('ItemDB');
			echo 'ok';
		}
		else
			echo 'item db no existe';
	break;

	case 'DELETE_DONATION_ITEM':
		if (!$FNC->isgm())
			exit;

		if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id']))
		{
			echo 'invalid type detected';
			exit;
		}

		$result = $DB->execute('UPDATE `item_db` SET `dona`=0 WHERE `id`=?', [$_POST['item_id']]);
		if ($result->rowCount())
		{
			//we have to delete the cache.
			$CACHE->delete('ItemDB');
			echo 'ok';
		}
		else
			echo 'item db no existe';
	break;

	default:
		echo 'denied';
	break;
}
exit(0);
?>
