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
            return;
        }
        if (!isset($args["player"])) {
            $sender->sendMessage("Usage: /mcrate key <type> <amount> <player>");
            return;
        }
        $target = empty($args["player"]) ? $sender : MCrates::getInstance()->getServer()->getPlayerExact($args["player"]);
        if (!$target instanceof Player) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.error.invalid-player"));
            return;
        }
        /** @var int $amount */
        $amount = $args["amount"] ?? 1;
        if (!is_numeric($amount)) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.error.not-numeric"));
            return;
        }
        $crate = MCrates::getInstance()->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.error.invalid-crate"));
            return;
        }
        $crate->giveKey($target, $amount);
        $target->sendMessage(MCrates::getInstance()->getMessage("commands.key.success.sender", ["{CRATE}" => $crate->getName()]));
        $sender->sendMessage(MCrates::getInstance()->getMessage("commands.key.success.target", ["{CRATE}" => $crate->getName(), "{TARGET}" => $target->getName()]));

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
}