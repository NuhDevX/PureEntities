<?php

declare(strict_types=1);

namespace leinne\pureentities;

use leinne\pureentities\entity\ai\path\astar\AStarPathFinder;
use leinne\pureentities\entity\LivingBase;
use leinne\pureentities\entity\neutral\IronGolem;
use leinne\pureentities\entity\neutral\ZombifiedPiglin;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Sheep;
use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\passive\SnowGolem;
use leinne\pureentities\event\EntityInteractByPlayerEvent;
use leinne\pureentities\task\AutoSpawnTask;
use leinne\pureentities\entity\Vehicle;
use pocketmine\block\BlockTypeIds as BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds as EntityLegacyIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds as ItemIds;
use pocketmine\item\SpawnEgg;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class PureEntities extends PluginBase implements Listener{
    public static bool $enableAstar = true;

    public function onEnable() : void{
        $entityFactory = EntityFactory::getInstance();
        /** Register hostile */
        $entityFactory->register(Creeper::class, function(World $world, CompoundTag $nbt) : Creeper{
            return new Creeper(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Creeper", "minecraft:creeper"]);
        $entityFactory->register(Skeleton::class, function(World $world, CompoundTag $nbt) : Skeleton{
            return new Skeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Skeleton", "minecraft:skeleton"]);
        $entityFactory->register(Zombie::class, function(World $world, CompoundTag $nbt) : Zombie{
            return new Zombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Zombie", "minecraft:zombie"]);

        /** Register neutral */
        $entityFactory->register(IronGolem::class, function(World $world, CompoundTag $nbt) : IronGolem{
            return new IronGolem(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["IronGolem", "minecraft:iron_golem"]);
        $entityFactory->register(ZombifiedPiglin::class, function(World $world, CompoundTag $nbt) : ZombifiedPiglin{
            return new ZombifiedPiglin(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ZombiePigman", "minecraft:zombie_pigman"]);
        $entityFactory->register(Spider::class, function(World $world, CompoundTag $nbt) : Spider{
            return new Spider(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Spider", "minecraft:spider"]);

        /** Register passive */
        $entityFactory->register(Chicken::class, function(World $world, CompoundTag $nbt) : Chicken{
            return new Chicken(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Chicken", "minecraft:chicken"]);
        $entityFactory->register(Cow::class, function(World $world, CompoundTag $nbt) : Cow{
            return new Cow(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Cow", "minecraft:cow"]);
        $entityFactory->register(Mooshroom::class, function(World $world, CompoundTag $nbt) : Mooshroom{
            return new Mooshroom(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Mooshroom", "minecraft:mooshroom"]);
        $entityFactory->register(Pig::class, function(World $world, CompoundTag $nbt) : Pig{
            return new Pig(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Pig", "minecraft:pig"]);
        $entityFactory->register(Sheep::class, function(World $world, CompoundTag $nbt) : Sheep{
            return new Sheep(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Sheep", "minecraft:sheep"]);
        $entityFactory->register(SnowGolem::class, function(World $world, CompoundTag $nbt) : SnowGolem{
            return new SnowGolem(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["SnowGolem", "minecraft:snow_golem"]);

        //BlockFactory::register(new block\MonsterSpawner(new BlockIdentifier(BlockLegacyIds::MOB_SPAWNER, 0, null, tile\MonsterSpawner::class), "Monster Spawner"), true);
        /** Register hostile */
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::CREEPER), "Creeper Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Creeper(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:creeper_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SKELETON), "Skeleton Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Skeleton(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:skeleton_spawn_egg");

        /** Register neutral */
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::IRON_GOLEM), "IronGolem Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new IronGolem(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:iron_golem_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::ZOMBIE_PIGMAN), "ZombifiedPiglin Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new ZombifiedPiglin(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:zombified_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SPIDER), "Spider Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Spider(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:spider_spawn_egg");

        /** Register passive */
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::CHICKEN), "Chicken Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Chicken(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:chicken_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::COW), "Cow Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Cow(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        },  "minecraft:cow_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::MOOSHROOM), "Mooshroom Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Mooshroom(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:mooshroom_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::PIG), "Pig Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Pig(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:pig_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SHEEP), "Sheep Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Sheep(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:sheep_spawn_egg");
        $this->registerItem(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SNOW_GOLEM), "SnowGolem Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new SnowGolem(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, "minecraft:snow_golem_spawn_egg");

        $this->saveDefaultConfig();
        $data = $this->getConfig()->getAll();

        $spawnable = $data["autospawn"] ?? [];
        if($spawnable["enable"] ?? false){
            $this->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask(), (int) ($spawnable["tick"] ?? 80));
        }

        self::$enableAstar = (bool) ($data["astar"]["enable"] ?? false);
        if(self::$enableAstar){
            AStarPathFinder::setData((int) ($data["astar"]["maximum-tick"] ?? 150), (int) ($data["astar"]["block-per-tick"] ?? 70));
        }

        $this->getServer()->getLogger()->info(
            TextFormat::AQUA . "\n" .
            "---------------------------------------------------------\n" .
            " _____                _____       _    _ _    _\n" .
            "|  __ \              |  ___|     | |  |_| |  |_|\n" .
            "| |__) |   _ _ __ ___| |__  _ __ | |__ _| |__ _  ___  ___ \n" .
            "|  ___/ | | | '__/ _ \  __|| '_ \| ___| | ___| |/ _ \/ __|\n" .
            "| |   | |_| | | |  __/ |___| | | | |__| | |__| |  __/\__ \\\n" .
            "|_|    \__,_|_|  \___|_____|_| |_|\___|_|\___|_|\___||___/\n" .
            "----------------------------------------------------------\n"
        );

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function registerItem(Item $item, string $itemName): void{
      GlobalItemDataHandlers::getDeserializer()->map($itemName, fn() => clone $item);
        GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData($itemName));
        StringToItemParser::getInstance()->register($itemName, fn() => clone $item);
        CreativeInventory::getInstance()->add($item);
    }
    
    public function OnInteract(PlayerInteractEvent $event) {
    	$item = $event->getItem();
    
    	if ($item->getTypeId() === ItemIds::ZOMBIE_SPAWN_EGG) {
			$event->cancel();
			$blockPosition = $event->getBlock()->getPosition();
			$entity = (new Zombie(
				Location::fromObject($blockPosition->add(0.5, 1, 0.5),
				$blockPosition->getWorld(), lcg_value() * 360, 0))
			);
			$entity->spawnToAll();
			
		}
    }

    public function onPlayerQuitEvent(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        if(isset(Vehicle::$riders[$id = $player->getId()])){
            Vehicle::$riders[$id]->removePassenger($player);
        }
    }

    public function onEntityDeathEvent(EntityDeathEvent $event) : void{
        $entity = $event->getEntity();
        if(isset(Vehicle::$riders[$id = $entity->getId()])){
            Vehicle::$riders[$id]->removePassenger($entity);
        }
    }

    public function onPlayerTeleportEvent(EntityTeleportEvent $event) : void{
        $entity = $event->getEntity();
        if(isset(Vehicle::$riders[$id = $entity->getId()])){
            Vehicle::$riders[$id]->removePassenger($entity);
        }
    }

    /** @priority HIGHEST */
    public function onDataPacketEvent(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
            $event->cancel();
            $player = $event->getOrigin()->getPlayer();
            $entity = $player->getWorld()->getEntity($packet->targetActorRuntimeId);
            if($entity instanceof Vehicle && !$entity->isClosed()){
                $entity->removePassenger($player);
            }
        }elseif(
            $packet instanceof InventoryTransactionPacket &&
            $packet->trData instanceof UseItemOnEntityTransactionData &&
            $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ITEM_INTERACT
        ){
            $player = $event->getOrigin()->getPlayer();
            $entity = $player->getWorld()->getEntity($packet->trData->getActorRuntimeId());
            if(($entity instanceof LivingBase || $entity instanceof Vehicle) && !$entity->isClosed()){
                $event->cancel();
                $item = $player->getInventory()->getItemInHand();
                $oldItem = clone $item;
                $ev = new EntityInteractByPlayerEvent($entity, $player, $item);
                $ev->call();

                if(!$ev->isCancelled() && $entity->interact($player, $item)){
                    if(
                        $player->hasFiniteResources() &&
                        !$item->equalsExact($oldItem) &&
                        $oldItem->equalsExact($player->getInventory()->getItemInHand())
                    ){
                        $player->getInventory()->setItemInHand($item);
                    }
                }
            }
        }elseif($packet instanceof MoveActorAbsolutePacket){
            $player = $event->getOrigin()->getPlayer();
            $entity = $player->getWorld()->getEntity($packet->actorRuntimeId);
            if($entity instanceof Vehicle && !$entity->isClosed() && $entity->getRider() === $player){
                $event->cancel();
                //[xRot, yRot, zRot] = [pitch, headYaw, yaw]
                $entity->absoluteMove($packet->position, $packet->yaw, $packet->pitch);
            }
        }elseif($packet instanceof AnimatePacket){
            $player = $event->getOrigin()->getPlayer();
            $vehicle = Vehicle::$riders[$player->getId()] ?? null;
            if($vehicle !== null && !$vehicle->isClosed() && $vehicle->handleAnimatePacket($packet)){
                $event->cancel();
            }
        }elseif($packet instanceof PlayerAuthInputPacket){
         $player = $event->getOrigin()->getPlayer();
         $vehicle = Vehicle::$riders[$player->getId()] ?? null;
            if($vehicle !== null && !$vehicle->isClosed() && $vehicle->getRider() === $player){
              $moveVector = $packet->getMoveVecX() !== 0.0 || $packet->getMoveVecZ() !== 0.0;
               if($moveVector){
                  $event->cancel();
                  $vehicle->updateMotion($packet->getMoveVecX(), $packet->getMoveVecZ());
                }
           }
       }
   }

    /**
     * @priority MONITOR
     *
     * @param BlockPlaceEvent $ev
     */
    public function onBlockPlaceEvent(BlockPlaceEvent $ev) : void{
        $item = $ev->getItem();
        $block = $ev->getBlock();
        $player = $ev->getPlayer();
        $bid = $block->getTypeId();
        if($bid === BlockLegacyIds::LIT_PUMPKIN || $bid === BlockLegacyIds::PUMPKIN || $bid === BlockLegacyIds::CARVED_PUMPKIN){
            if(
                $block->getSide(Facing::DOWN)->getTypeId() === BlockLegacyIds::SNOW
                && $block->getSide(Facing::DOWN, 2)->getTypeId() === BlockLegacyIds::SNOW
            ){
                $ev->cancel();

                $pos = $block->getPos()->asVector3();
                $air = VanillaBlocks::AIR();
                for($y = 0; $y < 2; ++$y){
                    --$pos->y;
                    $block->getPos()->getWorld()->setBlock($pos, $air);
                }

                $entity = new SnowGolem(Location::fromObject($block->getPos()->add(0.5, -2, 0.5), $block->getPos()->getWorld()));
                $entity->spawnToAll();

                if($player->hasFiniteResources()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }elseif(
                ($down = $block->getSide(Facing::DOWN))->getTypeId() === BlockLegacyIds::IRON
                && $block->getSide(Facing::DOWN, 2)->getTypeId() === BlockLegacyIds::IRON
            ){
                if(($first = $down->getSide(Facing::EAST))->getTypeId() === BlockLegacyIds::IRON){
                    $second = $down->getSide(Facing::WEST);
                }

                if(!isset($second) && ($first = $down->getSide(Facing::NORTH))->getTypeId() === BlockLegacyIds::IRON){
                    $second = $down->getSide(Facing::SOUTH);
                }

                if(!isset($second) || $second->getTypeId() !== BlockLegacyIds::IRON){
                    return;
                }

                $ev->cancel();
                $entity = new IronGolem(Location::fromObject($pos = $block->getPos()->add(0.5, -2, 0.5), $block->getPos()->getWorld()), CompoundTag::create()->setByte("PlayerCreated", 1));
                $entity->spawnToAll();

                $down->getPos()->getWorld()->setBlock($pos, $air = VanillaBlocks::AIR());
                $down->getPos()->getWorld()->setBlock($first->getPos(), $air);
                $down->getPos()->getWorld()->setBlock($second->getPos(), $air);
                $down->getPos()->getWorld()->setBlock($block->getPos()->add(0, -1, 0), $air);

                if($player->hasFiniteResources()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }
        }
    }
}
