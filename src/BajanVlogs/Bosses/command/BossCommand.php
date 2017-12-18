<?php

namespace BajanVlogs\Bosses\command;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Command\Command;
use pocketmine\Command\CommandSender;
use pocketmine\entity\Effect;

class BossCommand extends PluginBase{
	
	public function onEnable(){
		$this->getServer()->getLogger()->info("Bosses enabled!");
	}
	
	public function onDisable(){
		$this->getServer()->getLogger()->info("Bosses disabled!");
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		
		switch($cmd->getName()){
			
			case "boss":
				if($sender instanceof Player){
					$sender->addTitle("§c§lBosses§b§l", "§a§lBy BajanVlogs", 20, 40, 20);
				}
			break;
		}
		return true;
	}

}
