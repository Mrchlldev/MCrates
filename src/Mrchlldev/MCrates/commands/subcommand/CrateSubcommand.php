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

class CrateSubcommand extends BaseSubCommand
{

    private MCrates $plugin;
    
    public function __construct(MCrates $plugin) {
        $this->plugin = $plugin;
        parent::__construct("crate", "");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.use-in-game"));
            return;
        }
        if (!isset($args["type"])) {
            $sender->sendMessage("Usage: /mcrate crate <type>");
            return;
        }
        if ($args["type"] === "cancel") {
            if (!MCrates::getInstance()->inCrateCreationMode($sender)) {
                $sender->sendMessage(MCrates::getInstance()->getMessage("commands.crate.creation-mode.not-in-mode"));
                return;
            }
            MCrates::getInstance()->setInCrateCreationMode($sender, null);
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.crate.creation-mode.cancelled"));
            self::playSound($sender, "random.levelup");
            return;
        }
        $crate = MCrates::getInstance()->getCrate($args["type"]);
        if ($crate === null) {
            $sender->sendMessage(MCrates::getInstance()->getMessage("commands.crate.error.invalid-crate"));
            self::playSound($sender, "random.pop");
            return;
        }
        self::playSound($sender, "random.levelup");
        MCrates::getInstance()->setInCrateCreationMode($sender, $crate);
        $sender->sendMessage(MCrates::getInstance()->getMessage("commands.crate.success"));
    }

    /**
     * @throws ArgumentOrderException
     */
    public function prepare(): void
    {
        $this->setPermission("mcrate.command.crate");
        $this->registerArgument(0, new RawStringArgument("type"));
    }

    /**
     * @return null
     */
    public function getPermission() {
        return "mcrate.command.crate";
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
