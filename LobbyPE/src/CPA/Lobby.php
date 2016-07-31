<?php
namespace CPA;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\event\inventory;
use pocketmine\event\player\PlayerJoinEvent;

use CPA\Event\FinishLoginEvent;
use CPA\Account;
use CPA\Task\LobbyPopupTip;

//什么乱七八糟的代码，差评
//没那么多时间,一切以达到目标为目的
//你可以看看bb的代码风格,比我的还生涩
//至少比你好，看看我写的插件！
//....不是很懂你那样写有什么意义...没有可对接的东西

class Lobby extends PluginBase implements Listener{
	public $lang = array();//语言数据
	public $pos = array();//坐标数据
	public $v3 = array();//v3坐标
	public $inv = array();//背包数据
	public $ctf = array(
		"lang"=>array(),
		"pos"=>array(),
		"inv"=>array(),
		"lobby"=>"lobby"
	);//conf数据
	public $fl = array();
	public $Conf;//conf

	/*
	 * By:FXXK
	 * 启动函数
	 */
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info("注册类成功...");


		$this->Conf = new Config($this->getDataFolder() . "Config.yml", Config::YAML, $this->ctf);
		$this->ctf = $this->Conf->getAll();

		$this->ctf2pos();
		$this->ctf2item();
		$this->ctf2v3();
		$this->startText();
		$this->lang = $this->ctf["lang"];


		$this->getServer()->getScheduler()->scheduleRepeatingTask(new LobbyPopupTip($this), 18);
	}

	public function ctf2v3(){
		foreach($this->ctf["pos"] as $k => $v){
			$this->v3[$k] = $this->array2v3(json_decode($v));
		}
	}

	public function ctf2pos(){//将所有数组转化为position
		foreach($this->ctf["pos"] as $k => $v){
			$this->pos[$k] = $this->array2pos(json_decode($v));
		}
	}

	public function ctf2item(){//转换为item对象
		foreach($this->ctf["inv"] as $v){
			$v1 = json_decode($v);
			$this->inv[] = Item::get($v1[0], $v1[1], $v1[2]);
		}
	}

	public function startText(){//floating text
		foreach($this->ctf["par"] as $k=>$v){

		}

	}
	/*
	 * By:FXXK
	 * 监听函数
	 */
	public function playerLoginEvent(FinishLoginEvent $e){//登陆完成
		$p = $this->getServer()->getPlayer($e->getPlayer());//获取玩家对象
		$p->teleport($this->pos["spawn." . mt_rand(1, 4)]);//随机teleport1-4出生点
		$this->sendInv($p);//装备选项
		$pn = $p->getName();
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if(Account::getInstance()->isLog($p->getName()) and $p->getLevel()->getFolderName() == $this->cfg["lobby"]){
				Account::getInstance()->SendTip($p, $this->lang["Login.tips"]);
			}
		}

		//floatingText

	}

	public function onTp(EntityTeleportEvent $e){
		if($e->getEntity() instanceof Player){
			if($e->getTo()->getLevel()->getFolderName() == $this->ctf["lobby"]){//TP目标为主城时
				if($e->getFrom()->getLevel()->getFolderName() !== $this->ctf["Lobby"]){//回城时-从别的地图TP回来
					$p = $e->getEntity();
					$this->LobbySpawnPlayer($p);
					$this->sendInv($p);
					$e->setTo($this->pos["spawn." . mt_rand(1, 4)]);//随机传送
				}
			}
		}
	}

	public function playerDamage(EntityDamageEvent $e){//暂定
		if($e->getEntity()->getLevel()->getFolderName() == $this->cfg["lobby"]) $e->setCancelled();
	}

	public function onInteract(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		if($p->getLevel()->getFolderName() == $this->ctf["lobby"]){
			switch($p->getItemInHand()->getId()){
				case 405://史蒂夫的头,去选择职业
					if($p->distance($this->v3["lobby.skill"]) < 10){
						Account::getInstance()->SendMessage($p,$this->lang["lobby.skill.failDistance"]);
					}else{
						$p->teleport($this->v3["lobby.skill"]);
						Account::getInstance()->SendMessage($p,$this->lang["lobby.skill.tpSuccess"]);
					}
					break;
				case 364://床,长按回城
						if($p->distance($this->v3["lobby.spawn"]) < 10){
							Account::getInstance()->SendMessage($p, $this->lang["lobby.spawn.failDistance"]);
						}else{
							$p->teleport($this->v3["lobby.spawn"]);
							Account::getInstance()->SendMessage($p, $this->lang["lobby.spawn.tpSuccess"]);
						}
			break;
					break;
				case 397://苦力怕头,长按快速加入游戏
					break;
			}
		}
	}
	/*
	 * By:FXXK
	 * Tiles
	 */
	public function array2pos($array){
		$pos = new Position($array[0], $array[1], $array[2], $array[3]);
		return $pos;
	}

	public function array2v3($array){
		$v3 = new Vector3($array[0], $array[1], $array[2]);
		return $v3;
	}

	public function LobbySpawnPlayer(Player $p){
		$p->setMovementSpeed(1);//设置默认速度
		$p->removeAllEffects();//清空效果
		$p->setHealth(20);//设置生命值
		$p->setExperienceAndLevel(0, 0);//设置经验,等级
		$p->getInventory()->clearAll();//清空背包
		$p->setFoodEnabled(false);//停止计hunger
		$p->setFood(20);//饱食度
	}

	public function sendInv(Player $p){
		$p->getInventory()->clearAll();//清空背包
		$p->getInventory()->setContents($this->inv);//恢复指定物品
		$p->setNameTag(Account::getInstance()->getPrefix($p->getName()) . "§r§8" . $p->getName());//NameTag
	}
}