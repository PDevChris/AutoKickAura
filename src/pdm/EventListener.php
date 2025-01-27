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

    // Triggered when a player moves.
    public function onPlayerMove(PlayerMoveEvent $event): void {
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

    // Triggered when a player joins.
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        // Additional checks can be made here if needed for new players joining.
    }

    // Triggered when a player interacts with another entity (like another player).
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
