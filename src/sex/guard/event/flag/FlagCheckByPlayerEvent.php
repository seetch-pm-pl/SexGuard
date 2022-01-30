<?php

declare(strict_types=1);

namespace sex\guard\event\flag;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use sex\guard\data\Region;
use sex\guard\Manager;

class FlagCheckByPlayerEvent extends FlagCheckEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region, string $flag, private Player $player, private ?Block $block = null){
		parent::__construct($main, $region, $flag);
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getBlock() : Block{
		return $this->block;
	}
}