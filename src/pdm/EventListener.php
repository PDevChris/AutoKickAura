<?php

namespace pdm;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class EventListener implements Listener {

    private $plugin;

    public function __construct(PluginBase $plugin) {
        $this->plugin = $plugin;
    }

    // Handle player movement to detect AutoAura and Hitbox hacks
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        // AutoAura detection
        if ($this->plugin->autoAuraEnabled) {
            if ($this->detectAutoAura($player)) {
                $this->plugin->handleKick($player, $this->plugin->config->get("autoaura_kick_message"));
            }
        }

        // Hitbox detection
        if ($this->plugin->hitboxEnabled) {
            if ($this->detectHitboxHack($player)) {
                $this->plugin->handleKick($player, $this->plugin->config->get("hitbox_kick_message"));
            }
        }
    }

    // Handle player join events to notify staff
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        // Check if hack detection is enabled and notify staff
        if ($this->plugin->config->get("notify_staff_on_detection", true)) {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $staffPlayer) {
                if ($staffPlayer->hasPermission("auradetector.reload")) {
                    $staffPlayer->sendMessage(str_replace("{player}", $player->getName(), $this->plugin->config->get("staff_notify_message")));
                }
            }
        }
    }

    // Placeholder for AutoAura hack detection logic
    private function detectAutoAura(Player $player): bool {
        // Implement the logic for detecting AutoAura hacks (placeholder logic)
        return rand(0, 10) > 8;  // Simulating detection logic
    }

    // Placeholder for Hitbox hack detection logic
    private function detectHitboxHack(Player $player): bool {
        // Implement the logic for detecting Hitbox hacks (placeholder logic)
        return rand(0, 10) > 8;  // Simulating detection logic
    }
}
