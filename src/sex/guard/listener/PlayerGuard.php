<?php namespace sex\guard\listener;


/**
 *  _    _       _                          _  ____
 * | |  | |_ __ (_)_    _____ _ ______ __ _| |/ ___\_ _______      __
 * | |  | | '_ \| | \  / / _ \ '_/ __// _' | | /   | '_/ _ \ \    / /
 * | |__| | | | | |\ \/ /  __/ | \__ \ (_) | | \___| ||  __/\ \/\/ /
 *  \____/|_| |_|_| \__/ \___|_| /___/\__,_|_|\____/_| \___/ \_/\_/
 *
 * @author sex_KAMAZ
 * @link   http://universalcrew.ru
 *
 */

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use sex\guard\Manager;
use sex\guard\event\flag\FlagIgnoreEvent;
use sex\guard\event\flag\FlagCheckByPlayerEvent;

use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;


/**
 * @todo good listener should listen only one event.
 */
class PlayerGuard implements Listener
{
	/**
	 * @var Manager
	 */
	private $api;


	/**
	 * @param Manager $api
	 */
	function __construct( Manager $api )
	{
		$this->api = $api;
	}


	/**
	 *  _ _      _
	 * | (_)____| |_____ _ __   ___ _ __
	 * | | / __/   _/ _ \ '_ \ / _ \ '_/
	 * | | \__ \| ||  __/ | | |  __/ |
	 * |_|_|___/|___\___|_| |_|\___|_|
	 *
	 *
	 * @param PlayerQuitEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled FALSE
	 */
	function onQuit( PlayerQuitEvent $event )
	{
		$nick = strtolower($event->getPlayer()->getName());
		$api  = $this->api;
		
		if( isset($api->position[0][$nick]) )
		{
			unset($api->position[0][$nick]);
		}
		
		if( isset($api->position[1][$nick]) )
		{
			unset($api->position[1][$nick]);
		}
	}


	/**
	 * @internal chat flag.
	 *
	 * @param    PlayerChatEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled TRUE
	 */
	function onChat( PlayerChatEvent $event )
	{
		$player = $event->getPlayer();
		
		if( $this->isFlagDenied($player, 'chat') )
		{
			$event->cancel();
		}
	}


	/**
	 * @internal interact flag.
	 *
	 * @param    PlayerInteractEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled FALSE
	 */
	function onTouch( PlayerInteractEvent $event )
	{
		if( $event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK )
		{
			return; // thx Yexeed.
		}

		if( $event->isCancelled() )
		{
			return;
		}
		
		$player = $event->getPlayer();
		$block  = $event->getBlock();
		$nick   = strtolower($player->getName());
		$api    = $this->api;
		
		if( $block->getId() == BlockLegacyIds::SIGN_POST or $block->getId() == BlockLegacyIds::WALL_SIGN )
		{
			if( count($api->sign->getAll()) == 0 or $api->getValue('allow_sell', 'config') === FALSE )
			{
				return;
			}
			
			foreach( $api->sign->getAll() as $name => $data )
			{
				$pos = new Vector3($data['pos'][0], $data['pos'][1], $data['pos'][2]);
				$lvl = $data['level'];
				
				if( $block->getPosition()->equals($pos) and $block->getPosition()->getWorld()->getFolderName() == $lvl )
				{
					if( isset($api->extension['economyapi']) )
					{
						$economy = $api->extension['economyapi'];
						$money   = $economy->myMoney($nick);
					}

					if( isset($api->extension['universalmoney']) )
					{
						$economy = $api->extension['universalmoney'];
						$money   = $economy->getMoney($nick);
					}
					
					if( !isset($economy) )
					{
						return;
					}
					
					$region = $api->getRegion($block->getPosition());
					
					if( !isset($region) )
					{
						return;
					}
					
					if( $nick == $region->getOwner() )
					{
						$api->sendWarning($player, $api->getValue('player_already_owner'));
						return;
					}
					
					$val = $api->getGroupValue($player);
					
					if( count($api->getRegionList($nick)) > $val['max_count'] )
					{
						$api->sendWarning($player, str_replace('{max_count}', $val['max_count'], $api->getValue('rg_overcount')));
						return;
					}

					$price = intval($data['price']);

					if( $money < $price )
					{
						$api->sendWarning($player, str_replace('{price}', $price, $api->getValue('player_have_not_money')));
						return;
					}
					
					$economy->reduceMoney($nick, $price);
					$economy->addMoney($region->getOwner(), $price);

					$region->setOwner($nick);
					$block->getPosition()->getWorld()->setBlock($pos, VanillaBlocks::AIR());

					$api->sign->remove($name);
					$api->sign->save(TRUE);
					
					$api->sendWarning($player, str_replace('{region}', $region->getRegionName(), $api->getValue('player_buy_rg')));
					break;
				}
			}
			
			return;
		}
		
		$item = $event->getItem();
		
		if( $item->getId() == ItemIds::STICK )
		{
			$event->cancel();

			$region = $api->getRegion($block->getPosition());
			
			if( !isset($region) )
			{
				$api->sendWarning($player, $api->getValue('rg_not_exist'));
				return;
			}
			
			$msg = str_replace('{region}', $region->getRegionName(), $api->getValue('rg_info'));
			$msg = str_replace('{owner}',  $region->getOwner(), $msg);
			$msg = str_replace('{member}', implode(' ', $region->getMemberList()), $msg);
			
			$api->sendWarning($player, $msg);
			return;
		}

		if( $item->getId() == ItemIds::WOODEN_AXE )
		{
			$event->cancel();

			$region = $api->getRegion($block->getPosition());

			if( $region !== NULL and !$player->hasPermission('sexguard.all') )
			{
				if( $region->getOwner() != $nick )
				{
					$api->sendWarning($player, $api->getValue('rg_override'));
					return;
				}
			}
			
			if( !isset($api->position[0][$nick]) )
			{
				$api->position[0][$nick] = $block->getPosition();
				
				$api->sendWarning($player, $api->getValue('pos_1_set'));
				return;
			}
			
			if( !isset($api->position[1][$nick]) )
			{
				if( $api->position[0][$nick]->getWorld()->getFolderName() != $block->getPosition()->getWorld()->getFolderName() )
				{
					unset($api->position[0][$nick]);
					$api->sendWarning($player, $api->getValue('pos_another_world'));
					return;
				}
				
				$val  = $api->getGroupValue($player);
				$size = $api->calculateSize($api->position[0][$nick], $block->getPosition());
				
				if( $size > $val['max_size'] and !$player->hasPermission('sexguard.all') )
				{
					$msg = str_replace('{max_size}', $val['max_size'], $api->getValue('rg_oversize'));
					
					$api->sendWarning($player, $msg);
					return;
				}
				
				$api->position[1][$nick] = $block->getPosition();
				
				$api->sendWarning($player, $api->getValue('pos_2_set'));
				return;
			}
			
			if( isset($api->position[0][$nick]) and isset($api->position[1][$nick]) )
			{
				$api->position[0][$nick] = $block->getPosition();
				
				unset($api->position[1][$nick]);
				$api->sendWarning($player, $api->getValue('pos_1_set'));
				return;
			}
		}

		$flag = 'interact';

		if( $block->getId() == BlockLegacyIds::CHEST )
		{
			$flag = 'chest';
		}

		if( $block->getId() == BlockLegacyIds::ITEM_FRAME_BLOCK )
		{
			$flag = 'frame';
		}

		if( $block->getId() == BlockLegacyIds::GRASS )
		{
			$list = [
				ItemIds::WOODEN_SHOVEL,
				ItemIds::STONE_SHOVEL,
				ItemIds::IRON_SHOVEL,
				ItemIds::GOLD_SHOVEL,
				ItemIds::DIAMOND_SHOVEL,

				ItemIds::WOODEN_HOE,
				ItemIds::STONE_HOE,
				ItemIds::IRON_HOE,
				ItemIds::GOLD_HOE,
				ItemIds::DIAMOND_HOE
			];

			if( in_array($item->getId(), $list) )
			{
				/**
				 * @todo break?
				 */
				$flag = 'break';
			}
		}
		
		if( $this->isFlagDenied($player, $flag, $block) )
		{
			$event->cancel();
		}
	}


	/**
	 * @internal drop flag.
	 *
	 * @param    PlayerDropItemEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled FALSE
	 */
	function onDrop( PlayerDropItemEvent $event )
	{
		if( $event->isCancelled() )
		{
			return;
		}
		
		$player = $event->getPlayer();
		
		if( $this->isFlagDenied($player, 'drop') )
		{
			$event->cancel();
		}
	}


	/**
	 * @todo     check sleep flag for conflicts with interact.
	 *
	 * @internal sleep flag.
	 *
	 * @param    PlayerBedEnterEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled FALSE
	 */
	function onSleep( PlayerBedEnterEvent $event )
	{
		if( $event->isCancelled() )
		{
			return;
		}
		
		$player = $event->getPlayer();
		
		if( $this->isFlagDenied($player, 'sleep') )
		{
			$event->cancel();
		}
	}


	/**
	 * @internal bucket flag.
	 *
	 * @param    PlayerBucketFillEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled FALSE
	 */
	function onBucketFill( PlayerBucketFillEvent $event )
	{
		if( $event->isCancelled() )
		{
			return;
		}
		
		$player = $event->getPlayer();
		$block  = $event->getBlockClicked();
		
		if( $this->isFlagDenied($player, 'bucket', $block) )
		{
			$event->cancel();
		}
	}


	/**
	 * @internal bucket flag.
	 *
	 * @param    PlayerBucketEmptyEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled FALSE
	 */
	function onBucketEmpty( PlayerBucketEmptyEvent $event )
	{
		if( $event->isCancelled() )
		{
			return;
		}
		
		$player = $event->getPlayer();
		$block  = $event->getBlockClicked();
		
		if( $this->isFlagDenied($player, 'bucket', $block) )
		{
			$event->cancel();
		}
	}


	/**
	 * @param  Player $player
	 * @param  string $flag
	 *
	 * @return bool
	 */
	private function isFlagDenied( Player $player, string $flag, Block $block = NULL ): bool
	{
		if( $player->hasPermission('sexguard.noflag') )
		{
			return FALSE;
		}

		$api    = $this->api;

		$region = $api->getRegion($player->getPosition() ?? $block->getPosition() );
		
		if( !isset($region) )
		{
			return FALSE;
		}

		if( $region->getFlagValue($flag) )
		{
			return FALSE;
		}

		$val = $api->getGroupValue($player);
		
		if( in_array($flag, $val['ignored_flag']) )
		{
			if( !in_array($region->getRegionName(), $val['ignored_region']) )
			{
				$event = new FlagIgnoreEvent($api, $region, $flag, $player);

				$event->call();

				if( $event->isCancelled() )
				{
					return $event->isMainEventCancelled();
				}

				return FALSE;
			}
		}
		
		$nick = strtolower($player->getName());
		
		if( $nick != $region->getOwner() )
		{
			if( !in_array($nick, $region->getMemberList()) )
			{
				$event = new FlagCheckByPlayerEvent($api, $region, $flag, $player, $block);

				$event->call();

				if( $event->isCancelled() )
				{
					return $event->isMainEventCancelled();
				}

				$api->sendWarning($player, $api->getValue('warn_flag_'.$flag));
				return TRUE;
			}
		}
		
		return FALSE;
	}
}