<?php

declare(strict_types=1);

namespace BajanVlogs\Bosses;

use BajanVlogs\Bosses\task\HealTask;
use BajanVlogs\level\particle\SpellParticle;
use CortexPE\Player;
use CortexPE\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\SpawnEgg;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {
	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	public function onTap(PlayerInteractEvent $ev){
		if($ev->getItem()->getId() == Item::SPAWN_EGG){
			switch($ev->getItem()->getDamage()){
				case 110:
					if(!Main::$spawned){
						$nbt = Entity::createBaseNBT(new Vector3($ev->getBlock()->getX(), $ev->getBlock()->getY(), $ev->getBlock()->getZ()));
						$nbt["BOSStype"] = new StringTag("BOSStype", "Giant");
						$entity = Entity::createEntity("Zombie", $ev->getPlayer()->getLevel(), $nbt);
						$entity->setScale(7);
						$entity->setMaxHealth(3000);
						$entity->setHealth(3000);
						$entity->setNameTag("Giant\n" . TextFormat::RED . "❤ " . TextFormat::YELLOW . $entity->getHealth());
						$entity->setNameTagAlwaysVisible(true);
						$entity->spawnToAll();
						Main::$spawned = true;
						$this->plugin->bossCLONE = clone $entity;
						Server::getInstance()->getScheduler()->scheduleDelayedTask(new task\GiveHealthTask($this->plugin, $entity), 10);
					} else {
						$ev->getPlayer()->sendMessage(TextFormat::RED . "You can only summon ONE boss at a time.");
					}
					break;
			}
		}
	}

	public function onDamage(EntityDamageEvent $ev){
		if(isset($ev->getEntity()->namedtag["BOSStype"]) && $ev->getEntity()->namedtag["BOSStype"] == "Giant" && Main::$spawned){
			$ent = $ev->getEntity();
			if($ev->getDamage() < $ev->getEntity()->getHealth()){
				if($ev->getEntity()->getHealth() <= $ent->getMaxHealth() / 2 && !Main::$healing){
					Main::$healing = true;
					Server::getInstance()->getScheduler()->scheduleRepeatingTask(new task\HealTask($this->plugin, $ent), 5 * 20);
				}
				$ent->setNameTag("Giant\n" . TextFormat::RED . "❤ " . TextFormat::YELLOW . $ent->getHealth());
				if(mt_rand(1,10) == 2){
					$this->revenge($ent);
				}
			} else {
				Main::$spawned = false;
				Main::$healing = false;
				foreach($this->plugin->bossRewards as $a){
					$ent->getLevel()->dropItem(new Vector3($ent->getX(), $ent->getY(), $ent->getZ()),Item::get($a[0], $a[1], $a[2]));
				}
				foreach($ent->getLevel()->getNearbyEntities($ent->getBoundingBox()->expand(8,8,8)) as $e){
					if($e instanceof \pocketmine\Player){
						$e->addTitle(
							(string) $this->plugin->config->get("winTitle"),
							(string) $this->plugin->config->get("winSubTitle")
						);
						foreach($this->plugin->bossCommands as $cmd){
							$cmd = str_ireplace("%PLAYER%", $e->getName(), $cmd);
							Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $cmd);
						}
					}
				}
			}
		}
	}

	private function revenge(Entity $e){
		$amount = mt_rand(5,10);
		$radius = 8;
		$diff = intval(360 / $amount);

		$eff = Effect::getEffect(Effect::POISON);

		if($eff === null){
			$color = [46, 82, 153]; // Default to Blue
		} else {
			$color = $eff->getColor();
		}

		for($theta = 0; $theta <= 360; $theta += $diff){
			$offsetX = $radius * sin($theta);
			$offsetZ = $radius * cos($theta);

			$e->getLevel()->addParticle(new SpellParticle($e->getPosition()->add($offsetX, 0, $offsetZ), $color[0], $color[1], $color[2]));
			$this->spawnZombie($e->getPosition()->add($offsetX, 0, $offsetZ), mt_rand(13,20));
		}
	}

	private function spawnZombie(Vector3 $pos, int $health){
		$nbt = Entity::createBaseNBT($pos);
		$entity = Entity::createEntity("Zombie", $this->plugin->bossCLONE->getLevel(), $nbt);
		$entity->setMaxHealth($health);
		$entity->setHealth($health);
		$entity->spawnToAll();
	}
}
