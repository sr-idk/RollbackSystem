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
     public const ROLLBACKS_MSG = "§8[§l§4Rollback§r§8]§r§7: ";
     public static function run(Player $staff, Player $player)
     {
         $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
         $inv = $menu->getInventory();
         $playerN = $player->getName();
         $filePath = Loader::getInstance()->getDataFolder() . "players/" . $playerN . ".json";
 
         if (!file_exists($filePath)) {
             $staff->sendMessage("§cNo se encontró el archivo de configuración para el jugador: $playerN.");
             return;
         }
 
         $playerD = new Config($filePath);
 
         foreach ($playerD->getAll() as $deathId => $death) {
             $item = VanillaItems::PAPER()->setCustomName("§l§4" . $playerN . "§r§7's rollback #" . $deathId);
             $item->setLore([
                 "§r§cPlayer: §f" . $playerN,
                 "§r§cFecha: §f" . $death["date"],
                 "§r§cKiller: §f" . $death["killer"],
                 "§r§cRazón: §f" . $death["reason"],
             ]);
             $inv->addItem($item);
         }
 
         $menu->setListener(function (InvMenuTransaction $transaction) use ($playerD, $staff, $playerN): InvMenuTransactionResult {
             $slot = $transaction->getAction()->getSlot();
             $deathId = $slot + 1;
             $playert = $transaction->getPlayer();
 
             if (!$playerD->exists($deathId)) {
                 $playert->sendMessage("§cEste rollback no existe.");
                 return $transaction->discard();
             }
 
             $death = $playerD->get($deathId);
 
             foreach ($death["armor"] as $armor) {
                 $playert->getArmorInventory()->addItem(Serialize::deserialize($armor));
             }
 
             foreach ($death["items"] as $item) {
                 $playert->getInventory()->addItem(Serialize::deserialize($item));
             }
 
             $staff->sendMessage(self::ROLLBACKS_MSG . "§aRollback successful for §c" . $playerN);
             $playert->sendMessage(self::ROLLBACKS_MSG . "§aRollback successful!");
             return $transaction->discard();
         });
 
         $menu->send($player, "§l§4" . $playerN . "§r§8's Rollbacks");
     }
}