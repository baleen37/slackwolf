<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the WolfMan class.
 *
 * @package Slackwolf\Game\Roles
 */
class WolfMan extends Werewolf
{

    /**
     * {@inheritdoc}
     */
	public function appearsAsWerewolf() {
		return false;
	}


    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::WOLFMAN;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
    return "[울프] Seer에게 시민으로 표시됩니다.";
	}
}
