<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;
use sex\guard\Manager;

abstract class Argument{

	public const NAME = '';

	public function __construct(private Manager $plugin){
	}

	public function getName() : string{
		return static::NAME;
	}

	public function getPlugin() : Manager{
		return $this->plugin;
	}

	abstract public function execute(Player $sender, array $args) : bool;
}