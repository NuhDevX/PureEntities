<?php

declare(strict_types=1);

namespace leinne\pureentities\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\world\sound\Sound;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\entity\Entity;

class ShearSound implements Sound{

    public Entity $entity;
    
    public function __construct(Entity $entity){
    $this->entity = $entity;
    }

    public function encode(?Vector3 $pos) : array{
        return [
            LevelSoundEventPacket::create(LevelSoundEvent::SHEAR, $pos, -1, $this->entity::getNetworkTypeId(), false, false, $this->entity->getId())
        ];
    }
}
