<?php

namespace BajanVlogs\Bosses\task;

use BajanVlogs\Bosses\Main;
use pocketmine\item\Item;
use pocketmine\tile\Tile;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\tile\Chest;

class GiveHealthTask extends PluginTask {
	/** @var Main */
	public $owner;

	/** @var Entity */
	public $entity;

	public function __construct(Main $owner, Entity $entity){
		$this->owner = $owner;
		$this->entity = $entity;
	}

	public function onRun(int $currentTick){
		$this->entity->setMaxHealth(500);
		$this->entity->setHealth(500);
	}
}
