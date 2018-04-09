<?php
/*
Zammad Helper
(c) 2018 wenger@unifox.at
License: GNU GPL3

last modification 2018-04-09
*/


/*--------------------------
 * Zammad Class (Zammad-Helper
 *--------------------------*/
class Zammad
{
    private $config;
    private $groups;
    private $loglevel;
    private $cacheengine;
    private $mysqli;

    public function __construct()
    {
        /*--------------------------
   * Construct
   *--------------------------*/
    $this->groups=array();
        $this->cacheengine='local';
    }




    public function load_config($config)
    {
        /*--------------------------
   * load config
   *--------------------------*/
    $this->config['url']=$config['zurl'];
        if (isset($config['zuser'])) {
            $this->config['auth']['user']=$config['zuser'];
            $this->config['auth']['pass']=$config['zpasswd'];
        } else {
            $this->config['auth']=false;
        }
        if (isset($config['loglevel'])) {
            $this->loglevel=intval($config['loglevel']); // higher more debug
        } else {
            $this->loglevel=3; // higher more debug
        }
        if (isset($config['cache']['mysql'])) {
            $this->mysqli=$this->connectsql($config['cache']['mysql']);
            $this->cacheengine='mysql';
        }
    }





    public function clearcache()
    {
        /*--------------------------
   * clear the caching system
   *--------------------------*/
    if ($this->cacheengine=='local') {
        $this->groups=array();
    } elseif ($this->cacheengine=='mysql') {
        $sql="TRUNCATE Tables";
        $this->logger(3, "truncate sql");
        if (! $mysqlres = $this->mysqli->query("TRUNCATE groups")) {
            $this->logger(1, "error clearing mysql cache system");
            exit;
        }
    }
    }




    public function set_loglevel($loglevel)
    {
        /*--------------------------
 * set log verbosity
 *--------------------------*/
    $this->loglevel=intval($loglevel);
    }







    public function get_groupname($id)
    {
        /*--------------------------
   * get groupname (ask cache, or query zammad and store in cache)
   *--------------------------*/
    $this->logger(9, "suche ".$id);
        $found=false;
        $return="";
        if ($this->cacheengine=='local') {
            $this->logger(3, "suche in local cahe");
        // array-keyexists ?
        if (array_key_exists($id, $this->groups)) {
            $this->logger(10, "found in local cahe");
            $return=$this->groups[$id];
            $found=true;
        }
        } elseif ($this->cacheengine=='mysql') {
            //selectsql
        $this->logger(10, "suche in mysql cahe");
            $sql="SELECT `id`, `name` FROM `groups` WHERE `id` = \"$id\"";
            if (! $mysqlres = $this->mysqli->query($sql)) {
                $this->logger(1, "SQL Error $sql");
                exit;
            }
            if ($mysqlres->num_rows==1) {
                $this->logger(10, "found in mysql cache");
                $row=mysqli_fetch_assoc($mysqlres);
                $return=$row['name'];
                $found=true;
            }
        }

        if (!$found) {
            $this->logger(10, "not found in cache");
            $url=$this->config['url']."/groups/".$id;
            $json_return=$this->CallRESTAPi('GET', $url, false, $this->config['auth']);
            $json_array=json_decode($json_return, true);
        // cache abspeichern
        if ($this->cacheengine=='local') {
            $this->logger(10, "store in local cache");
            $this->groups[$id]=$json_array['name'];
        } elseif ($this->cacheengine=='mysql') {
            $this->logger(10, "store in mysql cache");
            $sql="INSERT INTO `groups` (`id`, `name`) VALUES (\"$id\", \"".$json_array['name']."\")";
            if (! $mysqlres = $this->mysqli->query($sql)) {
                $this->logger(1, "SQL Error $sql");
                exit;
            }
        }
        //rückgabewert
        $return=$json_array['name'];
        }
        return $return;
    }








    public function add_user($user)
    {
        /*--------------------------
        * Add User if needed
        *--------------------------*/
        $url=$this->config['url']."/users/search?query=".$user["email"]."&limit=1";
        $json_return=$this->CallRESTAPi('GET', $url, false, $this->config['auth']);
        $json_array=json_decode($json_return, true);
        //echo $url."\n";
        if (count($json_array)==1) {
            $this->logger(3, "User ".$user["email"]." found");
        } elseif (count($json_array)==0) {
            $this->logger(3, "User ".$user["email"]." not found - creating user");
            $data=json_encode($user);
          //print_r($data);
          $url=$this->config['url']."/users";
          //echo "\n$url";
          $json_return=$this->CallRESTAPi('POST', $url, $data, $this->config['auth']);
          //echo "---------> anlegen json return \n";
          //print_r($json_return);
          $json_array=json_decode($json_return, true);
          //echo "anlegen json array \n";
          //print_r($json_array);//header auswerten "Status: 201 Created"
        } else {
            $this->logger(1, "ERROR suche nach ".$user["email"]. "ergab ".count($json_array)." treffer. das darf nicht vorkommen");
        }
    }





    public function fetchticketdetails($ticketid)
    {
        /*--------------------------
      * Fetch Ticket-Details
      *--------------------------*/
        $url=$this->config['url']."/tickets/".$ticketid;
        $json_return=$this->CallRESTAPi('GET', $url, false, $this->config['auth']);
        $json_array=json_decode($json_return, true);
        print_r($json_array);
    }

    /*--------------------------
     * Call REST API
     *--------------------------*/
    private function CallRESTAPi($method, $url, $data = false, $auth = false)
    {
        /*--------------------------
       * Call REST API
       *--------------------------*/
        $curl = curl_init();
        //echo "$method\n$url\n$data\n";
        //print_r($auth);
        switch ($method) {
          case "POST":
              curl_setopt($curl, CURLOPT_POST, 1);

              if ($data) {
                  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
              }
              break;
          case "PUT":
              curl_setopt($curl, CURLOPT_PUT, 1);
              break;
          default:
              if ($data) {
                  $url = sprintf("%s?%s", $url, http_build_query($data));
              }
      }
      // Optional Authentication:
      if ($auth) {
          curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
          curl_setopt($curl, CURLOPT_USERPWD, $auth['user'].":".$auth['pass']);
      }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        //curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
/*
        print_r($response);
        echo "\n--------header\n";
        print_r($header);
        echo "\n--------body\n";
        print_r($body);*/
                if (curl_error($curl)) {
                    $this->logger(1, "Error ".curl_error($curl));
                    print_r($response);
                    echo "\n--------header\n";
                    print_r($header);
                    echo "\n--------body\n";
                    print_r($body);
                    echo "\n--------url\n";
                    print_r($url);
                    echo "\n--------data\n";
                    print_r($data);
                }
        curl_close($curl);
        //return $response;
        return $body;
    }






    private function logger($level, $text)
    {
        /*--------------------------
   * Logger
   *--------------------------*/
    if ($level<=$this->loglevel) {
        echo "\nZammad-Helper($level): ".strval($text);
    }
    }






    private function connectsql($config)
    {
    /*--------------------------
   * MySQL Connector
   *--------------------------*/

    $mysqli = new mysqli($config['server'], $config['user'], $config['passwd'], $config['db']);
        if (mysqli_connect_errno()) {
            $this->logger(1, "Connect failed: %s\n".mysqli_connect_error());
            exit();
        }
        if (!$mysqli->set_charset("utf8")) {
            $this->logger(3, "unable to set mysql charset to utf8");
            exit();
        }
        return $mysqli;
    }

// endclass
}
