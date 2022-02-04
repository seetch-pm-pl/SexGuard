<?php

declare(strict_types=1);

namespace sex\guard\utils;

use BadMethodCallException;
use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class CompoundTile extends Spawnable{

	private CompoundTag $data;

	public function __construct(World $world, Vector3 $pos, CompoundTag $data){
		$this->data = $data;
		parent::__construct($world, $pos);
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		foreach($this->data->getValue() as $name => $tag){
			$nbt->setTag($name, $tag);
		}
	}

	public function readSaveData(CompoundTag $nbt) : void{
		throw new BadMethodCallException("CompoundTiles should never be created through TileFactory");
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		throw new BadMethodCallException("CompoundTiles should never be saved");
	}
}