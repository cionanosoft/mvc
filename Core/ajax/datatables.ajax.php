<?php
include_once('../controller.php');
session_start();

$data = array();

if (!$_GET['info'])
	exit(0);

switch($_GET['info'])
{
    /**
     * Missing Doc
     **/
	case 'item_db':
		$data = array();

		$data_cache = $ITEM->get('ItemDB');
		if ( $data_cache )
		{
			$IT_TEMP = $ITEM->decode_arr($ITEM->get('ItemDB'));
			foreach($IT_TEMP as $poc => $arr)
			{
				$new_arr = array();
				foreach($arr as $poc => $val)
					array_push($new_arr, $arr[$poc]);
				array_push($data, $new_arr);
			}

			$results = array(
				"sEcho" => 1,
				"iTotalRecords" => count($data),
				"iTotalDisplayRecords" => count($data),
				"aaData"=>$data
			);

			echo json_encode($results);
		}
	break;

    /**
     * Missing Doc
     **/
	case 'acc':
		if ( !isset($_SESSION['level']) || $_SESSION['level'] < 99)
			exit;
		
		$result = $DB->execute("SELECT `account_id`, `userid`, `user_pass`, `email`, `state`, `last_ip`, `last_mac` FROM `login`");
		while($row = $result->fetch())
		{
			$new_arr = array();
			foreach ($row as $poc => $val)
			{
				if ($poc == "account_id")
					array_push($new_arr, '<a href="?admin.account.edit-'.$row['account_id'].'" class="btn btn-primary">Edit</a>');
				array_push($new_arr, $val);
			}
			array_push($data, $new_arr);
		}
		$results = array(
			"sEcho" => 1,
			"iTotalRecords" => count($data),
			"iTotalDisplayRecords" => count($data),
			"aaData"=>$data
		);
		echo json_encode($results);
	break;
    
    /**
     * Missing Doc
     **/	
	case 'chr':
		if ( !isset($_SESSION['level']) || $_SESSION['level'] < 99)
			exit;
		
		$result = $DB->execute("SELECT `char`.`account_id`,`login`.`userid`,`char`.`char_id`,	`char`.`name`, `char`.`class`, `char`.`playtime` FROM `char`INNER JOIN `login` ON `login`.`account_id` = `char`.`account_id`");
		while($row = $result->fetch())
		{
			$new_arr = array();
			foreach ($row as $poc => $val)
			{
				if ($poc == "account_id")
					array_push($new_arr, '<a href="?admin.char.edit-'.$row['char_id'].'" class="btn btn-primary">Edit</a>');
				array_push($new_arr, $val);
			}
			array_push($data, $new_arr);
		}
		$results = array(
			"sEcho" => 1,
			"iTotalRecords" => count($data),
			"iTotalDisplayRecords" => count($data),
			"aaData"=>$data
		);
		echo json_encode($results);	
	break;
        
    /*
     * Missing Doc
     * Should I make some inline development latter ?? Isaac
     */
    case 'F_usr':
		if ( !isset($_SESSION['level']) || $_SESSION['level'] < 99)
			exit;
		
		$result = $DB->execute("SELECT `account_id`, `display_name`, `forum_group_id`, `forum_banned` FROM `oboro_forum_user`", [], "forum");
		while($row = $result->fetch())
		{
			$new_arr = array();
			foreach ($row as $poc => $val)
			{
				if ($poc == "account_id")
					array_push($new_arr, '<a href="?admin.forum.user.edit-'.$row['account_id'].'" class="btn btn-primary">Edit</a>');
				array_push($new_arr, $val);
			}
			array_push($data, $new_arr);
		}
		$results = array(
			"sEcho" => 1,
			"iTotalRecords" => count($data),
			"iTotalDisplayRecords" => count($data),
			"aaData"=>$data
		);
		echo json_encode($results);	   
    break;
}

?>