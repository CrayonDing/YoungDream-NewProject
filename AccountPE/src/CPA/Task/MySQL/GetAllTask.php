<?php
namespace CPA\Task\Mysql;

use CPA\Account;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class GetAllTask extends AsyncTask{
    public function __construct($DB,$pn,$pl,$cb) {
    $this->DB=$db;
    $this->pn=$pn;
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
				$this->setResult($data);
			}else{
				$this->setResult(null);
		}
				
		}else{
				$this->setResult(null);
		}
    }

    public function onCompletion(Server $server){
		$bb=$this->cb;
        $server->getPluginManager()->getPlugin($this->pl)->$bb($this->name,$this->getResult());
    }

}