<?php

namespace pdm;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\Player;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    // Handle player movement to detect AutoAura and Hitbox hacks
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        // Check if AutoAura detection is enabled
        if ($this->plugin->autoAuraEnabled && $this->plugin->detectAutoAura($player)) {
            $this->plugin->handleKick($player, $this->plugin->autoAuraKickMessage);
        }

        // Check if Hitbox detection is enabled
        if ($this->plugin->hitboxEnabled && $this->plugin->detectHitbox($player)) {
            $this->plugin->handleKick($player, $this->plugin->hitboxKickMessage);
        }
    }

    // Handle player joining to possibly apply settings or notify staff
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        // Optionally log or notify staff that a player has joined
        if ($this->plugin->enabled) {
            $this->plugin->getLogger()->info(TextFormat::GREEN . $player->getName() . " has joined the server.");
        }
    }
}
