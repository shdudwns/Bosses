<?php

declare(strict_types=1);

namespace BajanVlogs\Bosses\command;

use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VanillaCommand;

class BossCommand extends VanillaCommand {
	public function __construct($name){
		parent::__construct($name,
			"A Description",
			"/plugin",
			[]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		// TODO: Do stuff like more bosses.
	}
}
