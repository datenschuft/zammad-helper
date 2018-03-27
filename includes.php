<?php
/*
Zammad Helper
(c) 2018 wenger@unifox.at
License: GNU GPL3
*/


/**
 * Zammad-API-Class
 */
class Zammad
{
    //public $var="value";
  private $config;
    private $groups;
    private $loglevel;
  //private $groups=array();
/*--------------------------
 * Construct
 *--------------------------*/
  public function __construct()
  {
      $this->groups=array();
    //userids
    //priority
  }
  /*--------------------------
   * load config
   *--------------------------*/
    public function load_config($config)
    {
        $this->config['url']=$config['zurl'];
        if (isset($config['zuser'])) {
            //$this->config['auth']=true;
          $this->config['auth']['user']=$config['zuser'];
            $this->config['auth']['pass']=$config['zpasswd'];
        } else {
            $this->config['auth']=false;
        }
        $this->loglevel=3; // higher more debug
    }
    public function set_loglevel($loglevel)
    {
        $this->loglevel=intval($loglevel);
    }
    /*--------------------------
     * get groupname
     *--------------------------*/
    public function get_groupname($id)
    {
        if (!array_key_exists($id, $this->groups)) {
            //echo "fetching group $id";
            // fetching unknown goup name
            //$json_return=CallAPI('GET', $this->configurl, $data = false, $auth);
            $url=$this->config['url']."/groups/".$id;
            $json_return=$this->CallRESTAPi('GET', $url, false, $this->config['auth']);
            $json_array=json_decode($json_return,TRUE);
            //echo $json_array['name'];
            //groups/{object_id}
            $this->groups[$id]=$json_array['name'];
            return $json_array['name'];
        } else {
            return $this->groups[$id];
        }
    }
/*Add User if Needed*/
		public function add_user ($user) {
			$url=$this->config['url']."/users/search?query=".$user["email"]."&limit=1";
			$json_return=$this->CallRESTAPi('GET', $url, false, $this->config['auth']);
			$json_array=json_decode($json_return,TRUE);
      //echo $url."\n";
			if (count($json_array)==1) {
				$this->logger(3,"User ".$user["email"]." found");
			} elseif (count($json_array)==0) {
				$this->logger(3,"User ".$user["email"]." nicht gefunden - lege an");
				$data=json_encode($user);
        //print_r($data);
        $url=$this->config['url']."/users";
        //echo "\n$url";
				$json_return=$this->CallRESTAPi('POST', $url, $data, $this->config['auth']);
        echo "---------> anlegen json return \n";
        print_r($json_return);
				$json_array=json_decode($json_return,TRUE);
        echo "anlegen json array \n";
				print_r($json_array);//header auswerten "Status: 201 Created"
			} else {
				$this->logger(1,"ERROR suche nach ".$user["email"]. "ergab ".count($json_array)." treffer. das darf nicht vorkommen");
			}


		}

    /*--------------------------
     * Call REST API
     *--------------------------*/
    private function CallRESTAPi($method, $url, $data = false, $auth = false)
    {
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
						$this->logger('error:' . curl_error($curl));
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
		/*Logging Function
		Add Syslog - support
		*/
		private function logger ($level,$text) {
      if ($level<=$this->loglevel){
			     echo "\nZammad-Helper($level): ".strval($text);
      }
		}
}
