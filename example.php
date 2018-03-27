<?php


include('includes.php');

// $c is a configuration array
$zammad= new Zammad();
$zammad->load_config($c);
$auth=array($c['zuser'],$c['zpasswd']);


//--------------------------
// fetching users from sql to add them to zammad, if not found
$mysqli=connectsql($c['sql'][1]);

if (! $mysqlres = $mysqli->query("SELECT * FROM `hr_oe` WHERE `mail` != \"\"")) {
        exit;
}
while ($row=mysqli_fetch_assoc($mysqlres)) {

	$user=array();
	$user["firstname"]=strval(utf8_encode($row['tel']));
	$user["lastname"]=strval(utf8_encode($row['text']));
	$user["email"]=strval(utf8_encode($row['mail']));
	$zammad->add_user($user);

}

//--------------------------
