<?php

namespace BajanVlogs\Bosses\entity;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\Server;

abstract class BaseEntity extends Creature{

    private $movement = true;
    private $wallcheck = true;

    protected $speed = 1;

    protected $stayTime = 0;
    protected $moveTime = 0;

    protected $created = false;

    /** @var Vector3|Entity */
    protected $baseTarget = null;
    /** @var Vector3|Entity */
    protected $mainTarget = null;

    protected $attacker = null;
    protected $atkTime = 0;

    protected $isFriendly = false;
    
    public function isFriendly(){
    	return $this->isFriendly;
    }
    public function setFriendly($bool){
    	$this->isFriendly = $bool;
    }

    public function __destruct(){}

    public function onUpdate($currentTick) : bool {
        return false;
    }

    public abstract function updateTick();

    public abstract function updateMove();

    public abstract function targetOption(Creature $creature, $distance);

    public function getSaveId(){
        $class = new \ReflectionClass(static::class);
        return $class->getShortName();
    }

    public function isCreated(){
        return $this->created;
    }

    public function isMovement(){
        return $this->movement;
    }

    public function setMovement($value){
        $this->movement = (bool) $value;
    }

    public function isWallCheck(){
        return $this->wallcheck;
    }

    public function setWallCheck($value){
        $this->wallcheck = (bool) $value;
    }

    public function getSpeed(){
        return $this->speed;
    }

    public function initEntity(){
        if(isset($this->namedtag->Movement)){
            $this->setMovement($this->namedtag["Movement"]);
        }
        Entity::initEntity();
    }

    public function saveNBT(){
        $this->namedtag->Movement = new ByteTag("Movement", $this->isMovement());
        parent::saveNBT();
    }

    public function spawnTo(Player $player){
        if(isset($this->hasSpawned[$player->getLoaderId()]) or !isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) return;

        $pk = new AddEntityPacket();
        $pk->entityUniqueId = $this->getID();
        $pk->entityRuntimeId = $this->getID();
        $pk->type = static::NETWORK_ID;
        $pk->position = new Vector3($this->getX(), $this->getY(), $this->getZ());
        $pk->motion = new Vector3(0,0,0);
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        $this->hasSpawned[$player->getLoaderId()] = $player;
    }

	public function updateMovement() {
		if (!$this->isClosed() && $this->getLevel() !== null) {
			parent::updateMovement();
		}
	}

    public function attack(EntityDamageEvent $source){
        if($this->attackTime > 0 or $this->noDamageTicks > 0){
            $lastCause = $this->getLastDamageCause();
            if($lastCause !== null) $source->setCancelled();
        }

        Entity::attack($source);

        if($source->isCancelled()) return;

        if($source instanceof EntityDamageByEntityEvent){
            $this->atkTime = 16;
            $this->stayTime = 0;
            $this->attacker = $source->getDamager();
        }

        $pk = new EntityEventPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->event = $this->isAlive() ? 2 : 3;
        Server::getInstance()->broadcastPacket($this->hasSpawned, $pk);
    }

    public function move(float $dx,float $dy,float $dz):bool{
        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;
        $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz));
        foreach($list as $bb){
            $dy = $bb->calculateYOffset($this->boundingBox, $dy);
        }
        $this->boundingBox->offset(0, $dy, 0);
        foreach($list as $bb){
            if(
                $this->isWallCheck()
                and $this->boundingBox->maxY > $bb->minY
                and $this->boundingBox->minY < $bb->maxY
                and $this->boundingBox->maxZ > $bb->minZ
                and $this->boundingBox->minZ < $bb->maxZ
            ){
                if($this->boundingBox->maxX + $dx >= $bb->minX and $this->boundingBox->maxX <= $bb->minX){
                    if(($x1 = $bb->minX - ($this->boundingBox->maxX + $dx)) < 0) $dx += $x1;
                }
                if($this->boundingBox->minX + $dx <= $bb->maxX and $this->boundingBox->minX >= $bb->maxX){
                    if(($x1 = $bb->maxX - ($this->boundingBox->minX + $dx)) > 0) $dx += $x1;
                }
            }
        }
        $this->boundingBox->offset($dx, 0, 0);
        foreach($list as $bb){
            if(
                $this->isWallCheck()
                and $this->boundingBox->maxY > $bb->minY
                and $this->boundingBox->minY < $bb->maxY
                and $this->boundingBox->maxX > $bb->minX
                and $this->boundingBox->minX < $bb->maxX
            ){
                if($this->boundingBox->maxZ + $dz >= $bb->minZ and $this->boundingBox->maxZ <= $bb->minZ){
                    if(($z1 = $bb->minZ - ($this->boundingBox->maxZ + $dz)) < 0) $dz += $z1;
                }
                if($this->boundingBox->minZ + $dz <= $bb->maxZ and $this->boundingBox->minZ >= $bb->maxZ){
                    if(($z1 = $bb->maxZ - ($this->boundingBox->minZ + $dz)) > 0) $dz += $z1;
                }
            }
        }
        $this->boundingBox->offset(0, 0, $dz);
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);

        $this->checkChunks();

        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        Timings::$entityMoveTimer->stopTiming();
        return true;
    }

    public function close(){
        $this->created = false;
        parent::close();
    }

}
