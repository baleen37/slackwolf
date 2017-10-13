<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the  Bodyguard class.
 *
 * @package Slackwolf\Game\Roles
 */
class Bodyguard extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::BODYGUARD;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "[시민] 밤마다 지목된 시민을 보호할 수 있습니다. 하지만 연속으로 같은 사람을 보호할 수 없습니다.";
	}
}
