<?php
/*
Zammad Helper
(c) 2018 wenger@unifox.at
License: GNU AFFERO GENERAL PUBLIC LICENSE Version 3

last modification 2018-04-17
*/

/*Zammad-cache extends zammad/zammad-api-client-php": "1.0.*"*/
class Zammadcache extends ZammadAPIClient\Client
{
    private $groups;
    private $users;
    private $loglevel;
    private $cacheengine;
    private $mysqli;

/* Constructor */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->groups=array();
        $this->users=array();
        $this->cacheengine='local';
    }


/* loading configuration */
    public function load_config($config)
    {
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


/* just a test to extend the original class */
    public function version()
    {
        echo 'Zammad Helper Cached v0.0.0.0.0.1 - Beta [Cacheengine:'.$this->cacheengine."]";
    }



/* MySQL connection - for sql caching */
    private function connectsql($config)
    {
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



/* clearing cache */
  public function clearcache()
  {
      if ($this->cacheengine=='local') {
          $this->groups=array();
          $this->users=array();
      } elseif ($this->cacheengine=='mysql') {
          $sql="TRUNCATE Tables";
          $this->logger(3, "truncate sql");
          if (! $mysqlres = $this->mysqli->query("TRUNCATE groups")) {
              $this->logger(1, "error clearing mysql cache system (groups)");
              exit;
          }
          if (! $mysqlres = $this->mysqli->query("TRUNCATE users")) {
              $this->logger(1, "error clearing mysql cache system (users)");
              exit;
          }
      }
  }


/* logging function */
  private function logger($level, $text)
  {
      if ($level<=$this->loglevel) {
          echo "\nZammad-Helper($level): ".strval($text);
      }
  }


/* returning userdetail if cached, else load from zammad, cache and return infos */
    public function getuser($id)
    {
        $this->logger(9, "searching uid".$id);
        $found=false;
        $return=array();
        if ($this->cacheengine=='local') {
            $this->logger(3, "searching in local cahe");
            if (array_key_exists($id, $this->users)) {
                $this->logger(10, "found in local cahe");
                $return=$this->users[$id];
                $found=true;
            }
        } elseif ($this->cacheengine=='mysql') {
            $this->logger(10, "searching in mysql cahe");
            $sql="SELECT `id`, `info` FROM `users` WHERE `id` = \"$id\"";
            if (! $mysqlres = $this->mysqli->query($sql)) {
                $this->logger(1, "SQL Error $sql");
                exit;
            }
            if ($mysqlres->num_rows==1) {
                $this->logger(10, "found in mysql cache");
                $row=mysqli_fetch_assoc($mysqlres);
                $return=json_decode($row['info'], true);
                $found=true;
            }
        }
        if (!$found) {
            $this->logger(10, "not found in cache");
            $user=$this->resource(ZammadAPIClient\ResourceType::USER)->get($id);
            $u=$user->getValues();
            $return=$u;


            if ($this->cacheengine=='local') {
                $this->logger(10, "store in local cache");
                $this->users[$id]=$u;
            } elseif ($this->cacheengine=='mysql') {
                $this->logger(10, "store in mysql cache");
                $sql="INSERT INTO `users` (`id`, `info`) VALUES (\"$id\", \"".$this->mysqli->real_escape_string(json_encode($u))."\")";
                if (! $mysqlres = $this->mysqli->query($sql)) {
                    $this->logger(1, "SQL Error $sql");
                    exit;
                }
            }
        }
        return $return;
    }

} //endclass
