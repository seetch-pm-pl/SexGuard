<?php

declare(strict_types=1);

namespace sex\guard\event\flag;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use sex\guard\data\Region;
use sex\guard\Manager;

class FlagIgnoreEvent extends FlagCheckEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region, string $flag, private Player $player){
		parent::__construct($main, $region, $flag);
	}

	public function getPlayer() : Player{
		return $this->player;
	}
}