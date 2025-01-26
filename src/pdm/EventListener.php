<?php

namespace AutoKickAura;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    // Event for detecting AutoAura and Hitbox hacks
    public function onPlayerMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        
        // AutoAura detection
        if ($this->isAutoAura(player)) {
            $this->handleHackDetection($player);
        }
        
        // Hitbox detection
        if ($this->plugin->getConfig()->get("enable_hitbox_detection", false) && $this->isHitbox(player)) {
            $this->handleHackDetection($player);
        }
    }

    // Detect AutoAura hack (simplified version)
    private function isAutoAura(Player $player): bool {
        // Placeholder logic for AutoAura detection (e.g., checking attack speed or behavior)
        // You can expand this with actual logic
        return false;
    }

    // Detect Hitbox hack (simplified version)
    private function isHitbox(Player $player): bool {
        // Placeholder logic for Hitbox detection
        // Check if the player hits targets outside of normal range or other signs of hitbox expansion
        return false;
    }

    // Handle when a player is detected using hacks
    private function handleHackDetection(Player $player) {
        $name = $player->getName();

        // Increment kick count
        $this->plugin->increaseKickCount($player);

        // Handle the kick or ban based on the kick count
        if ($this->plugin->getConfig()->get('kick_counts')[$name] >= $this->plugin->getConfig()->get('kick_threshold')) {
            $this->banPlayer($player);
        } else {
            $this->kickPlayer($player);
        }
    }

    // Kick the player
    private function kickPlayer(Player $player) {
        $kickMessage = $this->plugin->getConfig()->get('kick_message', 'You have been kicked for using hacks!');
        $player->kick($kickMessage);
    }

    // Ban the player
    private function banPlayer(Player $player) {
        $banMessage = $this->plugin->getConfig()->get('ban_message', 'You have been banned for using hacks!');
        $banDuration = $this->plugin->getConfig()->get('ban_duration', 6);  // Ban duration in days
        $banTime = time() + ($banDuration * 24 * 60 * 60); // Convert days to seconds
        $this->plugin->getServer()->getBanList()->addBan($player->getName(), $banMessage, $banTime, null);
        $player->kick($banMessage);
    }

    // Notify staff when a hack is detected
    private function notifyStaff(Player $player) {
        if ($this->plugin->getConfig()->get('notify_staff_on_detection', false)) {
            $staffMessage = str_replace("{player}", $player->getName(), $this->plugin->getConfig()->get('staff_notify_message', "Player {player} was detected using hacks and has been kicked/banned."));
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
                if ($onlinePlayer->hasPermission('auradetector.notify')) {  // Only notify staff with permission
                    $onlinePlayer->sendMessage($staffMessage);
                }
            }
        }
    }
}
