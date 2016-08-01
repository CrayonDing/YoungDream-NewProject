<?php
namespace CPA\Task\Mysql\Auth;
use CPA\Account;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
class LoginTask extends AsyncTask {
    public function __construct($DB, $pn, $passwd, $pl, $cb) {
        $this->DB     = $db;
        $this->pn     = $pn;
        $this->passwd = $passwd;
        $this->pl     = $pl;
        $this->cb     = $cb;
    }
    public function onRun() {
        $db     = @new \mysqli($this->DB["IP"], $this->DB["USER"], $this->DB["PASS"], $this->DB["DB"]);
        $name   = strtolower($this->pn);
        $result = $db->query("SELECT * FROM " . $this->DB["ACTABLE"] . " WHERE name = '$name'");
        echo ($db->connect_error);
        if ($result instanceof \mysqli_result) {
            $data = $result->fetch_assoc();
            $result->free();
            if ($data["name"] === $name) {
                if ($data["passwd"] === $this->passwd) { //密码输对了
                    $extra = "";
                    if ($data["vip"] !== 0) { //数组到变量
                        $data["vip"] = json_decode($data["vip"], true);
                        if ($data["vip"][1] < date("now")) { //已过期,从db中清除
                            $data["vip"] = 0;
                            $extra       = $extra . " , 'vip' = 0";
                        }
                    }
                    if ($data["MF"] !== 0) { //数组到变量
                        $data["MF"] = json_decode($data["MF"], true);
                        if ($data["MF"][1] < date("now")) { //已过期,从db中清除
                            $data["MF"] = 0;
                            $extra      = $extra . " , 'MF' = 0";
                        }
                    }
                    if ($data["ban"] !== 0) { //验证ban
                        if ($data["ban"] < date("now")) { //已过期,从db中清除
                            $data["ban"] = 0;
                            $extra       = $extra . " , 'ban' = 0";
                        }
                    }
                    $bb[0]  = $data;
                    $result = $db->query("SELECT * FROM " . $this->DB["INVTABLE"] . " WHERE name = '$name'");
                    echo ($db->connect_error);
                    if ($result instanceof \mysqli_result) {
                        $data = $result->fetch_assoc();
                        $result->free();
                        if ($data["name"] === $name) {
                            $bb[1] = $data;
                        } else {
                            $bb[1] = null;
                        }
                    }
                    $db->query("UPDATE " . $this->DB["ACTABLE"] . " SET  ol = '" . $this->DB["SERVERNAME"] . "'" . $extra . " WHERE pn = '$player' ");
                    $this->setResult($bb);
                } else { //密码输错了
                    $this->setResult(null);
                }
            } else {
                $this->setResult(null);
            }
            
        } else {
            $this->setResult(null);
        }
    }
    public function onCompletion(Server $server) {
        $bb = $this->cb;
        $server->getPluginManager()->getPlugin($this->pl)->$bb($this->name, $this->getResult());
    }
}
