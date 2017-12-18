<?php

namespace BajanVlogs\Bosses\task;

use BajanVlogs\Bosses\Main;
use pocketmine\scheduler\PluginTask;

class UpdateEntityTask extends PluginTask{

    public function onRun($currentTicks){
        foreach(Main::getEntities() as $entity){
            if($entity->isCreated()) $entity->updateTick();
        }
    }

}
