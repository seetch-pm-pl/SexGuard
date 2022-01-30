<?php

declare(strict_types=1);

namespace sex\guard\event;

use pocketmine\event\plugin\PluginEvent;
use sex\guard\data\Region;
use sex\guard\Manager;

class RegionEvent extends PluginEvent{

	public function __construct(Manager $main, private Region $region){
		parent::__construct($main);
	}

	public function getRegion() : Region{
		return $this->region;
	}
}