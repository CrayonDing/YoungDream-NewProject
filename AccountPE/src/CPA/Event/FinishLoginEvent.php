<?php

namespace CPA\Event;

use pocketmine\event\Cancellable;;
use pocketmine\event\plugin\PluginEvent;

class FinishLoginEvent extends PluginEvent implements Cancellable{
	public static $handlerList = null;

	public function __construct($player){
		$this->player = $player;
	}
	public function getPlayer(){
		return $this->player;
	}
  public function getPlayerName(){
		return $this->player;
	}
  public function getName(){
		return $this->player;
	}
	
}