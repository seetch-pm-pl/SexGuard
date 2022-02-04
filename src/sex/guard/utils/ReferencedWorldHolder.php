<?php

declare(strict_types=1);

namespace sex\guard\utils;

use pocketmine\Server;
use pocketmine\world\World;
use UnexpectedValueException;

trait ReferencedWorldHolder{

	protected string $world;

	public function getWorldName() : string{
		return $this->world;
	}

	public function getWorld() : World{
		$world = Server::getInstance()->getWorldManager()->getWorldByName($this->getWorldName());
		if($world === null){
			throw new UnexpectedValueException("World " . $this->getWorldName() . " was deleted, unloaded or renamed");
		}
		return $world;
	}
}