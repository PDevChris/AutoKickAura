<?php

namespace pdm;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\player\Player;

class EventListener implements Listener {

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        // Only check if the player has actually moved significantly
        if ($event->getFrom()->distanceSquared($event->getTo()) < 0.01) {
            return; // Ignore micro-movements
        }

        if ($this->plugin->autoAuraEnabled && $this->plugin->detectAutoAura($player)) {
            $this->plugin->handleKick($player, $this->plugin->autoAuraKickMessage);
        }

        if ($this->plugin->hitboxEnabled && $this->plugin->detectHitbox($player)) {
            $this->plugin->handleKick($player, $this->plugin->hitboxKickMessage);
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        $this->plugin->playerData[$player->getName()] = [
            "warnings" => 0,
            "lastCheckTime" => 0
        ];
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        unset($this->plugin->playerData[$player->getName()]);
    }

    public function onPlayerInteractEntity(PlayerInteractEntityEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        // AutoAura Detection
        if ($this->plugin->autoAuraEnabled && $this->plugin->detectAutoAura($player)) {
            $this->plugin->handleKick($player, $this->plugin->autoAuraKickMessage);
        }

        // Hitbox Detection
        if ($this->plugin->hitboxEnabled && $this->plugin->detectHitbox($player)) {
            $this->plugin->handleKick($player, $this->plugin->hitboxKickMessage);
        }
    }
}
