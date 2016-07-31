<?php

namespace CPA\Task;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use CPA\Account;

class LobbyPopupTip extends PluginTask {

    private $plugin;

    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($tick) {
    $players = $this->getOwner()->getServer()->getDefaultLevel()->getPlayers();
    foreach ($players as $player) {
    $name=$player->getName();
    if(!Account::getInstance()->isLog($name)){//未登录
        if(Account::getInstance()->isQueryed($name)){
//已获取完毕
            if(Account::getInstance()->isReg($name)){//未注册
            $player->sendTip(TextFormat::BOLD . TextFormat::GRAY . "/r <password> <password>" . TextFormat::RED . "\nPlease register / 请注册");
            }else{//已注册
            $player->sendTip(TextFormat::BOLD . TextFormat::GRAY . "Type your password in chat / 直接发送密码" . TextFormat::RED . "\nPlease login / 请登录");
            }
        }
else{//未获取完毕
        $player->sendTip(TextFormat::BOLD. TextFormat::RED . "\nPlease wait... / 请等待....");
        }
    }else{//已登录
        $id = $player->getItemInHand()->getId();
        if(isset( $this->plugin->lang["Item.popup.$id"]) and $player->getLevel()->getFolderName() == $this->plugin->cfg["lobby"]){
            $pn=$player->getName();
            $coin=Account::getInstance()->getData($pn,"coin");
            $money=Account::getInstance()->getData($pn,"money");
            $exp=Account::getInstance()->getData($pn,"exp");
                Account::getInstance()->SendPopup($player, $this->plugin->lang["Item.popup.$id"]);
        }
    }
    }
//结束循环
    }

}