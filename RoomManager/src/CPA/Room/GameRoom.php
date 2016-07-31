<?php
namespace CPA/Room;
use \pocketmine\server;
class GameRoom{
  public $Name;//房间名称
  public $Mark=null;
  public $Level;//绑定的level
  public $willsave;
  public function __constract($name,$level,$mark,$willsave=false){//构建函数
   $this->Name = $name;
   $this->Mark = $mark;
   $this->Level = $level;
   $this->WillSave = $willsave;
  }
  public function setMark($v){
  	 return $this->Mark=$v;
  }
  public function getMark(){
  	 return $this->Mark;
  }
  public function getLevel(){
  	  return $this->Level;
  }
  public function getName(){
  	  return $this->Name;
  }
  public function remove(){
  	//Call event:OnGameRoomRemove
  	//Unset level
  	if($this->WillSave){
  		//Moveback floder
  	}else{
  		//DeleteFolder
  	}
  	return true;
  }
  public function rollback(){
  	//Call event:onGameRoomRB
  	//Unset level
  	//Copy folder
  }
  
}