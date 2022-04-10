<?php

declare(strict_types=1);

namespace sex\guard\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;

class HighlightingManager{

	/** @var array<string, array<int, ReferencedPosition>> */
	private static array $staticDataHolders = [];
	/** @var CompoundTag[] */
	private static array $staticData = [];

	private static int $id = 1;

	public static function highlightStaticCube(string $player, string $world, Vector3 $pos1, Vector3 $pos2, Vector3 $dataHolder) : int{
		if(!isset(self::$staticDataHolders[$player])){
			self::$staticDataHolders[$player] = [];
		}

		self::$staticDataHolders[$player][self::$id] = new ReferencedPosition($dataHolder->floor(), $world);
		self::$staticData[self::$id] = CompoundTag::create()
			->setString("structureName", "selection")
			->setString("dataField", "")
			->setInt("xStructureOffset", $pos1->getFloorX() - $dataHolder->getFloorX())
			->setInt("yStructureOffset", $pos1->getFloorY() - $dataHolder->getFloorY())
			->setInt("zStructureOffset", $pos1->getFloorZ() - $dataHolder->getFloorZ())
			->setInt("xStructureSize", $pos2->getFloorX() - $pos1->getFloorX() + 1)
			->setInt("yStructureSize", $pos2->getFloorY() - $pos1->getFloorY() + 1)
			->setInt("zStructureSize", $pos2->getFloorZ() - $pos1->getFloorZ() + 1)
			->setInt("data", 5)
			->setByte("rotation", 0)
			->setByte("mirror", 0)
			->setFloat("integrity", 100.0)
			->setLong("seed", 0)
			->setByte("ignoreEntities", 1)
			->setByte("includePlayers", 0)
			->setByte("removeBlocks", 0)
			->setByte("showBoundingBox", 1)
			->setByte("isMovable", 1)
			->setByte("isPowered", 0);

		self::sendStaticHolder($player, self::$id);

		return self::$id++;
	}

	private static function sendStaticHolder(string $player, int $id) : void{
		if(($p = Server::getInstance()->getPlayerExact($player)) instanceof Player){
			PacketUtils::sendFakeBlock(self::$staticDataHolders[$player][$id], self::$staticDataHolders[$player][$id]->getWorld(), $p, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS, self::$staticData[$id]);
		}
	}

	private static function removeStaticHolder(string $player, int $id) : void{
		if(($p = Server::getInstance()->getPlayerExact($player)) instanceof Player){
			//Minecraft doesn't delete BlockData if the original Block shouldn't have some or whole chunks get sent
			PacketUtils::sendFakeBlock(self::$staticDataHolders[$player][$id], $p->getWorld(), $p, BlockLegacyIds::STRUCTURE_BLOCK << Block::INTERNAL_METADATA_BITS);
			PacketUtils::resendBlock(self::$staticDataHolders[$player][$id], $p->getWorld(), $p);
		}
	}

	public static function clear(string $player, int $id) : void{
		if(isset(self::$staticDataHolders[$player][$id])){
			self::removeStaticHolder($player, $id);
			unset(self::$staticDataHolders[$player][$id], self::$staticData[$id]);
		}
	}

	public static function resendAll(string $player) : void{
		if(isset(self::$staticDataHolders[$player]) && (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player)){
			foreach(self::$staticDataHolders[$player] as $id => $pos){
				if($pos->getWorldName() === $p->getWorld()->getFolderName()){
					self::sendStaticHolder($player, $id);
				}else{
					self::removeStaticHolder($player, $id);
				}
			}
		}
	}
}