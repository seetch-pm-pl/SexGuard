<?php

declare(strict_types=1);

namespace sex\guard\data;

use pocketmine\Server;
use pocketmine\world\World;
use sex\guard\event\region\RegionFlagChangeEvent;
use sex\guard\event\region\RegionLoadEvent;
use sex\guard\event\region\RegionMemberChangeEvent;
use sex\guard\event\region\RegionOwnerChangeEvent;
use sex\guard\event\region\RegionSaveEvent;
use sex\guard\Manager;

class Region{

	public function __construct(private string $name, private array $property = []){
		$event = new RegionLoadEvent(Manager::getInstance(), $this);
		$event->call();
	}

	public function addMember(string $nick) : void{
		$event = new RegionMemberChangeEvent(Manager::getInstance(), $this, $nick, RegionMemberChangeEvent::TYPE_ADD);
		$event->call();

		if($event->isCancelled()){
			return;
		}

		$nick = strtolower($nick);

		if(in_array($nick, $this->property['member'])){
			return;
		}

		$this->property['member'][] = $nick;

		$this->save();
	}

	public function removeMember(string $nick) : void{
		$event = new RegionMemberChangeEvent(Manager::getInstance(), $this, $nick, RegionMemberChangeEvent::TYPE_REMOVE);
		$event->call();

		if($event->isCancelled()){
			return;
		}

		$key = array_search(strtolower($nick), $this->property['member']);

		unset($this->property['member'][$key]);
		$this->save();
	}


	public function setOwner(string $nick) : void{
		$event = new RegionOwnerChangeEvent(Manager::getInstance(), $this, $this->property['owner'], $nick);
		$event->call();

		if($event->isCancelled()){
			return;
		}

		$this->property['owner'] = strtolower($nick);

		$this->save();
	}

	public function setFlag(string $flag, bool $value) : void{
		$event = new RegionFlagChangeEvent(Manager::getInstance(), $this, $flag, $value);
		$event->call();

		if($event->isCancelled()){
			return;
		}

		$flag = strtolower($flag);

		if(isset($this->property['flag'][$flag])){
			$this->property['flag'][$flag] = $value;

			$this->save();
		}
	}

	public function getRegionName() : string{
		return strtolower($this->name);
	}

	public function getOwner() : string{
		return strtolower($this->property['owner']);
	}

	/**
	 * @return string[]
	 */
	public function getMemberList() : array{
		return $this->property['member'];
	}

	public function getMin(string $coordinate) : int{
		return $this->property['min'][strtolower($coordinate)] ?? 0;
	}

	public function getMax(string $coordinate) : int{
		return $this->property['max'][strtolower($coordinate)] ?? 0;
	}

	public function getLevel() : ?World{
		return Server::getInstance()->getWorldManager()->getWorldByName($this->property['level']);
	}

	public function getLevelName() : string{
		return $this->property['level'] ?? 'undefined';
	}

	public function getFlagValue(string $flag) : bool{
		$flag = strtolower($flag);

		return $this->property['flag'][$flag] ?? Manager::DEFAULT_FLAG[$flag] ?? false;
	}

	private function save() : void{
		$event = new RegionSaveEvent(Manager::getInstance(), $this);
		$event->call();

		if($event->isCancelled()){
			return;
		}

		Manager::getInstance()->saveRegion($this);
	}

	public function toData() : array{
		return $this->property;
	}
}