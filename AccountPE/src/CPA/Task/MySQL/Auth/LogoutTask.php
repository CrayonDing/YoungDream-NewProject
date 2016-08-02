<?php
namespace CPA\Task\Mysql\Auth;
use CPA\Account;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
class LogoutTask extends AsyncTask
{
    public function __construct($DB, $pn, $pl, $cb)
    {
        $this->DB = $db;
        $this->pn = $pn;
        $this->pl = $pl;
        $this->cb = $cb;
    }
    public function onRun()
    {
        $db   = @new \mysqli($this->DB["IP"], $this->DB["USER"], $this->DB["PASS"], $this->DB["DB"]);
        $name = strtolower($this->pn);
        $db->query("UPDATE " . $this->DB["ACTABLE"] . " SET  ol = 0
 WHERE name = '$name' ");
        $this->setResult(true);
        echo ("Logout success");
    }
    
    public function onCompletion(Server $server)
    {
        $bb = $this->cb;
        $server->getPluginManager()->getPlugin($this->pl)->$bb($this->name);
    }
}

