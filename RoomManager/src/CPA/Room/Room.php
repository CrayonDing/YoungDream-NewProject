<?php
namespace CPA/Room;
use \pocketmine\Server;
class Room{
  public $RoomList;//房间列表
  public $LevelNameList;//地图名称列表
  public $Folder;//地图目录
  public function __constract($plugin){//构建函数
   $this->plugin = $plugin;
   $this->Folder = $plugin->MapFolder;
  }
  public function getRoom($name){
  	 return $this->RoomList[$name];
  }
  public function hasRoom($name){
  	  return isset($thus->RoomList[$name]);
  }
  public function removeRoom($name){
  	$result=$this->RoomList[$name]->remove();
  	  unset($LevelNameList[$this->RoomList[$name]->getLevel()->getFolderName()],$this->RoomList[$name]);
  	  
  	  return $result;
  	    }
  public function getAllRoom(){
  	  return $this->RoomList;
  }
   public function getRoomType($name){
  	  
  }
  public function isRoom($name){
  	 
  }
  public function getRoomByFolderName($name){
  	$result=$this->LevelNeList[$name];
  	if(isset($result)){
  		return $result;
  	}else{
  		return false;
  	}
  }
  public function createRoom($type,$name,$mapname=null,$mark=null,$willsave=false){
  	if(isset($this->RoomList[$name])){
  	  switch($type){
  	  	  case "GameRoom":
  	  	  $room = new GameRoom($name,$mapname,$mark,$willsave);
  	  	  
  	  	  $this->RoomList[$name] = $room;
  	  	  $this->LevelNameList[$room->getLevel()->getFolderName()]=$room;
  	  	  //call event:onGameRoomCreate
  	  	  return  $room;
    	  /*	  
  	     case "Lobby":
  	  	  $room = new Lobby($name,$mapname,$mark,$willsave);
  	  	  $this->RoomList[$name] = $room;
  	  	  //call event:onLobbyCreate
  	  	  return  $room;
  	  	  break;
  	  	  */
  	  	  default:
  	  	  return false;
  	  }
  }else{
  	  return false;
  }
  public function hasMap($foldername){
  	
  }
}