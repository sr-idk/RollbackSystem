<?php

namespace idk\rollback\cmd;

use idk\rollback\Menu;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class RbCommand extends Command
{
    public function __construct()
    {
        parent::__construct("rb", "Rollback command", "/rb <player>");
        $this->setPermission("rollback.cmd");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (count($args) < 1) {
            $sender->sendMessage("Usage: /rb <player>");
            return;
        }

        $player = $sender->getServer()->getPlayerExact($args[0]);
        if ($player === null) {
            $sender->sendMessage("Player not found.");
            return;
        }
        $playerD = $player->getInventory()->getContents();

        Menu::run($sender, $player, );
    }
}