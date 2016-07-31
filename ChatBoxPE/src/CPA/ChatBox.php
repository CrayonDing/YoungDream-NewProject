<?php
namespace CPA/ChatBox;
class ChatBox extends PluginBase implements Listener{
public $cfg=array(
"Default"=>"Lobby"
);
public $data;
pyblic $channel;
public static function getInstance(){
    return self::$obj;
}
/*
*By:FXXK
*启动函数
*/
public function onEnable(){
}
}