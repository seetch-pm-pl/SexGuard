<?php

declare(strict_types=1);

namespace sex\guard\event\region;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use sex\guard\event\RegionEvent;

class RegionSaveEvent extends RegionEvent implements Cancellable{

	use CancellableTrait;
}