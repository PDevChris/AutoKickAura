<?php

namespace pdm;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class Main extends PluginBase implements Listener {

    private $kickThreshold;
    private $banDuration;
    private $kickMessage;
    private $banMessage;
    private $notifyStaffOnDetection;
    private $staffNotifyMessage;
    private $enabled;
    private $playerKickCount = [];

    public function onEnable(): void {
        // Load the configuration file
        $this->saveDefaultConfig();
        $config = $this->getConfig();

        // Get settings from the config file
        $this->kickThreshold = $config->get("kick_threshold", 3);
        $this->banDuration = $config->get("ban_duration", 6);
        $this->kickMessage = $config->get("kick_message", "You have been kicked for using hacks!");
        $this->banMessage = $config->get("ban_message", "You have been banned for using Aura!");
        $this->notifyStaffOnDetection = $config->get("notify_staff_on_detection", true);
        $this->staffNotifyMessage = $config->get("staff_notify_message", "Player {player} was detected using hacks and has been kicked/banned.");
        $this->enabled = $config->get("enabled", true);

        // Register events
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getLogger()->info("AutoKickAura plugin has been sucessfully enabled.");
    }

    public function onDisable(): void {
        $this->getLogger()->info("AutoKickAura plugin has been sucessfully disabled.");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "auradetector") {
            if ($sender->hasPermission("auradetector.reload")) {
                $this->reloadConfig();
                $sender->sendMessage("§aAutoKickAura configuration has been reloaded.");
                return true;
            } else {
                $sender->sendMessage("§cYou don't have permission to reload the configuration.");
                return false;
            }
        }
        return false;
    }

    // Event for detecting kill aura (simplified example)
    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        if (!$event->getEntity() instanceof Player || !$event->getDamager() instanceof Player) {
            return;
        }

        $attacker = $event->getDamager();
        $victim = $event->getEntity();

        // Example: simplistic logic for detecting rapid attacks (simplified version)
        if ($attacker instanceof Player && $victim instanceof Player) {
            // Check for suspicious behavior (e.g., rapid attack rate, here we simulate with a basic check)
            if ($attacker->getLastDamageCause() && $attacker->getLastDamageCause()->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                $this->handleDetection($attacker);
            }
        }
    }

    private function handleDetection(Player $player): void {
        // Increase the kick count or ban immediately
        if (isset($this->playerKickCount[$player->getName()])) {
            $currentKicks = $this->playerKickCount[$player->getName()];
            if ($currentKicks >= $this->kickThreshold) {
                $this->banPlayer($player);
            } else {
                $this->kickPlayer($player, $currentKicks + 1);
            }
        } else {
            $this->kickPlayer($player, 1); // First offense, kick the player
        }
    }

    private function kickPlayer(Player $player, int $kickCount): void {
        // Store the player's current kick count
        $this->playerKickCount[$player->getName()] = $kickCount;
        $player->kick($this->kickMessage);

        // Notify staff if enabled
        if ($this->notifyStaffOnDetection) {
            foreach ($this->getServer()->getOnlinePlayers() as $staff) {
                if ($staff->hasPermission("auradetector.notify")) {
                    $staff->sendMessage(str_replace("{player}", $player->getName(), $this->staffNotifyMessage));
                }
            }
        }
    }

    private function banPlayer(Player $player): void {
        // Ban the player for the configured duration
        $this->getServer()->getNameBans()->addBan($player->getName(), $this->banMessage, time() + ($this->banDuration * 86400), null); // 86400 seconds = 1 day
        $player->kick($this->banMessage);

        // Notify staff about the ban
        if ($this->notifyStaffOnDetection) {
            foreach ($this->getServer()->getOnlinePlayers() as $staff) {
                if ($staff->hasPermission("auradetector.notify")) {
                    $staff->sendMessage(str_replace("{player}", $player->getName(), $this->staffNotifyMessage));
                }
            }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        // Reset the player's kick count when they join
        $player = $event->getPlayer();
        $this->playerKickCount[$player->getName()] = 0;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        // Remove the player from the kick count list when they leave
        $player = $event->getPlayer();
        unset($this->playerKickCount[$player->getName()]);
    }
}
