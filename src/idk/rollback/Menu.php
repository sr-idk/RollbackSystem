<?php

namespace idk\rollback;

use idk\rollback\serialize\Serialize;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class Menu
{
    public static function run(Player $staff, Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $inv = $menu->getInventory();
        $playerN = $player->getName();
        $filePath = Loader::getInstance()->getDataFolder() . "players/" . $playerN . ".json";

        if (!file_exists($filePath)) {
            $staff->sendMessage("§cNo se encontró el archivo de configuración para el jugador: $playerN.");
            return;
        }

        $playerD = new Config($filePath);

        foreach ($playerD->getAll() as $deathId => $death) {
            $item = VanillaItems::PAPER()->setCustomName("§l§b" . $playerN . "§r§7's rollback #" . $deathId);
            $lore = [
                "§r§3Player: §f" . $playerN,
                "§r§3Fecha: §f" . $death["date"],
                "§r§3Killer: §f" . $death["killer"],
                "§r§3Razón: §f" . $death["reason"],
            ];
            $item->setLore($lore);
            $inv->addItem($item);
        }

        $menu->setListener(function (InvMenuTransaction $transaction) use ($playerD): InvMenuTransactionResult {
            $slot = $transaction->getAction()->getSlot();
            $deathId = $slot + 1;
            $player = $transaction->getPlayer();
        
            if (!$playerD->exists($deathId)) {
                $player->sendMessage("§cEste rollback no existe.");
                return $transaction->discard();
            }
        
            $death = $playerD->get($deathId);
            $serializedArmor = $death["armor"];
            $serializedItems = $death["items"];
        
            foreach ($serializedArmor as $armor) {
                $armor = Serialize::deserialize($armor);
                $player->getArmorInventory()->addItem($armor);
            }
        
            foreach ($serializedItems as $item) {
                $item = Serialize::deserialize($item);
                $player->getInventory()->addItem($item);
            }
        
            return $transaction->discard();
        });

        $menu->send($player, "§l§b" . $playerN . "§r§8's Rollbacks");
    }
}