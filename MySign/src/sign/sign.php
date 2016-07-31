<?php
namespace sign;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;

class sign extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener
{
public $playerdata = array();
public function onEnable(){
	$this->getLogger()->info(TextFormat::GREEN.'开始验证插件授权...');
		$data=ZXDA::checkHosts();
		if(!$data['success'])
		{
			ZXDA::killit($data['message'],$this);
			return;
		}
		ZXDA::check($this,388,'MTIyNjUzMDc0ODUyNzMxOTE5MDUwNzM5MzcyMjg3MzIyMzM2NTIwNjg2MjY5NDg2MTU3OTY0MzMzNjQ5NjQxNTE1MjI0ODM4NzA0Njc2MjkzNDMyNjExNjg1MjA0MDA3MjIzMTk3ODQ1ODE3NDk0NDY4MjM4Mzk5MTI4OTI3MDE3ODg4Mzk4NjAxNTU5Mzg0MzAzMjgxODIyMjMxOTMyNTg0Mzc1ODYwODEyMTQyOTgwNzA4MjUwMDIyOTQ1NjU5NTY4Mzg4Nzc2MzA2MzM2ODA4MjAyMDE3NTU4MDYxMjY0NzQ1MDA1OQ==');
		$data=ZXDA::getInfo($this,25);
		if($data['success'])
		{
			if(version_compare($data['version'],$this->getDescription()->getVersion())<=0)
			{
				$this->getLogger()->info(TextFormat::GREEN.'您当前使用的插件是最新版');
			}
			else
			{
				$this->getLogger()->info(TextFormat::GREEN.'检测到新版本,最新版:'.$data['version'].",更新日志:\n".$data['update_info']);
			}
		}
		else
		{
			$this->getLogger()->warning('更新检查失败');
		}
		//继续加载插件
	   $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->path = $this->getDataFolder();
	   @mkdir($this->path);
	 $this->data = new Config($this->getDataFolder()."signdata.yml",Config::YAML,array());
	 $config = new Config($this->getDataFolder()."config.yml",Config::YAML,array(
	 "签到.单次.金钱"=>30,
	 "签到.单次.随机金钱.开启"=>false,
	 "签到.单次.随机金钱.最小值"=>10,
	 "签到.单次.随机金钱.最大值"=>30,
	 "签到.累计天数.开启"=>true,
	 "签到.累计天数.金钱"=>2,
	 "签到.累计天数.上限"=>30,
	 "签到.进入提示.是否开启"=>true,
	 "签到.VIP加成.是否开启"=>false,
	 "签到.VIP插件.名称"=>"rvip",
	 "签到.VIP加成.倍数"=>1.5
	 ));
	 $this->cfg=$config->getAll();
	 $this->cfg["签到.VIP插件.名称"]=strtolower($config->get("签到.VIP插件.名称"));
	 $lang = new Config($this->getDataFolder()."lang.yml",Config::YAML,array(
	 "进入提示"=>"§9< 签到系统 < §e 输入/sign 即可签到",
	 "签到成功.单天签到"=>"§9< 签到系统 < §e 成功签到,获得 %1 金钱",
	 "签到成功.连续签到"=>"§9< 签到系统 < §e 由于连续签到 %3 天 额外获得 %2 金钱",
	 "签到成功.连续签到.达到上限"=>"§9< 签到系统 < §e 连续签到达到上限( %4 天) 额外获得 %2 金钱",
	 "签到失败"=>"§9< 签到系统 < §e 签到失败,您已经签过到了",
	 "签到成功.VIP"=>"§9< 签到系统 < §e 您是VIP,经折算后今天共获得 %5 金钱"
	 ));
	 $this->lang=$lang->getAll();
	}
	public function onJoin(\pocketmine\event\player\PlayerJoinEvent $e){
		if(!ZXDA::isVerified())
		{
			return;
		}
		if($this->cfg["签到.进入提示.是否开启"]){
			$e->getPlayer()->sendMessage($this->lang["进入提示"]);
		}
	}
	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $args) {
		switch ($cmd->getName()) {
			case "mysign":
			case "qiandao":
			$p=$sender;
			$pn=$sender->getName();$c=$this->data = new Config($this->getDataFolder()."signdata.yml",Config::YAML,array());
			$data=$c->get($pn);
				date_default_timezone_set('Asia/Shanghai');
				if(!isset($data[2])){//如果没签过到
					$data[2] = strtotime("now");
					$data[1] = 0;
					$value = $data;
					$this->data->set($pn,$value);$this->data->save();
					$sign = true;
				}elseif(date("Y-m-d",strtotime("now")) !== date("Y-m-d",$data[2])){//如果以前签过到，今天没签过
					$sign = true;
					if(date("Y-m-d",strtotime("-1 day")) == date("Y-m-d",$data[2])){
						$data[2] = strtotime("now");
						$data[1] = $data[1] + 1;
						$value = $data;
						$this->data->set($pn,$value);$this->data->save();
					}else{
						$data[2] = strtotime("now");
						$data[1] = 0;
						$value = $data;
						$this->data->set($pn,$value);$this->data->save();
					}
				}
				if(isset($sign)){
					$p->sendMessage(str_replace(array("%1"),array($this->cfg["签到.单次.金钱"]),$this->lang["签到成功.单天签到"]));
					if($this->cfg["签到.单次.随机金钱.开启"]){
						mt_rand($this->cfg["签到.单次.随机金钱.最小值"],$this->cfg["签到.单次.随机金钱.最大值"]);
						
					}else{
					$money = $this->cfg["签到.单次.金钱"];
					}
					if(!$data[1] == 0 and $this->cfg["签到.累计天数.开启"]){
						if($data[1] < $this->cfg["签到.累计天数.上限"]){
							$newmoney = $this->cfg["签到.累计天数.金钱"] * $data[1];
							$money = $money + $newmoney;
							$p->sendMessage(str_replace(array("%2","%3"), array($newmoney,$data[1]), $this->lang["签到成功.单天签到"]));
						}else{
							$newmoney = $this->cfg["签到.累计天数.金钱"] * $data[1];
							$money = $money + $this->cfg["签到.累计天数.金钱"] * $this->cfg["签到.累计天数.上限"];
							$p->sendMessage(str_replace(array("%2","%4"), array($newmoney,$this->cfg["签到.累计天数.上限"]), $this->lang["签到成功.累计签到"]));
						}

					}
					if($this->cfg["签到.VIP加成.是否开启"]){
					    if($this->getVIP($pn)){
				    		$money = $money * $this->cfg["签到.VIP加成.倍数"];
							$p->sendMessage(str_replace(array("%5"), array($money), $this->lang["签到成功.VIP"]));
				    	}
					}
					EconomyAPI::getInstance()->addMoney($pn, $money);
				}else{
					$p->sendMessage($this->lang["签到失败"]);//签到失败
				}
				break;
		}
	}
	public function getVIP($pn){
		switch($this->cff["签到.VIP插件.名称"]){
			case "rvip":
			$a=$this->getPluginManager()->getPlugin("RVIP");
			if($a!==null){
				if($a->VIP("get",$pn)==0){
					return false;
				}else{
					return true;
				}
			}else{
				return false;
			}
			break;
			case "fvip":
			$FVIP=$this->getPluginManager()->getPlugin("RVIP")->getInstance();
			if($FVIP !== null){
		       	return $FVIP->isVIP($name);
			}else{
				return false;
			}
			break;
		}
	}
}
class ZXDA
{
	private static $_API_VERSION=5010;
	private static $_VERIFIED=false;
	private static $_VERIFY_SERVERS=array(
		'v1.zxda-verify.net',
		'v2.zxda-verify.net',
		'v3.zxda-verify.net',
		'v4.zxda-verify.net',
		'v5.zxda-verify.net');
	private static $_UPDATE_SERVER_INDEX=0;
	
	public static function checkHosts()
	{
		$data='';
		if(file_exists(getenv('systemroot').'/system32/drivers/etc/hosts'))
		{
			$data=@file_get_contents(getenv('systemroot').'/system32/drivers/etc/hosts');
		}
		else if(file_exists('/etc/hosts'))
		{
			$data=@file_get_contents('/etc/hosts');
		}
		else
		{
			return array(
				'success'=>false,
				'message'=>'暂不支持当前操作系统(0008)');
		}
		if($data=='')
		{
			return array(
				'success'=>false,
				'message'=>'暂不支持当前操作系统(0009)');
		}
		foreach(self::$_VERIFY_SERVERS as $host)
		{
			if(stripos($data,$host)!==false)
			{
				return array(
					'success'=>false,
					'message'=>'非法解析(0010)');
			}
			unset($host);
		}
		return array(
			'success'=>true,
			'message'=>'Hosts校验通过');
	}
	
	public static function check($plugin,$pid,$key)
	{
		try
		{
			$key=base64_decode($key);
			date_default_timezone_set('Asia/Shanghai');
			self::$_VERIFIED=false;
			if(!function_exists('curl_init'))
			{
				self::killit('bin不合法(0001)',$plugin);
			}
			$token=sha1(uniqid());
			$submit=array(
				'id'=>$pid,
				'port'=>\pocketmine\Server::getInstance()->getPort(),
				'token'=>$token,
				'server'=>'');
			for($i=0;$i<count(self::$_VERIFY_SERVERS);$i++)
			{
				self::info('§a正在连接授权服务器 #'.($i+1).' ...',$plugin);
				$ip=@gethostbyname(self::$_VERIFY_SERVERS[$i]);
				$submit['server']=ip2long($ip);
				$ch=self::zxda_curl_init('http://'.$ip.'/check.php?api='.self::$_API_VERSION,array(
					'id'=>$pid,
					'submit'=>json_encode(array_merge($submit,array(
						'sign'=>base64_encode(self::rsa_encode(md5(json_encode($submit)),$key,768)))))));
				@$data=explode('|',curl_exec($ch));
				if(count($data)>=2)
				{
					self::$_UPDATE_SERVER_INDEX=$i;
					break;
				}
				@curl_close($ch);
				self::info('§e授权服务器 #'.($i+1).' 连接失败',$plugin);
				unset($ip,$ch,$data);
			}
			if(!isset($data) || !is_array($data))
			{
				self::killit('无法连接任何授权服务器(0011)',$plugin);
			}
			if(count($data)<2)
			{
				@var_dump($data);
				self::killit('网络错误或服务器内部错误(0002)['.@curl_error($ch).']',$plugin);
			}
			if($data[0]!='')
			{
				self::killit($data[1],$plugin);
			}
			$data=@json_decode(base64_decode($data[1]),true);
			if(!isset($data['key']) || !isset($data['data']) || strlen($key=self::rsa_decode(base64_decode($data['key']),$key,768))!=32 || !is_array($result=@json_decode(self::aes_decode($data['data'],$key),true)))
			{
				@var_dump($data);
				self::killit('网络错误或服务器内部错误(0003)['.@curl_error($ch).']',$plugin);
			}
			else if(!isset($result['success']))
			{
				@var_dump($data);
				self::killit('网络错误或服务器内部错误(0004)['.@curl_error($ch).']',$plugin);
			}
			else if(!$result['success'])
			{
				self::killit(isset($result['info'])?$result['info']:'出现了未知错误',$plugin);
			}
			else if(!isset($result['token']) || !isset($result['info']))
			{
				self::killit('网络错误或服务器内部错误(0005)['.@curl_error($ch).']',$plugin);
			}
			else if($result['token']!==sha1(strrev($token)))
			{
				self::killit('请购买授权后再使用此插件(0006)',$plugin);
			}
			else
			{
				self::$_VERIFIED=true;
				@$plugin->getLogger()->info('§a'.$result['info']);
			}
			@curl_close($ch);
		}
		catch(\Exception $err)
		{
			@ob_start();
			@var_dump($err);
			@file_put_contents($plugin->getDataFolder().'../../0007_data.dump',ob_get_contents());
			@ob_end_clean();
			self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助',$plugin);
		}
	}
	
	public static function isVerified()
	{
		return self::$_VERIFIED;
	}
	
	public static function getInfo($plugin,$pid)
	{
		if(!function_exists('curl_init'))
		{
			self::killit('bin不合法(0001)',$plugin);
		}
		$ip=@gethostbyname(self::$_VERIFY_SERVERS[self::$_UPDATE_SERVER_INDEX]);
		$ch=self::zxda_curl_init('http://'.$ip.'/info.php?api='.self::$_API_VERSION,array(
			'id'=>$pid,
			'server_address'=>$ip));
		$result=json_decode(curl_exec($ch),true);
		if(!is_array($result))
		{
			return array(
				'success'=>false,
				'message'=>'网络错误或服务器内部错误(0002)['.@curl_error($ch).']');
		}
		if(!isset($result['success']))
		{
			return array(
				'success'=>false,
				'message'=>'网络错误或服务器内部错误(0004)['.@curl_error($ch).']');
		}
		else if(!$result['success'])
		{
			return array(
				'success'=>false,
				'message'=>isset($result['info'])?$result['info']:'出现了未知错误');
		}
		else
		{
			@curl_close($ch);
			return array(
				'success'=>true,
				'message'=>'',
				'version'=>$result['version'],
				'update_info'=>$result['update_info']);
		}
	}
	
	public static function info($msg,$plugin=null)
	{
		if($plugin===null)
		{
			echo($msg);
		}
		else
		{
			@$plugin->getLogger()->info($msg);
		}
	}
	
	public static function killit($msg,$plugin=null)
	{
		if($plugin===null)
		{
			echo('抱歉,插件授权验证失败[SDK:'.self::$_API_VERSION."]\n附加信息:".$msg);
		}
		else
		{
			@$plugin->getLogger()->warning('§e抱歉,插件授权验证失败[SDK:'.self::$_API_VERSION.']');
			@$plugin->getLogger()->warning('§e附加信息:'.$msg);
		}
		exit(1);
		die('');
		@posix_kill(getmypid(),9);
		function getNull()
		{
			return(null);
		}
		getNull()->wtf还关不掉吗();
		while(true);
	}
	
	private static function zxda_curl_init($url,$data)
	{
		$ch=@curl_init();
		@curl_setopt($ch,CURLOPT_URL,$url);
		@curl_setopt($ch,CURLOPT_HTTPHEADER,array('User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 ZXDA_Verify'));
		@curl_setopt($ch,CURLOPT_PORT,7655);
		@curl_setopt($ch,CURLOPT_TIMEOUT,15);
		@curl_setopt($ch,CURLOPT_POST,true);
		@curl_setopt($ch,CURLOPT_HEADER,false);
		@curl_setopt($ch,CURLOPT_AUTOREFERER,true);
		@curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		@curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
		@curl_setopt($ch,CURLOPT_FORBID_REUSE,1);
		@curl_setopt($ch,CURLOPT_FRESH_CONNECT,1);
		@curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		@curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
		@curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		unset($url,$data);
		return $ch;
	}
	
	//RSA加密算法实现
	public static function rsa_encode($message,$modulus,$keylength=1024,$isPriv=true){$result=array();while(strlen($msg=substr($message,0,$keylength/8-5))>0){$message=substr($message,strlen($msg));$result[]=self::number_to_binary(self::pow_mod(self::binary_to_number(self::add_PKCS1_padding($msg,$isPriv,$keylength/8)),'65537',$modulus),$keylength/8);unset($msg);}return implode('***&&&***',$result);}
	public static function rsa_decode($message,$modulus,$keylength=1024){$result=array();foreach(explode('***&&&***',$message) as $message){$result[]=self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message),'65537',$modulus),$keylength/8),$keylength/8);unset($message);}return implode('',$result);}
	private static function pow_mod($p,$q,$r){$factors=array();$div=$q;$power_of_two=0;while(bccomp($div,'0')==1){$rem=bcmod($div,2);$div=bcdiv($div,2);if($rem){array_push($factors,$power_of_two);}$power_of_two++;}$partial_results=array();$part_res=$p;$idx=0;foreach($factors as $factor){while($idx<$factor){$part_res=bcpow($part_res,'2');$part_res=bcmod($part_res,$r);$idx++;}array_push($partial_results,$part_res);}$result='1';foreach($partial_results as $part_res){$result=bcmul($result,$part_res);$result=bcmod($result,$r);}return $result;}
	private static function add_PKCS1_padding($data,$isprivateKey,$blocksize){$pad_length=$blocksize-3-strlen($data);if($isprivateKey){$block_type="\x02";$padding='';for($i=0;$i<$pad_length;$i++){$rnd=mt_rand(1,255);$padding .= chr($rnd);}}else{$block_type="\x01";$padding=str_repeat("\xFF",$pad_length);}return "\x00".$block_type.$padding."\x00".$data;}
	private static function remove_PKCS1_padding($data,$blocksize){assert(strlen($data)==$blocksize);$data=substr($data,1);if($data{0}=='\0'){return '';}assert(($data{0}=="\x01") || ($data{0}=="\x02"));$offset=strpos($data,"\0",1);return substr($data,$offset+1);}
	private static function binary_to_number($data){$radix='1';$result='0';for($i=strlen($data)-1;$i>=0;$i--){$digit=ord($data{$i});$part_res=bcmul($digit,$radix);$result=bcadd($result,$part_res);$radix=bcmul($radix,'256');}return $result;}
	private static function number_to_binary($number,$blocksize){$result='';$div=$number;while($div>0){$mod=bcmod($div,'256');$div=bcdiv($div,'256');$result=chr($mod).$result;}return str_pad($result,$blocksize,"\x00",STR_PAD_LEFT);}
	
	//AES加密算法实现
	public static function aes_encode($plaintext,$password,$nBits=256,$keep=0){$blockSize=16;if(!($nBits==128||$nBits==192||$nBits==256))return '';$nBytes=$nBits/8;$pwBytes=array();for($i=0; $i<$nBytes; $i++)$pwBytes[$i]=ord(substr($password,$i,1))&0xff;$key=self::cipher($pwBytes,self::keyExpansion($pwBytes));$key=array_merge($key,array_slice($key,0,$nBytes-16));$counterBlock=array();if($keep==0){$nonce=floor(microtime(true)*1000);$nonceMs=$nonce%1000;$nonceSec=floor($nonce/1000);$nonceRnd=floor(rand(0,0xffff));}else{$nonce=10000;$nonceMs=$nonce%1000;$nonceSec=floor($nonce/1000);$nonceRnd=10000;}for($i=0; $i<2; $i++)$counterBlock[$i]=self::urs($nonceMs,$i*8)&0xff;for($i=0; $i<2; $i++)$counterBlock[$i+2]=self::urs($nonceRnd,$i*8)&0xff;for($i=0; $i<4; $i++)$counterBlock[$i+4]=self::urs($nonceSec,$i*8)&0xff;$ctrTxt='';for($i=0; $i<8; $i++)$ctrTxt.=chr($counterBlock[$i]);$keySchedule=self::keyExpansion($key);$blockCount=ceil(strlen($plaintext)/$blockSize);$ciphertxt=array();for($b=0; $b<$blockCount; $b++){for($c=0; $c<4; $c++)$counterBlock[15-$c]=self::urs($b,$c*8)&0xff;for($c=0; $c<4; $c++)$counterBlock[15-$c-4]=self::urs($b/0x100000000,$c*8);$cipherCntr=self::cipher($counterBlock,$keySchedule);$blockLength=$b<$blockCount-1 ? $blockSize : (strlen($plaintext)-1)%$blockSize+1;$cipherByte=array();for($i=0; $i<$blockLength; $i++){$cipherByte[$i]=$cipherCntr[$i]^ord(substr($plaintext,$b*$blockSize+$i,1));$cipherByte[$i]=chr($cipherByte[$i]);}$ciphertxt[$b]=implode('',$cipherByte);}$ciphertext=$ctrTxt . implode('',$ciphertxt);$ciphertext=base64_encode($ciphertext);return $ciphertext;}
	public static function aes_decode($ciphertext,$password,$nBits=256){$blockSize=16;if(!($nBits==128||$nBits==192||$nBits==256))return '';$ciphertext=base64_decode($ciphertext);$nBytes=$nBits/8;$pwBytes=array();for($i=0; $i<$nBytes; $i++)$pwBytes[$i]=ord(substr($password,$i,1))&0xff;$key=self::cipher($pwBytes,self::keyExpansion($pwBytes));$key=array_merge($key,array_slice($key,0,$nBytes-16));$counterBlock=array();$ctrTxt=substr($ciphertext,0,8);for($i=0; $i<8; $i++)$counterBlock[$i]=ord(substr($ctrTxt,$i,1));$keySchedule=self::keyExpansion($key);$nBlocks=ceil((strlen($ciphertext)-8)/$blockSize);$ct=array();for($b=0; $b<$nBlocks; $b++)$ct[$b]=substr($ciphertext,8+$b*$blockSize,16);$ciphertext=$ct;$plaintxt=array();for($b=0; $b<$nBlocks; $b++){for($c=0; $c<4; $c++)$counterBlock[15-$c]=self::urs($b,$c*8)&0xff;for($c=0; $c<4; $c++)$counterBlock[15-$c-4]=self::urs(($b+1)/0x100000000-1,$c*8)&0xff;$cipherCntr=self::cipher($counterBlock,$keySchedule);$plaintxtByte=array();for($i=0; $i<strlen($ciphertext[$b]); $i++){$plaintxtByte[$i]=$cipherCntr[$i]^ord(substr($ciphertext[$b],$i,1));$plaintxtByte[$i]=chr($plaintxtByte[$i]);}$plaintxt[$b]=implode('',$plaintxtByte);}$plaintext=implode('',$plaintxt);return $plaintext;}
	private static function cipher($input,$w){$Nb=4;$Nr=count($w)/$Nb-1;$state=array();for($i=0; $i<4*$Nb; $i++)$state[$i%4][floor($i/4)]=$input[$i];$state=self::addRoundKey($state,$w,0,$Nb);for($round=1; $round<$Nr; $round++){$state=self::subBytes($state,$Nb);$state=self::shiftRows($state,$Nb);$state=self::mixColumns($state,$Nb);$state=self::addRoundKey($state,$w,$round,$Nb);}$state=self::subBytes($state,$Nb);$state=self::shiftRows($state,$Nb);$state=self::addRoundKey($state,$w,$Nr,$Nb);$output=array(4*$Nb);for($i=0; $i<4*$Nb; $i++)$output[$i]=$state[$i%4][floor($i/4)];return $output;}
	private static function addRoundKey($state,$w,$rnd,$Nb){for($r=0; $r<4; $r++){for($c=0; $c<$Nb; $c++)$state[$r][$c]^=$w[$rnd*4+$c][$r];}return $state;}
	private static function subBytes($s,$Nb){for($r=0; $r<4; $r++){for($c=0; $c<$Nb; $c++)$s[$r][$c]=self::$sBox[$s[$r][$c]];}return $s;}
	private static function shiftRows($s,$Nb){$t=array(4);for($r=1; $r<4; $r++){for($c=0; $c<4; $c++)$t[$c]=$s[$r][($c+$r)%$Nb];for($c=0; $c<4; $c++)$s[$r][$c]=$t[$c];}return $s;}
	private static function mixColumns($s,$Nb){for($c=0; $c<4; $c++){$a=array(4);$b=array(4);for($i=0; $i<4; $i++){$a[$i]=$s[$i][$c];$b[$i]=$s[$i][$c]&0x80 ? $s[$i][$c]<<1^0x011b : $s[$i][$c]<<1;}$s[0][$c]=$b[0]^$a[1]^$b[1]^$a[2]^$a[3];$s[1][$c]=$a[0]^$b[1]^$a[2]^$b[2]^$a[3];$s[2][$c]=$a[0]^$a[1]^$b[2]^$a[3]^$b[3];$s[3][$c]=$a[0]^$b[0]^$a[1]^$a[2]^$b[3];}return $s;}
	private static function keyExpansion($key){$Nb=4;$Nk=count($key)/4;$Nr=$Nk+6;$w=array();$temp=array();for($i=0; $i<$Nk; $i++){$r=array($key[4*$i],$key[4*$i+1],$key[4*$i+2],$key[4*$i+3]);$w[$i]=$r;}for($i=$Nk; $i<($Nb*($Nr+1)); $i++){$w[$i]=array();for($t=0; $t<4; $t++)$temp[$t]=$w[$i-1][$t];if($i%$Nk==0){$temp=self::subWord(self::rotWord($temp));for($t=0; $t<4; $t++)$temp[$t]^=self::$rCon[$i/$Nk][$t];}else if($Nk>6&&$i%$Nk==4){$temp=self::subWord($temp);}for($t=0; $t<4; $t++)$w[$i][$t]=$w[$i-$Nk][$t]^$temp[$t];}return $w;}
	private static function subWord($w){for($i=0; $i<4; $i++)$w[$i]=self::$sBox[$w[$i]];return $w;}
	private static function rotWord($w){$tmp=$w[0];for($i=0; $i<3; $i++)$w[$i]=$w[$i+1];$w[3]=$tmp;return $w;}
	private static $sBox=array(0x63,0x7c,0x77,0x7b,0xf2,0x6b,0x6f,0xc5,0x30,0x01,0x67,0x2b,0xfe,0xd7,0xab,0x76,0xca,0x82,0xc9,0x7d,0xfa,0x59,0x47,0xf0,0xad,0xd4,0xa2,0xaf,0x9c,0xa4,0x72,0xc0,0xb7,0xfd,0x93,0x26,0x36,0x3f,0xf7,0xcc,0x34,0xa5,0xe5,0xf1,0x71,0xd8,0x31,0x15,0x04,0xc7,0x23,0xc3,0x18,0x96,0x05,0x9a,0x07,0x12,0x80,0xe2,0xeb,0x27,0xb2,0x75,0x09,0x83,0x2c,0x1a,0x1b,0x6e,0x5a,0xa0,0x52,0x3b,0xd6,0xb3,0x29,0xe3,0x2f,0x84,0x53,0xd1,0x00,0xed,0x20,0xfc,0xb1,0x5b,0x6a,0xcb,0xbe,0x39,0x4a,0x4c,0x58,0xcf,0xd0,0xef,0xaa,0xfb,0x43,0x4d,0x33,0x85,0x45,0xf9,0x02,0x7f,0x50,0x3c,0x9f,0xa8,0x51,0xa3,0x40,0x8f,0x92,0x9d,0x38,0xf5,0xbc,0xb6,0xda,0x21,0x10,0xff,0xf3,0xd2,0xcd,0x0c,0x13,0xec,0x5f,0x97,0x44,0x17,0xc4,0xa7,0x7e,0x3d,0x64,0x5d,0x19,0x73,0x60,0x81,0x4f,0xdc,0x22,0x2a,0x90,0x88,0x46,0xee,0xb8,0x14,0xde,0x5e,0x0b,0xdb,0xe0,0x32,0x3a,0x0a,0x49,0x06,0x24,0x5c,0xc2,0xd3,0xac,0x62,0x91,0x95,0xe4,0x79,0xe7,0xc8,0x37,0x6d,0x8d,0xd5,0x4e,0xa9,0x6c,0x56,0xf4,0xea,0x65,0x7a,0xae,0x08,0xba,0x78,0x25,0x2e,0x1c,0xa6,0xb4,0xc6,0xe8,0xdd,0x74,0x1f,0x4b,0xbd,0x8b,0x8a,0x70,0x3e,0xb5,0x66,0x48,0x03,0xf6,0x0e,0x61,0x35,0x57,0xb9,0x86,0xc1,0x1d,0x9e,0xe1,0xf8,0x98,0x11,0x69,0xd9,0x8e,0x94,0x9b,0x1e,0x87,0xe9,0xce,0x55,0x28,0xdf,0x8c,0xa1,0x89,0x0d,0xbf,0xe6,0x42,0x68,0x41,0x99,0x2d,0x0f,0xb0,0x54,0xbb,0x16);
	private static $rCon=array(array(0x00,0x00,0x00,0x00),array(0x01,0x00,0x00,0x00),array(0x02,0x00,0x00,0x00),array(0x04,0x00,0x00,0x00),array(0x08,0x00,0x00,0x00),array(0x10,0x00,0x00,0x00),array(0x20,0x00,0x00,0x00),array(0x40,0x00,0x00,0x00),array(0x80,0x00,0x00,0x00),array(0x1b,0x00,0x00,0x00),array(0x36,0x00,0x00,0x00));
	private static function urs($a,$b){$a&=0xffffffff;$b&=0x1f;if($a&0x80000000&&$b>0){$a=($a>>1)&0x7fffffff;$a=$a>>($b-1);}else{$a=($a>>$b);}return $a;}
}
