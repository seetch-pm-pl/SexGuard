<?php

declare(strict_types=1);

namespace sex\guard\event\region;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use sex\guard\data\Region;
use sex\guard\event\RegionEvent;
use sex\guard\Manager;

class RegionMemberChangeEvent extends RegionEvent implements Cancellable{

	use CancellableTrait;

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;

	private int $type;

	public function __construct(Manager $main, Region $region, private string $member, int $type){
		parent::__construct($main, $region);

		$this->member = strtolower($member);
		$this->type = $type == self::TYPE_ADD ? self::TYPE_ADD : self::TYPE_REMOVE;
	}

	public function getMember() : string{
		return strtolower($this->member);
	}

	public function getType() : int{
		return $this->type;
	}
}