<?php

//config
$c=array();
// Zammad - System
$c['zurl']="https://zammad.example.com/api/v1";
$c['zuser']="zammad-admin@yourdomain";
$c['zpasswd']="securepassword";
//loglevel
$c['loglevel']=10;
//caching
// none only local variable caching
// or sql
$c['cache']['mysql']['server']="127.0.0.1";
$c['cache']['mysql']['user']="cacheuser";
$c['cache']['mysql']['passwd']="cachepwd";
$c['cache']['mysql']['db']="zammad_helper";

include('includes.php');


$zammad= new Zammad();
$zammad->load_config($c);
$auth=array($c['zuser'],$c['zpasswd']);

//--------------------------
// Clear Zammad-Helper caching - System
$zammad->clearcache();



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
// fetching Groupname id:12
print_r ($zammad->get_groupname(12));

//--------------------------
// fetching Username  id:26
print_r ($zammad->get_username(26));
