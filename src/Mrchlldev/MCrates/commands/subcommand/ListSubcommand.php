<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates\commands\subcommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Mrchlldev\MCrates\MCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\utils\Config;

class ListSubcommand extends BaseSubCommand
{
  
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.use-in-game"));
            return;
        }
        MCrates::getInstance()->sendListCrate($sender);
    }

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("mcrate.command.list");
    }

    /**
     * @return null
     */
    public function getPermission() {
        return "mcrate.command.list";
    }
}