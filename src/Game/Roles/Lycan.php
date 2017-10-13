<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Lycan class.
 *
 * @package Slackwolf\Game\Roles
 */
class Lycan extends Role
{
    /**
     * {@inheritdoc}
     */
	public function appearsAsWerewolf() {
		return true;
	}

    /**
     * {@inheritdoc}
     */
	public function isWerewolfTeam() {
		return false;
	}

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::LYCAN;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
    return "[시민] Seer에게 Werewolf로 보입니다.";
	}
}
