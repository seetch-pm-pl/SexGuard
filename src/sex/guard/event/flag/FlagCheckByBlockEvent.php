<?php

declare(strict_types=1);

namespace sex\guard\event\flag;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use sex\guard\data\Region;
use sex\guard\Manager;

class FlagCheckByBlockEvent extends FlagCheckEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region, string $flag, private Block $block, private ?Player $player = null){
		parent::__construct($main, $region, $flag);
	}

	public function getBlock() : Block{
		return $this->block;
	}

	public function getPlayer() : Player{
		return $this->player;
	}
}