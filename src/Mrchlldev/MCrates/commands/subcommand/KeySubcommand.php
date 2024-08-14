<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates\commands\subcommand;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Mrchlldev\MCrates\MCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class KeySubcommand extends BaseSubCommand
{
    
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /mcrate key <type>");
            self::playSound($sender, "random.pop");
            return;
        }
        if (!isset($args["player"])) {
            $sender->sendMessage("Usage: /mcrate key <type> <amount> <player>");
            self::playSound($sender, "random.pop");
            return;
        }
        $target = empty($args["player"]) ? $sender : MCrates::getInstance()->getServer()->getPlayerExact($args["player"]);
        if (!$target instanceof Player) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.error.invalid-player"));
            self::playSound($sender, "random.pop");
            return;
        }
        /** @var int $amount */
        $amount = $args["amount"] ?? 1;
        if (!is_numeric($amount)) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.error.not-numeric"));
            self::playSound($sender, "random.pop");
            return;
        }
        $crate = MCrates::getInstance()->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.error.invalid-crate"));
            self::playSound($sender, "random.pop");
            return;
        }
        $crate->giveKey($target, $amount);
        $target->sendMessage(MCrates::getInstance()->getMessage("commands.key.success.sender", ["{CRATE}" => $crate->getName()]));
        self::playSound($target, "random.orb");
        $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.success.target", ["{CRATE}" => $crate->getName(), "{TARGET}" => $target->getName()]));
        self::playSound($sender, "random.levelup");

    }

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("mcrate.command.key");
        $this->registerArgument(0, new RawStringArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
        $this->registerArgument(2, new RawStringArgument("player", true));
    }

    /**
     * @return null
     */
    public function getPermission() {
        return "mcrate.command.key";
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