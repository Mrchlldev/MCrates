<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates\tasks;

use Mrchlldev\MCrates\crates\Crate;
use Mrchlldev\MCrates\crates\CrateItem;
use Mrchlldev\MCrates\MCrates;
use Mrchlldev\MCrates\tiles\CrateTile;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class RouletteTask extends Task
{
    const INVENTORY_ROW_COUNT = 9;

    private Player $player;
    private Crate $crate;
    private CrateTile $tile;
    private InvMenu $menu;

    private int $currentTick = 0;
    private bool $showReward = false;
    private int $itemsLeft;
    private $counting = 0;
    /** @var int */
    private $color = 0;
    /** @var int[] */
    private $avslot = [16, 15, 14, 13, 12, 11, 10, 19, 28, 37, 38, 39, 40, 41, 42, 43, 34, 25];
    /** @var int[] */
    private $empty = [0, 1, 2, 3, 4, 5, 6, 7, 8 ,9, 17, 18, 20, 21, 22, 23, 24, 26, 27, 29, 30, 32, 33, 35, 35, 36, 44, 45, 46, 47, 48, 50, 51, 52, 53];
    /** @var CrateItem[] */
    private array $lastRewards = [];

    public function __construct(CrateTile $tile)
    {
        /** @var Player $player */
        $player = $tile->getCurrentPlayer();
        $this->player = $player;

        /** @var Crate $crate */
        $crate = $tile->getCrateType();
        $this->crate = $crate;

        $this->tile = $tile;

        $this->menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $this->menu->setName(MCrates::getInstance()->getMessage("crates.opened-crate", ["{CRATE}" => $crate->getName()]));
        $this->menu->getInventory()->setContents([
            31 => ($endRod = VanillaBlocks::END_ROD()->asItem()->setCustomName("§r§l§e-")),
            49 => $endRod
        ]);
        $this->menu->setListener(InvMenu::readonly());
        $this->menu->send($player);

        $this->itemsLeft = $crate->getDropCount();
    }

    public function onRun(): void
    {
        if (!$this->player->isOnline()) {
            $this->tile->closeCrate();
            if (($handler = $this->getHandler()) !== null) $handler->cancel();
            return;
        }
        $this->currentTick++;
        $speed = MCrates::getInstance()->getConfig()->getNested("crates.roulette.speed");
        $safeSpeed = max($speed, 1);
        $duration = MCrates::getInstance()->getConfig()->getNested("crates.roulette.duration");
        $safeDuration = (($duration / $safeSpeed) >= 5.5) ? $duration : (5.5 * $safeSpeed);
        if ($this->currentTick >= $safeDuration) {
            if (!$this->showReward) {
                $this->showReward = true;
            } elseif ($this->currentTick - $safeDuration > 20) {
                $this->itemsLeft--;
                $reward = $this->lastRewards[floor(self::INVENTORY_ROW_COUNT / 2)];
                if ($reward->getType() === "item"){
                  $itemName = $reward->getItem()->getCustomName() ?? $reward->getItem()->getName();
                  if($reward->getItem()->getCustomName() === null){
                    $reward->getItem()->getName();
                  }
                  $this->player->getInventory()->addItem($reward->getItem());
                  }
                $server = $this->player->getServer();
                foreach ($reward->getCommands() as $command) {
                    $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), str_replace("{PLAYER}", $this->player->getName(), $command));
                }
                if ($this->itemsLeft === 0) {
                    foreach ($this->crate->getCommands() as $command) {
                        $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), str_replace("{PLAYER}", $this->player->getName(), $command));
                    }
                    $this->player->removeCurrentWindow();
                    self::playSound($this->player, "random.explode");
                    $this->tile->closeCrate();
                    if (($handler = $this->getHandler()) !== null) $handler->cancel();
                } else {
                    $this->currentTick = 0;
                    $this->showReward = false;
                }
            }
            return;
        }

        if ($this->currentTick % $safeSpeed === 0) {
            $this->lastRewards[self::INVENTORY_ROW_COUNT] = $this->crate->getDrop(1)[0];
            /**
             * @var int $slot
             * @var CrateItem $lastReward
             */
            foreach ($this->lastRewards as $slot => $lastReward) {
                if ($slot !== 0) {
                    $this->lastRewards[$slot - 1] = $lastReward;
                    $this->menu->getInventory()->setItem($this->avslot[$this->counting], $lastReward->getItem());
                    if($this->counting === 17){
                        $this->counting = 0;
                    } else {
                        $this->counting++;
                    }
                    foreach($this->empty as $empty){
                      $item = VanillaBlocks::STAINED_GLASS_PANE()->asItem()->setCustomName("§r§f-");
                      $this->menu->getInventory()->setItem($empty, $item, $this->color);
                    }
                    if($this->color === 15){
                        $this->color = 0;
                    } else {
                        $this->color++;
                    }
                    $p = $this->player;
                    self::playSound($p, "random.orb");
                }
            }
        }
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
