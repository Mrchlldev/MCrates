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
            return;
        }
        /** @var int $amount */
        $amount = $args["amount"] ?? 1;
        if (!is_numeric($amount)) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.error.not-numeric"));
            return;
        }
        $crate = MCrates::getInstance()->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.error.invalid-crate"));
            return;
        }
        foreach (MCrates::getInstance()->getServer()->getOnlinePlayers() as $target) {
            $crate->giveKey($target, $amount);
            $target->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.success.sender", ["{CRATE}" => $crate->getName()]));
        }
        $sender->sendMessage(MCrates::getInstance()->getMessage("commands.keyall.success.target", ["{CRATE}" => $crate->getName()]));
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
}