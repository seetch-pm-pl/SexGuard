<?php

declare(strict_types=1);

namespace sex\guard;

use Exception;
use JsonException;
use pocketmine\block\tile\TileFactory;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\VersionString;
use pocketmine\world\Position;
use pocketmine\world\World;
use sex\guard\command\GuardCommand;
use sex\guard\data\Region;
use sex\guard\event\region\RegionCreateEvent;
use sex\guard\event\region\RegionRemoveEvent;
use sex\guard\listener\block\BlockListener;
use sex\guard\listener\entity\EntityListener;
use sex\guard\listener\player\PlayerListener;
use sex\guard\task\CheckUpdateTask;
use sex\guard\utils\CompoundTile;
use sex\guard\utils\HighlightingManager;

class Manager extends PluginBase{

	public const DEFAULT_FLAG = [
		'interact' => true,
		'teleport' => true,
		'combust' => false,
		'explode' => false,
		'change' => false,
		'bucket' => false,
		'damage' => true,
		'chest' => false,
		'frame' => false,
		'place' => false,
		'break' => false,
		'sleep' => false,
		'decay' => true,
		'drop' => true,
		'chat' => true,
		'pvp' => false,
		'mob' => true
	];

	private static ?Manager $instance = null;

	public static function getInstance() : Manager{
		return self::$instance;
	}

	private Config $message, $region, $config, $group;

	/** @var Region[][] */
	private array $data = [];

	/** @var Position[] */
	public array $position = [];
	public array $structure = [];

	/** @var PluginBase[] */
	public array $extension = [];

	public Config $sign;

	protected function onLoad() : void{
		foreach($this->getResources() as $resource){
			$this->saveResource($resource->getFilename());
		}

		$this->loadInstance();
	}

	protected function onEnable() : void{
		$this->initProvider();

		if($this->getDescription()->getVersion() !== $this->getValue('config-version', 'config')){
			$this->getLogger()->warning("An outdated config was provided attempting to generate a new one...");
			if(!rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.old.yml")){
				$this->getLogger()->critical("An unknown error occurred while attempting to generate the new config");
				$this->getServer()->getPluginManager()->disablePlugin($this);
			}
			$this->reloadConfig();

			try{
				$this->getConfig()->set("config-version", $this->getDescription()->getVersion());
				$this->getConfig()->save();
			}catch(JsonException $e){
				$this->getLogger()->critical("An error occurred while attempting to generate the new config, " . $e->getMessage());
			}
		}

		TileFactory::getInstance()->register(CompoundTile::class);

		$this->initListener();
		$this->initCommand();
		$this->initExtension();

		try{
			$this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask);
		}catch(Exception $ex){
			$this->getLogger()->warning($ex->getMessage());
		}
	}

	protected function onDisable() : void{
		$this->region->save();
		$this->sign->save();
	}

	/**
	 * @param Position $min
	 * @param Position $max
	 *
	 * @return Region[]
	 */
	public function getOverride(Position $min, Position $max) : array{
		$level = $min->getWorld()->getFolderName();

		if($level !== $max->getWorld()->getFolderName()){
			return [];
		}

		if(!isset($this->data[$level])){
			return [];
		}

		$data = $this->data[$level];

		if(count($data) == 0){
			unset($data);
			return [];
		}

		$arr = [];

		foreach($data as $rg){
			if(
				$rg->getMin('x') <= $max->getX() and $min->getX() <= $rg->getMax('x') and
				$rg->getMin('y') <= $max->getY() and $min->getY() <= $rg->getMax('y') and
				$rg->getMin('z') <= $max->getZ() and $min->getZ() <= $rg->getMax('z')
			){
				$arr[] = $rg;
			}
		}

		unset($data);
		return $arr;
	}

	public function getRegion(Position $pos) : ?Region{
		$level = $pos->getWorld()->getFolderName();

		if(!isset($this->data[$level])){
			return null;
		}

		$data = $this->data[$level];

		if(count($data) == 0){
			unset($data);
			return null;
		}

		$x = $pos->getFloorX();
		$y = $pos->getFloorY();
		$z = $pos->getFloorZ();

		end($data);

		for($i = key($data); $i >= 0; $i--) // sqlite sucks.
		{
			if(!isset($data[$i])){
				continue;
			}

			$rg = $data[$i];

			if(
				$rg->getMin('x') <= $x and $x <= $rg->getMax('x') and
				$rg->getMin('y') <= $y and $y <= $rg->getMax('y') and
				$rg->getMin('z') <= $z and $z <= $rg->getMax('z')
			){
				unset($data);
				return $rg;
			}
		}

		unset($data);
		return null;
	}

	public function getRegionByName(string $name) : ?Region{
		$name = strtolower($name);

		foreach($this->getServer()->getWorldManager()->getWorlds() as $level){
			$level = $level->getFolderName();

			if(!isset($this->data[$level])){
				continue;
			}

			$data = $this->data[$level];

			if(count($data) == 0){
				unset($data);
				continue;
			}

			foreach($data as $rg){
				if($rg->getRegionName() !== $name){
					continue;
				}

				unset($data);
				return $rg;
			}
		}

		unset($data);
		return null;
	}

	public function createRegion(string $nick, string $name, Position $min, Position $max) : void{
		$level = $min->getWorld()->getFolderName();
		$nick = strtolower($nick);
		$name = strtolower($name);

		if($this->getValue('full-height', 'config') === true){
			$min_y = 0;
			$max_y = 256;
		}

		$data = [
			'owner' => $nick,
			'member' => [],
			'level' => $level,
			'min' => [
				'x' => $min->getX(),
				'y' => $min_y ?? $min->getY(),
				'z' => $min->getZ()
			],
			'max' => [
				'x' => $max->getX(),
				'y' => $max_y ?? $max->getY(),
				'z' => $max->getZ()
			],
			'flag' => $this->getValue('default-flags', 'config')
		];

		$region = new Region($name, $data);
		$event = new RegionCreateEvent($this, $region);

		$event->call();

		if($event->isCancelled()){
			return;
		}

		$this->data[$level][] = $region;

		unset($this->position[0][$nick]);
		unset($this->position[1][$nick]);
		$this->saveRegion($region);
	}

	public function removeRegion(string $name) : bool{
		$name = strtolower($name);

		foreach($this->getServer()->getWorldManager()->getWorlds() as $level){
			$level = $level->getFolderName();

			if(!isset($this->data[$level])){
				continue;
			}

			$data = $this->data[$level];

			if(count($data) == 0){
				unset($data);
				continue;
			}

			foreach($data as $key => $rg){
				if($rg->getRegionName() !== $name){
					continue;
				}

				$event = new RegionRemoveEvent($this, $rg);

				$event->call();

				if($event->isCancelled()){
					return false;
				}

				unset($this->data[$level][$key]);

				$this->data[$level] = array_values($this->data[$level]);

				$this->region->remove($name);
				$this->region->save();

				unset($data);
				return true;
			}
		}

		unset($data);
		return false;
	}

	/**
	 * @param string $nick
	 * @param bool   $include_member
	 *
	 * @return Region[]
	 */
	public function getRegionList(string $nick, bool $include_member = false) : array{
		$nick = strtolower($nick);
		$nick = strtolower($nick);
		$arr = [];

		foreach($this->getServer()->getWorldManager()->getWorlds() as $level){
			$level = $level->getFolderName();

			if(!isset($this->data[$level])){
				continue;
			}

			$data = $this->data[$level];

			if(count($data) == 0){
				unset($data);
				continue;
			}

			foreach($data as $rg){
				if($rg->getOwner() == $nick){
					$arr[] = $rg;

					continue;
				}

				if(!$include_member){
					continue;
				}

				if(!in_array($nick, $rg->getMemberList())){
					continue;
				}

				$arr[] = $rg;
			}
		}

		unset($data);
		return $arr;
	}

	public function getValue(string $key, string $type = 'message') : string|int|array|bool|null{
		$type = strtolower($type);
		$key = mb_strtolower($key);
		$error = "Configuration error: пункт '$key' не найден в $type.yml. Пожалуйста, удалите старый конфиг (/plugins/sexGuard/$type.yml) и перезагрузите сервер.";

		if($type == 'config'){
			$value = $this->config->get($key);
		}elseif($type == 'group'){
			$value = $this->group->get($key);
			$value = !$value ? $this->group->get('default') : $value;

			if(!$value){
				$this->getLogger()->error($error);

				$value = [
					'max-size' => 5000,
					'max-count' => 4,
					'ignored-flags' => [],
					'ignored-regions' => []
				];
			}
		}else{
			$value = $this->message->get($key);

			if($value === false){
				$this->getLogger()->error($error);

				$value = "§l§c- §fGUARD §c- Произошла внутренняя ошибка§r";
			}
		}

		return $value;
	}

	public function saveRegion(Region $region) : void{
		$this->region->set($region->getRegionName(), $region->toData());
		$this->region->save();
	}

	public function calculateSize(Position $pos1, Position $pos2) : int{
		$x = [min($pos1->getX(), $pos2->getX()), max($pos1->getX(), $pos2->getX())];
		$y = [min($pos1->getY(), $pos2->getY()), max($pos1->getY(), $pos2->getY())];
		$z = [min($pos1->getZ(), $pos2->getZ()), max($pos1->getZ(), $pos2->getZ())];

		if($this->getValue('full-height', 'config') === true){
			$y = [0, 1];
		}

		return ($x[1] - $x[0]) * ($y[1] - $y[0]) * ($z[1] - $z[0]);
	}

	/**
	 * @return string[]
	 */
	public function getAllowedFlag() : array{
		$list = array_map('strtolower', $this->getValue('allowed-flags', 'config'));

		foreach($list as $flag){
			if(isset(self::DEFAULT_FLAG[$flag])){
				continue;
			}

			unset($list[$flag]);
		}

		return $list;
	}

	public function sendWarning(Player $player, string $message){
		if(empty($message)){
			return;
		}

		switch($this->getValue('notification-type', 'config')){
			case 0:
				$player->sendPopup($message);
				break;
			case 1:
				$player->sendMessage($message);
				break;
			default:
				$player->sendTip($message);
				break;
		}
	}

	/**
	 * @param Player $player
	 *
	 * @return int[]
	 */
	public function getGroupValue(Player $player) : array{
		if(isset($this->extension['pureperms'])){
			$group = $this->extension['pureperms']->getUserDataMgr()->getGroup($player)->getName();
		}elseif(isset($this->extension['universalgroup'])){
			$group = $this->extension['universalgroup']->getGroup($player->getName())->getId();
		}elseif(isset($this->extension['sexgroup'])){
			$group = $this->extension['sexgroup']->getPlayerGroup($player->getName())->getId();
		}

		return $this->getValue($group ?? 'default', 'group');
	}

	private function loadInstance() : void{
		self::$instance = $this;
	}

	private function initProvider(){
		$folder = $this->getDataFolder();

		$this->group = new Config($folder . 'group.yml');
		$this->config = new Config($folder . 'config.yml');
		$this->message = new Config($folder . 'messages-' . $this->config->get('language') . '.yml');

		$this->sign = new Config($folder . 'sign.json');
		$this->region = new Config($folder . 'region.json');

		$this->sign->reload();
		$this->region->reload();

		foreach($this->region->getAll() as $name => $data){
			/**
			 * @todo check data on load.
			 */
			$rg = new Region($name, $data);
			$level = $rg->getLevelName();

			$this->data[$level][] = $rg;
		}
	}

	private function initListener() : void{
		(new BlockListener($this))->register();
		(new EntityListener($this))->register();
		(new PlayerListener($this))->register();
	}

	private function initCommand() : void{
		$command = new GuardCommand($this);

		$map = $this->getServer()->getCommandMap();
		$replace = $map->getCommand($command->getName());

		if(isset($replace)){
			$replace->setLabel('');
			$replace->unregister($map);
		}

		$map->register($this->getName(), $command);
	}

	private function initExtension() : void{
		$list = [
			'PurePerms',
			'EconomyAPI',
			'UniversalGroup',
			'UniversalMoney',
			'SexGroup',
			'Econ'
		];

		foreach($list as $extension){
			if($this->getValue(strtolower($extension) . '-support', 'config') === true){
				$plugin = $this->getServer()->getPluginManager()->getPlugin($extension);

				if(isset($plugin)){
					$this->extension[strtolower($extension)] = $plugin;
				}
			}
		}
	}

	public function updateSelection(Player $player, Position $origPos1, Position $origPos2) : void{
		if($this->getValue('show-selection', 'config') == true){
			$minX = min($origPos1->getX(), $origPos2->getX());
			$maxX = max($origPos1->getX(), $origPos2->getX());
			$minY = max(min($origPos1->getY(), $origPos2->getY()), World::Y_MIN);
			$maxY = min(max($origPos1->getY(), $origPos2->getY()), World::Y_MAX - 1);
			$minZ = min($origPos1->getZ(), $origPos2->getZ());
			$maxZ = max($origPos1->getZ(), $origPos2->getZ());

			$pos1 = new Vector3($minX, $minY, $minZ);
			$pos2 = new Vector3($maxX, $maxY, $maxZ);

			if(isset($this->structure[$player->getName()])){
				HighlightingManager::clear($player->getName(), $this->structure[$player->getName()]);
			}

			$this->structure[$player->getName()] = HighlightingManager::highlightStaticCube($player->getName(), $player->getWorld()->getFolderName(), $pos1, $pos2, new Vector3(floor(($pos2->getX() + $pos1->getX()) / 2), World::Y_MIN, floor(($pos2->getZ() + $pos1->getZ()) / 2)));
		}
	}

	public function clearSelection(Player $player) : void{
		if(isset($this->structure[$player->getName()])){
			HighlightingManager::clear($player->getName(), $this->structure[$player->getName()]);
			unset($this->structure[$player->getName()]);
		}
	}

	public function compareVersion(bool $success, ?VersionString $new = null, string $url = "") : void{
		if($success){
			$current = new VersionString($this->getDescription()->getVersion());
			switch($current->compare($new)){
				case -1:
					$this->getLogger()->warning("You are using the development version, there may be many fatal errors.");
					break;

				case 0:
					$this->getLogger()->notice("You are using a stable version, no update is required.");
					break;

				case 1:
					$messages = [
						"Your version of {$current->getFullVersion()} is out of date. Version {$new->getBaseVersion()} was released!",
						"Download: {$url}"
					];
					foreach($messages as $message) $this->getLogger()->notice($message);
			}
		}else{
			$this->getLogger()->notice("Because there was a problem with the network, we could not confirm whether there was an update.");
		}
	}
}