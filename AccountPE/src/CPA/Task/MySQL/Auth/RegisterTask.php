<?php
namespace CPA\Task\Mysql\Auth;

use CPA\Account;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class RegisterTask extends AsyncTask{
    public function __construct($DB,$pn,$passwd,$pl,$cb) {
    $this->DB=$db;
    $this->pn=$pn;
    $this->passwd = $passwd;
    $this->pl=$pl;
    $this->cb=$cb;
    }

    public function onRun() {
$db = @new \mysqli($this->DB["IP"],$this->DB["USER"],$this->DB["PASS"],$this->DB["DB"]);
    	$name = strtolower($this->pn);
	   $result = $db->query("SELECT * FROM ".$this->DB["ACTABLE"]." WHERE name = '$name'");
		echo($db->connect_error);
		if($result instanceof \mysqli_result){
			$data = $result->fetch_assoc();
			$result->free();
			if($data["name"] === $player){
				foreach($data as $k=>$v){
				echo "值:".$k."内容:".$v."\n";
				}
				$r1=$data;
			}else{
				$r1=null;
		}
				
		}else{
				$r1=null;
		}
    if(r1!==null){//开始新建
    $this->database->query("INSERT INTO uniterpg
			(name,passwd,rank,KD,xp,money,coin,MF,la,ol)
			VALUES
			('$user','".$this->passwd."','".json_encode(array(
			0=>0,
			1=>0
			),true)."',0,0,0,0,0,'en',0)");
			$this->setResult($this->passwd);
    }else{//已有数据，不再新建，未知错误
    $this->setResult(false);
    }
    }

    public function onCompletion(Server $server){
		$bb=$this->cb;
        $server->getPluginManager()->getPlugin($this->pl)->$bb($this->name,$this->getResult());
    }

}