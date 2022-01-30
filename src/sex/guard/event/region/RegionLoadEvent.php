<?php

declare(strict_types=1);

namespace sex\guard\event\region;

use pocketmine\event\CancellableTrait;
use sex\guard\data\Region;
use sex\guard\event\RegionEvent;
use sex\guard\Manager;

class RegionLoadEvent extends RegionEvent{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region){
		parent::__construct($main, $region);
	}
}