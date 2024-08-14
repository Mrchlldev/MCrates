<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates\commands\subcommand;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Mrchlldev\MCrates\MCrates;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

class KeyAllSubcommand extends BaseSubCommand
{
    
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /mcrate keyall <type>");
            self::playSound($sender, "random.pop");
            return;
        }
        /** @var int $amount */
        $amount = $args["amount"] ?? 1;
        if (!is_numeric($amount)) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.error.not-numeric"));
            self::playSound($sender, "random.pop");
            return;
        }
        $crate = MCrates::getInstance()->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.error.invalid-crate"));
            self::playSound($sender, "random.pop");
            return;
        }
        foreach (MCrates::getInstance()->getServer()->getOnlinePlayers() as $target) {
            $crate->giveKey($target, $amount);
            self::playSound($target, "random.orb");
            $target->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.success.sender", ["{CRATE}" => $crate->getName()]));
        }
        $sender->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.success.target", ["{CRATE}" => $crate->getName()]));
        self::playSound($sender, "random.levelup");

    }

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("mcrate.command.keyall");
        $this->registerArgument(0, new RawStringArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
    }

    /**
     * @return null
     */
    public function getPermission() {
        return "mcrate.command.keyall";
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