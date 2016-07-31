<?php
namespace CPA;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\entity\Entity;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use CPA\Task\Event\FinishLoginEvent;
use CPA\Task\MySQL\GetAllTask;
use CPA\Task\MySQL\Auth\LoginTask;
use CPA\Task\MySQL\Auth\LogoutTask;
use CPA\Task\MySQL\Auth\RegisterTask;
class Account extends PluginBase implements Listener {
    public $DB = array("IP" => "LOCALHOST:3307",
    "USER" => "admin", "PASS" => "admin",
    "DB" => "CPA", "ACTABLE" => "Accunt",
    "INVTABLE" => "MGInv",
    "LOGTABLE" => "Log",
    "SERVERNAME" => "Game-1",
    "DEFAULT_LANG" => "cn"
    );
    public $lang = array(
    "ALREADY_LOGINED" => "Already logined / 此账号已经登录",
    "NOW_LOGGING" => "Now logging,please wait... / 正在登陆...",
    "NO_REGISTER" => TextFormat::DARK_GRAY . "====== AccountPE ======" . TextFormat::LIGHT_PURPLE . "\nYou have not register / 您未注册" . TextFormat::DARK_GRAY . " use /r <password> <password> to register / 来注册 \n",
    "PASSWD_ALREADY_LOGINED" => "You do not have to type your passwd! / 差一点泄露密码!",
    "ALREADY_LOGINED_ANOTHER" => "AccountPE\n(尝试更换ID /Try to change ID)\n已经在其他服务器登录 / Already logined in another server."
    );
    public $Log; //是否登录
    public $Reg; //是否注册
    public static function getInstance() {
        return self::$obj;
    }
    /*
     *By:FXXK
     *启动函数
     */
    public function onEnable() {
        //会一直保持单线程连接
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN . "主线程:账户程序注册类成功...");
        
        $this->Conf = new Config($this->getDataFolder() . "Config.yml", Config::YAML, $this->DB);
        $this->DB   = $this->Conf->getAll();
        $Langcfg    = new Config($this->getDataFolder() . "Lang.yml", Config::YAML, $this->Lang);
        $this->Lang = $Langcfg->getAll();
        
        foreach ($this->DB as $k => $v) {
            $this->getLogger()->info(TextFormat::GREEN . "主线程:配置信息 $k => $v");
        }
        
        $this->getLogger()->info(TextFormat::Green . "主线程:开始测试连通性并创建库");
        $this->TestSQL();
        $this->getLogger()->info(TextFormat::GREEN . "主线程:MySQL联通性可用.正在更新玩家异常数据.....");
        $this->LeftConf = new Config($this->getDataFolder() . "OnlinePlayer.yml", Config::YAML, array());
        $LeftArray      = $this->LeftConf->getAll();
        if ($LeftArray !== null) { //出现异常玩家 
            $this->MainThreadLogout($Leftarray);
            $LeftArray = array();
            $this->LeftConf->setAll($LeftArray);
            $this->getLogger()->info(TextFormat::GREEN . "下线所有异常玩家完毕");
        } else {
            $this->getLogger()->info(TextFormat::GREEN . "无异常玩家");
        }
        
    }
    
    
    public function onDisable() {
        $this->SQL->close(); //断开连接？函数忘了
    }
    public function TestSQL() {
        $this->SQL = @new \mysqli($this->DB["IP"], $this->DB["USER"], $this->DB["PASS"], $this->DB["DB"]);
        $this->SQL->query("
        CREATE TABLE IF NOT EXISTS " . $this->DB["ACTABLE"] . "(
        Id int(11) NOT NULL auto_increment,
        name text NOT NULL,
                passwd text NOT NULL,
        rank text NOT NULL,
                KD text NOT NULL,
        xp text NOT NULL,
        money text NOT NULL,
        coin text NOT NULL,
        MF text NOT NULL,
        vip text NOT NULL,
        la text NOT NULL,
        ol text NOT NULL,
        PRIMARY KEY (`Id`)
        )");
        $this->SQL->query("
        CREATE TABLE IF NOT EXISTS " . $this->DB["INVTABLE"] . "(
        Id int(11) NOT NULL auto_increment,
        name text NOT NULL,
                normal text NOT NULL,
        HG text NOT NULL,
        SW text NOT NULL, 
                TPVP text NOT NULL,
        PRIMARY KEY (`Id`)
        )");
        //建立库,不要断开,储存全局变量
    }
    
    
    public function MainThreadLogout($LeftArray) {
        foreach ($LeftArray as $k => $v) {
            $this->getLogger()->info(TextFormat::RED . "主线程:玩家 " . TextFormat::YELLOW . "$k" . TextFormat::RED . " 正在下线");
            $name = strtolower($k);
            
            $this->SQL->query("UPDATE " . $this->DB["ACTABLE"] . " SET  ol = 0
 WHERE name = '$name' ");
            $this->getLogger()->info(TextFormat::RED . "主线程:玩家   " . TextFormat::YELLOW . "$k" . TextFormat::GREEN . " 下线成功");
        }
    }
    
    
    /*
     *By:FXXK
     *监听函数
     */
    
    
    public function onPreJoin(PlayerPreLoginEvent $e) {
        $pn = $e->getPlayer()->getName();
        if (!$this->isLog($pn)) {
            $this->isQueryed[$pn] = false;
            $this->getLogger()->info(TextFormat::GREEN . "异步:开始查询 " . TextFormat::YELLOW . "$pn" . TextFormat::GREEN . " 的账户信息");
            $task = new GetAllTask($this->DB, $pn, "AccountPE", "FinishGetAll");
            $this->getServer()->getScheduler()->scheduleAsyncTask($task);
        } else {
            $e->getPlayer()->kick($this->getLang("ALREADY_LOGINED"));
        }
    }
    
    
    public function onPlayerJoin(PlayerJoinEvent $e) {
        $e->setJoinMessage("");
        $p = $e->getPlayer();
        $p->setMovementSpeed(0); //不准走路
        $this->addEffect($p, 15, 9999); //变瞎
    }
    public function onQuit(PlayerQuitEvent $e) {
        $pn = $e->getPlayer()->getName();
        if ($this->isLog($pn)) { //已登录
            $this->getLogger()->info(TextFormat::GREEN . "异步:开始改变 " . TextFormat::YELLOW . "$pn" . TextFormat::GREEN . "        的在线情况");
            $task = new LogoutTask($this->DB, $pn, "AccountPE", "FinishLogout");
            $this->getServer()->getScheduler()->scheduleAsyncTask($task);
            $e->setQuitMessage("");
        }
    }
    
    
    /**
     * @param PlayerChatEvent $e
     *
     * @priority LOWEST 
     */
    public function onChat(PlayerChatEvent $e) {
        $pn = $e->getPlayer()->getName();
        if (!$this->isLog($pn)) { //未登录
            $e->setCancelled(true);
            if ($this->isQueryed($pn)) { //查询完毕
                if ($this->isReg($pn)) { //已注册
                    $e->getPlayer()->sendMessage($this->getLang("NOW_LOGGING"));
                    $task = new LoginTask($this->DB, $pn, $e->getMessage(), "AccountPE", "FinishLogin");
                    $this->getServer()->getScheduler()->scheduleAsyncTask($task);
                } else { //未注册
                    $e->getPlayer()->sendMessage($this->getLang("NO_REGISTER"));
                }
            } else { //未查询
            }
        } else { //防止瞎鸡巴输密码
            if ($e->getMessage() == $this->data[$pn]["passwd"]) {
                $e->setCancelled();
                $e->getPlayer()->sendMessage($this->getLang("PASSWD_ALREADY_LOGGINED"));
            }
        }
    }
    
    
    /**
     * @param PlayerCommandPreprocessEvent $event
     *
     * @priority LOWEST 
     */
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event) {
        if (!$this->isLog($event->getPlayer()->getName())) {
            $message = $event->getMessage();
            if ($message{0} === "/") { //Command
                $event->setCancelled(true);
                $command = substr($message, 1);
                $args    = explode(" ", $command);
                if ($args[0] === "r") {
                    if (count($args) == 3) {
                        if ($args[1] == $args[2]) {
                            if (strlen($args[1]) < 13 and strlen($args[1]) > 5) { //注册
                                $task = new RegisterTask($this->DB, $pn, $args[1], "AccountPE", "FinishRegister");
                                $this->getServer()->getScheduler()->scheduleAsyncTask($task);
                                $m = "正在注册....  \  Querying....";
                            } else { //提示位数
                                $m = "§9< 注册失败 < §e 密码过短或过长!(6-12位)\n§9< Error < §e Password is too long or to short!(6-12)";
                            }
                        } else { //提示两次输入一致
                            $m = "§9< 注册失败 < §e 密码输入不一致\n§9< Error < §e Passwords are not the same";
                        }
                    } else { //提示useage
                        $m = "§9< 注册失败 < §e /r <密码> <重复密码>\n§9< Error < §e /r <password> <password>";
                    }
                    $event->getPlayer()->sendMessage($m);
                } else {
                    $event->setCancelled(true);
                }
            }
        }
    }
    
    
    /*
     *By: FXXK
     *回传函数
     */
    public function FinishLogin($p, $v) {
        $player = $this->getServer()->getPlayer($p);
        if ($v = null) { //登录失败
            $player->sendMessage("§9< 登录失败 < §e 密码错误! Incorrect Password");
        } else { //更改变量
            $this->Log[$p] = true;
            $this->SendMessage($player, array(
                "en" => "§9< Logined successfully < §e Enjoy Games!\n§9< Language < §e Your default language is English",
                "cn" => "§9< 登录成功 < §e 享受游戏吧！\n§9< 语言系统 < §e 默认语言为中文"
            ));
            $player->sendMessage(" * To change your language,use /cn or /en\n* 使用 /cn 或 /en 改变你的语言 ");
            $this->data[$p] = $v[0];
            $this->inv[$p]  = $v[1]; //赋值变量
            $p->setMovementSpeed(1);
            $this->addEffect($player, 15, 0);
            $this->getServer()->getPluginManager()->callEvent(new FinishLoginEvent($p));
        }
    }
    
    
    public function FinishGetAll($p, $v) {
        $this->isQueryed[$p] = true;
        $this->data[$p]      = $v;
        if ($v == null) { //无信息
        } else {
            $this->Reg[$p] = true;
            $m             = "处理完毕";
            if ($v["ban"] !== 0) { //检测到ban,
                $m = $m . "检测到ban,已踢出";
                $t = 0;
                $this->getServer()->getPlayer($p)->kick("CPA Servers Account System:\n您已被系统封禁(剩余$t小时) / You‘ve been banned.($t hours left)"); //TO DO 剩余时间
            } elseif ($v["ol"] !== 0) { //有其他在线服务器
                $m = $m . "已经在" . $v["ol"] . "登录,以踢出";
                $this->getServer()->getPlayer($p)->kick($this->getLang("ALREADY_LOGINED_ANOTHER"));
            } else { //正常登录         
                if ($this->isReg($p)) {
                    //已注册
                    $message = TextFormat::DARK_GRAY . "====== CPA MINIGAMES ======" . TextFormat::LIGHT_PURPLE . "\nYou haven't register / 您未注册" . TextFormat::DARK_GRAY . " use /r <password> <password> to register / 来注册 \n" . TextFormart::YELLOW . "You can change your default language after register\n注册后您可以更改默认语言" . TextFormat::DARK_GRAY . "CPA Servers Account System";
                } else {
                    $message = TextFormat::DARK_GRAY . "====== CPA MINIGAMES ======" . TextFormat::LIGHT_PURPLE . "\nYou haven't logim / 您未登录" . TextFormat::DARK_GRAY . "Send your password to login / 发送密码来登录 \n" . TextFormart::PURPLE . " ! If you haven't came to this server,please change your ID\n ! 如果从未到过本服务器,请更换游戏名" . TextFormat::DARK_GRAY . "CPA Servers Account System";
                }
                $this->getServer()->getPlayer($p)->sendMessage($message);
                
            }
        }
        $this->getLogger()->info(TextFormat::GREEN . "异步:查询完毕 " . TextFormat::YELLOW . "$pn" . TextFormat::GREEN . "  交移主线程处理\n主线程:$m");
    }
    
    
    
    public function FinishLogout($pn) {
        if (isset($this->data[$pn])) {
            unset($this->data[$pn]);
        }
        if (isset($this->Log[$pn])) {
            unset($this->Log[$pn]);
        }
        if (isset($this->Reg[$pn])) {
            unset($this->Reg[$pn]);
        }
        if (isset($this->isQueryed[$pn])) {
            unset($this->isQueryed[$pn]);
        }
        if (isset($this->inv[$pn])) {
            unset($this->inv[$pn]);
        }
    }
    
    
    public function FinishRegister($pn, $v) {
        if (!$v) { //注册失败
            $this->getServer()->getPlayer($pn)->kick("CPA Account System:\nUnknown error during process of register 未知错误.");
            $this->getLogger()->info(TextFormat::GREEN . "玩家 " . TextFormat::YELLOW . "$pn" . TextFormat::GREEN . "        的注册过程出现未知错误");
        } else { //注册成功,登录
            $p->sendMessage("§9< Register successfully < §e Now login");
            $task = new LoginTask($this->DB, $pn, $v, "AccountPE", "FinishLogin");
            $this->getServer()->getScheduler()->scheduleAsyncTask($task);
        }
    }
    
    
    /*
     *By:FXXK
     *自定义函数(API部分)
     */
    public function addEffect($p, $id, $time, $level = 1, $bool = true) {
        $effect = Effect::getEffect($id);
        $effect->setVisible($bool);
        $effect->setAmplifier($level);
        $effect->setDuration(20 * $time);
        $p->addEffect($effect);
        /*
        ID1 速度    ID2 缓慢    ID3 急迫    ID4 挖掘疲劳    ID5 力量    ID8 跳跃提升    ID9 反胃    ID10 生命恢复
        ID11 抗性提升    ID12 防火    ID13 水下呼吸    ID14 隐身ID15 变瞎    ID18 虚弱    ID19 中毒    ID20 凋零
        */
    }
    
    
    public function isLog($pn) {
        if (isset($this->Log[$pn])) {
            return $this->Log[$pn];
        } else {
            return false;
        }
    }
    
    
    public function isReg($pn) {
        if (isset($this->Reg[$pn])) {
            return $this->Reg[$pn];
        } else {
            return false;
        }
    }
    
    
    public function GetVIP($pn) {
        if (isset($this->VIP[$pn])) {
            return $this->VIP[$pn];
        } else {
            return false;
        }
    }
    
    
    public function SendMessage($p, $m) {
        $p->sendMessage($m[$this->GetLanguage($p->getName())]);
    }
    
    
    public function SendPopup($p, $m) {
        $p->sendPopup($m[$this->GetLanguage($p->getName())]);
    }
    
    
    public function SendTip($p, $m) {
        $p->sendTip($m[$this->GetLanguage($p->getName())]);
        
    }
    
    
    public function GetLanguage($pn) {
        if (isset($this->data[$pn]["la"])) {
            return $this->data[$pn]["la"];
        } else {
            return "en";
        }
    }
    
    
    public function GetVIP($pn) {
        if (isset($this->data[$pn]["vip"][0])) {
            return $this->data[$pn]["vip"][0];
        } else {
            return false;
        }
    }
    
    
    public function getData($pn, $column = "all") {
        if ($column == "all") {
            if (isset($this->data[$pn])) {
                return $this->data[$pn];
            } else {
                return false;
            }
        } else {
            if (isset($this->data[$pn][$column])) {
                return $this->data[$pn][$column];
            } else {
                return false;
            }
        }
    }
    
    
    public function getLang($strname, $str1, $str2) {
        return str_replace($str1, $str2, $this->Lang[$strname]); //LANG
    }
}
