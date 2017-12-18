<?php

namespace BajanVlogs\Bosses;

use BajanVlogs\Bosses\command\BossCommand;
use BajanVlogs\Bosses\entity\BaseEntity;
use BajanVlogs\Bosses\entity\Zombie;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{
	/** @var Entity */
	public $bossCLONE;
	public static $spawned = false; // Anti lag.
	public static $healing = false;

	public $path;

	public static $data;
	public static $drops;
	public static $spawn;

	/** @var BaseEntity[] */
	private static $entities = [];
	private static $knownEntities = [];

	/** @var Config */
	public $config;

	/** @var array */
	public $bossRewards = [];
	public $bossCommands = [];

	public function onLoad(){
		$this->getLogger()->info("Loading...");
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"bossItemDropFormat: " => "DO NOT CHANGE THIS... I PUT IT HERE JUST SO YOU CAN READ IT.",
			"FORMAT: ItemID: " => "Diamond and some otheer loot",
			"FORMAT: ItemDamage: " => "The Loot's Damage (Good for Crate keys and Custom TNT)",
			"FORMAT: Count: " => "The loot's count",
			"zombieBossItems" => [
				[Item::DIAMOND, 0, 64],
				[Item::EMERALD, 0, 64],
			],
			"zombieBossCommands" => [
				"tell %PLAYER% Hello!",
			],
			"winTitle" => TextFormat::RED . "Boss",
			"winSubTitle" => TextFormat::GREEN . "Defeated",
		]);

		$this->bossRewards = $this->config->get("zombieBossItems", []);
		$this->bossCommands = $this->config->get("zombieBossCommands", []);
	}

	public function onEnable(){
		self::registerEntity(Zombie::class);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new task\UpdateEntityTask($this), 1);
    }

	public static function getEntities(Level $level = null){
		$entities = self::$entities;
		if($level != null){
			foreach($entities as $id => $entity){
				if($entity->getLevel() !== $level) unset($entities[$id]);
			}
		}
		return $entities;
	}

	public static function registerEntity($name){
		$class = new \ReflectionClass($name);
		if(is_a($name, BaseEntity::class, true) and !$class->isAbstract()){
			Entity::registerEntity($name, true);
			if($name::NETWORK_ID !== -1){
				self::$knownEntities[$name::NETWORK_ID] = $name;
			}
			self::$knownEntities[$class->getShortName()] = $name;
		}
	}

	public function EntitySpawnEvent(EntitySpawnEvent $ev){
		$entity = $ev->getEntity();
		if(is_a($entity, BaseEntity::class, true) && !$entity->isClosed()) self::$entities[$entity->getId()] = $entity;
	}

	public function EntityDespawnEvent(EntityDespawnEvent $ev){
		$entity = $ev->getEntity();
		if($entity instanceof BaseEntity) unset(self::$entities[$entity->getId()]);
	}
}
