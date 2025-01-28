<?php

namespace pdm;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerInteractEntityEvent;

class EventListener implements Listener {

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        // Cooldown check: only check if enough time has passed
        $currentTime = time();
        $lastCheckTime = $this->plugin->lastCheckTimes[$player->getName()] ?? 0;

        if ($currentTime - $lastCheckTime < 5) {
            return; // Skip detection if it's too soon
        }

        $this->plugin->lastCheckTimes[$player->getName()] = $currentTime; // Update last check time

        // AutoAura Detection
        if ($this->plugin->autoAuraEnabled && $this->plugin->detectAutoAura($player)) {
            $this->plugin->handleKick($player, $this->plugin->autoAuraKickMessage);
        }

        // Hitbox Detection
        if ($this->plugin->hitboxEnabled && $this->plugin->detectHitbox($player)) {
            $this->plugin->handleKick($player, $this->plugin->hitboxKickMessage);
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        // Send a message to players when they join
        if ($this->plugin->autoAuraEnabled || $this->plugin->hitboxEnabled) {
            $player->sendMessage("Welcome to the server! Please refrain from using hacks.");
        }
    }

    public function onPlayerInteractEntity(PlayerInteractEntityEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        // AutoAura detection could be expanded to check for interactions
        if ($this->plugin->autoAuraEnabled && $this->plugin->detectAutoAura($player)) {
            $this->plugin->handleKick($player, $this->plugin->autoAuraKickMessage);
        }

        // Hitbox detection could be expanded to check for interactions as well.
        if ($this->plugin->hitboxEnabled && $this->plugin->detectHitbox($player)) {
            $this->plugin->handleKick($player, $this->plugin->hitboxKickMessage);
        }
    }
}
