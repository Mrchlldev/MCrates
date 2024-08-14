<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates;

use Mrchlldev\MCrates\tiles\CrateTile;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\ShulkerBox as SBX;
use pocketmine\block\tile\{Chest, Barrel, ShulkerBox, Beacon, EnderChest, EnchantTable};
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

class EventListener implements Listener
{
    public function __construct(private readonly MCrates $plugin)
    {
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $world = $block->getPosition()->getWorld();
        $item = $player->getInventory()->getItemInHand();

        if ($block->getTypeId() === BlockTypeIds::CHEST || $block->getTypeId() === BlockTypeIds::BARREL || $block->getTypeId() === BlockTypeIds::ENCHANTING_TABLE || $block->getTypeId() === BlockTypeIds::ENDER_CHEST || $block->getTypeId() === BlockTypeIds::BEACON || $block instanceof SBX) {
            $tile = $world->getTile($block->getPosition());
            if ($tile instanceof CrateTile) {
                if ($tile->getCrateType() === null) {
                    $player->sendTip($this->plugin->getMessage("crates.error.invalid-crate"));
                } elseif ($tile->getCrateType()->isValidKey($item)) {
                    $tile->openCrate($player, $item);
                } elseif ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                    $tile->previewCrate($player);
                }
                $event->cancel();
                return;
            }
            if ($tile instanceof Chest || 
            $tile instanceof Barrel || 
            $tile instanceof EnchantTable || 
            $tile instanceof EnderChest ||
            $tile instanceof ShulkerBox || 
            $tile instanceof Beacon) {
                if (($crate = $this->plugin->getCrateToCreate($player)) !== null) {
                    $newTile = new CrateTile($world, $block->getPosition());
                    $newTile->setCrateType($crate);
                    $tile->close();
                    $world->addTile($newTile);
                    $player->sendMessage($this->plugin->getMessage("crates.success.crate-created", ["{CRATE}" => $crate->getName()]));
                    self::playSound($player, "random.levelup");
                    $this->plugin->setInCrateCreationMode($player, null);
                    $event->cancel();
                    return;
                }
            }
        }
        if ($item->getNamedTag()->getTag("KeyType") !== null) $event->cancel();
    }

    public static function playSound(Player $p,
        string $sound,
        float $minimumVolume = 1.0,
        float $volume = 1.0,
        float $pitch = 1.0) {
        $position = null;
        $pos = $p->getPosition();
        $pk = new PlaySoundPacket();
        $pk->soundName = $sound;
        $pk->volume = $volume > $minimumVolume ? $minimumVolume : $volume;
        $pk->pitch = $pitch;
        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;
        $p->getNetworkSession()->sendDataPacket($pk);
    }
}